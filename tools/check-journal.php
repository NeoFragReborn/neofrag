<?php
declare(strict_types=1);

/**
 * check-journal — sert des pages puis lit le journal PHP, ou relit une fenêtre de temps : rien ne doit s'y être écrit.
 *
 * Famille : navigateur
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le 2026-09-20, une méthode neuve du routeur passait une chaîne littérale à `trigger()`, qui prend
 * son argument par référence : **erreur fatale à chaque redirection de langue**. Elle était
 * invisible partout où l'on regardait, parce que l'en-tête `Location` part AVANT le plantage : le
 * client recevait son 302, suivait, obtenait son 404. Sept épreuves d'adresses, quatre de fichiers
 * racine, six de pages, puis la batterie complète — **24 contrôles verts sur 24** — pendant que
 * chaque requête écrivait quatorze lignes de trace dans le journal.
 *
 * Ce que ça dit : **un code HTTP correct ne prouve pas que la requête s'est bien passée.** Tout ce
 * qui survient après l'envoi des en-têtes est muet pour un client, et pour tous nos contrôles qui
 * jugent au code de retour. Le seul témoin est le journal.
 *
 * Ce que fait cet outil : il note la taille du journal, sert un échantillon de pages représentatif,
 * puis lit ce qui s'est ajouté. Toute erreur fatale, exception non rattrapée, alerte ou avis PHP
 * l'échoue en nommant la ligne. Toute ligne que le PRODUIT écrit lui-même l'échoue aussi : le
 * journal de production est le premier instrument de diagnostic, ce qui n'est pas une anomalie
 * n'a rien à y faire — un checker dont les refus sont ordinaires le déclare (`refus_ordinaire()`).
 *
 * Le mode lecture, `--depuis=`
 * ---------------------------
 * Le 2026-09-22 au soir, le journal de la DÉMONSTRATION portait depuis des heures six défauts que
 * rien n'avait vus — la page des événements en 404, les votes des sondages perdus, le widget du
 * forum toujours vide. Aucun outil ne le lisait : celui-ci ne regardait que ce qui s'écrivait
 * pendant qu'il servait ses seize pages, et la démonstration a bien plus de contenu qu'un site d'essai.
 *
 * `--depuis=24h` ne sert rien : il relit ce que l'installation a écrit dans la fenêtre donnée,
 * regroupé par message, et juge. C'est la lecture de reprise et d'après déploiement, pour la
 * production comme pour la démonstration. Le classement et le rapport vivent dans `lib/journal.php`.
 *
 * Usage
 * -----
 *   php tools/check-journal.php                    sert seize pages sur cette installation, puis lit
 *   php tools/check-journal.php --journal=/var/www/neofrag/logs/php.log --base=https://example.org
 *   php tools/check-journal.php --journal=/var/www/demo/logs/php.log --depuis=24h
 *   php tools/check-journal.php --depuis="2026-09-22 18:00"    une date, à l'heure du serveur
 *   php tools/check-journal.php --verbeux
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/journal.php';

[$o] = nf_options(['journal' => nf_racine().'/logs/php.log', 'base' => '', 'depuis' => '', 'verbeux' => FALSE, 'port' => 0]);

// En lecture d'une installation servie par d'autres (`--depuis`), un journal absent est AMBIGU — rien
// d'écrit, ou journal mal configuré : refus. Quand l'outil sert lui-même le site, il le crée d'avance.
// Sauf à côté de son `.1` : le bouton « Vider » du Monitoring RENOMME le journal (il garde l'ancien),
// et rien n'a été écrit depuis. Un vidage dans la fenêtre en a emporté le début dans `.1` : on lit les
// deux. Vu le 2026-10-04 : la vitrine, vidée par le mainteneur après une mise à jour, n'avait plus une
// erreur — et l'outil refusait de le dire.
$ancien = $o['journal'].'.1';
$vide   = $o['depuis'] !== '' && !is_file($o['journal']) && is_file($ancien);

if ($o['depuis'] !== '' ? !is_file($o['journal']) && !$vide : !nf_journal_preparer($o['journal']))
{
    nf_refus("journal introuvable ou non inscriptible : {$o['journal']} — vérifier NEOFRAG_LOGS dans config/neofrag.php, ou passer --journal=<chemin>");
}

// Le dossier de l'installation, pour ôter son chemin des messages : `logs/php.log` en est à deux niveaux.
$installation = dirname((string) realpath($vide ? $ancien : $o['journal']), 2);

// ── Le mode lecture : une fenêtre de temps, rien de servi ─────────────────────────
if ($o['depuis'] !== '')
{
    if (preg_match('/^(\d+)\s*(m|h|j|d)$/', $o['depuis'], $m))
    {
        $depuis = time() - (int) $m[1] * ['m' => 60, 'h' => 3600, 'j' => 86400, 'd' => 86400][$m[2]];
    }
    else if (($depuis = strtotime($o['depuis'])) === FALSE)
    {
        nf_refus("--depuis={$o['depuis']} : ni une durée (30m, 24h, 7j), ni une date lisible");
    }

    // L'ancien d'abord : ses entrées précèdent celles du journal. Écrit pour la dernière fois avant la
    // fenêtre, il n'a rien à dire.
    $entrees = [];
    $lus     = [];

    foreach ([$ancien, $o['journal']] as $fichier)
    {
        if (!is_file($fichier) || ($fichier === $ancien && (int) filemtime($fichier) < $depuis))
        {
            continue;
        }

        $lu = nf_journal_depuis_date($fichier, $depuis);

        if ($lu === NULL)
        {
            nf_refus("aucune ligne horodatée dans {$fichier} : format inconnu, rien ne peut être daté — refus de conclure");
        }

        $entrees = array_merge($entrees, $lu);
        $lus[]   = basename($fichier);
    }

    printf("Journal : %s (%s)\nFenêtre : depuis le %s UTC — %d entrée(s)%s\n",
        $o['journal'],
        $vide ? 'absent, son .1 à côté : vidé depuis le Monitoring, rien d\'écrit depuis' : nf_journal_taille($o['journal']).' octets',
        gmdate('d/m/Y H:i', $depuis),
        count($entrees),
        $lus ? ', lues dans '.implode(' et ', $lus) : '');

    $classe  = nf_journal_classer($entrees);
    $fautifs = nf_journal_montrer($classe, $installation);

    if ($fautifs)
    {
        nf_echec(sprintf('%d message(s) distinct(s) à traiter dans la fenêtre — %d erreur(s) PHP, %d ligne(s) du produit', $fautifs, count($classe['php']), count($classe['produit'])));
    }

    nf_ok(sprintf('aucune erreur PHP ni ligne du produit depuis le %s UTC%s', gmdate('d/m H:i', $depuis), $classe['autres'] ? ' ('.count($classe['autres']).' autre(s) ligne(s) montrée(s))' : ''));
}

// ── Le mode épreuve : servir des pages, puis lire ce qui s'est ajouté ─────────────
//
// La liste est courte à dessein : ce contrôle ne remplace pas `check-liens`, qui parcourt tout le
// site. Il cherche ce qui plante SANS se voir, et cela se joue sur les chemins du routeur — avec ou
// sans préfixe de langue, avec ou sans extension, page connue ou inconnue.
$chemins = [
    '/',                       // redirection de langue, le chemin qui a planté
    '/fr',
    '/fr/forum',
    '/fr/news',
    '/fr/wiki',
    '/fr/contact',
    '/fr/members',
    '/forum',                  // sans préfixe : redirection
    '/news',
    '/robots.txt',             // fichiers racine : routés à part
    '/sitemap.xml',
    '/humans.txt',
    '/favicon.ico',
    '/inexistant-'.bin2hex(random_bytes(3)),         // 404 attendu
    '/inexistant-'.bin2hex(random_bytes(3)).'.json', // 404 attendu, chemin des extensions
    '/fr/inexistant-'.bin2hex(random_bytes(3)),
];

$base = rtrim($o['base'], '/');

if ($base === '')
{
    $base = nf_serveur(nf_port($o['port']))->base;
}

$avant = nf_journal_taille($o['journal']);

printf("Journal : %s (%d octets au départ)\nBase    : %s\n\n", $o['journal'], $avant, $base);

foreach ($chemins as $chemin)
{
    $reponse = nf_http($base.$chemin, ['suivre' => 3, 'timeout' => 20]);

    if ($o['verbeux'])
    {
        printf("  %-46s %d\n", $chemin, $reponse['code']);
    }
}

$entrees = nf_journal_depuis_octet($o['journal'], $avant);
$classe  = nf_journal_classer($entrees);

printf("%d entrée(s) ajoutée(s) au journal pendant l'épreuve.\n", count($entrees));

nf_journal_montrer($classe, $installation);

/*
 * Ce qu'il ne faut pas faire devant une ligne du produit : l'exempter pour retrouver le vert. La
 * reconnaissance se fait à la FORME (une étiquette entre crochets, cf. `lib/journal.php`), justement
 * pour qu'aucune liste ne puisse être rallongée en silence.
 */
if ($classe['php'])
{
    nf_echec(count($classe['php'])." erreur(s) PHP pendant que les pages répondaient — un code HTTP correct ne prouve rien : ce qui suit l'envoi des en-têtes est muet pour le client");
}

if ($classe['produit'])
{
    nf_echec(count($classe['produit']).' ligne(s) écrite(s) par le produit dans le journal des erreurs');
}

nf_ok('aucune erreur PHP pendant que '.count($chemins).' page(s) étaient servies');
