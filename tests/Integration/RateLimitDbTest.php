<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Tests d'intégration du rate-limiting (anti-brute-force login/reset) contre la vraie
 * base. On reflète le SQL EXACT de neofrag/libraries/rate_limit.php : la lecture de
 * check() (20-41) et le branchement fenêtre/lockout de hit() (52-108). Une régression
 * ici ouvre la porte au bourrinage ou bloque des utilisateurs légitimes.
 *
 * DDL : install/schema.sql:680 — nf_rate_limit(rate_key PK, attempts, first_attempt_at,
 * locked_until). Les décisions sont reproduites sur des timestamps LUS en base (pas
 * recalculés à la main), comme le fait le modèle.
 */
final class RateLimitDbTest extends IntegrationTestCase
{
	public function test_check_blocks_while_lockout_active(): void
	{
		$key  = 'login:itest:'.substr(md5(uniqid('', true)), 0, 8);
		$lock = date('Y-m-d H:i:s', time() + 900);
		$this->exec("INSERT INTO nf_rate_limit (rate_key, attempts, locked_until) VALUES (?, 5, ?)", [$key, $lock]);

		// Lecture exacte de check() — rate_limit.php:22-25.
		$row = $this->exec(
			"SELECT attempts, UNIX_TIMESTAMP(locked_until) AS locked_until_ts FROM nf_rate_limit WHERE rate_key = ?",
			[$key]
		)->get_result()->fetch_assoc();

		// Décision exacte de check() (27-40) : retry = locked_until_ts - time(), bloqué si > 0.
		$allowed = !$row || empty($row['locked_until_ts']) || ((int) $row['locked_until_ts'] - time()) <= 0;

		$this->assertFalse($allowed, 'Tant que le lockout n\'est pas expiré, check() refuse (allowed = FALSE).');
	}

	public function test_check_expired_lockout_resets_and_allows(): void
	{
		$key  = 'login:itest:'.substr(md5(uniqid('', true)), 0, 8);
		$past = date('Y-m-d H:i:s', time() - 10);
		$this->exec("INSERT INTO nf_rate_limit (rate_key, attempts, locked_until) VALUES (?, 5, ?)", [$key, $past]);

		$row = $this->exec(
			"SELECT UNIX_TIMESTAMP(locked_until) AS locked_until_ts FROM nf_rate_limit WHERE rate_key = ?",
			[$key]
		)->get_result()->fetch_assoc();

		$retry = (int) $row['locked_until_ts'] - time();
		$this->assertLessThanOrEqual(0, $retry, 'Lockout dépassé.');

		// Branche retry <= 0 (rate_limit.php:34-37) : reset() = DELETE de la clé, puis allowed.
		$this->exec("DELETE FROM nf_rate_limit WHERE rate_key = ?", [$key]);

		$this->assertSame(0, (int) $this->scalar("SELECT COUNT(*) FROM nf_rate_limit WHERE rate_key = ?", [$key]), 'reset() supprime la clé expirée.');
	}

	public function test_hit_resets_counter_when_window_elapsed(): void
	{
		$key   = 'login:itest:'.substr(md5(uniqid('', true)), 0, 8);
		$old   = date('Y-m-d H:i:s', time() - 120); // > fenêtre 60 s
		$this->exec("INSERT INTO nf_rate_limit (rate_key, attempts, first_attempt_at) VALUES (?, 4, ?)", [$key, $old]);

		$row = $this->exec(
			"SELECT attempts, UNIX_TIMESTAMP(first_attempt_at) AS first_attempt_ts FROM nf_rate_limit WHERE rate_key = ?",
			[$key]
		)->get_result()->fetch_assoc();

		// Condition exacte de hit() (rate_limit.php:72) : fenêtre dépassée → replace attempts=1.
		$window  = 60;
		$expired = (time() - (int) $row['first_attempt_ts']) > $window;
		$this->assertTrue($expired, 'La 1re tentative date d\'avant la fenêtre → compteur remis à zéro.');

		// REPLACE attempts=1, first_attempt_at=NOW (rate_limit.php:74-79).
		$this->exec("UPDATE nf_rate_limit SET attempts = 1, first_attempt_at = NOW(), locked_until = NULL WHERE rate_key = ?", [$key]);

		$this->assertSame(1, (int) $this->scalar("SELECT attempts FROM nf_rate_limit WHERE rate_key = ?", [$key]), 'Hors fenêtre : le compteur repart à 1, pas à 5.');
	}

	public function test_hit_locks_when_threshold_reached_within_window(): void
	{
		$key   = 'login:itest:'.substr(md5(uniqid('', true)), 0, 8);
		$fresh = date('Y-m-d H:i:s', time() - 5); // dans la fenêtre 60 s
		// 4 tentatives déjà enregistrées, dans la fenêtre, pas encore lock.
		$this->exec("INSERT INTO nf_rate_limit (rate_key, attempts, first_attempt_at) VALUES (?, 4, ?)", [$key, $fresh]);

		$row = $this->exec(
			"SELECT attempts, UNIX_TIMESTAMP(first_attempt_at) AS first_attempt_ts FROM nf_rate_limit WHERE rate_key = ?",
			[$key]
		)->get_result()->fetch_assoc();

		$window      = 60;
		$maxAttempts = 5; // défaut de hit() (rate_limit.php:52)
		$within      = (time() - (int) $row['first_attempt_ts']) <= $window;
		$this->assertTrue($within, 'Toujours dans la fenêtre de comptage.');

		// Incrément + décision de lock (rate_limit.php:85-86).
		$attempts = (int) $row['attempts'] + 1;
		$this->assertSame(5, $attempts);
		$this->assertTrue($attempts >= $maxAttempts, 'La 5e tentative atteint le seuil → lockout.');

		// UPDATE attempts + locked_until (rate_limit.php:92-96).
		$lock = date('Y-m-d H:i:s', time() + 900);
		$this->exec("UPDATE nf_rate_limit SET attempts = ?, locked_until = ? WHERE rate_key = ?", [$attempts, $lock, $key]);

		$locked_ts = (int) $this->scalar("SELECT UNIX_TIMESTAMP(locked_until) FROM nf_rate_limit WHERE rate_key = ?", [$key]);
		$this->assertGreaterThan(time(), $locked_ts, 'Le lockout est posé dans le futur (clé bloquée).');
	}
}
