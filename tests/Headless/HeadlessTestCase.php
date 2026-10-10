<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

use PHPUnit\Framework\TestCase;

/**
 * Socle des tests d'OBJETS (boot headless du framework, cf. boot.php). Permet d'instancier
 * de VRAIS modèles et d'appeler leurs méthodes data contre la DB de test — au-delà du miroir
 * SQL d'IntegrationTestCase. Chaque test tourne dans une transaction rollback sur la connexion
 * du framework (NeoFrag()->db), celle qu'utilisent les modèles → isolation totale.
 *
 * Skippé si le boot échoue (DB injoignable hors conteneur), comme IntegrationTestCase.
 *
 *   docker compose exec web composer test:headless
 */
abstract class HeadlessTestCase extends TestCase
{
	private static bool $booted = false;

	/*
	 * Pourquoi le saut n'est pas prononcé ici. Un markTestSkipped() dans setUpBeforeClass() saute la
	 * classe entière, et PHPUnit 11 le compte comme une SUITE sautée, pas comme des tests sautés :
	 * `--fail-on-skipped` n'y voit rien. La CI a affiché « OK, but some tests were skipped! Tests: 504,
	 * Skipped: 127 » avec un code de sortie zéro, pendant des semaines. On mémorise donc la raison, et
	 * c'est setUp() qui saute, test par test — ce que le drapeau transforme bien en échec.
	 */
	private static ?string $indisponible = null;

	/** La raison d'un démarrage manqué, gardée pour les classes suivantes : boot.php ne se rejoue pas. */
	private static ?string $echec_du_boot = null;

	public static function setUpBeforeClass(): void
	{
		self::$indisponible = null;

		if (self::$echec_du_boot !== null)
		{
			self::$indisponible = self::$echec_du_boot;

			return;
		}

		if (!self::$booted)
		{
			try
			{
				require dirname(__DIR__).'/Headless/boot.php';
			}
			catch (\Throwable $e)
			{
				self::$indisponible = self::$echec_du_boot = 'Boot headless impossible (DB injoignable ?) : '.$e->getMessage();

				return;
			}

			// boot.php définit NEOFRAG_HEADLESS AVANT de pouvoir échouer (require de config/…) : un
			// second require est donc un no-op SILENCIEUX, sans exception. Sans ce contrôle, seule la
			// PREMIÈRE classe headless était skippée et toutes les suivantes partaient en erreur
			// « Call to undefined function NeoFrag() » dans setUp(). Constaté : 31 erreurs quand
			// config/ est absent, ce qui est justement l'état du job `test` en CI.
			if (!function_exists('NeoFrag') || NeoFrag() === NULL)
			{
				self::$indisponible = self::$echec_du_boot = 'Boot headless incomplet (config/ ou DB indisponible).';

				return;
			}

			self::$booted = true;
		}
	}

	protected function setUp(): void
	{
		if (self::$indisponible !== null)
		{
			self::markTestSkipped(self::$indisponible);
		}

		\NeoFrag()->db->transaction();
	}

	protected function tearDown(): void
	{
		if (self::$indisponible === null)
		{
			\NeoFrag()->db->rollback();
		}
	}

	/** Le framework a démarré pour cette classe : un tearDown() qui le touche le demande d'abord (setUp() a pu sauter). */
	protected static function amorce(): bool
	{
		return self::$indisponible === null;
	}

	/** Connexion DB du framework (celle des modèles sous test). */
	protected function db()
	{
		return \NeoFrag()->db;
	}

	/** Crée un membre fixture via l'ORM du framework, renvoie son id. */
	protected function createUser(string $prefix = 'htest'): int
	{
		$username = $prefix.'_'.substr(md5(uniqid('', true)), 0, 10);

		return (int) $this->db()->insert('nf_user', [
			'username' => $username,
			'password' => '',
			'salt'     => '',
			'data'     => '',
		]);
	}
}
