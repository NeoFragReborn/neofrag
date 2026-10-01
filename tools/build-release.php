<?php
declare(strict_types=1);

/**
 * build-release — produit les paquets prêts à uploader par FTP (hébergement mutualisé).
 *
 * Famille : outil
 *
 *   - dist/neofrag-reborn-<v>.zip        : site PRINCIPAL (vitrine). config/neofrag.php standard.
 *   - dist/neofrag-reborn-demo-<v>.zip   : site DÉMO. + install/demo.sql + NEOFRAG_DEMO=TRUE.
 *   - dist/neofrag-reborn-public-<v>.zip : DISTRIBUTION générique FTP-ready (sans la vitrine).
 *   - dist/neofrag-reborn-update-<v>.zip : paquet de MISE À JOUR, à PLAT (aucun dossier racine).
 *   - dist/version.json, dist/checksum.json : manifestes servis avec le paquet de mise à jour.
 *
 * Le paquet de mise à jour est PLAT, et c'est la différence qui compte : l'auto-updater écrit chaque
 * entrée à son propre chemin. Un paquet d'installation, qui range tout sous `neofrag-reborn/`, aurait
 * donc créé un sous-dossier de ce nom au lieu de remplacer quoi que ce soit — la mise à jour aurait
 * « réussi » sans rien mettre à jour.
 *
 * Inclut vendor/ (pas de composer sur mutualisé) et .htaccess (mod_rewrite). Exclut les secrets
 * (config/db.php|crypt.php|password.php — générés par l'installeur web), le runtime (cache/logs/
 * backups/upload), le scratch dev (.git, tests, .playwright-mcp…) et le verrou d'install.
 *
 * Le user upload le contenu du zip, visite /install/ (DB + admin), met les dossiers writable,
 * et (démo) ajoute le cron de reset. Cf. docs/deploy-ftp.md.
 *
 * Usage
 * -----
 *   php tools/build-release.php
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

nf_options([]);

$root    = nf_racine();
$version = nf_version($root);
$dist    = $root . '/dist';

@mkdir($dist, 0775, true);

// vendor de PRODUCTION : on régénère sans require-dev (l'autoloader « files » require certains
// packages en dur au boot — supprimer des dossiers casserait l'autoload). Restauré en fin de script.
if (has_composer()) {
    register_shutdown_function(static function () use ($root) {
        echo "  restauration du vendor de dev…\n";
        run_composer($root, ['install', '--no-interaction', '-q']);
    });
    echo "  vendor de production (composer install --no-dev)…\n";
    if (!run_composer($root, ['install', '--no-dev', '--optimize-autoloader', '--no-interaction', '-q'])) {
        nf_refus('composer install --no-dev a échoué — abandon (vendor de dev restauré)');
    }
} elseif (is_dir($root.'/vendor/phpunit') || is_dir($root.'/vendor/phpstan')) {
    // Sans composer, on ne peut pas régénérer le vendor : on vérifie qu'il est DÉJÀ celui de la
    // production. L'ancien avertissement passait inaperçu dans le journal de la CI, et le paquet de
    // mise à jour partait avec PHPUnit et PHPStan (76 Mo au lieu de 17, 2026-09-23).
    nf_refus("composer est introuvable et vendor/ contient les outils de développement (PHPUnit, PHPStan) :\n"
        ."  le paquet les embarquerait. Lancer d'abord `composer install --no-dev --optimize-autoloader`.");
}

// Garde-fou (#5 audit) : le table-map doit recouvrir EXACTEMENT les tables vives, sinon un module
// serait packagé avec un install.sql incomplet (tables manquantes → module cassé chez l'utilisateur).
// On valide la partition avant de packager ; échec = abandon (relancer extract-module-sql + package-addons).
echo "  validation du table-map (extract-module-sql --check)…\n";
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/extract-module-sql.php') . ' --check 2>&1', $vout, $vcode);
if ($vcode !== 0) {
    nf_refus(implode("\n", $vout) . "\nABANDON : table-map périmé ou base injoignable — corrige puis relance.");
}

build($root, $dist, $version, 'principal'); // site vitrine (avec thème vitrine)
build($root, $dist, $version, 'demo');      // site démo (+ demo.sql)
build($root, $dist, $version, 'public');    // DISTRIBUTION générique FTP-ready (sans vitrine, vendor inclus)

// Paquet de mise à jour + ses deux manifestes. Les trois forment un JEU COHÉRENT : version.json
// porte le SHA-256 du zip, checksum.json l'empreinte MD5 de chaque fichier livré. Publier l'un sans
// les autres fait échouer la vérification d'intégrité côté site — publier les trois ensemble.
$entries = build($root, $dist, $version, 'update');
manifests($dist, $version, $entries);

echo "\n✓ Paquets dans dist/ (v{$version}).\n";
echo "  à publier ensemble sur l'origine de mise à jour : neofrag-reborn-update-{$version}.zip, version.json, checksum.json\n";

function has_composer(): bool
{
    // Cross-plateforme : `command -v` n'existe pas sous Windows (cmd.exe) → utiliser `where`.
    // Sans ça, has_composer() renvoyait toujours FALSE sous Windows et build-release.php embarquait
    // les dépendances DEV (phpunit, phpstan…) dans les zips « production ». Le build de release
    // reste conseillé sous Linux/Docker (cf. .github/workflows/release.yml).
    $probe = (stripos(PHP_OS, 'WIN') === 0) ? 'where composer 2>NUL' : 'command -v composer 2>/dev/null';
    return trim((string) @shell_exec($probe)) !== '';
}

function run_composer(string $root, array $args): bool
{
    $cmd = 'composer ' . implode(' ', array_map('escapeshellarg', $args))
         . ' --working-dir=' . escapeshellarg($root) . ' 2>&1';
    exec($cmd, $out, $code);
    if ($code !== 0) {
        fwrite(STDERR, implode("\n", $out) . "\n");
    }
    return $code === 0;
}

/**
 * Construit une variante. Renvoie la table « chemin dans le zip => chemin sur le disque », dont
 * manifests() se sert pour calculer les empreintes du paquet de mise à jour.
 *
 * @return array<string,string>
 */
function build(string $root, string $dist, string $version, string $variant): array
{
    $suffix   = ['principal' => '', 'demo' => '-demo', 'public' => '-public', 'update' => '-update'][$variant] ?? '';
    $demo     = $variant === 'demo';
    $name     = "neofrag-reborn{$suffix}-{$version}";
    // Paquet de mise à jour : AUCUN dossier racine (cf. en-tête). Les autres en gardent un, pour que
    // l'utilisateur qui décompresse obtienne un dossier propre plutôt que 1400 fichiers en vrac.
    $top      = $variant === 'update' ? '' : 'neofrag-reborn';
    $prefix   = $top === '' ? '' : $top.'/';
    $zip_path = "{$dist}/{$name}.zip";
    @unlink($zip_path);

    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE) !== true) {
        nf_refus("impossible de créer {$zip_path}");
    }

    $entries = [];
    $count   = 0;

    // `.git`, `node_modules` et `dist` ne sont jamais descendus : excluded() les refuserait fichier
    // par fichier, mais les parcourir coûte des dizaines de milliers d'entrées pour rien.
    foreach (nf_parcourir($root, ['.git', 'node_modules', 'dist']) as $rel => $file) {
        if (excluded($rel, $variant)) {
            continue;
        }
        $zip->addFile($file->getPathname(), $prefix.$rel);
        $entries[$prefix.$rel] = $file->getPathname();
        $count++;
    }

    // Manifeste du paquet de mise à jour : ce qu'il PROTÈGE et ce qu'il SUPPRIME.
    //
    // Déclaré, et non déduit. Jusqu'ici, `apply_update_package()` retirait de `neofrag/` tout ce que
    // le paquet ne livrait pas — un raisonnement juste tant que le paquet est complet, et dévastateur
    // dès qu'il ne l'est pas : il a failli effacer le framework entier sur un paquet d'une autre
    // nature. Une liste écrite ne se trompe pas de la même façon.
    //
    // Seul le paquet de mise à jour en porte un : les autres variantes servent à une installation
    // neuve, où il n'y a rien à protéger ni à retirer.
    if ($variant === 'update') {
        $zip->addFromString($prefix.'nf-manifest.json', update_manifest($root, $version));
        $count++;
    }

    // config/neofrag.php généré (non versionné). Démo : NEOFRAG_DEMO=TRUE.
    $zip->addFromString($prefix.'config/neofrag.php', neofrag_config($demo));
    // NB : config/email.php n'est PAS embarqué — l'installeur le génère (host vide => mail()). Ainsi
    // un redéploiement ne réécrase pas un SMTP configuré. La config dev (mailpit) reste exclue.
    $count++;

    $zip->close();
    printf("  %-32s %5d fichiers  %6.1f Mo\n", $name . '.zip', $count, filesize($zip_path) / 1048576);

    return $entries;
}

/**
 * Le manifeste embarqué dans le paquet de mise à jour.
 *
 * `protected` : ce que la mise à jour ne doit JAMAIS écrire, même si le paquet en porte une version.
 * Ce sont les données et la configuration du site qui reçoit la mise à jour — sa base, ses fichiers
 * envoyés, ses journaux, ses sauvegardes. `apply_update_package()` protège aussi `config/` en place et
 * `install/db.txt` en dur ; les déclarer ici les rend lisibles, et permet d'en ajouter sans toucher au code
 * de l'installeur.
 *
 * `remove` : les fichiers que CETTE version retire. La liste se calcule en comparant l'inventaire
 * précédent — `dist/checksum.json` de la version publiée — à ce que le paquet livre aujourd'hui. Sans
 * inventaire précédent sous la main, elle reste vide : mieux vaut ne rien supprimer que supprimer au
 * jugé, et le site gardera quelques fichiers morts jusqu'à la version suivante.
 */
function update_manifest(string $root, string $version): string
{
    $proteges = [
        'config/',      // identifiants de base, clés, réglages du site
        'install/db.txt', // le verrou d'installation : il appartient au site (le reste d'install/ suit les versions)
        'upload/',      // ce que les membres ont envoyé
        'logs/',        // le journal, qui sert justement à comprendre une mise à jour ratée
        'backups/',     // les sauvegardes, dont celle prise juste avant d'écrire
        'cache/',       // reconstruit seul, et jamais livré
    ];

    $retires = [];
    $ancien  = $root.'/dist/checksum.json';   // `dist/` vit DANS la racine du projet, pas a cote

    if (is_file($ancien) && is_array($avant = json_decode((string) file_get_contents($ancien), TRUE))) {
        $aujourdhui = [];

        foreach (nf_parcourir($root, ['.git', 'node_modules', 'dist']) as $rel => $file) {
            $aujourdhui[$rel] = TRUE;
        }

        foreach (array_keys($avant) as $rel) {
            if (!isset($aujourdhui[$rel])) {
                $retires[] = $rel;
            }
        }

        sort($retires);
    }

    return (string) json_encode([
        'format'       => 1,
        'version'      => $version,
        'generated_at' => date('c'),
        'protected'    => $proteges,
        'remove'       => $retires,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

/**
 * Écrit dist/version.json et dist/checksum.json, les deux manifestes que le Monitoring télécharge.
 *
 *   - version.json  : ce que le site compare à SA version, et de quoi télécharger le paquet en
 *                     toute sûreté. Il ne porte qu'un NOM DE FICHIER, jamais une URL : l'origine
 *                     vient de l'allow-list côté site, et compromettre ce manifeste ne la déplace pas.
 *   - checksum.json : une empreinte MD5 par fichier livré, restreinte aux dossiers que le Monitoring
 *                     parcourt réellement (cf. $folders du modèle, moins le runtime) plus index.php.
 *                     Un fichier absent de cette liste serait signalé « ne devrait pas se trouver là ».
 *
 * @param array<string,string> $entries chemin dans le zip => chemin sur le disque
 */
function manifests(string $dist, string $version, array $entries): void
{
    $zip_name = "neofrag-reborn-update-{$version}.zip";
    $zip_path = "{$dist}/{$zip_name}";

    // Dossiers réellement parcourus par le Monitoring : $folders moins backups/cache/config/logs/
    // overrides/upload (runtime, propre à chaque site — jamais comparé à une empreinte).
    $scanned = ['addons/', 'css/', 'fonts/', 'images/', 'js/', 'lib/', 'modules/', 'neofrag/', 'themes/', 'widgets/'];

    $checksum = [];
    foreach ($entries as $rel => $path) {
        if ($rel === 'index.php') {
            $checksum[$rel] = md5_file($path);
            continue;
        }
        foreach ($scanned as $d) {
            if (str_starts_with($rel, $d)) {
                $checksum[$rel] = md5_file($path);
                break;
            }
        }
    }
    ksort($checksum);

    $manifest = ['neofrag' => [
        'version' => $version,
        'file'    => $zip_name,
        'sha256'  => hash_file('sha256', $zip_path),
        'size'    => filesize($zip_path),
        'date'    => gmdate('Y-m-d\TH:i:s\Z'),
    ]];

    file_put_contents("{$dist}/version.json",  json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    file_put_contents("{$dist}/checksum.json", json_encode($checksum, JSON_UNESCAPED_SLASHES));

    printf("  %-32s %5d empreintes\n", 'checksum.json', count($checksum));
    printf("  %-32s sha256 %s\n",      'version.json',  substr($manifest['neofrag']['sha256'], 0, 16).'…');
}

/** Exclusions communes + spécifiques au paquet (variant : principal | demo | public). */
function excluded(string $rel, string $variant): bool
{
    // Le contenu démo n'est que dans le paquet démo.
    if ($rel === 'install/demo.sql') {
        return $variant !== 'demo';
    }

    // Paquet PUBLIC (distribution générique, à diffuser) : pas la vitrine (site spécifique, non générique).
    // La vitrine (thème « vitrine » + widget « landing ») = le site spécifique de l'auteur. Elle n'est que
    // dans le paquet PRINCIPAL ; la démo (qui a son propre contenu) et le public (générique) ne l'embarquent pas.
    if ($variant !== 'principal') {
        if ($rel === 'install/vitrine.sql') {
            return true; // mise en page vitrine = site de l'auteur, jamais dans démo/public
        }
        foreach (['themes/vitrine/', 'widgets/landing/'] as $d) {
            if (str_starts_with($rel . '/', $d)) {
                return true;
            }
        }
    }

    // Modèle « tout bundlé » (option C) : TOUS les modules/widgets/thèmes (Tier 0/1/2) sont dans le paquet,
    // avec leur install.sql → installables HORS-LIGNE, sans marketplace. Le marketplace ne sert plus qu'aux
    // mises à jour et aux addons tiers. (Seules les variantes overlay : public sans la vitrine, démo + demo.sql.)

    // NB : le vendor/ doit être un vendor de PROD cohérent (composer install --no-dev) — on ne
    // supprime PAS de dossiers dev ici (l'autoloader « files » require certains en dur au boot).

    // Dossiers exclus du paquet FTP : dev/repo (pas de runtime). docs/ et tools/ ne servent pas
    // au site déployé (tools/maintenance.php = cron optionnel, à ajouter à la main si besoin).
    static $dirs = ['.git/', '.github/', '.wf-out/', '.playwright-mcp/', '.claude/', '.idea/', '.vscode/',
        '.tmpshots/', 'node_modules/', 'tests/', 'backups/', 'cache/', 'logs/', 'dist/', 'docs/', 'tools/',
        '.phpunit.cache/'];
    foreach ($dirs as $d) {
        if (str_starts_with($rel . '/', $d) || str_starts_with($rel, $d)) {
            return true;
        }
    }

    // upload/ : garder seulement le .htaccess de sécurité.
    if (str_starts_with($rel, 'upload/') && $rel !== 'upload/.htaccess') {
        return true;
    }

    static $files = ['config/db.php', 'config/crypt.php', 'config/password.php', 'config/neofrag.php', 'config/email.php',
        'install/db.txt', '_landing-preview.html', '.gitignore', '.gitattributes', '.dockerignore',
        '.env', 'phpunit.xml', '.phpunit.result.cache', 'settings.md', 'scan-modal.md',
        // Fichiers repo/dev inutiles sur l'hébergement FTP :
        'README.md', 'CHANGELOG.md', 'docker-compose.yml', 'compose.yml', 'Dockerfile', 'nginx.conf', 'Caddyfile',
        'composer.json', 'composer.lock',
        // Outillage d'analyse statique (dev/CI uniquement, pas de runtime) :
        'phpstan.neon', 'phpstan-base.neon', 'phpstan-baseline.neon', 'phpstan-bootstrap.php',
        // Outillage front : le site servi ne depend d'aucun paquet npm.
        'package.json', 'package-lock.json', 'eslint.config.js', 'playwright.config.js'];
    if (in_array($rel, $files, true)) {
        return true;
    }

    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    if (in_array($ext, ['log', 'map', 'scssc'], true)) {
        return true;
    }

    return false;
}

function neofrag_config(bool $demo): string
{
    $lines = [
        "<?php",
        "",
        "define('NEOFRAG_DEBUG_BAR',  FALSE);",
        "define('NEOFRAG_SAFE_MODE',  FALSE);",
        "define('NEOFRAG_LOGS',       FALSE);",
        "define('NEOFRAG_LOGS_DB',    FALSE);",
        "define('NEOFRAG_LOGS_I18N',  FALSE);",
    ];
    if ($demo) {
        $lines[] = "";
        $lines[] = "// Site de démonstration : administration en lecture seule, auto-reset via cron.";
        $lines[] = "define('NEOFRAG_DEMO',       TRUE);";
    }
    return implode("\n", $lines) . "\n";
}

function nf_version(string $root): string
{
    $index = (string) @file_get_contents($root . '/index.php');
    if (preg_match("/NEOFRAG_VERSION',\s*'([^']+)'/", $index, $m)) {
        return $m[1];
    }
    return '1.0.0';
}
