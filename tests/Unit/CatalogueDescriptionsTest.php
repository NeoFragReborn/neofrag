<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Ce que la place de marché dit de chaque addon : son titre et sa description, lus dans le
 * `__info()` (c'est ce que `tools/package-addons.php` publie dans `catalog.json`).
 *
 * Le 2026-10-04, trente descriptions tenaient en moins de 60 caractères ou se disaient « module
 * gaming », et le module des événements s'appelait « Événements gaming » — alors que le produit sert
 * aussi bien une guilde qu'un club ou une association. Elles ont été réécrites ; cette épreuve
 * empêche d'y revenir.
 */
final class CatalogueDescriptionsTest extends TestCase
{
	private const RACINE = __DIR__.'/../..';

	/** @return array<string, array{titre: string, description: string}>  « type/nom » des addons diffusés */
	private static function addons(): array
	{
		$addons = [];

		foreach (['modules', 'widgets', 'themes'] as $dossier)
		{
			foreach (glob(self::RACINE.'/'.$dossier.'/*/*.php') ?: [] as $fichier)
			{
				$nom = basename(dirname($fichier));

				if (basename($fichier) !== $nom.'.php')
				{
					continue;
				}

				$source = (string) file_get_contents($fichier);

				// Le cœur n'est pas au catalogue, ni ce qui ne se diffuse pas (le thème vitrine).
				if (!str_contains($source, 'function __info') || preg_match("/'core'\s*=>\s*TRUE/", $source) || preg_match("/'distributed'\s*=>\s*FALSE/", $source))
				{
					continue;
				}

				$info = substr($source, (int) strpos($source, 'function __info'));
				$lire = static function (string $champ) use ($info): string {
					return preg_match("/'".$champ."'\s*=>\s*(?:\\\$this->lang\(\s*)?'((?:\\\\.|[^'\\\\])*)'/s", $info, $m) ? stripcslashes($m[1]) : '';
				};

				$addons[$dossier.'/'.$nom] = ['titre' => $lire('title'), 'description' => $lire('description')];
			}
		}

		return $addons;
	}

	public function test_les_descriptions_disent_ce_que_fait_l_addon(): void
	{
		$addons = self::addons();
		$fautes = [];

		self::assertGreaterThan(50, count($addons), 'les addons diffusés ont bien été trouvés');

		foreach ($addons as $addon => $info)
		{
			if (mb_strlen($info['description']) < 60)
			{
				$fautes[] = $addon.' : trop courte ('.mb_strlen($info['description']).') — '.$info['description'];
			}

			if (preg_match('/\b(module|widget|thème) gaming\b/iu', $info['description']))
			{
				$fautes[] = $addon.' : étiquette « gaming » — '.$info['description'];
			}
		}

		self::assertSame([], $fautes);
	}

	public function test_aucun_titre_n_est_une_etiquette_de_positionnement(): void
	{
		$fautes = [];

		foreach (self::addons() as $addon => $info)
		{
			if (preg_match('/\bgaming\b/iu', $info['titre']))
			{
				$fautes[] = $addon.' : '.$info['titre'];
			}
		}

		self::assertSame([], $fautes);
	}
}
