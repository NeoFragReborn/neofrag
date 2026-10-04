<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Des liens du produit qui menaient à un 404, faute de route ou de contrôleur (2026-10-04).
 *
 *   - les logos du widget « Partenaires » pointent sur `partners/{id}/{nom}` : la route, qui compte
 *     la visite puis renvoie vers le site du partenaire, avait été retirée (d0e8b1fa) ;
 *   - les boutons « Payer » et « Acheter » appelaient `payments/ajax/…` et `shop/ajax/…` : une adresse
 *     qui ne commence pas par `ajax/` fait choisir le contrôleur PUBLIC du module, qui n'a pas la
 *     méthode — seul le contrôleur `ajax` l'a ;
 *   - le widget « Newsletter » postait sur une page dont le formulaire ne lisait pas son champ.
 *
 * Ces épreuves lisent les sources : la résolution d'une adresse rejoue celle du cœur
 * (`Module::get_method()`), avec ses motifs réels (`NeoFrag::$route_patterns`).
 */
final class LiensEtRoutesTest extends TestCase
{
	private const RACINE = __DIR__.'/../..';

	/** @return array<string, string>  route => méthode, lues dans le `__info()` du module */
	private static function routes(string $module): array
	{
		$source = (string) file_get_contents(self::RACINE.'/modules/'.$module.'/'.$module.'.php');

		self::assertSame(1, preg_match("/'routes'\s*=>\s*\[(.*?)\n\t\t\t\]/s", $source, $bloc), "bloc routes de $module");
		preg_match_all("/'([^']*)'\s*=>\s*'(_?[a-z_0-9]+)'/", $bloc[1], $paires, PREG_SET_ORDER);

		$routes = [];

		foreach ($paires as [, $route, $methode])
		{
			$routes[$route] = $methode;
		}

		return $routes;
	}

	/**
	 * La méthode que le cœur choisit pour une adresse du module — même filtrage des routes
	 * d'administration et d'ajax, même expression que `Module::get_method()`.
	 */
	private static function methode(string $module, string $chemin, bool $ajax = FALSE): ?string
	{
		$routes  = self::routes($module);
		$motifs  = \NF\NeoFrag\NeoFrag::$route_patterns;
		$adresse = '';

		if ($ajax)
		{
			$routes  = array_filter($routes, static fn (string $r): bool => (bool) preg_match('#^ajax#', $r), ARRAY_FILTER_USE_KEY);
			$adresse = 'ajax/';
		}

		$adresse .= $chemin;

		foreach ($routes as $route => $methode)
		{
			$regex = str_replace(array_map(static fn (string $a): string => '{'.$a.'}', array_keys($motifs)), array_values($motifs), $route);

			if (preg_match('#^'.$regex.'$#', $adresse))
			{
				return $methode;
			}
		}

		return NULL;
	}

	/** Les méthodes publiques déclarées par un contrôleur du module, lues dans sa source. */
	private static function methodes_du_controleur(string $module, string $controleur): array
	{
		$fichier = self::RACINE.'/modules/'.$module.'/controllers/'.$controleur.'.php';

		if (!is_file($fichier))
		{
			return [];
		}

		preg_match_all('/public function ([a-z_0-9]+)\s*\(/', (string) file_get_contents($fichier), $m);

		return $m[1];
	}

	public function test_la_visite_d_un_partenaire_a_sa_route(): void
	{
		self::assertSame('_partner', self::methode('partners', '12/mon-partenaire'));
		self::assertContains('_partner', self::methodes_du_controleur('partners', 'checker'), 'le checker compte la visite et redirige');
	}

	/** @return array<string, array{string}> */
	public static function vues_des_partenaires(): array
	{
		return [
			'widget, bandeau' => ['widgets/partners/views/index.tpl.php'],
			'widget, colonne' => ['widgets/partners/views/column.tpl.php'],
			'page du module'  => ['modules/partners/views/index.tpl.php'],
		];
	}

	#[DataProvider('vues_des_partenaires')]
	public function test_les_liens_des_partenaires_passent_par_la_visite(string $vue): void
	{
		$source = (string) file_get_contents(self::RACINE.'/'.$vue);

		self::assertMatchesRegularExpression("#url\('partners/'\.\\\$partners?(\[\\\$i\])?\['partner_id'\]\.'/'\.\\\$partners?(\[\\\$i\])?\['name'\]\)#", $source);
	}

	/** @return array<string, array{string, string, string, string}> */
	public static function appels_ajax(): array
	{
		return [
			'payer un pack'    => ['payments', 'checkout/5', '_checkout', 'modules/payments/views/index.tpl.php'],
			'acheter un objet' => ['shop',     'buy/5',      '_buy',      'modules/shop/views/index.tpl.php'],
		];
	}

	#[DataProvider('appels_ajax')]
	public function test_les_achats_appellent_le_controleur_ajax(string $module, string $chemin, string $methode, string $vue): void
	{
		// `ajax/<module>/…` : le cœur retire `ajax` puis le module, résout `ajax/<chemin>`, et
		// choisit le contrôleur `ajax`, le seul qui porte la méthode.
		self::assertSame($methode, self::methode($module, $chemin, TRUE));
		self::assertContains($methode, self::methodes_du_controleur($module, 'ajax'));
		self::assertNotContains($methode, self::methodes_du_controleur($module, 'index'), 'le contrôleur public ne l\'a pas : d\'où le 404');
		self::assertStringContainsString("url('ajax/$module/", (string) file_get_contents(self::RACINE.'/'.$vue));
	}

	/**
	 * La famille : aucune adresse écrite `<module>/ajax/…`. Elle désigne le contrôleur public du
	 * module, jamais son contrôleur `ajax` — c'était le défaut de « Payer » et d'« Acheter ».
	 */
	public function test_aucun_lien_n_ecrit_module_slash_ajax(): void
	{
		$fautes = [];

		foreach (['modules', 'widgets', 'themes'] as $dossier)
		{
			$fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::RACINE.'/'.$dossier, \FilesystemIterator::SKIP_DOTS));

			foreach ($fichiers as $f)
			{
				if (!in_array($f->getExtension(), ['php', 'js'], TRUE))
				{
					continue;
				}

				if (preg_match_all("#url\('([a-z_]+)/ajax/#", (string) file_get_contents($f->getPathname()), $m))
				{
					foreach ($m[1] as $module)
					{
						if ($module !== 'admin')
						{
							$fautes[] = str_replace('\\', '/', substr($f->getPathname(), strlen(self::RACINE) + 1)).' : '.$module.'/ajax/…';
						}
					}
				}
			}
		}

		self::assertSame([], $fautes);
	}

	public function test_le_widget_newsletter_poste_ou_on_le_lit(): void
	{
		$widget = (string) file_get_contents(self::RACINE.'/widgets/newsletter/controllers/index.php');

		self::assertSame('_subscribe', self::methode('newsletter', 'subscribe'));
		self::assertContains('_subscribe', self::methodes_du_controleur('newsletter', 'index'));
		self::assertStringContainsString("url('newsletter/subscribe')", $widget);
		self::assertStringContainsString('name="email"', $widget, 'le champ que lit Index::_subscribe()');
		self::assertStringContainsString('name="_"', $widget, 'le jeton de session du module');
		self::assertStringNotContainsString('data[email]', $widget, 'le nom que le formulaire de la page ne lisait pas');
	}
}
