<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use NF\Modules\Newsletter\Models\Newsletter;

/**
 * Les liens qui partent dans un courriel, et l'adresse d'inscription à la newsletter.
 *
 * Un lien relatif (`/fr/newsletter/confirm/…`) se résout, dans un client de messagerie, contre le
 * domaine de ce client : le bouton « Confirmer mon inscription » menait nulle part, comme le lien de
 * désinscription des campagnes, et ceux des courriels du forum et des discussions (2026-10-04).
 * L'inscription des membres l'avait déjà compris : `absolute_url()`.
 */
final class LiensDesCourrielsTest extends TestCase
{
	private const RACINE = __DIR__.'/../..';

	/** Le code sans ses commentaires : un exemple d'usage dans un bloc de doc n'est pas un appel. */
	private static function sans_commentaires(string $source): string
	{
		$code = '';

		foreach (token_get_all($source) as $jeton)
		{
			if (is_array($jeton) && in_array($jeton[0], [T_COMMENT, T_DOC_COMMENT], TRUE))
			{
				continue;
			}

			$code .= is_array($jeton) ? $jeton[1] : $jeton;
		}

		return $code;
	}

	/** @return array<string, array{mixed, ?string}> */
	public static function saisies(): array
	{
		return [
			'adresse simple'           => ['membre@exemple.fr', 'membre@exemple.fr'],
			'majuscules et blancs'     => ['  Membre@Exemple.FR ', 'membre@exemple.fr'],
			'sans arobase'             => ['membre.exemple.fr', NULL],
			'vide'                     => ['', NULL],
			'blancs seuls'             => ['   ', NULL],
			'balise'                   => ['<script>@exemple.fr', NULL],
			'trop longue'              => [str_repeat('a', 250).'@exemple.fr', NULL],
			'pas une chaîne'           => [['membre@exemple.fr'], NULL],
			'rien'                     => [NULL, NULL],
		];
	}

	#[DataProvider('saisies')]
	public function test_l_adresse_saisie(mixed $saisie, ?string $attendu): void
	{
		self::assertSame($attendu, Newsletter::adresse($saisie));
	}

	/**
	 * Chaque valeur `*_url` passée à un modèle de courriel (`->template('…', [...])`) est absolue :
	 * construite par `absolute_url()`, directement ou dans la variable qui la porte.
	 */
	public function test_les_liens_des_modeles_de_courriel_sont_absolus(): void
	{
		$fautes = [];
		$vus    = 0;

		foreach (['modules', 'widgets', 'neofrag'] as $dossier)
		{
			$fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::RACINE.'/'.$dossier, \FilesystemIterator::SKIP_DOTS));

			foreach ($fichiers as $f)
			{
				if ($f->getExtension() !== 'php')
				{
					continue;
				}

				$source = self::sans_commentaires((string) file_get_contents($f->getPathname()));

				if (!preg_match_all("/->template\(\s*'[a-z_]+\.[a-z_]+',\s*\[(.*?)\]\s*\)/s", $source, $appels))
				{
					continue;
				}

				foreach ($appels[1] as $placeholders)
				{
					preg_match_all("/'([a-z_]+_url)'\s*=>\s*([^,\n]+)/", $placeholders, $valeurs, PREG_SET_ORDER);

					foreach ($valeurs as [, $cle, $expression])
					{
						$vus++;
						$expression = trim($expression);

						// Une variable : son affectation, dans le même fichier.
						if (preg_match('/^\$([a-z_]+)$/', $expression, $v) && preg_match('/\$'.$v[1].'\s*=\s*([^;]+);/', $source, $affectation))
						{
							$expression = $affectation[1];
						}

						if (!str_contains($expression, 'absolute_url('))
						{
							$fautes[] = str_replace('\\', '/', substr($f->getPathname(), strlen(self::RACINE) + 1)).' : '.$cle.' = '.$expression;
						}
					}
				}
			}
		}

		self::assertGreaterThanOrEqual(5, $vus, 'les courriels à lien du produit ont bien été trouvés');
		self::assertSame([], $fautes);
	}

	public function test_les_liens_des_campagnes_sont_absolus(): void
	{
		$modele = (string) file_get_contents(self::RACINE.'/modules/newsletter/models/newsletter.php');

		self::assertStringContainsString("absolute_url('newsletter/unsubscribe/'", $modele, 'le lien de désinscription');
		self::assertStringNotContainsString('newsletter/track/', $modele, 'plus de pixel de suivi (2026-10-08) : un traceur que l\'inscription ne demandait pas');
		self::assertDoesNotMatchRegularExpression("/(?<!absolute_)url\('newsletter\/(unsubscribe|track|confirm)\//", $modele);
	}
}
