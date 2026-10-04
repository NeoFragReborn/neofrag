<?php
declare(strict_types=1);

/**
 * check-seo — ce que lit un moteur de recherche : robots.txt, plans du site, et l'en-tête des pages qu'ils annoncent.
 *
 * Famille : navigateur
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Mesuré en production le 2026-10-03 : un plan du site aux adresses relatives — que Google
 * ignore —, une seule langue, ni le wiki ni le forum ; `robots.txt` qui l'annonçait en relatif ; un titre
 * « NeoFrag Reborn | NeoFrag Reborn » ; l'accueil canonique sur `/fr/index` ; chaque billet du Blog
 * canonique sur `/fr/articles/…`, une redirection ; des liens entre langues relatifs et sans `x-default`.
 * Aucun de ces défauts ne se voit à l'écran : la page s'affiche, seul un moteur est trompé. Ce contrôle
 * lit ce que lit un moteur, sur les pages servies.
 *
 * Ce qu'il vérifie
 * ----------------
 *   1. `robots.txt` annonce le plan du site par une adresse COMPLÈTE ;
 *   2. `/sitemap.xml` est un XML valide : l'index des langues sur un site multilingue, sinon le plan ;
 *      chaque plan ne porte que des adresses complètes, sur l'origine du site, chacune une fois, et
 *      aucune page qui n'a rien à faire dans un moteur (recherche, profils, administration) ;
 *   3. un échantillon des adresses de chaque plan (`--max`) RÉPOND 200 sans redirection, n'est pas en
 *      `noindex`, et se déclare canonique sur l'adresse même du plan ;
 *   4. l'accueil de chaque langue : un titre sans répétition, une description de 50 à 160 caractères
 *      qui ne répète pas le nom, un canonique sur la racine de la langue, un `hreflang` complet par
 *      langue plus `x-default`, Open Graph (titre, description, adresse = canonique, locale), et les
 *      données structurées `WebSite` et `Organization` ;
 *   5. la page de recherche est en `noindex`.
 *
 * L'origine attendue est celle de `config/url.php` — un site d'essai se présente souvent avec celle de la production :
 * ses adresses complètes commencent par `https://neofrag-reborn.xyz` alors qu'il est servi en local. Le
 * contrôle les ramène sur le serveur qu'il interroge pour les ouvrir.
 *
 * Usage
 * -----
 *   php tools/check-seo.php                                      sert l'installation, et la relit
 *   php tools/check-seo.php --base=https://neofrag-reborn.xyz    relit un site en ligne
 *   php tools/check-seo.php --max=200                            adresses ouvertes par plan (60)
 *   php tools/check-seo.php --origine=https://exemple.org        l'origine attendue, si elle diffère
 *   php tools/check-seo.php --contenu                            la description de l'accueil compte aussi
 *   php tools/check-seo.php --habillage=vitrine --contenu        dans un thème donné, le temps de l'épreuve
 *
 * La description de l'accueil est du CONTENU, saisi dans Paramètres → Référencement : un site neuf n'en a
 * pas encore de bonne, et ce n'est pas un défaut du produit. Elle n'est qu'un conseil, sauf `--contenu`
 * — ce que joue la publication pour le site officiel.
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
// Les fonctions du produit qui fabriquent ces balises : le contrôle compare comme elles.
require_once nf_racine().'/neofrag/helpers/seo.php';

[$o] = nf_options(['base' => '', 'origine' => '', 'max' => 60, 'port' => 0, 'contenu' => FALSE, 'habillage' => '']);

if ($o['base'] !== '')
{
    $base = rtrim($o['base'], '/');
}
else
{
    // Le thème public de l'épreuve, rétabli à la fin, interruption comprise (cf. capture.php).
    if ($o['habillage'] !== '')
    {
        $db = nf_connexion();
        nf_theme_temporaire($db);
        nf_reglage_poser($db, 'nf_default_theme', $o['habillage']);
        nf_reglage_poser($db, 'nf_theme_epoch', (string) time());
    }

    $serveur = nf_serveur(nf_port((int) $o['port']), ['NF_OUTIL_SESSION' => '', 'NF_OUTIL_CONSENT' => 'essentials']);
    $base    = rtrim($serveur->base, '/');
}

$origine = rtrim($o['origine'], '/');

if ($origine === '' && $o['base'] === '' && is_file($config = nf_racine().'/config/url.php'))
{
    $url = [];
    include $config;
    $origine = rtrim((string) ($url['site'] ?? ''), '/');
}

$origine = $origine !== '' ? $origine : $base;
$ecarts   = [];
$conseils = [];
$ouvertes = 0;

/** Une adresse complète du site, ramenée sur le serveur qu'on interroge. */
$locale = static fn (string $adresse): string => str_starts_with($adresse, $origine) ? $base.substr($adresse, strlen($origine)) : $adresse;

/**
 * L'en-tête d'une page, tel qu'un moteur le lit.
 *
 * @return array{titres: list<string>, description: ?string, robots: string, canonique: ?string, alternates: array<string, string>, og: array<string, list<string>>, jsonld: list<mixed>}
 */
$entete = static function (string $html): array {
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $x   = new DOMXPath($dom);
    $lire = static fn (string $requete): array => array_map(static fn ($n): string => trim((string) $n->nodeValue), iterator_to_array($x->query($requete) ?: []));

    $og = [];

    foreach ($x->query('//meta[starts-with(@property, "og:")]') ?: [] as $meta)
    {
        $og[$meta->getAttribute('property')][] = $meta->getAttribute('content');
    }

    $alternates = [];

    foreach ($x->query('//link[@rel="alternate"][@hreflang]') ?: [] as $lien)
    {
        $alternates[$lien->getAttribute('hreflang')] = $lien->getAttribute('href');
    }

    return [
        'titres'      => $lire('//head/title'),
        'description' => $lire('//meta[@name="description"]/@content')[0] ?? NULL,
        'robots'      => strtolower($lire('//meta[@name="robots"]/@content')[0] ?? ''),
        'canonique'   => $lire('//link[@rel="canonical"]/@href')[0] ?? NULL,
        'alternates'  => $alternates,
        'og'          => $og,
        'jsonld'      => array_map(static fn (string $j) => json_decode($j, TRUE), $lire('//script[@type="application/ld+json"]')),
    ];
};

/** Le titre se répète-t-il ? « X | X », « X — X ». */
$repete = static function (string $titre): bool {
    $parties = array_map(static fn (string $p): string => mb_strtolower(trim(html_entity_decode($p, ENT_QUOTES | ENT_HTML5, 'UTF-8'))), preg_split('/\s+[|—–-]\s+/u', $titre) ?: []);

    return count($parties) !== count(array_unique($parties));
};

/** Un plan ou un index, lu ; NULL s'il n'est pas du XML. */
$xml = static function (string $corps): ?SimpleXMLElement {
    $precedent = libxml_use_internal_errors(TRUE);
    $doc       = simplexml_load_string($corps);
    libxml_use_internal_errors($precedent);

    return $doc === FALSE ? NULL : $doc;
};

printf("Base : %s — origine attendue : %s\n\n", $base, $origine);

// ── 1. robots.txt ───────────────────────────────────────────────────────────────────────────
$robots = nf_http($base.'/robots.txt', ['suivre' => 0]);

if ($robots['code'] !== 200)
{
    $ecarts[] = sprintf('robots.txt répond %s', $robots['code'] ?: 'rien');
}
else if (!preg_match('/^\s*sitemap\s*:\s*(\S+)\s*$/mi', $robots['corps'], $m))
{
    $ecarts[] = 'robots.txt n\'annonce aucun plan du site';
}
else if (!preg_match('#^https?://#', $m[1]))
{
    $ecarts[] = sprintf('robots.txt annonce un plan du site relatif (%s) : la norme exige une adresse complète', $m[1]);
}

// ── 2. L'index des plans, puis chaque plan ──────────────────────────────────────────────────
$plans  = [];
$racine = nf_http($base.'/sitemap.xml', ['suivre' => 0]);
$doc    = $racine['code'] === 200 ? $xml($racine['corps']) : NULL;

if ($doc === NULL)
{
    nf_refus(sprintf('/sitemap.xml répond %s, ou n\'est pas du XML — rien à juger', $racine['code'] ?: 'rien'));
}

if ($doc->getName() === 'sitemapindex')
{
    foreach ($doc->sitemap as $plan)
    {
        $plans[] = trim((string) $plan->loc);
    }
}
else
{
    $plans[] = $origine.'/sitemap.xml';
}

$accueils = [];

foreach ($plans as $plan)
{
    if (!str_starts_with($plan, $origine.'/'))
    {
        $ecarts[] = sprintf('l\'index annonce un plan hors de l\'origine du site : %s', $plan);
        continue;
    }

    $reponse = $plan === $origine.'/sitemap.xml' ? $racine : nf_http($locale($plan), ['suivre' => 0]);
    $urlset  = $reponse['code'] === 200 ? $xml($reponse['corps']) : NULL;

    if ($urlset === NULL || $urlset->getName() !== 'urlset')
    {
        $ecarts[] = sprintf('%s répond %s, ou n\'est pas un plan (urlset)', $plan, $reponse['code'] ?: 'rien');
        continue;
    }

    $adresses = [];

    foreach ($urlset->url as $entree)
    {
        $adresse = trim((string) $entree->loc);

        if (!str_starts_with($adresse, $origine.'/'))
        {
            $ecarts[] = sprintf('%s : adresse relative ou étrangère au site — %s', basename(dirname($plan)) ?: 'plan', $adresse);
            continue;
        }

        if (isset($adresses[$adresse]))
        {
            $ecarts[] = sprintf('%s : %s figure deux fois', $plan, $adresse);
        }

        // Le premier segment après la langue : `wiki/admin` est un guide, `admin` l'administration.
        if (preg_match('#^/(?:[a-z]{2}/)?(search|user|members|admin)(/|$|\?)#', substr($adresse, strlen($origine))))
        {
            $ecarts[] = sprintf('%s annonce une page qui n\'a rien à faire dans un moteur : %s', $plan, $adresse);
        }

        $adresses[$adresse] = TRUE;
    }

    // L'accueil de la langue est la première adresse ; les autres, un échantillon réparti.
    $liste   = array_keys($adresses);
    $accueil = $liste[0] ?? NULL;

    if ($accueil !== NULL)
    {
        $accueils[] = $accueil;
    }

    $pas = max(1, (int) ceil(count($liste) / max(1, (int) $o['max'])));

    printf("  %-42s %4d adresse(s), %d ouverte(s)\n", $plan, count($liste), (int) ceil(count($liste) / $pas));

    foreach (array_values(array_filter($liste, static fn ($i) => $i % $pas === 0, ARRAY_FILTER_USE_KEY)) as $adresse)
    {
        $page = nf_http($locale($adresse), ['suivre' => 0]);
        $ouvertes++;

        if ($page['code'] !== 200)
        {
            $ecarts[] = sprintf('%s répond %s%s — un plan n\'annonce que des pages qui répondent', $adresse, $page['code'] ?: 'rien',
                !empty($page['entetes']['location']) ? ' vers '.$page['entetes']['location'] : '');
            continue;
        }

        $e = $entete($page['corps']);

        if (str_contains($e['robots'], 'noindex'))
        {
            $ecarts[] = sprintf('%s est en noindex, mais le plan l\'annonce', $adresse);
        }

        if ($e['canonique'] !== $adresse)
        {
            $ecarts[] = sprintf('%s se déclare canonique sur %s', $adresse, $e['canonique'] ?? 'rien');
        }

        if (count($e['titres']) !== 1 || $e['titres'][0] === '' || $repete($e['titres'][0]))
        {
            $ecarts[] = sprintf('%s : titre absent, multiple ou répété (« %s »)', $adresse, implode(' / ', $e['titres']));
        }
    }
}

// ── 3. L'accueil de chaque langue ───────────────────────────────────────────────────────────
$langues = count($accueils);

foreach ($accueils as $accueil)
{
    $page = nf_http($locale($accueil), ['suivre' => 0]);

    if ($page['code'] !== 200)
    {
        $ecarts[] = sprintf('l\'accueil %s répond %s', $accueil, $page['code'] ?: 'rien');
        continue;
    }

    $e    = $entete($page['corps']);
    $nom  = $e['og']['og:site_name'][0] ?? '';
    $desc = $e['description'];

    if ($desc === NULL || mb_strlen($desc) < 50 || mb_strlen($desc) > 160 || nf_seo_meme_texte($desc, $nom))
    {
        // Du CONTENU, saisi dans Paramètres → Référencement : un conseil, un écart avec --contenu.
        $conseils[] = sprintf('%s : description absente, hors de 50 à 160 caractères, ou égale au nom du site (« %s ») — Paramètres → Référencement', $accueil, (string) $desc);
    }

    if ($e['canonique'] !== $accueil || str_ends_with($accueil, '/index'))
    {
        $ecarts[] = sprintf('%s : canonique %s — l\'accueil est la racine de sa langue', $accueil, $e['canonique'] ?? 'absent');
    }

    if ($langues > 1)
    {
        if (count(array_diff_key($e['alternates'], ['x-default' => 1])) !== $langues || !isset($e['alternates']['x-default']))
        {
            $ecarts[] = sprintf('%s : %d lien(s) hreflang pour %d langue(s), x-default %s', $accueil, count($e['alternates']), $langues, isset($e['alternates']['x-default']) ? 'présent' : 'ABSENT');
        }

        foreach ($e['alternates'] as $langue => $href)
        {
            if (!str_starts_with($href, $origine.'/'))
            {
                $ecarts[] = sprintf('%s : hreflang « %s » relatif ou étranger — %s', $accueil, $langue, $href);
            }
        }

        if (!in_array($accueil, $e['alternates'], TRUE))
        {
            $ecarts[] = sprintf('%s ne s\'annonce pas lui-même en hreflang', $accueil);
        }
    }

    foreach (['og:title', 'og:description', 'og:url', 'og:locale'] as $propriete)
    {
        if (empty($e['og'][$propriete][0]))
        {
            $ecarts[] = sprintf('%s : %s absent', $accueil, $propriete);
        }
    }

    if (($e['og']['og:url'][0] ?? NULL) !== $e['canonique'])
    {
        $ecarts[] = sprintf('%s : og:url (%s) diffère du canonique', $accueil, $e['og']['og:url'][0] ?? 'absent');
    }

    if (!empty($e['og']['og:image'][0]) && !preg_match('#^https?://#', $e['og']['og:image'][0]))
    {
        $ecarts[] = sprintf('%s : og:image relative — %s', $accueil, $e['og']['og:image'][0]);
    }

    $types = [];

    foreach ($e['jsonld'] as $bloc)
    {
        if (!is_array($bloc))
        {
            $ecarts[] = sprintf('%s : données structurées illisibles (JSON invalide)', $accueil);
            continue;
        }

        foreach ($bloc['@graph'] ?? [$bloc] as $noeud)
        {
            $types[] = (string) ($noeud['@type'] ?? '');
        }
    }

    foreach (['WebSite', 'Organization'] as $type)
    {
        if (!in_array($type, $types, TRUE))
        {
            $ecarts[] = sprintf('%s : pas de données structurées %s', $accueil, $type);
        }
    }

    printf("  %-42s %s\n", $accueil, $e['titres'][0] ?? '(sans titre)');
}

// ── 4. La recherche ne s'indexe pas ─────────────────────────────────────────────────────────
if ($accueils)
{
    $recherche = nf_http($locale(rtrim($accueils[0], '/').'/search?q=neofrag'), ['suivre' => 0]);

    if ($recherche['code'] === 200 && !str_contains($entete($recherche['corps'])['robots'], 'noindex'))
    {
        $ecarts[] = 'la page de recherche n\'est pas en noindex : chaque recherche fabriquerait une page de plus';
    }
}

// ── Verdict ─────────────────────────────────────────────────────────────────────────────────
if ($conseils && $o['contenu'])
{
    $ecarts = array_merge($ecarts, $conseils);
}
else if ($conseils)
{
    printf("\n%d conseil(s) sur le contenu (des écarts avec --contenu) :\n\n", count($conseils));

    foreach ($conseils as $conseil)
    {
        echo '  · ', $conseil, "\n";
    }
}

if ($ecarts)
{
    printf("\n%d écart(s) :\n\n", count($ecarts));

    foreach ($ecarts as $ecart)
    {
        echo '  ✗ ', $ecart, "\n";
    }

    nf_echec(count($ecarts).' écart(s) dans ce que lisent les moteurs');
}

nf_ok(sprintf('%d plan(s), %d page(s) annoncée(s) ouvertes, %d accueil(s) : ce que lisent les moteurs est juste', count($plans), $ouvertes, count($accueils)));

