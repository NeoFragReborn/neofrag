<?php
declare(strict_types=1);

/**
 * check-liens — parcourt le site et refuse tout lien interne qui ne mène nulle part.
 *
 * Famille : navigateur
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le défaut a été signalé ainsi : « des pages vides un peu partout ». Le premier exemple —
 * `/gallery/1/Événements` — venait d'un lien construit avec le TITRE au lieu du permalien : la page
 * existe à `/gallery/1/evenements`, mais rien ne pointe dessus. Aucun test ne voyait ce défaut,
 * parce qu'il ne casse ni le code ni la page cible : il casse le CHEMIN entre les deux.
 *
 * Un lien interne mort est un fait mesurable. Cet outil part de quelques pages, suit tous les liens
 * du même site, et rapporte ceux qui ne rendent pas une page — avec la page qui les portait, sans
 * quoi la correction serait une chasse au trésor.
 *
 * Ce qu'il ne suit JAMAIS : les adresses qui AGISSENT — supprimer, désactiver, basculer, purger, se
 * déconnecter. Les suivre avec une session d'administrateur détruirait le site qu'on contrôle.
 *
 * Usage
 * -----
 *   php tools/check-liens.php                     site public + administration, 1000 pages max
 *   php tools/check-liens.php --max=2000
 *
 * Mille pages par défaut, et non plus trois cents : un site d'essai bien rempli compte quelque 740 adresses, et le
 * 2026-09-22 la moitié jamais atteinte portait une candidature en erreur 500, l'édition d'un champ
 * de profil qui plantait, et trois familles de liens morts. Un parcours qui s'arrête à mi-chemin
 * rend un verdict sur la moitié du site.
 *   php tools/check-liens.php --depart=/fr/gallery
 *   php tools/check-liens.php --ignorer=/demo      une adresse servie par une AUTRE installation
 *   php tools/check-liens.php --port=8093
 *   php tools/check-liens.php --journal=<chemin>  le journal PHP à relire après le parcours
 *
 * Et le journal, relu après le parcours
 * -------------------------------------
 * Un lien qui répond 200 n'a pas prouvé que sa page s'est bien rendue. Le 2026-09-22, la page des
 * événements rendait 404 sous un titre parfaitement normal, et le widget du forum lisait une case
 * sur un entier à chaque affichage : le journal le disait, pas le code HTTP. Ce parcours ouvre
 * des centaines de pages ; il relit donc aussi ce qu'elles ont écrit au journal, et
 * refuse toute erreur PHP et toute ligne du produit — le classement est celui de `lib/journal.php`,
 * le même que `check-journal`.
 *
 * Et le texte codé deux fois
 * --------------------------
 * Le site range ses textes codés (`&eacute;`) ; une page qui les recodait affichait « &eacute; » au
 * lieu de « é » (1 080 appels, vus par le mainteneur le 2026-10-05). `check-double-codage` lit le code ; ce
 * parcours lit ce que les pages SERVENT, administration comprise, et refuse tout `&amp;eacute;` hors
 * du code, des champs et des scripts. Un texte qui cite une entité exprès l'écrit en code.
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/journal.php';
require __DIR__.'/lib/parcours.php';

/*
 * `--ignorer` : le contrôle sert le site avec le serveur intégré de PHP, qui ne connaît QUE cette
 * installation. Une adresse servie par le serveur web depuis une AUTRE installation — la
 * démonstration sous `/demo`, par exemple — y répond donc 404 alors qu'elle fonctionne
 * parfaitement en ligne.
 */
[$o] = nf_options(['max' => 1000, 'depart' => [], 'ignorer' => [], 'port' => 0, 'journal' => nf_racine().'/logs/php.log']);

$departs = $o['depart'] ?: ['/fr', '/fr/admin'];

$db      = nf_connexion();
$serveur = nf_serveur(nf_port($o['port']), ['NF_OUTIL_SESSION' => nf_session_admin($db)]);
$base    = $serveur->base;

printf("Départ : %s — %d page(s) au plus\n\n", implode(', ', $departs), $o['max']);

// Le journal est mesuré AVANT le parcours : seul ce que ces pages y écrivent sera jugé.
// L'outil sert lui-même le site : un journal absent (installation neuve) est créé d'avance.
$journal_lu    = nf_journal_preparer($o['journal']);
$journal_avant = nf_journal_taille($o['journal']);

// ── Le parcours : `lib/parcours.php`, partagé avec check-mise-en-page ──────────
// (Les adresses qui agissent n'y sont jamais ouvertes : NF_ADRESSES_QUI_AGISSENT.)
$parcours = nf_parcourir_site($base, $departs, $o['max'], $o['ignorer']);
$casses   = $parcours['casses'];
$codes    = $parcours['codes'];
$ouverts  = $parcours['ouverts'];

// ── Le rapport ──────────────────────────────────────────────────────────────
printf("%d page(s) ouverte(s), %d adresse(s) connue(s).\n", $ouverts, $parcours['connues']);

if ($parcours['restantes'])
{
    printf("(%d adresse(s) non atteintes : limite de %d — relancer avec --max plus grand)\n", $parcours['restantes'], $o['max']);
}

// ── Le journal : ce que ces pages ont écrit pendant qu'elles répondaient ─────────
$journal = ['php' => [], 'produit' => [], 'autres' => []];

if ($journal_lu)
{
    $journal = nf_journal_classer(nf_journal_depuis_octet($o['journal'], $journal_avant));
    nf_journal_montrer($journal, nf_racine());
}
else
{
    // Pas de journal : les liens se jugent quand même, mais le rapport le dit — un silence ici
    // passerait pour un journal propre.
    nf_avertir("\njournal introuvable ({$o['journal']}) : les pages n'ont été jugées que sur leur code HTTP.");
}

$au_journal = count($journal['php']) + count($journal['produit']);

if ($codes)
{
    printf("\n%d page(s) montrent un texte codé deux fois (« &eacute; » à l'écran) — écrire nf_texte() :\n\n", count($codes));

    foreach ($codes as $x)
    {
        printf("  ✗ %s\n           %s\n", $x['chemin'], $x['extrait']);
    }
}

if ($casses)
{
    printf("\n%d lien(s) interne(s) mort(s) :\n\n", count($casses));

    foreach ($casses as $x)
    {
        // Un code 0 ne veut rien dire pour qui lit le rapport : on nomme l'empêchement.
        $etat = $x['code'] ? sprintf('HTTP %d', $x['code'])
                           : 'injoignable'.($x['raison'] !== '' ? ' ('.$x['raison'].')' : '');

        printf("  ✗ %-14s %s\n           depuis : %s\n", $etat, $x['chemin'], $x['depuis']);
    }

    nf_echec(sprintf('%d lien(s) mort(s) sur %d page(s)%s%s', count($casses), $ouverts,
        $au_journal ? sprintf(', et %d ligne(s) fautive(s) au journal', $au_journal) : '',
        $codes ? sprintf(', et %d page(s) au texte codé deux fois', count($codes)) : ''));
}

if ($au_journal)
{
    nf_echec(sprintf('aucun lien mort, mais %d ligne(s) fautive(s) au journal pendant le parcours de %d page(s) — %d erreur(s) PHP, %d ligne(s) du produit',
        $au_journal, $ouverts, count($journal['php']), count($journal['produit'])));
}

if ($codes)
{
    nf_echec(sprintf('aucun lien mort, mais %d page(s) au texte codé deux fois sur %d', count($codes), $ouverts));
}

nf_ok('aucun lien interne mort, aucun texte codé deux fois'.($journal_lu ? ", et rien d'écrit au journal pendant le parcours" : ' (journal non lu)'));
