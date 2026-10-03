<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * neofrag/helpers/seo.php — ce que les moteurs lisent dans l'en-tête d'une page.
 *
 * Chaque test fige un défaut mesuré en production le 2026-10-03 : titre « X | X », accueil canonique
 * sur `/fr/index`, liens entre langues relatifs, navigateur `de-DE` envoyé vers la langue par défaut.
 */
final class HelpersSeoTest extends TestCase
{
    public function test_l_accueil_est_la_racine_de_sa_langue(): void
    {
        $this->assertSame('', nf_seo_chemin(['index']));
        $this->assertSame('', nf_seo_chemin([]));
        $this->assertSame('forum/3/annonces', nf_seo_chemin(['forum', '3', 'annonces']));
        $this->assertSame('wiki/index', nf_seo_chemin(['wiki', 'index']), 'seul l\'accueil perd « index »');
        $this->assertSame('blog/21/titre', nf_seo_chemin(['articles', '21', 'titre'], ['articles' => 'blog']), 'l\'adresse publique, pas le nom du module');
        $this->assertSame('forum/articles', nf_seo_chemin(['forum', 'articles'], ['articles' => 'blog']), 'seul le premier segment désigne le module');
    }

    public function test_les_adresses_sont_completes(): void
    {
        $o = 'https://exemple.org';

        $this->assertSame('https://exemple.org/fr', nf_seo_adresse($o, '/', 'fr', ''));
        $this->assertSame('https://exemple.org/en/forum/3/annonces', nf_seo_adresse($o, '/', 'en', 'forum/3/annonces'));
        $this->assertSame('https://exemple.org/', nf_seo_adresse($o, '/', '', ''), 'x-default de l\'accueil : la racine');
        $this->assertSame('https://exemple.org/forum', nf_seo_adresse($o.'/', '/', '', 'forum'));
        $this->assertSame('https://exemple.org/site/de/blog', nf_seo_adresse($o, '/site/', 'de', 'blog'), 'installé dans un sous-dossier');
    }

    public function test_le_titre_ne_repete_jamais_le_nom(): void
    {
        $this->assertSame('NeoFrag Reborn', nf_seo_titre('', 'NeoFrag Reborn', 'NeoFrag Reborn'));
        $this->assertSame('NeoFrag Reborn', nf_seo_titre('NeoFrag Reborn', 'NeoFrag Reborn'));
        $this->assertSame('NeoFrag Reborn — le CMS libre des communautés', nf_seo_titre('', 'NeoFrag Reborn', 'le CMS libre des communautés'));
        $this->assertSame('Forum | NeoFrag Reborn', nf_seo_titre('Forum', 'NeoFrag Reborn', 'une accroche'));
        $this->assertSame('Forum', nf_seo_titre('Forum', ''));
        $this->assertSame('Neo', nf_seo_titre('neo ', 'Neo'), 'casse et espaces ne font pas un autre texte');
        $this->assertSame('Néo', nf_seo_titre('N&eacute;o', 'Néo'), 'une entité n\'est pas un autre texte');
    }

    public function test_la_description_tient_en_une_ligne_et_se_coupe_entre_deux_mots(): void
    {
        $this->assertSame('Un CMS libre, pour toi.', nf_seo_description("<p>Un CMS <b>libre</b>,</p>\n  pour&nbsp;toi."));

        $long  = str_repeat('communauté ', 30);
        $coupe = nf_seo_description($long, 160);

        $this->assertLessThanOrEqual(160, mb_strlen($coupe));
        $this->assertStringEndsWith('…', $coupe);
        $this->assertStringEndsWith('communauté…', $coupe, 'jamais au milieu d\'un mot');
    }

    public function test_la_locale_open_graph(): void
    {
        $this->assertSame('fr_FR', nf_seo_locale(['fr_FR.UTF8', 'fr.UTF8']));
        $this->assertSame('en_GB', nf_seo_locale('en_GB.UTF8'));
    }

    public function test_le_code_de_verification_accepte_la_balise_entiere(): void
    {
        $this->assertSame('abc123-XYZ_9', nf_seo_code_verification('abc123-XYZ_9'));
        $this->assertSame('Q7w-8kL0', nf_seo_code_verification('<meta name="google-site-verification" content="Q7w-8kL0" />'));
        $this->assertSame('', nf_seo_code_verification('"><script>alert(1)</script>'), 'rien qui sorte de l\'attribut');
        $this->assertSame('', nf_seo_code_verification(''));
    }

    public function test_une_variante_regionale_vaut_pour_sa_langue(): void
    {
        $this->assertSame(['de-de', 'de'], nf_langues_acceptees('de-DE'));
        $this->assertSame(['en-us', 'en', 'fr'], nf_langues_acceptees('en-US,fr;q=0.5'));
        $this->assertSame(['fr', 'en'], nf_langues_acceptees('fr;q=0.8, en;q=0.7, *;q=0'), '* ne désigne aucune langue');
        $this->assertSame(['pt-br', 'pt', 'es'], nf_langues_acceptees('pt-BR,pt;q=0.9,es;q=0.8'));
        $this->assertSame([], nf_langues_acceptees(''));
    }

    public function test_les_donnees_structurees_de_l_accueil(): void
    {
        $j = nf_seo_jsonld_site('https://exemple.org', 'Exemple', 'https://exemple.org/fr', 'fr', 'https://exemple.org/logo.png',
            ['https://github.com/exemple', 'javascript:alert(1)', ''], 'https://exemple.org/fr/search?q={search_term_string}');

        $this->assertSame('https://schema.org', $j['@context']);
        [$site, $organisation] = $j['@graph'];

        $this->assertSame('https://exemple.org/#site', $site['@id'], 'le même identifiant dans toutes les langues');
        $this->assertSame('SearchAction', $site['potentialAction']['@type']);
        $this->assertSame(['https://github.com/exemple'], $organisation['sameAs'], 'seules des adresses web');
        $this->assertSame('https://exemple.org/logo.png', $organisation['logo']);

        $sans = nf_seo_jsonld_site('https://exemple.org', 'Exemple', 'https://exemple.org/fr', 'fr');
        $this->assertArrayNotHasKey('potentialAction', $sans['@graph'][0]);
        $this->assertArrayNotHasKey('sameAs', $sans['@graph'][1]);
    }

    public function test_la_fusion_des_donnees_structurees(): void
    {
        $article = ['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => 'Titre'];
        $site    = nf_seo_jsonld_site('https://exemple.org', 'Exemple', 'https://exemple.org/fr', 'fr');

        $this->assertSame([], nf_seo_jsonld_fusion(NULL, []));
        $this->assertSame($article, nf_seo_jsonld_fusion($article), 'un seul nœud reste un nœud');

        $fusion = nf_seo_jsonld_fusion($site, $article);
        $this->assertCount(3, $fusion['@graph']);
        $this->assertArrayNotHasKey('@context', $fusion['@graph'][2], 'un seul @context, en tête');
    }

    public function test_la_date_d_un_plan_du_site(): void
    {
        $maintenant = strtotime('2026-10-03 12:00:00');

        $this->assertSame('2026-10-01', nf_seo_date('2026-10-01 08:30:00', $maintenant));
        $this->assertSame('2026-09-30', nf_seo_date(strtotime('2026-09-30 10:00:00'), $maintenant));
        $this->assertNull(nf_seo_date('0000-00-00 00:00:00', $maintenant), 'une date nulle ne s\'écrit pas');
        $this->assertNull(nf_seo_date('2027-01-01', $maintenant), 'ni une date future');
        $this->assertNull(nf_seo_date(NULL, $maintenant));
        $this->assertNull(nf_seo_date('demain', $maintenant));
    }

    public function test_le_plan_et_son_index_sont_du_xml_valide_aux_adresses_completes(): void
    {
        $plan = nf_seo_plan_xml([
            ['loc' => 'https://exemple.org/fr', 'lastmod' => NULL],
            ['loc' => 'https://exemple.org/fr/forum/1/a&b', 'lastmod' => '2026-10-01'],
        ]);

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>'.$plan);
        $this->assertNotFalse($xml, 'une esperluette ne casse pas le XML');
        $this->assertSame('urlset', $xml->getName());
        $this->assertSame('https://exemple.org/fr/forum/1/a&b', (string) $xml->url[1]->loc);
        $this->assertSame('2026-10-01', (string) $xml->url[1]->lastmod);
        $this->assertCount(0, $xml->url[0]->lastmod, 'pas de date inventée');

        $index = simplexml_load_string(nf_seo_index_xml(['https://exemple.org/fr/sitemap.xml', 'https://exemple.org/en/sitemap.xml']));
        $this->assertSame('sitemapindex', $index->getName());
        $this->assertCount(2, $index->sitemap);
    }
}
