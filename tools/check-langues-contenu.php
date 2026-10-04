<?php
declare(strict_types=1);

/**
 * check-langues-contenu — un contenu rédigé dans une seule langue doit rester consultable dans les autres.
 *
 * Famille : navigateur
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Une actualité écrite en français seulement proposait quand même les cinq autres langues dans son
 * sélecteur, et les cinq rendaient 404 : `WHERE lang = 'en'` ne trouvait rien, ce qui était
 * techniquement juste. Le geste le plus naturel d'un visiteur étranger tombait sur une page
 * d'erreur. Mesuré sur la démonstration le 2026-09-21 : douze pages de détail, soixante adresses
 * mortes sur soixante-douze demandées.
 *
 * Le repli existe depuis (`Model::langue_du_contenu()`), mais il ne suffit pas d'enlever le 404 :
 * trois choses doivent être vraies EN MÊME TEMPS sur la page servie, et aucune ne se voit dans le
 * code. Ce contrôle les mesure sur des pages réelles.
 *
 *   1. la page RÉPOND — c'est le défaut de départ ;
 *   2. elle le DIT — un bandeau, sinon une page française passe pour la version anglaise du site ;
 *   3. elle ne MENT PAS aux moteurs — `hreflang` n'annonce que les langues qui existent vraiment,
 *      et `canonical` désigne l'original, sans quoi six adresses sont indexées pour un seul texte.
 *
 * Il part des listes publiques, qui existent dans toutes les langues, et suit les liens de détail
 * qu'elles proposent : ce que fait un visiteur, et non une liste d'adresses écrite à la main, qui
 * vieillirait à la première remise à zéro de la démonstration.
 *
 * Éprouvé à l'envers le 2026-09-21 : en rétablissant le filtre `WHERE lang = <demandée>` dans
 * `check_news`, le contrôle repasse au rouge sur les pages d'actualité.
 *
 * Et un contenu TRADUIT ?
 * -----------------------
 * Depuis le 2026-09-23, la démonstration porte une version anglaise de ses actualités, articles,
 * pages et galeries. Pour une langue où le contenu existe, les attentes s'inversent : la page est
 * servie DANS cette langue, sans bandeau, s'annonce en `hreflang` et se désigne en `canonical` — et
 * cela même depuis l'adresse de l'autre langue, celle que produit le sélecteur de langue. C'est ce
 * cas qui a trouvé le 404 des actualités traduites. Les langues où le contenu existe se lisent dans
 * les `hreflang` de la page d'origine ; les autres restent mesurées comme un contenu monolingue.
 *
 * Usage
 * -----
 *   php tools/check-langues-contenu.php
 *   php tools/check-langues-contenu.php --listes=news,articles
 *   php tools/check-langues-contenu.php --par-liste=3      pages de détail examinées par liste
 *   php tools/check-langues-contenu.php --port=8106
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/serveur.php';

[$o] = nf_options([
    'listes'    => 'news,articles,gallery,teams',
    'par-liste' => 2,
    'port'      => 0,
]);

$listes  = array_values(array_filter(array_map('trim', explode(',', (string) $o['listes']))));
$serveur = nf_serveur(nf_port((int) $o['port']));

/*
 * La langue d'origine du site sert de point de départ : c'est celle dans laquelle le contenu de
 * démonstration est écrit. On la lit sur la page d'accueil plutôt que de la supposer.
 */
$accueil = nf_http($serveur->base.'/', ['suivre' => 3]);
$origine = preg_match('#/([a-z]{2})(?:/|$)#', (string) $accueil['arrivee'], $m) ? $m[1] : 'fr';

printf("Base : %s — langue d'origine : %s\n\n", $serveur->base, $origine);

// ── 1. Les pages de détail, prises là où un visiteur les prend : dans les listes ──────────────
$cibles = [];

foreach ($listes as $liste)
{
    $r = nf_http($serveur->base.'/'.$origine.'/'.$liste, ['suivre' => 2]);

    if ($r['code'] !== 200)
    {
        printf("  · %-10s liste absente (%s) — ignorée\n", $liste, $r['code'] ?: 'pas de réponse');

        continue;
    }

    preg_match_all('#href="(/'.$origine.'/'.preg_quote($liste, '#').'/(?:[a-z]+/)?\d+/[a-z0-9-]+)"#i', (string) $r['corps'], $m);

    foreach (array_slice(array_values(array_unique($m[1])), 0, max(1, (int) $o['par-liste'])) as $chemin)
    {
        $cibles[] = $chemin;
    }
}

if (!$cibles)
{
    $serveur->arreter();
    nf_refus('aucune page de détail trouvée : ce site n\'a pas de contenu, il n\'y a rien à mesurer');
}

// ── 2. Les langues installées, autres que celle d'origine ────────────────────────────────────
preg_match_all('#<link rel="alternate"[^>]*hreflang="([a-z]{2})"#', (string) $accueil['corps'], $m);
$autres = array_values(array_diff(array_unique($m[1]), [$origine]));

if (!$autres)
{
    $serveur->arreter();
    nf_refus('une seule langue installée : le repli ne peut pas se produire');
}

$autres = array_slice($autres, 0, 2);

// ── 3. Les trois propriétés, sur chaque page et dans chaque autre langue ─────────────────────
$ecarts      = [];
$mesures     = 0;
$disponibles = [];

foreach ($cibles as $chemin)
{
    foreach ($autres as $langue)
    {
        $demande = preg_replace('#^/'.$origine.'/#', '/'.$langue.'/', $chemin);
        $mesures++;

        // Le contenu existe-t-il dans cette langue ? La page d'origine le dit, dans ses hreflang.
        if (!isset($disponibles[$chemin]))
        {
            preg_match_all('#<link rel="alternate"[^>]*hreflang="([a-z]{2})"#', (string) nf_http($serveur->base.$chemin, ['suivre' => 1])['corps'], $d);
            $disponibles[$chemin] = array_values(array_unique($d[1]));
        }

        if (in_array($langue, $disponibles[$chemin], TRUE))
        {
            // Contenu TRADUIT : servi dans la langue demandée, depuis l'adresse de l'autre langue.
            $r    = nf_http($serveur->base.$demande, ['suivre' => 2]);
            $html = (string) $r['corps'];

            if ($r['code'] !== 200)
            {
                $ecarts[] = [$demande, sprintf('répond %s — le contenu existe en « %s » : l\'adresse du sélecteur de langue doit y mener', $r['code'] ?: 'rien', $langue)];

                continue;
            }

            if (preg_match('#<div class="alert alert-info[^>]*>.*?fa-language#s', $html))
            {
                $ecarts[] = [$demande, sprintf('bandeau « autre langue » alors que le contenu existe en « %s »', $langue)];
            }

            if (!preg_match('#<link rel="canonical" href="[^"]*/'.$langue.'/#', $html))
            {
                $ecarts[] = [$demande, sprintf('canonical absent ou ne désignant pas la version « %s », qui existe', $langue)];
            }

            continue;
        }

        $r    = nf_http($serveur->base.$demande, ['suivre' => 1]);
        $html = (string) $r['corps'];

        if ($r['code'] !== 200)
        {
            $ecarts[] = [$demande, sprintf('répond %s — un contenu monolingue doit être servi, pas refusé', $r['code'] ?: 'rien')];

            continue;
        }

        if (!preg_match('#<div class="alert alert-info[^>]*>.*?fa-language#s', $html))
        {
            $ecarts[] = [$demande, 'aucun bandeau : la page ne dit pas qu\'elle sert une autre langue'];
        }

        preg_match_all('#<link rel="alternate"[^>]*hreflang="([a-z]{2})"#', $html, $h);

        if (in_array($langue, $h[1], TRUE))
        {
            $ecarts[] = [$demande, sprintf('annonce hreflang="%s" alors que le contenu n\'existe pas dans cette langue', $langue)];
        }

        if (!preg_match('#<link rel="canonical" href="[^"]*/'.$origine.'/#', $html))
        {
            $ecarts[] = [$demande, sprintf('canonical absent ou ne désignant pas la version « %s » : six adresses pour un seul texte', $origine)];
        }
    }
}

$serveur->arreter();

printf("%d page(s) de détail, %d mesure(s) dans %d autre(s) langue(s).\n", count($cibles), $mesures, count($autres));

if ($ecarts)
{
    echo "\n";

    foreach ($ecarts as [$ou, $quoi])
    {
        printf("  ✗ %-46s %s\n", $ou, $quoi);
    }

    nf_echec(sprintf('%d écart(s) sur %d mesure(s)', count($ecarts), $mesures));
}

nf_ok(sprintf('%d mesure(s) : un contenu monolingue répond dans les autres langues et le dit ; un contenu traduit est servi dans la langue demandée ; seules des adresses vivantes sont annoncées', $mesures));
