<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Tests d'intégration de l'enforcement de la banlist IP (sécurité, fail-open) contre
 * la vraie base. On reflète le SQL EXACT de l'early-reject de neofrag/core/output.php
 * (87-140) : SELECT par IP + branche d'expiration (un ban expiré ne bloque PAS et est
 * purgé). Une régression ici laisse passer un banni ou bloque à tort.
 */
final class BanlistDbTest extends IntegrationTestCase
{
	/** Insère un ban et renvoie son ban_id. $expires = NULL → permanent. */
	private function insertBan(string $ip, ?string $expires): int
	{
		if ($expires === null)
		{
			$this->exec("INSERT INTO nf_ip_banlist (ip, reason, expires_at) VALUES (?, 'itest', NULL)", [$ip]);
		}
		else
		{
			$this->exec("INSERT INTO nf_ip_banlist (ip, reason, expires_at) VALUES (?, 'itest', ?)", [$ip, $expires]);
		}

		return (int) $this->db()->insert_id;
	}

	/**
	 * Reproduit la décision exacte d'output.php:104-129 :
	 *   $ban = SELECT ban_id, UNIX_TIMESTAMP(expires_at) AS expires_ts WHERE ip = ?
	 *   expiré = !empty(expires_ts) && (int)expires_ts < time()   → purge + laisse passer
	 *   sinon (permanent OU futur)                                → 403 (bloqué)
	 * Renvoie [blocked, purged].
	 */
	private function enforce(string $ip): array
	{
		$row = $this->exec(
			"SELECT ban_id, UNIX_TIMESTAMP(expires_at) AS expires_ts FROM nf_ip_banlist WHERE ip = ?",
			[$ip]
		)->get_result()->fetch_assoc();

		if (!$row)
		{
			return ['blocked' => false, 'purged' => false];
		}

		if (!empty($row['expires_ts']) && (int) $row['expires_ts'] < time())
		{
			// Branche expirée : DELETE FROM nf_ip_banlist WHERE ban_id = ? (output.php:114)
			$this->exec("DELETE FROM nf_ip_banlist WHERE ban_id = ?", [(int) $row['ban_id']]);

			return ['blocked' => false, 'purged' => true];
		}

		return ['blocked' => true, 'purged' => false];
	}

	private function banRowCount(string $ip): int
	{
		return (int) $this->scalar("SELECT COUNT(*) FROM nf_ip_banlist WHERE ip = ?", [$ip]);
	}

	public function test_permanent_ban_blocks_and_is_kept(): void
	{
		$ip = '203.0.113.11';
		$this->insertBan($ip, null);

		$r = $this->enforce($ip);

		$this->assertTrue($r['blocked'], 'Un ban permanent (expires_at NULL) bloque l\'IP.');
		$this->assertFalse($r['purged'], 'Un ban permanent n\'est jamais purgé.');
		$this->assertSame(1, $this->banRowCount($ip));
	}

	public function test_future_ban_blocks(): void
	{
		$ip     = '203.0.113.12';
		$future = date('Y-m-d H:i:s', time() + 86400);
		$this->insertBan($ip, $future);

		$r = $this->enforce($ip);

		$this->assertTrue($r['blocked'], 'Un ban à expiration future bloque encore.');
		$this->assertFalse($r['purged']);
	}

	public function test_expired_ban_lets_through_and_is_purged(): void
	{
		$ip   = '203.0.113.13';
		$past = date('Y-m-d H:i:s', time() - 86400);
		$this->insertBan($ip, $past);

		$r = $this->enforce($ip);

		$this->assertFalse($r['blocked'], 'Un ban expiré ne bloque plus (laisse passer).');
		$this->assertTrue($r['purged'], 'Le ban expiré est nettoyé (auto-purge).');
		$this->assertSame(0, $this->banRowCount($ip), 'La ligne expirée a été supprimée.');
	}

	public function test_unique_ip_rejects_duplicate_ban(): void
	{
		$ip = '203.0.113.14';
		$this->insertBan($ip, null);

		// UNIQUE KEY uk_ip (schema.sql:675) interdit deux bans pour la même IP.
		$dup = $this->db()->prepare("INSERT INTO nf_ip_banlist (ip, reason, expires_at) VALUES (?, 'dup', NULL)");
		$dup->bind_param('s', $ip);
		$ok = @$dup->execute();

		$this->assertFalse($ok, 'Un 2e ban sur la même IP doit être rejeté par la clé unique uk_ip.');
		$this->assertSame(1, $this->banRowCount($ip));
	}
}
