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
 * Connexion : variables d'env NF_TEST_DB_* (défauts = stack Docker dev). Pour une
 * base dédiée, exporter NF_TEST_DB_NAME=neofrag_test. Si la base est injoignable
 * (ex. exécution hors conteneur), toute la suite est marquée « skipped » — la
 * suite unit reste verte.
 *
 * À lancer depuis le conteneur :
 *   docker compose exec web composer test:integration
 */
abstract class IntegrationTestCase extends TestCase
{
	protected static ?mysqli $pdo = null;

	public static function setUpBeforeClass(): void
	{
		mysqli_report(MYSQLI_REPORT_OFF);

		$host = getenv('NF_TEST_DB_HOST') ?: 'db';
		$user = getenv('NF_TEST_DB_USER') ?: 'neofrag';
		$pass = getenv('NF_TEST_DB_PASS') ?: 'neofragpass';
		$name = getenv('NF_TEST_DB_NAME') ?: 'neofrag';
		$port = (int) (getenv('NF_TEST_DB_PORT') ?: 3306);

		$db = @new mysqli($host, $user, $pass, $name, $port);

		if ($db->connect_errno)
		{
			self::markTestSkipped('Base de test injoignable ('.$host.'): '.$db->connect_error);
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
		self::$pdo->begin_transaction();
	}

	protected function tearDown(): void
	{
		self::$pdo->rollback();
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
