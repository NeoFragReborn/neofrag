<?php
declare(strict_types=1);

/**
 * check-js — exécute les épreuves JS de tests/Browser/ dans un vrai navigateur.
 *
 * Famille : navigateur
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Certaines propriétés du front ne s'observent QUE dans un navigateur : ce qui s'exécute ou non,
 * dans quel document, avec quel nonce CSP, ce qu'un `insertAdjacentHTML` fait réellement. Aucun
 * test PHP ne les voit. Le projet n'a pas de pile de test JS — pas de package.json, décision
 * assumée — et le prix de cette absence a été payé en clair : les cinq régressions de la dé-jQuery
 * du 2026-08-28 ne se voyaient qu'à l'usage réel, et le JS inerte des widgets ajoutés en éditeur en
 * direct a vécu des mois sans que rien ne le signale.
 *
 * Plutôt qu'installer un écosystème entier, une page HTML par sujet et ce lanceur suffisent.
 *
 * Comment ça marche
 * -----------------
 * Le module `window.NF` est extrait du VRAI gabarit (neofrag/views/theme/main.tpl.php) et servi en
 * `/nf.js` — jamais une copie, qui dériverait. Chaque `tests/Browser/*.test.html` est chargé dans un
 * navigateur sans interface, rend son verdict dans un `<pre id="nf-verdict">`, et le lanceur agrège.
 * Toutes les sources JS du projet sont servies sous `/src/<chemin>`, blocs PHP neutralisés — une
 * épreuve charge donc le vrai fichier, pas une copie. Écrire une épreuve : cf. tests/Browser/harness.js.
 *
 * Usage
 * -----
 *   php tools/check-js.php
 *   php tools/check-js.php --garder      conserve le dossier de travail et les journaux pour inspection
 *   php tools/check-js.php --port=8099
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/navigateur.php';

[$o] = nf_options(['garder' => FALSE, 'port' => 0]);

$gabarit = nf_racine().'/neofrag/views/theme/main.tpl.php';
$dossier = nf_racine().'/tests/Browser';

// ── Le module NF, extrait du vrai gabarit ─────────────────────────────────────
if (!is_file($gabarit))
{
    nf_refus("gabarit introuvable : {$gabarit}");
}

$html  = (string) file_get_contents($gabarit);
$debut = strpos($html, 'window.NF = (function(){');
$fin   = $debut === FALSE ? FALSE : strpos($html, '})();', $debut);

if ($debut === FALSE || $fin === FALSE)
{
    nf_refus("le module window.NF est introuvable dans le gabarit — l'extraction doit être adaptée");
}

$module = substr($html, $debut, $fin - $debut + 5);

if (str_contains($module, '<?'))
{
    nf_refus("le module window.NF contient du PHP : il n'est plus extractible tel quel");
}

// ── Les épreuves ──────────────────────────────────────────────────────────────
$epreuves = glob($dossier.'/*.test.html') ?: [];

if (!$epreuves)
{
    nf_refus("aucune épreuve dans {$dossier} (fichiers *.test.html)");
}

// ── Salle de travail : les épreuves + le module extrait + les sources servies ─
$salle = nf_temp('salle-'.bin2hex(random_bytes(4)));
@mkdir($salle, 0775, TRUE);

foreach (glob($dossier.'/*.html') ?: [] as $f)
{
    copy($f, $salle.'/'.basename($f));
}

copy($dossier.'/harness.js', $salle.'/harness.js');
file_put_contents($salle.'/nf.js', $module);

// Toutes les sources JS du projet sont servies sous /src/<chemin>, avec les blocs PHP remplacés par
// un identifiant nu. Une épreuve vise ainsi le VRAI fichier plutôt qu'une copie qui dériverait, sans
// avoir à monter l'application. Même substitution que check-js-sources.
foreach (nf_fichiers(['js', 'neofrag', 'modules', 'widgets', 'themes', 'addons'], ['js']) as $rel => $chemin)
{
    $cible = $salle.'/src/'.$rel;

    if (!is_dir($d = dirname($cible)))
    {
        @mkdir($d, 0775, TRUE);
    }

    file_put_contents($cible, preg_replace('/<\?php.*?\?>|<\?=.*?\?>|<\?.*?\?>/s', 'NF_PHP', (string) file_get_contents($chemin)));
}

// Le serveur sert la salle, avec le routeur par défaut de `php -S` (pas celui du CMS : ici on ne
// sert que des fichiers). NULL comme routeur = aucun fichier de routage.
$serveur = nf_serveur(nf_port($o['port']), [], $salle, '');

printf("Navigateur : %s\n", nf_chrome());
printf("%d épreuve(s) dans tests/Browser/\n\n", count($epreuves));

$echecs_total = 0;
$garder       = $o['garder'];

foreach ($epreuves as $chemin)
{
    $nom = basename($chemin);

    printf("═══ %s\n", $nom);

    $dom = nf_chrome_dom(sprintf('%s/%s', $serveur->base, rawurlencode($nom)), ['budget' => 10000, 'profil' => 'js']);

    if (!preg_match('#<pre id="nf-verdict">(.*?)</pre>#s', $dom, $m))
    {
        // Muet = échec. Une épreuve qui ne rend pas de verdict n'a rien prouvé ; la compter comme
        // réussie serait exactement le « test qui skippe en silence » que l'on fuit.
        nf_avertir("  Aucun verdict rendu — la page n'a pas appelé NFTest.fini(), ou le JS a échoué avant.");
        $echecs_total++;
        $garder = TRUE; // on conserve de quoi enquêter
        continue;
    }

    $verdict = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    echo $verdict, "\n\n";

    if (($n = substr_count($verdict, 'ECHEC')) > 0)
    {
        $echecs_total += $n;
        $garder = TRUE;
    }
}

$serveur->arreter();

if ($garder)
{
    nf_avertir("Dossier de travail conservé pour inspection : {$salle}");
}
else
{
    exec('rm -rf '.escapeshellarg($salle));
}

if ($echecs_total > 0)
{
    nf_echec(sprintf('%d échec(s) au total', $echecs_total));
}

nf_ok(sprintf('%d épreuve(s), aucun échec', count($epreuves)));
