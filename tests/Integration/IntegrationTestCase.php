<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

use PHPUnit\Framework\TestCase;
use mysqli;

/**
 * Socle des tests d'INTÉGRATION (base de données réelle).
 *
 * Chaque test tourne dans une TRANSACTION rollback en tearDown → isolation totale,
 * la base n'est jamais modifiée durablement. Aucune DDL dans les tests (la DDL
 * auto-commit en MySQL casserait le rollback).
 *
 * Connexion : variables d'environnement NF_TEST_DB_*. Si la base est injoignable, toute la suite
 * est marquée « skipped » et la suite unitaire reste verte.
 *
 * ATTENTION à ce filet : il est silencieux. Le défaut de l'hôte était `db`, le nom du service
 * dans l'ancienne pile Docker. Cette pile n'existant plus, la résolution du nom échouait à chaque
 * exécution et les 56 tests d'intégration étaient sautés — sans erreur, sans rouge, sans que
 * personne ne le remarque. Le défaut est désormais `127.0.0.1`, c'est-à-dire l'installation
 * native, la seule que le projet utilise encore.
 *
 * Préparer la base une fois (l'outil crée la base, l'utilisateur et le schéma) :
 *   php tools/prepare-test-db.php
 *
 * Puis lancer la suite complète :
 *   composer test
 */
abstract class IntegrationTestCase extends TestCase
{
	protected static ?mysqli $pdo = null;

	/*
	 * Pourquoi le saut n'est pas prononcé ici. Un markTestSkipped() dans setUpBeforeClass() saute la
	 * classe entière, et PHPUnit 11 le compte comme une SUITE sautée, pas comme des tests sautés :
	 * `--fail-on-skipped` n'y voit rien. La CI a affiché « OK, but some tests were skipped! Tests: 504,
	 * Skipped: 127 » avec un code de sortie zéro, pendant des semaines. On mémorise donc la raison, et
	 * c'est setUp() qui saute, test par test — ce que le drapeau transforme bien en échec.
	 */
	protected static ?string $indisponible = null;

	public static function setUpBeforeClass(): void
	{
		mysqli_report(MYSQLI_REPORT_OFF);
		self::$indisponible = null;

		/*
		 * Trois sources, dans cet ordre : l'environnement, puis `config/db-test.php` qu'écrit
		 * `tools/prepare-test-db.php`, puis les valeurs par défaut.
		 *
		 * Le fichier existe parce que les variables d'environnement ne survivent pas d'une séance
		 * à l'autre : le 2026-09-20, 18 suites se sautaient sur le site d'essai — près de 200 assertions
		 * qui ne vérifiaient plus rien — parce que personne ne les avait reposées. Et le nom par
		 * défaut, `neofrag_test`, est justement celui de la base du SITE sur ce site d'essai : aucun
		 * réglage par défaut ne pouvait marcher. `config/` n'est pas versionné.
		 */
		$local = [];

		if (is_file($fichier = dirname(__DIR__, 2).'/config/db-test.php'))
		{
			$local = (array) (require $fichier);
		}

		$reglage = static function (string $cle, string $defaut) use ($local): string {
			return (string) (getenv('NF_TEST_DB_'.strtoupper($cle)) ?: ($local[$cle] ?? $defaut));
		};

		$host = $reglage('host', '127.0.0.1');
		$user = $reglage('user', 'neofrag_test');
		$pass = $reglage('pass', 'neofrag_test');
		$name = $reglage('name', 'neofrag_test');
		$port = (int) $reglage('port', '3306');

		$db = @new mysqli($host, $user, $pass, $name, $port);

		if ($db->connect_errno)
		{
			self::$indisponible = 'Base de test injoignable ('.$user.'@'.$host.':'.$port.'/'.$name.') : '.$db->connect_error
				."\n".'      Prépare-la une fois avec : php tools/prepare-test-db.php';

			return;
		}

		$db->set_charset('utf8mb4');
		self::$pdo = $db;
	}

	public static function tearDownAfterClass(): void
	{
		if (self::$pdo)
		{
			self::$pdo->close();
			self::$pdo = null;
		}
	}

	protected function setUp(): void
	{
		if (self::$indisponible !== null)
		{
			self::markTestSkipped(self::$indisponible);
		}

		self::$pdo->begin_transaction();
	}

	protected function tearDown(): void
	{
		// tearDown() est appelé même quand setUp() a sauté le test : il n'y a alors rien à défaire.
		if (self::$pdo)
		{
			self::$pdo->rollback();
		}
	}

	protected function db(): mysqli
	{
		return self::$pdo;
	}

	/** Exécute une requête (échoue le test si erreur SQL). */
	protected function exec(string $sql, array $params = []): \mysqli_stmt
	{
		$stmt = self::$pdo->prepare($sql);

		if (!$stmt)
		{
			$this->fail('SQL prepare error: '.self::$pdo->error.' — '.$sql);
		}

		if ($params)
		{
			$types = '';
			foreach ($params as $p)
			{
				$types .= is_int($p) ? 'i' : 's';
			}
			$stmt->bind_param($types, ...$params);
		}

		if (!$stmt->execute())
		{
			$this->fail('SQL execute error: '.$stmt->error.' — '.$sql);
		}

		return $stmt;
	}

	/** Première colonne de la première ligne (scalaire). */
	protected function scalar(string $sql, array $params = [])
	{
		$res = $this->exec($sql, $params)->get_result();
		$row = $res ? $res->fetch_row() : null;

		return $row ? $row[0] : null;
	}

	/** Crée un membre fixture, renvoie son id. */
	protected function createUser(string $prefix = 'itest'): int
	{
		$username = $prefix.'_'.substr(md5(uniqid('', true)), 0, 10);
		$this->exec("INSERT INTO nf_user (username, password, salt, data) VALUES (?, '', '', '')", [$username]);

		return (int) self::$pdo->insert_id;
	}
}
