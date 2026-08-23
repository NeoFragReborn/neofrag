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

	public static function setUpBeforeClass(): void
	{
		if (!self::$booted)
		{
			try
			{
				require dirname(__DIR__).'/Headless/boot.php';
			}
			catch (\Throwable $e)
			{
				self::markTestSkipped('Boot headless impossible (DB injoignable ?) : '.$e->getMessage());
			}

			// boot.php définit NEOFRAG_HEADLESS AVANT de pouvoir échouer (require de config/…) : un
			// second require est donc un no-op SILENCIEUX, sans exception. Sans ce contrôle, seule la
			// PREMIÈRE classe headless était skippée et toutes les suivantes partaient en erreur
			// « Call to undefined function NeoFrag() » dans setUp(). Constaté : 31 erreurs quand
			// config/ est absent, ce qui est justement l'état du job `test` en CI.
			if (!function_exists('NeoFrag') || NeoFrag() === NULL)
			{
				self::markTestSkipped('Boot headless incomplet (config/ ou DB indisponible).');
			}

			self::$booted = true;
		}
	}

	protected function setUp(): void
	{
		\NeoFrag()->db->transaction();
	}

	protected function tearDown(): void
	{
		\NeoFrag()->db->rollback();
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
