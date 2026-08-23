<?php
declare(strict_types=1);
// Outil d'administration : jamais servi en HTTP (sinon maintenance/migrations/dumps
// seraient executables par n'importe qui si tools/ etait expose par erreur).
if (PHP_SAPI !== 'cli')
{
	http_response_code(404);
	exit;
}


/**
 * NeoFrag Reborn — produit les paquets prêts à uploader par FTP (hébergement mutualisé).
 *
 *   - dist/neofrag-reborn-<v>.zip        : site PRINCIPAL (vitrine). config/neofrag.php standard.
 *   - dist/neofrag-reborn-demo-<v>.zip   : site DÉMO. + install/demo.sql + NEOFRAG_DEMO=TRUE.
 *
 * Inclut vendor/ (pas de composer sur mutualisé) et .htaccess (mod_rewrite). Exclut les secrets
 * (config/db.php|crypt.php|password.php — générés par l'installeur web), le runtime (cache/logs/
 * backups/upload), le scratch dev (.git, tests, .playwright-mcp…) et le verrou d'install.
 *
 * Le user upload le contenu du zip, visite /install/ (DB + admin), met les dossiers writable,
 * et (démo) ajoute le cron de reset. Cf. docs/deploy-ftp.md.
 *
 * Usage : docker compose exec -T web php tools/build-release.php   (ou: php tools/build-release.php)
 */

$root    = dirname(__DIR__);
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
        fwrite(STDERR, "composer install --no-dev a échoué — abandon (vendor de dev restauré).\n");
        exit(1);
    }
} else {
    fwrite(STDERR, "AVERTISSEMENT : composer introuvable → le vendor inclura les dépendances dev.\n");
}

// Garde-fou (#5 audit) : le table-map doit recouvrir EXACTEMENT les tables vives, sinon un module
// serait packagé avec un install.sql incomplet (tables manquantes → module cassé chez l'utilisateur).
// On valide la partition avant de packager ; échec = abandon (relancer extract-module-sql + package-addons).
echo "  validation du table-map (extract-module-sql --check)…\n";
exec('php ' . escapeshellarg($root . '/tools/extract-module-sql.php') . ' --check 2>&1', $vout, $vcode);
if ($vcode !== 0) {
    fwrite(STDERR, implode("\n", $vout) . "\nABANDON : table-map périmé ou base injoignable — corrige puis relance.\n");
    exit(1);
}

build($root, $dist, $version, 'principal'); // site vitrine (avec thème vitrine)
build($root, $dist, $version, 'demo');      // site démo (+ demo.sql)
build($root, $dist, $version, 'public');    // DISTRIBUTION générique FTP-ready (sans vitrine, vendor inclus)

echo "\n✓ Paquets dans dist/ (v{$version}).\n";

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

function build(string $root, string $dist, string $version, string $variant): void
{
    $suffix   = ['principal' => '', 'demo' => '-demo', 'public' => '-public'][$variant] ?? '';
    $demo     = $variant === 'demo';
    $name     = "neofrag-reborn{$suffix}-{$version}";
    $top      = 'neofrag-reborn'; // dossier racine dans le zip
    $zip_path = "{$dist}/{$name}.zip";
    @unlink($zip_path);

    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE) !== true) {
        fwrite(STDERR, "Impossible de créer {$zip_path}\n");
        exit(1);
    }

    $count = 0;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $file) {
        $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

        if (excluded($rel, $variant)) {
            continue;
        }
        if ($file->isDir()) {
            continue; // les dossiers sont créés implicitement par addFile
        }
        $zip->addFile($file->getPathname(), "{$top}/{$rel}");
        $count++;
    }

    // config/neofrag.php généré (non versionné). Démo : NEOFRAG_DEMO=TRUE.
    $zip->addFromString("{$top}/config/neofrag.php", neofrag_config($demo));
    // NB : config/email.php n'est PAS embarqué — l'installeur le génère (host vide => mail()). Ainsi
    // un redéploiement ne réécrase pas un SMTP configuré. La config dev (mailpit) reste exclue.
    $count++;

    $zip->close();
    printf("  %-32s %5d fichiers  %6.1f Mo\n", $name . '.zip', $count, filesize($zip_path) / 1048576);
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
        'README.md', 'CHANGELOG.md', 'docker-compose.yml', 'compose.yml', 'Dockerfile', 'nginx.conf',
        'composer.json', 'composer.lock',
        // Outillage d'analyse statique (dev/CI uniquement, pas de runtime) :
        'phpstan.neon', 'phpstan-baseline.neon', 'phpstan-bootstrap.php'];
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
