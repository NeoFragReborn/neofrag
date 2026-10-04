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

    public function test_la_source_d_une_redirection_se_lit_comme_la_requete(): void
    {
        $langues = ['fr', 'en'];

        $this->assertSame('ancienne-page', nf_redirection_source('/fr/ancienne-page', $langues));
        $this->assertSame('ancienne-page', nf_redirection_source('https://exemple.org/en/ancienne-page/?x=1#y', $langues));
        $this->assertSame('ancienne-page', nf_redirection_source('ancienne-page', $langues));
        $this->assertSame('index.php', nf_redirection_source('/index.php?page=3', $langues), "l'adresse d'un ancien site, extension comprise");
        $this->assertSame('frites/maison', nf_redirection_source('/frites/maison', $langues), "seul un code de langue entier se retire");
        $this->assertSame('', nf_redirection_source('/fr/', $langues));
        $this->assertSame('café', nf_redirection_source('/caf%C3%A9', $langues));
    }

    public function test_la_cible_d_une_redirection_est_sure(): void
    {
        $langues = ['fr'];

        $this->assertSame('nouvelle-page', nf_redirection_cible('/fr/nouvelle-page', $langues));
        $this->assertSame('https://ailleurs.org/x', nf_redirection_cible('https://ailleurs.org/x', $langues));
        $this->assertSame('', nf_redirection_cible('javascript:alert(1)', $langues));
        $this->assertSame('', nf_redirection_cible('data:text/html,x', $langues));
        $this->assertSame('', nf_redirection_cible('//ailleurs.org/x', $langues), "une adresse sans schéma mène hors du site");
        $this->assertSame('', nf_redirection_cible('https://', $langues));
    }

    public function test_une_saisie_se_mesure_en_clair(): void
    {
        // Ce que rend le formulaire du produit pour la description portugaise de la vitrine : 139 caractères.
        $saisie = utf8_htmlentities(' Cria o site da tua guilda, do teu clube ou da tua associação sem uma linha de código: fórum, eventos, membros, donativos. Livre e gratuito. ');

        $this->assertGreaterThan(160, mb_strlen($saisie), 'mesurée encodée, elle dépassait la limite');
        $this->assertSame(139, mb_strlen(nf_seo_saisie($saisie)));
        $this->assertSame('Q7w-8kL0', nf_seo_code_verification(nf_seo_saisie(utf8_htmlentities('<meta name="google-site-verification" content="Q7w-8kL0" />'))), 'la balise entière, collée dans le formulaire');
        $this->assertSame("l'ancienne-page", nf_seo_saisie('l&#039;ancienne-page'));
        $this->assertSame('', nf_seo_saisie(['un', 'tableau']));
    }

    public function test_la_verification_google_par_le_dns(): void
    {
        $txt = ['v=spf1 ip4:203.0.113.10 ~all', '"google-site-verification=exemple-de-code-0000"'];

        $this->assertTrue(nf_seo_txt_annonce($txt, 'google-site-verification='), 'guillemets compris, tels que certains résolveurs les rendent');
        $this->assertFalse(nf_seo_txt_annonce(['v=spf1 ~all'], 'google-site-verification='));
        $this->assertFalse(nf_seo_txt_annonce(['x google-site-verification=abc'], 'google-site-verification='), 'au début de l\'enregistrement seulement');
        $this->assertFalse(nf_seo_txt_annonce([], 'google-site-verification='));
    }

    public function test_seul_un_disallow_pour_tous_ferme_le_site(): void
    {
        $this->assertTrue(nf_seo_robots_ferme("User-agent: *\nDisallow: /"));
        $this->assertTrue(nf_seo_robots_ferme("user-agent : *   # tous\r\ndisallow : /  \n"));
        $this->assertTrue(nf_seo_robots_ferme("User-agent: Googlebot\nUser-agent: *\nDisallow: /"), 'un groupe de plusieurs agents');
        $this->assertFalse(nf_seo_robots_ferme("User-agent: GPTBot\nDisallow: /\n\nUser-agent: *\nDisallow: /admin"), 'un robot nommé seulement');
        $this->assertFalse(nf_seo_robots_ferme("User-agent: *\nDisallow: /\nAllow: /blog"), 'rouvert en partie');
        $this->assertFalse(nf_seo_robots_ferme("User-agent: *\nDisallow:"), 'un Disallow vide autorise tout');
        $this->assertFalse(nf_seo_robots_ferme("User-agent: *\nDisallow: /admin/"));
        $this->assertFalse(nf_seo_robots_ferme(''));
    }

    public function test_indexnow_ne_signale_que_ce_qui_a_change(): void
    {
        $avant = ['https://e.org/fr' => '2026-10-01', 'https://e.org/fr/news' => '2026-10-01', 'https://e.org/fr/faq' => NULL, 'https://e.org/fr/vieille' => NULL];
        $plan  = ['https://e.org/fr' => '2026-10-01', 'https://e.org/fr/news' => '2026-10-03', 'https://e.org/fr/faq' => NULL, 'https://e.org/fr/news/9/neuve' => '2026-10-03'];

        $this->assertSame([
            'nouvelles' => ['https://e.org/fr/news/9/neuve'],
            'changees'  => ['https://e.org/fr/news'],
            'disparues' => ['https://e.org/fr/vieille'],
        ], nf_indexnow_ecarts($avant, $plan));

        $this->assertSame(['nouvelles' => [], 'changees' => [], 'disparues' => []], nf_indexnow_ecarts($plan, $plan), 'rien ne change, rien ne part');
        $this->assertSame(['https://e.org/fr/faq'], nf_indexnow_ecarts(['https://e.org/fr/faq' => NULL], ['https://e.org/fr/faq' => '2026-10-03'])['changees'], 'une date qui apparaît');
    }

    public function test_indexnow_n_envoie_que_les_adresses_du_site(): void
    {
        $cle   = str_repeat('ab12', 8);
        $corps = nf_indexnow_corps('https://exemple.org/', '/', $cle, ['https://exemple.org/fr/a', 'https://ailleurs.org/fr/b', 'https://exemple.org/fr/a', 'http://exemple.org/fr/c']);

        $this->assertSame('exemple.org', $corps['host']);
        $this->assertSame('https://exemple.org/'.$cle.'.txt', $corps['keyLocation'], 'la clé à la racine');
        $this->assertSame(['https://exemple.org/fr/a'], $corps['urlList'], 'ni étrangère, ni en double, ni d\'un autre schéma');

        $sous = nf_indexnow_corps('https://exemple.org', '/site/', $cle, ['https://exemple.org/site/fr/a', 'https://exemple.org/autre']);
        $this->assertSame('https://exemple.org/site/'.$cle.'.txt', $sous['keyLocation'], 'installé dans un sous-dossier');
        $this->assertSame(['https://exemple.org/site/fr/a'], $sous['urlList'], 'la clé ne vaut que pour son dossier');

        $this->assertCount(10000, nf_indexnow_corps('https://e.org', '/', $cle, array_map(fn (int $n): string => 'https://e.org/p'.$n, range(1, 10050)))['urlList']);
    }

    public function test_indexnow_la_cle_et_les_reponses(): void
    {
        $this->assertTrue(nf_indexnow_cle_valide(bin2hex(random_bytes(16))));
        $this->assertFalse(nf_indexnow_cle_valide('ABCDEF0123456789ABCDEF0123456789'), 'le site les crée en minuscules');
        $this->assertFalse(nf_indexnow_cle_valide('robots'));
        $this->assertFalse(nf_indexnow_cle_valide(''));

        $this->assertSame('recue', nf_indexnow_issue(200));
        $this->assertSame('recue', nf_indexnow_issue(202), 'la clé reste à vérifier');
        $this->assertSame('refusee', nf_indexnow_issue(403));
        $this->assertSame('refusee', nf_indexnow_issue(422));
        $this->assertSame('plus_tard', nf_indexnow_issue(429));
        $this->assertSame('plus_tard', nf_indexnow_issue(503));
        $this->assertSame('plus_tard', nf_indexnow_issue(0), 'pas de réponse');
    }
}
