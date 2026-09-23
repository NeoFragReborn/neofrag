<?php
declare(strict_types=1);

/**
 * check-widget-contract — chaque couple widget/type répond à l'éditeur en direct, et se rend SANS réglages sans rien écrire au journal.
 *
 * Famille : navigateur
 *
 * Pourquoi
 * --------
 * Sur les widgets du projet, ceux qui fournissent un `controllers/admin.php` et ceux qui n'en ont
 * pas coexistent. Cette asymétrie est LÉGITIME — un widget sans réglage n'a rien à configurer — mais
 * elle n'était simplement pas supportée : l'éditeur en direct supposait l'écran d'administration
 * présent, et les autres cassaient à l'ajout. Le défaut a été corrigé, rien ne garantissait qu'il
 * ne revienne pas.
 *
 * Plutôt que d'uniformiser de force les widgets, on vérifie le CONTRAT : quel que soit le widget,
 * demander son écran de configuration à l'éditeur répond 200. Cela attrape la classe entière de
 * défauts, y compris pour les widgets pas encore écrits.
 *
 * L'endpoint interrogé est `admin/ajax/live-editor/widget-admin`, le seul de l'éditeur à ne
 * dépendre d'AUCUNE disposition existante : il prend un nom de widget et un type, et appelle
 * `get_admin()` — précisément le chemin où l'asymétrie se manifestait.
 *
 * La seconde clause : se rendre sans réglages
 * -------------------------------------------
 * Un widget peut arriver SANS réglages — posé par l'`install()` d'un thème, restauré d'une
 * disposition ancienne, ou livré par un jeu de données partiel. `check-widget-reglages` vérifie que
 * chaque CHECKER complète ses réglages ; mais le 2026-09-22, le journal de la démonstration a montré
 * que ce n'était pas ce qui s'affichait : le checker ne sert qu'à l'enregistrement du formulaire, et
 * quatre widgets écrivaient des `Undefined array key` à chaque page. Rien ne pouvait le voir, puisque
 * rien ne RENDAIT un widget sans réglages.
 *
 * L'outil pose donc chaque couple sur la page de contact, sans réglages (`lib/banc.php`), rend la
 * page, et lit ce que ce rendu a écrit au journal (`lib/journal.php`). Il retire chaque widget avant
 * de poser le suivant. Il compte aussi les widgets réellement rendus : un banc qui n'aurait rien posé
 * ne doit pas passer pour un banc sans défaut.
 *
 * Usage
 * -----
 *   php tools/check-widget-contract.php
 *   php tools/check-widget-contract.php --port=8103
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/journal.php';
require __DIR__.'/lib/banc.php';

[$o, $reste] = nf_options(['port' => 0]);

// L'ancienne forme `php tools/check-widget-contract.php 8097` reste acceptée.
if ($reste && ctype_digit($reste[0]))
{
    $o['port'] = (int) $reste[0];
}

/**
 * La liste des widgets ET de leurs types d'affichage, lue sur le disque.
 *
 * Un widget peut proposer plusieurs présentations — `mini`, `full`… — déclarées dans la clé
 * `types` de son `__info()`, exactement ce que l'éditeur lit pour peupler son assistant. Il faut
 * les éprouver toutes : neuf widgets ont un écran de configuration qui n'existe QUE pour un type
 * secondaire, et n'auraient donc rien révélé si l'on s'était arrêté au type par défaut.
 */
$widgets = [];

foreach (nf_addons('widget') as $nom => $dossier)
{
    $types  = ['index'];   // toujours présent : c'est la présentation par défaut
    $source = (string) file_get_contents($dossier.'/'.$nom.'.php');

    if (preg_match("/'types'\s*=>\s*\[(.*?)\]/s", $source, $bloc)
        && preg_match_all("/'([a-z0-9_]+)'\s*=>/i", $bloc[1], $cles))
    {
        $types = array_values(array_unique(array_merge($types, $cles[1])));
    }

    $widgets[$nom] = $types;
}

if (!$widgets)
{
    nf_refus('aucun widget trouvé dans widgets/');
}

$db      = nf_connexion();
$serveur = nf_serveur(nf_port($o['port']), ['NF_OUTIL_SESSION' => nf_session_admin($db)]);

// ── Contrôle préalable : notre session est-elle bien reconnue ? ─────────────
// Si l'administration nous renvoie ailleurs, tous les widgets répondront « accès refusé » et le
// rapport accusera le projet d'un défaut qui n'est que le nôtre. On le dit ici, une fois.
$controle = nf_http($serveur->base.'/fr/admin', ['suivre' => 0, 'ajax' => TRUE]);

if ($controle['code'] !== 200)
{
    nf_refus(sprintf("la session d'administrateur n'est pas reconnue (HTTP %d sur /fr/admin) — sans elle, chaque widget répondrait « accès refusé » et le rapport serait faux. Journal : %s",
        $controle['code'], $serveur->journal));
}

// ── L'épreuve ───────────────────────────────────────────────────────────────
$base     = $serveur->base.'/fr/admin/ajax/live-editor/widget-admin';
$echecs   = [];
$avec     = 0;
$sans     = 0;
$epreuves = array_sum(array_map('count', $widgets));

printf("%d widgets, %d couple(s) widget/type à éprouver contre %s\n\n",
    count($widgets), $epreuves, 'admin/ajax/live-editor/widget-admin');

foreach ($widgets as $widget => $types)
{
    foreach ($types as $type)
    {
        $reponse = nf_http($base, ['post' => ['widget' => $widget, 'type' => $type], 'ajax' => TRUE, 'suivre' => 0, 'timeout' => 20]);

        if ($reponse['code'] !== 200)
        {
            $echecs[] = ['widget' => $widget, 'type' => $type, 'code' => $reponse['code'], 'corps' => substr(trim($reponse['corps']), 0, 160)];
            continue;
        }

        // Un corps vide est une réponse LÉGITIME : elle dit « rien à configurer ici », et c'est
        // ainsi que l'assistant décide de masquer l'étape « Configuration ».
        trim($reponse['corps']) !== '' ? $avec++ : $sans++;
    }
}

printf("  %d couple(s) proposent un écran de configuration\n", $avec);
printf("  %d couple(s) n'en proposent pas — réponse vide, légitime et supportée\n\n", $sans);

// ── La seconde clause : chaque couple, posé SANS réglages, se rend sans rien écrire au journal ──
$journal = nf_racine().'/logs/php.log';
$theme   = (string) (nf_reglage($db, 'nf_default_theme') ?: 'nebula');
$rendus  = 0;
$bruyants = [];

// Sur une installation neuve, le journal n'existe pas encore : l'outil sert lui-même le site, il le
// crée d'avance (cf. `nf_journal_preparer()`). Seul un journal qu'on ne peut pas écrire rend aveugle.
if (!nf_journal_preparer($journal))
{
    nf_refus("journal inutilisable : $journal — ni présent et inscriptible, ni créable : la seconde clause ne peut rien mesurer");
}

$contact = nf_http($serveur->base.'/fr/contact', ['timeout' => 20]);

if ($contact['code'] !== 200)
{
    nf_refus(sprintf('la page de contact, où le banc pose les widgets, répond HTTP %d', $contact['code']));
}

foreach ($widgets as $widget => $types)
{
    foreach ($types as $type)
    {
        $retirer = nf_banc_widget($db, $theme, $widget, $type, NULL);
        $avant   = nf_journal_taille($journal);
        $page    = nf_http($serveur->base.'/fr/contact', ['timeout' => 20]);
        $entrees = nf_journal_depuis_octet($journal, $avant);
        $retirer();

        if (preg_match('/class="widget widget-'.preg_quote($widget, '/').'[" ]/', $page['corps']))
        {
            $rendus++;
        }

        $classe = nf_journal_classer($entrees);

        if ($classe['php'] || $classe['produit'] || $page['code'] >= 500)
        {
            $bruyants[] = ['widget' => $widget, 'type' => $type, 'code' => $page['code'],
                           'lignes' => nf_journal_regrouper(array_merge($classe['php'], $classe['produit']), nf_racine())];
        }
    }
}

printf("  %d couple(s) posé(s) sans réglages sur /fr/contact (thème %s), %d réellement rendu(s)\n\n", $epreuves, $theme, $rendus);

if ($rendus === 0)
{
    nf_refus('le banc n\'a rendu AUCUN widget sur la page de contact : la seconde clause n\'a rien mesuré');
}

foreach ($bruyants as $b)
{
    printf("  ✗ %-18s type %-10s sans réglages%s\n", $b['widget'], $b['type'], $b['code'] >= 500 ? sprintf(' — HTTP %d', $b['code']) : '');

    foreach (array_slice($b['lignes'], 0, 4) as $g)
    {
        printf("      %3d×  %s\n", $g['nombre'], mb_substr($g['message'], 0, 150));
    }
}

if (!$echecs && !$bruyants)
{
    nf_ok(sprintf('les %d widgets répondent sur leurs %d type(s), et se rendent sans réglages sans rien écrire au journal', count($widgets), $epreuves));
}

foreach ($echecs as $e)
{
    printf("  ✗ %-18s type %-10s HTTP %d\n", $e['widget'], $e['type'], $e['code']);

    if ($e['corps'] !== '')
    {
        printf("    %s\n", $e['corps']);
    }
}

if ($bruyants && !$echecs)
{
    nf_echec(sprintf('%d couple(s) widget/type écrivent au journal quand ils sont rendus sans réglages — un thème qui les pose, ou une disposition ancienne, remplit le journal de production à chaque page', count($bruyants)));
}

nf_echec(sprintf("%d couple(s) widget/type ne répondent pas%s — un widget qui ne répond pas ici casse à l'ajout dans l'éditeur. Journal : %s",
    count($echecs), $bruyants ? sprintf(', et %d écrivent au journal sans réglages', count($bruyants)) : '', $serveur->journal));
