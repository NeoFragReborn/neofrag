<?php
declare(strict_types=1);

/**
 * capture — capture d'écran des pages du site, y compris celles qui exigent une session d'administrateur.
 *
 * Famille : outil
 *
 * Pourquoi
 * --------
 * Lire le code ne prouve pas que l'écran est correct. Un cas réel : l'assistant d'ajout de widget
 * s'affichait sans aucun style alors que la feuille existait, était juste et était déployée — deux
 * fichiers homonymes, et le résolveur servait l'un ou l'autre selon l'appelant. Aucune lecture de
 * code ne pouvait le montrer ; il fallait regarder la page servie.
 *
 * Chaque session refabriquait donc son script de capture jetable, avec à chaque fois les mêmes
 * pièges : le cookie de session à poser avant le rendu (sinon l'administration redirige et on
 * capture la page d'accueil en croyant capturer le tableau de bord), le thème clair/sombre qui vit
 * dans le `localStorage` du navigateur (sinon les deux captures sortent identiques), et le serveur
 * de test laissé en vie sur son port (qui fait ensuite mentir tous les contrôles suivants).
 *
 * Usage
 * -----
 *   php tools/capture.php                                    un échantillon, 1600 px, thème clair
 *   php tools/capture.php --largeur=390 --theme=sombre
 *   php tools/capture.php --sortie=/tmp/mes-captures -- /fr/admin/events /fr/user
 *   php tools/capture.php --visiteur -- /fr                  sans session ni bandeau cookies
 *   php tools/capture.php --html -- /fr/admin/quotes         garde AUSSI le HTML servi (.html)
 *   php tools/capture.php --habillage=forge --largeur=390 --visiteur -- /fr   dans un thème donné
 *
 * `--habillage` : le thème PUBLIC de la capture. Il devient le thème par défaut le temps de la
 * capture, et l'ancien est rétabli à la fin, interruption comprise (`nf_theme_temporaire()`). Sans
 * lui, chaque défaut signalé dans un thème précis se reproduisait en changeant le réglage à la main
 * (2026-09-23 : un menu absent en mobile sous nebula, des entrées qui passent à la ligne ailleurs).
 *
 * `--html` : le HTML tel que le serveur l'a rendu, avec la même session, à côté de la capture. Une
 * image cassée ou un texte inattendu se lisent dans la source, et chaque enquête refaisait jusque-là
 * sa requête à la main pour y arriver — cookie de session compris (2026-09-23).
 *
 * Options : --largeur=N  --hauteur=N  --theme=clair|sombre  --habillage=THÈME  --sortie=DOSSIER  --port=N  --visiteur  --html
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/navigateur.php';

[$o, $chemins] = nf_options([
    'largeur'  => 1600,
    'hauteur'  => 1400,
    'theme'    => 'clair',
    'sortie'   => sys_get_temp_dir().'/nf-captures',
    'port'     => 0,
    'visiteur' => FALSE,
    'html'     => FALSE,
    'habillage' => '',
]);

if (!$chemins)
{
    // Un échantillon qui couvre les trois familles d'écrans.
    $chemins = ['/fr', '/fr/user', '/fr/admin', '/fr/admin/events', '/fr/admin/settings'];
}

$sombre = str_starts_with(strtolower($o['theme']), 's') || str_starts_with(strtolower($o['theme']), 'd');
$db     = nf_connexion();

if ($o['habillage'] !== '')
{
    if (!in_array($o['habillage'], nf_themes_installes($db), TRUE))
    {
        nf_refus(sprintf('thème « %s » absent de ce site — installés : %s', $o['habillage'], implode(', ', nf_themes_installes($db))));
    }

    nf_theme_temporaire($db);
    nf_reglage_poser($db, 'nf_default_theme', $o['habillage']);
    // L'époque change : un cookie de thème posé par un visiteur (le sélecteur de la démo) cède la place.
    nf_reglage_poser($db, 'nf_theme_epoch', (string) time());
}

// `--visiteur` : la page telle que la voit quelqu'un qui n'est PAS connecté et qui a déjà répondu au
// bandeau cookies. Sans cela, toute capture porte la barre d'administration et le bandeau de
// consentement — deux bandes qui n'ont rien à faire sur la vignette d'aperçu d'un thème.
$serveur = nf_serveur(nf_port($o['port']), [
    'NF_OUTIL_SESSION' => $o['visiteur'] ? '' : nf_session_admin($db),
    'NF_OUTIL_CONSENT' => $o['visiteur'] ? 'essentials' : '',
    'NF_OUTIL_THEME'   => $sombre ? 'dark' : 'light',
]);

@mkdir($o['sortie'], 0775, TRUE);

printf("Base : %s — %d px de large, thème %s%s\nSortie : %s\n\n",
    $serveur->base, $o['largeur'], $sombre ? 'sombre' : 'clair', $o['habillage'] !== '' ? ', habillage '.$o['habillage'] : '', $o['sortie']);

$echecs = 0;

foreach ($chemins as $chemin)
{
    $nom   = trim((string) preg_replace('/[^a-z0-9]+/i', '-', $chemin), '-') ?: 'accueil';
    $cible = $o['sortie'].'/'.$nom.'.png';

    if (nf_chrome_capture($serveur->base.$chemin, $cible, [
        'largeur' => $o['largeur'], 'hauteur' => $o['hauteur'], 'profil' => $sombre ? 'sombre' : 'clair',
    ]))
    {
        printf("  %-32s %7.0f Ko\n", $chemin, filesize($cible) / 1024);
    }
    else
    {
        $echecs++;
        printf("  %-32s ÉCHEC\n", $chemin);
    }

    if ($o['html'])
    {
        $reponse = nf_http($serveur->base.$chemin);
        file_put_contents($source = $o['sortie'].'/'.$nom.'.html', $reponse['corps']);
        printf("  %-32s HTTP %d, %s\n", '', $reponse['code'], $source);
    }
}

printf("\n%d capture(s), %d échec(s).\n", count($chemins) - $echecs, $echecs);

exit($echecs ? NF_ECHEC : NF_OK);
