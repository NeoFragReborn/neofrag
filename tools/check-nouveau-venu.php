<?php
declare(strict_types=1);

/**
 * check-nouveau-venu — le README et le guide du contributeur, suivis à la lettre sur une machine vierge, mènent à un site qui tourne et à une batterie verte.
 *
 * Famille : cible
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * La première chose que fait celui qui découvre le projet, c'est copier les commandes du README. Si
 * l'une échoue, il s'en va. Personne ne les jouait : celles du bloc « Développer » finissaient par
 * `vendor/bin/phpunit --fail-on-skipped`, qui échoue forcément sans base de test — et le README ne
 * disait pas comment la créer (relevé le 2026-10-04, en écrivant ce contrôle).
 *
 * Les blocs à jouer sont marqués dans les documents eux-mêmes, par un commentaire invisible à la
 * lecture posé juste au-dessus : `<!-- nouveau-venu … -->`. Le contrôle les joue TELS QUELS, avec
 * `bash`, dans un dossier vide :
 *   1. le bloc du README, dans son propre dossier : clone, Composer, assemblage, contrôles ;
 *   2. les blocs du guide du contributeur, à la suite, dans un autre : clone, installation, serveur,
 *      base des tests, suite de tests, PHPStan et sa baseline, contrôles, contrôles en navigateur ;
 * puis il vérifie que le site répond, et que la baseline de PHPStan régénérée est identique à celle
 * du dépôt. Les documents joués sont ceux du dépôt cloné : s'ils diffèrent de ceux qu'il a lus, il
 * refuse de conclure.
 *
 * Ce qu'il substitue, et rien d'autre :
 *   - `--db-user=…` et `--db-pass=…` : le compte MySQL/MariaDB du nouveau venu (`NF_NV_DB_USER`,
 *     `NF_NV_DB_PASS`) ; `NF_ADMIN_PASS` doit être posée, comme le guide le demande ;
 *   - `php -S …` : lancé en arrière-plan (il ne rend jamais la main), puis attendu ;
 *   - avec `--depot-neofrag=<url>` (ou `--depot-extensions=`) : l'adresse du `git clone`, pour éprouver
 *     une version candidate avant qu'elle soit dans le dépôt public.
 * Un autre `…` dans un bloc joué est une commande que personne ne peut copier : il refuse.
 *
 * Usage
 * -----
 *   NF_NV_DB_USER=… NF_NV_DB_PASS=… NF_ADMIN_PASS=… php tools/check-nouveau-venu.php --dossier=/tmp/nouveau-venu
 *   php tools/check-nouveau-venu.php --dossier=… --depot-neofrag=https://github.com/<org>/<candidate>.git
 *   php tools/check-nouveau-venu.php --montrer        les scripts qui seraient joués, sans rien jouer
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/serveur.php';

[$o] = nf_options(['dossier' => '', 'depot-neofrag' => '', 'depot-extensions' => '', 'montrer' => FALSE]);

const NF_NV_DOCUMENTS = ['README.md', '.github/CONTRIBUTING.md'];

/**
 * Les blocs marqués d'un document, dans l'ordre.
 *
 * @return list<string>
 */
function blocs_marques(string $texte): array
{
    preg_match_all('/^<!-- nouveau-venu\b[^\n]*-->\R```(?:bash|sh)\R(.*?)^```/ms', $texte, $m);

    return $m[1];
}

/** Le script bash d'un ou plusieurs blocs, avec les seules substitutions déclarées dans l'en-tête. */
function script(array $blocs, array $depots): string
{
    $lignes = ['set -eu', 'export NF_ADMIN_PASS', ''];

    foreach ($blocs as $bloc)
    {
        // Les continuations `\` en fin de ligne restent telles quelles : bash les lit comme dans un terminal.
        foreach (preg_split('/\R/', rtrim($bloc)) ?: [] as $ligne)
        {
            // `<adresse>#<branche>` : la candidate est sur une branche du banc d'essai. Le dossier cloné
            // garde le nom que le document attend (`cd neofrag` suit).
            foreach ($depots as $nom => $url)
            {
                if ($url !== '')
                {
                    [$adresse, $branche] = array_pad(explode('#', $url, 2), 2, '');
                    $ligne = str_replace('git clone https://github.com/NeoFragReborn/'.$nom.'.git',
                        'git clone '.($branche !== '' ? '--branch '.escapeshellarg($branche).' ' : '').escapeshellarg($adresse).' '.$nom, $ligne);
                }
            }

            $ligne = str_replace(['--db-user=…', '--db-pass=…'], ['--db-user="$NF_NV_DB_USER"', '--db-pass="$NF_NV_DB_PASS"'], $ligne);

            if (str_contains($ligne, '…'))
            {
                nf_refus("un bloc joué porte une valeur à compléter (« … ») que personne ne peut copier telle quelle :\n  {$ligne}");
            }

            // Le serveur intégré ne rend jamais la main : il part en arrière-plan, et l'on attend qu'il réponde.
            if (preg_match('/^php -S (\S+)/', $ligne, $s))
            {
                $commande = trim((string) preg_replace('/\s+#.*$/', '', $ligne));
                $lignes[] = $commande.' > "$NF_NV_JOURNAL" 2>&1 &';
                $lignes[] = 'echo $! > "$NF_NV_PID"';
                $lignes[] = 'for i in $(seq 1 50); do php -r \'exit(@fsockopen("'.explode(':', $s[1])[0].'", '.(int) explode(':', $s[1])[1].') ? 0 : 1);\' && break; sleep 0.2; done';
                continue;
            }

            $lignes[] = $ligne;
        }

        $lignes[] = '';
    }

    return implode("\n", $lignes)."\n";
}

/** Joue un script dans un dossier ; rend le code de sortie, la sortie s'affiche au fil de l'eau. */
function jouer(string $script, string $dossier, array $env): int
{
    $fichier = $dossier.'/.nouveau-venu.sh';
    file_put_contents($fichier, $script);

    $p = proc_open(['bash', $fichier], [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDOUT], $tuyaux, $dossier, $env);

    if (!is_resource($p))
    {
        nf_refus('bash est introuvable : ce contrôle joue les commandes comme un terminal');
    }

    fclose($tuyaux[0]);

    return proc_close($p);
}

// ── Les documents ─────────────────────────────────────────────────────────────
$blocs = [];

foreach (NF_NV_DOCUMENTS as $document)
{
    $blocs[$document] = blocs_marques((string) @file_get_contents(nf_racine().'/'.$document));

    if (!$blocs[$document])
    {
        nf_refus("aucun bloc marqué « <!-- nouveau-venu --> » dans {$document} : ce contrôle se joue sur le dépôt public neofrag");
    }
}

$depots  = ['neofrag' => $o['depot-neofrag'], 'extensions' => $o['depot-extensions']];
$scripts = [
    'README.md'              => script($blocs['README.md'], $depots),
    '.github/CONTRIBUTING.md' => script($blocs['.github/CONTRIBUTING.md'], $depots),
];

if ($o['montrer'])
{
    foreach ($scripts as $document => $script)
    {
        echo "── {$document}\n{$script}\n";
    }

    nf_ok('scripts montrés, rien n\'est joué');
}

foreach (['NF_NV_DB_USER', 'NF_NV_DB_PASS', 'NF_ADMIN_PASS'] as $variable)
{
    if ((string) getenv($variable) === '')
    {
        nf_refus("{$variable} manque : le compte de base de données du nouveau venu, et le mot de passe de l'administrateur que le guide fait poser");
    }
}

$dossier = rtrim(str_replace('\\', '/', $o['dossier']), '/');

if ($dossier === '' || (is_dir($dossier) && array_diff(scandir($dossier) ?: [], ['.', '..'])))
{
    nf_refus('--dossier=<un dossier vide, ou à créer> : la machine vierge du nouveau venu');
}

@mkdir($dossier.'/readme', 0775, TRUE);
@mkdir($dossier.'/contributeur', 0775, TRUE);

$pid     = $dossier.'/serveur.pid';
$journal = $dossier.'/serveur.log';
$env     = [
    'PATH'           => (string) getenv('PATH'),
    'HOME'           => (string) (getenv('HOME') ?: $dossier),
    'COMPOSER_HOME'  => (string) (getenv('COMPOSER_HOME') ?: $dossier.'/.composer'),
    'NF_NV_DB_USER'  => (string) getenv('NF_NV_DB_USER'),
    'NF_NV_DB_PASS'  => (string) getenv('NF_NV_DB_PASS'),
    'NF_ADMIN_PASS'  => (string) getenv('NF_ADMIN_PASS'),
    'NF_NV_PID'      => $pid,
    'NF_NV_JOURNAL'  => $journal,
];

foreach (['NF_CHROME', 'NF_BROWSER', 'GIT_CONFIG_GLOBAL'] as $facultative)
{
    if (($valeur = getenv($facultative)) !== FALSE)
    {
        $env[$facultative] = $valeur;
    }
}

register_shutdown_function(static function () use ($pid): void {
    if (is_file($pid) && ($n = (int) file_get_contents($pid)) > 0)
    {
        @exec('kill '.$n.' 2>/dev/null');
    }
});

$echecs = [];

// 1. Le README, dans son propre dossier
echo "\n═══ README.md — le bloc « Développer », tel quel\n\n";

if (($code = jouer($scripts['README.md'], $dossier.'/readme', $env)) !== 0)
{
    $echecs[] = "le bloc du README s'arrête (code {$code})";
}

// 2. Le guide du contributeur, à la suite, dans un autre dossier
echo "\n═══ .github/CONTRIBUTING.md — « Démarrer » puis « Avant d'ouvrir une PR », tels quels\n\n";

if (($code = jouer($scripts['.github/CONTRIBUTING.md'], $dossier.'/contributeur', $env)) !== 0)
{
    $echecs[] = "le parcours du guide du contributeur s'arrête (code {$code})";
}

// 3. Ce que le parcours promet
echo "\n═══ Ce que le parcours promet\n\n";

foreach (['readme' => 'README.md', 'contributeur' => '.github/CONTRIBUTING.md'] as $ou => $document)
{
    $clone = $dossier.'/'.$ou.'/neofrag';

    foreach (NF_NV_DOCUMENTS as $lu)
    {
        if (is_file($clone.'/'.$lu) && blocs_marques((string) file_get_contents($clone.'/'.$lu)) !== $blocs[$lu])
        {
            nf_refus("le {$lu} du dépôt cloné n'est pas celui qui a été joué : la version éprouvée n'est pas celle du dépôt — jouer l'outil du dépôt cloné");
        }
    }
}

if (preg_match('#php -S (\S+)#', implode("\n", $blocs['.github/CONTRIBUTING.md']), $s)
    && preg_match('#--site-url=(\S+)#', implode("\n", $blocs['.github/CONTRIBUTING.md']), $adresse))
{
    $r = nf_http($adresse[1].'/', ['suivre' => 3, 'timeout' => 60]);
    printf("  %-6s le site installé répond à %s : HTTP %d\n", $r['code'] === 200 ? 'OK' : 'ÉCHEC', $adresse[1], $r['code']);

    if ($r['code'] !== 200 || stripos($r['corps'], '</html>') === FALSE)
    {
        $echecs[] = "le site installé ne répond pas à {$adresse[1]} (HTTP {$r['code']}) — journal du serveur : {$journal}";
    }
}

$clone = $dossier.'/contributeur/neofrag';

if (is_dir($clone.'/.git'))
{
    exec('git -C '.escapeshellarg($clone).' status --porcelain -- phpstan-baseline.neon 2>&1', $etat, $code);
    $stable = $code === 0 && !$etat;
    printf("  %-6s la baseline de PHPStan régénérée est celle du dépôt\n", $stable ? 'OK' : 'ÉCHEC');

    if (!$stable)
    {
        $echecs[] = 'composer stan:baseline réécrit phpstan-baseline.neon : la baseline livrée est périmée';
    }
}

echo "\n";

if ($echecs)
{
    foreach ($echecs as $echec)
    {
        echo '  ✗ ', $echec, "\n";
    }

    nf_echec(count($echecs).' promesse(s) non tenue(s) — un nouveau venu se serait arrêté là');
}

nf_ok('le README et le guide du contributeur, suivis à la lettre, mènent à un site qui tourne et à une batterie verte');
