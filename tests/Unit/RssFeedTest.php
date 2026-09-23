<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Widgets\Rss\Lib\Cache;
use NF\Widgets\Rss\Lib\Feed;
use NF\Widgets\Rss\Lib\Settings;

/**
 * Le lecteur de flux distant.
 *
 * Ce qui mérite une épreuve : la lecture d'un XML écrit par quelqu'un d'autre. Un flux n'est jamais
 * exactement conforme — dates aux trois formats, résumés bourrés de balises, liens Atom dans un
 * attribut, auteurs en Dublin Core — et surtout il peut être hostile. Les cas ci-dessous sont donc
 * autant des cas de compatibilité que des cas de sécurité.
 *
 * Tests PURS : `Feed`, `Settings` et `Cache` ne connaissent ni le réseau, ni la base, ni le service
 * locator, à l'image des providers de `widgets/twitch/lib`.
 */
final class RssFeedTest extends TestCase
{
	private const RSS2 = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
  <channel>
    <title>Les nouvelles de la ligue</title>
    <link>https://ligue.example/</link>
    <item>
      <title>Finale reportée</title>
      <link>https://ligue.example/finale-reportee</link>
      <pubDate>Sat, 19 Sep 2026 14:30:00 +0200</pubDate>
      <description><![CDATA[<p>La finale est <b>reportée</b> au 3 octobre.</p>]]></description>
      <author>redaction@ligue.example (Camille Roy)</author>
    </item>
    <item>
      <title>Le classement de septembre</title>
      <link>https://ligue.example/classement</link>
      <dc:date>2026-09-12T09:00:00Z</dc:date>
      <description>Les dix premières équipes.</description>
      <dc:creator>Dominique Pei</dc:creator>
    </item>
  </channel>
</rss>
XML;

	private const ATOM = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Journal de bord</title>
  <entry>
    <title>Version 2.0</title>
    <link rel="enclosure" href="https://exemple.org/v2.zip"/>
    <link rel="alternate" href="https://exemple.org/v2"/>
    <published>2026-09-18T08:00:00Z</published>
    <summary>Ce qui change dans la version 2.0.</summary>
    <author><name>Alex Nguyen</name></author>
  </entry>
</feed>
XML;

	public function test_un_flux_rss_2(): void
	{
		$articles = Feed::parse(self::RSS2);

		self::assertCount(2, $articles);
		self::assertSame('Finale reportée', $articles[0]['title']);
		self::assertSame('https://ligue.example/finale-reportee', $articles[0]['link']);
		self::assertSame('La finale est reportée au 3 octobre.', $articles[0]['summary'], 'le balisage du résumé ne ressort pas');
		self::assertSame('Camille Roy', $articles[0]['author'], "le nom est gardé, l'adresse e-mail non");
		self::assertSame('2026-09-19 12:30', gmdate('Y-m-d H:i', (int) $articles[0]['date']));

		self::assertSame('Dominique Pei', $articles[1]['author'], 'auteur en Dublin Core');
		self::assertSame('2026-09-12 09:00', gmdate('Y-m-d H:i', (int) $articles[1]['date']), 'date en Dublin Core');
	}

	public function test_un_flux_atom(): void
	{
		$articles = Feed::parse(self::ATOM);

		self::assertCount(1, $articles);
		self::assertSame('Version 2.0', $articles[0]['title']);
		self::assertSame('https://exemple.org/v2', $articles[0]['link'], "le lien alternate, pas la pièce jointe");
		self::assertSame('Alex Nguyen', $articles[0]['author']);
		self::assertSame('Journal de bord', Feed::titre(self::ATOM));
	}

	public function test_le_titre_du_flux(): void
	{
		self::assertSame('Les nouvelles de la ligue', Feed::titre(self::RSS2));
		self::assertSame('', Feed::titre('pas du XML du tout'));
	}

	public function test_le_nombre_d_articles_est_respecte(): void
	{
		self::assertCount(1, Feed::parse(self::RSS2, 1));
		self::assertCount(2, Feed::parse(self::RSS2, 50), 'on ne rend pas plus que ce que le flux contient');
		self::assertSame([], Feed::parse(self::RSS2, 0));
	}

	/**
	 * Une entité externe transforme un lecteur naïf en lecteur de fichiers du serveur.
	 *
	 * C'est l'attaque classique contre tout ce qui analyse du XML venu d'ailleurs (« XXE »). Le flux
	 * entier est refusé : un document qui déclare un DOCTYPE n'a aucune raison légitime de le faire.
	 */
	public function test_une_entite_externe_est_refusee(): void
	{
		$hostile = '<?xml version="1.0"?>'."\n"
			.'<!DOCTYPE rss [<!ENTITY vol SYSTEM "file:///etc/passwd">]>'."\n"
			.'<rss version="2.0"><channel><item><title>&vol;</title></item></channel></rss>';

		self::assertSame([], Feed::parse($hostile));
		self::assertSame('', Feed::titre($hostile));
	}

	/** Un lien `javascript:` dans un flux atterrirait tel quel dans le `href` de notre page. */
	public function test_un_lien_hors_http_est_jete(): void
	{
		$xml = '<rss version="2.0"><channel><item>'
			.'<title>Piège</title><link>javascript:alert(1)</link>'
			.'</item></channel></rss>';

		$articles = Feed::parse($xml);

		self::assertCount(1, $articles, "l'article reste, seul son lien tombe");
		self::assertSame('', $articles[0]['link']);
	}

	public function test_un_xml_invalide_ne_fait_rien_planter(): void
	{
		self::assertSame([], Feed::parse(''));
		self::assertSame([], Feed::parse('   '));
		self::assertSame([], Feed::parse('<rss><channel>'));
		self::assertSame([], Feed::parse('{"ceci":"du json"}'));
		self::assertSame([], Feed::parse('<rss version="2.0"><channel></channel></rss>'), 'un flux vide rend une liste vide');
	}

	public function test_un_resume_trop_long_est_coupe_a_un_mot(): void
	{
		$long = str_repeat('mot ', 200);
		$xml  = '<rss version="2.0"><channel><item><title>T</title><description>'.$long.'</description></item></channel></rss>';

		$resume = Feed::parse($xml)[0]['summary'];

		self::assertLessThanOrEqual(Feed::RESUME_MAX + 1, mb_strlen($resume));
		self::assertStringEndsWith('…', $resume);
		self::assertStringNotContainsString('mo…', $resume, 'la coupe tombe entre deux mots');
	}

	// ── Les réglages ──────────────────────────────────────────────────────────

	public function test_l_adresse_du_flux(): void
	{
		self::assertSame('https://exemple.org/feed.xml', Settings::url('https://exemple.org/feed.xml'));
		self::assertSame('http://exemple.org/feed.xml', Settings::url('  http://exemple.org/feed.xml  '));
		self::assertSame('', Settings::url('file:///etc/passwd'), 'seuls http et https');
		self::assertSame('', Settings::url('php://filter/resource=index.php'));
		self::assertSame('', Settings::url('javascript:alert(1)'));
		self::assertSame('', Settings::url('exemple.org/feed.xml'), 'sans schéma, on ne devine pas');
		self::assertSame('', Settings::url(NULL));
		self::assertSame('', Settings::url(['https://exemple.org']));
	}

	public function test_les_reglages_sont_bornes(): void
	{
		$r = Settings::normaliser(['url' => 'https://exemple.org/f.xml', 'count' => 900, 'ttl' => 7]);

		self::assertSame(Settings::ARTICLES_MAX, $r['count'], 'un nombre délirant est ramené au maximum');
		self::assertSame(Settings::DUREE_DEFAUT, $r['ttl'], 'une durée hors liste retombe sur le défaut');

		self::assertSame(1, Settings::normaliser(['count' => -5])['count']);
		self::assertSame(5, Settings::normaliser([])['count']);
		self::assertSame(900, Settings::normaliser(['ttl' => '900'])['ttl'], 'la base rend des chaînes');
	}

	public function test_les_booleens_d_affichage(): void
	{
		self::assertTrue(Settings::normaliser([])['show_date'], 'par défaut on montre la date');
		self::assertFalse(Settings::normaliser(['show_date' => '0'])['show_date']);
		self::assertTrue(Settings::normaliser(['show_date' => '1'])['show_date']);
		self::assertFalse(Settings::normaliser(['show_summary' => 0])['show_summary']);
	}

	public function test_le_titre_saisi_est_ramene_a_une_ligne(): void
	{
		self::assertSame('Ma ligue', Settings::normaliser(['title' => "  Ma\n\tligue  "])['title']);
		self::assertSame(80, mb_strlen(Settings::normaliser(['title' => str_repeat('a', 200)])['title']));
	}

	/** La clé de cache est un nom de fichier : jamais un chemin, quoi qu'on lui donne. */
	public function test_la_cle_de_cache_ne_contient_aucun_chemin(): void
	{
		$cle = Settings::cle('https://exemple.org/../../etc/passwd?a=/b');

		self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $cle);
		self::assertNotSame(Settings::cle('https://exemple.org/autre'), $cle);
	}

	// ── Le cache ──────────────────────────────────────────────────────────────

	public function test_le_cache_rend_ce_qu_on_y_a_mis_et_dit_s_il_est_frais(): void
	{
		$dossier = sys_get_temp_dir().'/nf-rss-'.bin2hex(random_bytes(6));
		$cache   = new Cache($dossier);
		$url     = 'https://exemple.org/feed.xml';

		self::assertNull($cache->lire($url, 3600), 'un cache vide ne rend rien');

		$articles = [['title' => 'Un', 'link' => 'https://exemple.org/1', 'date' => 1, 'summary' => '', 'author' => '']];
		self::assertTrue($cache->ecrire($url, $articles, 'Mon flux'));

		$lu = $cache->lire($url, 3600);
		self::assertNotNull($lu);
		self::assertSame('Mon flux', $lu['title']);
		self::assertSame('Un', $lu['items'][0]['title']);
		self::assertTrue($lu['fresh']);

		// Le point qui compte : périmé, le contenu est CONSERVÉ et servi — personne n'attend le réseau.
		$perime = $cache->lire($url, 0);
		self::assertNotNull($perime);
		self::assertFalse($perime['fresh']);
		self::assertSame('Un', $perime['items'][0]['title']);

		self::rmdir_r($dossier);
	}

	public function test_le_cache_negatif_evite_de_retenter_un_flux_mort(): void
	{
		$dossier = sys_get_temp_dir().'/nf-rss-'.bin2hex(random_bytes(6));
		$cache   = new Cache($dossier);
		$url     = 'https://mort.example/feed.xml';

		self::assertFalse($cache->en_echec($url));

		$cache->marquer_echec($url);
		self::assertTrue($cache->en_echec($url));

		// Une écriture réussie efface la marque : le flux est revenu.
		$cache->ecrire($url, [['title' => 'Revenu', 'link' => '', 'date' => NULL, 'summary' => '', 'author' => '']], '');
		self::assertFalse($cache->en_echec($url));

		self::rmdir_r($dossier);
	}

	private static function rmdir_r(string $dossier): void
	{
		foreach (glob($dossier.'/*') ?: [] as $fichier)
		{
			@unlink($fichier);
		}

		@rmdir($dossier);
	}
}
