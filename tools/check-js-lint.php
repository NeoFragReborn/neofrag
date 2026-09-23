<?php
declare(strict_types=1);

/**
 * check-js-lint — passe ESLint sur nos sources JavaScript, PHP interpolé neutralisé.
 *
 * Famille : statique
 *
 * Pourquoi
 * --------
 * `check-js-sources` répond à deux questions — est-ce que ça compile, est-ce que ça appelle encore
 * jQuery — et il les tient bien. Il ne voit pas une variable globale créée par oubli d'un `var`, un
 * `innerHTML` nourri d'une valeur calculée, un `eval` déguisé, ni du code après un `return`. Ces
 * défauts-là ne cassent pas la page : ils la laissent à moitié vivante, sans une ligne de journal.
 *
 * Pourquoi un outil, et pas `npx eslint`
 * --------------------------------------
 * Une partie des fichiers JavaScript du produit contient du PHP interpolé — `url('admin/ajax/…')`,
 * `$this->lang('…')` — qu'aucun analyseur JavaScript n'accepte. Cet outil en fabrique des copies où
 * chaque bloc PHP devient l'identifiant nu `NF_PHP`, **en conservant le compte des lignes** pour que
 * les numéros signalés désignent le vrai fichier, puis lance ESLint sur ces copies et retraduit les
 * chemins. Sans cela, le linter refuserait un quart du périmètre, et on l'aurait vite désactivé.
 *
 * Deux verdicts, deux poids
 * -------------------------
 * Les ERREURS font échouer : chacune décrit un défaut qui a déjà mordu ici, ou qui mord en silence.
 * Les AVERTISSEMENTS — variables inutilisées, `console.log` oubliés — décrivent du désordre : les
 * passer en erreur demanderait de réécrire des dizaines de fichiers d'un coup, ce qui n'est pas le
 * but d'un premier passage. Leur nombre ne doit pas MONTER : c'est le même cliquet que
 * `check-strict-types`, et c'est ce qui empêche le désordre de s'installer tranquillement.
 *
 * Usage
 * -----
 *   php tools/check-js-lint.php
 *   php tools/check-js-lint.php --tout          montre aussi les avertissements, un par un
 *   php tools/check-js-lint.php --plafond       imprime le nombre d'avertissements à figer ici
 *   NF_NODE=/chemin/node php tools/check-js-lint.php
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

/**
 * Le plafond d'avertissements : il ne monte jamais.
 *
 * Relevé le 2026-09-22 sur 78 fichiers : 42 `innerHTML` nourris d'une valeur calculée et 3 variables
 * inutilisées. Le baisser quand on en corrige, jamais l'inverse : une hausse veut dire qu'on vient
 * d'ajouter du désordre, et c'est précisément le moment de le voir.
 */
const PLAFOND = 45;

[$o] = nf_options(['tout' => FALSE, 'plafond' => FALSE]);

// ── Node, et ESLint installé ────────────────────────────────────────────────────────────────
$racine = nf_racine();

if (!is_dir($racine.'/node_modules/eslint'))
{
    nf_refus('ESLint n\'est pas installé — lancer `npm install` à la racine du dépôt');
}

$npx = trim((string) @shell_exec(stripos(PHP_OS, 'WIN') === 0 ? 'where npx 2>NUL' : 'command -v npx 2>/dev/null'));

if ($npx === '' || !is_file(explode("\n", $npx)[0]))
{
    nf_refus('npx introuvable — installer Node.js');
}

$npx = explode("\n", $npx)[0];

// ── Les copies neutralisées ─────────────────────────────────────────────────────────────────
/*
 * Elles vivent sous `cache/`, qui est ignoré par git, par les autres contrôles (cf. NF_EXCLUS) et
 * par le serveur web. Les mettre dans le dossier temporaire du système interdirait à ESLint de les
 * rattacher à la configuration du dépôt.
 */
$salle = $racine.'/cache/js-lint';

$effacer = static function(string $dossier) use (&$effacer): void {
    if (!is_dir($dossier))
    {
        return;
    }

    foreach (scandir($dossier) ?: [] as $entree)
    {
        if ($entree === '.' || $entree === '..')
        {
            continue;
        }

        $chemin = $dossier.'/'.$entree;
        is_dir($chemin) ? $effacer($chemin) : @unlink($chemin);
    }

    @rmdir($dossier);
};

$effacer($salle);
register_shutdown_function(static function() use ($effacer, $salle): void { $effacer($salle); });

$fichiers = nf_fichiers(NF_DOSSIERS_JS, ['js']);
$avec_php = 0;

foreach ($fichiers as $rel => $absolu)
{
    $source = (string) file_get_contents($absolu);

    /*
     * Chaque bloc PHP devient `NF_PHP`, suivi d'autant de sauts de ligne qu'il en contenait. Un
     * identifiant nu, et non une chaîne : le PHP apparaît tantôt comme valeur, tantôt DÉJÀ entre
     * guillemets JS, et un identifiant reste valide dans les deux cas. Les sauts de ligne rendus
     * gardent les numéros de ligne alignés sur le fichier d'origine — sans quoi chaque signalement
     * désignerait la mauvaise ligne, et l'outil ferait perdre plus de temps qu'il n'en fait gagner.
     */
    $nettoye = preg_replace_callback(
        '/<\?php.*?\?>|<\?=.*?\?>|<\?.*?\?>/s',
        static fn (array $m): string => 'NF_PHP'.str_repeat("\n", substr_count($m[0], "\n")),
        $source,
        -1,
        $n
    );

    if ($n > 0)
    {
        $avec_php++;
    }

    $cible = $salle.'/'.$rel;
    @mkdir(dirname($cible), 0775, TRUE);
    file_put_contents($cible, $nettoye);
}

printf("%d fichier(s) JavaScript (%d contiennent du PHP interpolé).\n\n", count($fichiers), $avec_php);

// ── ESLint, en JSON pour que le verdict ne dépende pas d'un format d'affichage ───────────────
$commande = sprintf(
    'cd %s && %s eslint --no-config-lookup -c eslint.config.js --format json %s 2>/dev/null',
    escapeshellarg($racine),
    escapeshellarg($npx),
    escapeshellarg('cache/js-lint')
);

$brut = (string) @shell_exec($commande);
$json = json_decode($brut, TRUE);

if (!is_array($json))
{
    nf_refus('ESLint n\'a rien rendu d\'exploitable — vérifier `npx eslint --version` et eslint.config.js');
}

// ── Le dépouillement ────────────────────────────────────────────────────────────────────────
$erreurs = [];
$alertes = [];

foreach ($json as $fichier)
{
    $rel = preg_replace('#^.*?cache/js-lint/#', '', str_replace('\\', '/', (string) ($fichier['filePath'] ?? '')));

    foreach ($fichier['messages'] ?? [] as $m)
    {
        $ligne = sprintf('%s:%s  %s  (%s)', $rel, $m['line'] ?? '?', $m['message'] ?? '', $m['ruleId'] ?? 'parse');

        // `severity` 2 = erreur, 1 = avertissement.
        if ((int) ($m['severity'] ?? 2) === 2)
        {
            $erreurs[] = $ligne;
        }
        else
        {
            $alertes[] = $ligne;
        }
    }
}

if ($o['plafond'])
{
    printf("Avertissements à figer dans PLAFOND : %d\n", count($alertes));

    nf_ok(sprintf('%d erreur(s), %d avertissement(s)', count($erreurs), count($alertes)));
}

if ($erreurs)
{
    echo "ERREURS :\n\n";

    foreach ($erreurs as $e)
    {
        echo '  ✗ ', $e, "\n";
    }

    echo "\n";
}

if ($o['tout'] && $alertes)
{
    echo "AVERTISSEMENTS :\n\n";

    foreach ($alertes as $a)
    {
        echo '  · ', $a, "\n";
    }

    echo "\n";
}

printf("%d erreur(s), %d avertissement(s) — plafond %d.\n", count($erreurs), count($alertes), PLAFOND);

if ($erreurs)
{
    nf_echec(sprintf('%d erreur(s) ESLint', count($erreurs)));
}

if (count($alertes) > PLAFOND)
{
    nf_echec(sprintf(
        '%d avertissements pour un plafond de %d — corriger les nouveaux, ou justifier la hausse en relevant PLAFOND (`--tout` les liste)',
        count($alertes),
        PLAFOND
    ));
}

nf_ok(sprintf(
    'aucune erreur sur %d fichier(s) JavaScript ; %d avertissement(s), sous le plafond de %d',
    count($fichiers),
    count($alertes),
    PLAFOND
));
