<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Rate limiting library — anti-brute-force / anti-spam.
 * Stockage DB pour fonctionner sans Redis/APCu (compat hébergement mutualisé).
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;

class Rate_Limit extends Library
{
	/**
	 * Vérifie si la clé n'est pas déjà bloquée.
	 *
	 * @param string $key Identifiant unique (ex: "login:user:bob", "login:ip:1.2.3.4")
	 * @return array{allowed: bool, retry_after: int} retry_after en secondes (0 si allowed)
	 */
	public function check($key)
	{
		$row = NeoFrag()->db	->select('attempts', 'UNIX_TIMESTAMP(locked_until) AS locked_until_ts')
								->from('nf_rate_limit')
								->where('rate_key', $key)
								->row(FALSE);

		if (!$row || empty($row['locked_until_ts']))
		{
			return ['allowed' => TRUE, 'retry_after' => 0];
		}

		$retry = (int)$row['locked_until_ts'] - time();

		if ($retry <= 0)
		{
			$this->reset($key);
			return ['allowed' => TRUE, 'retry_after' => 0];
		}

		return ['allowed' => FALSE, 'retry_after' => $retry];
	}

	/**
	 * Enregistre une tentative. Bloque la clé si seuil atteint.
	 *
	 * @param string $key
	 * @param int $maxAttempts Nombre max d'essais avant lockout (défaut 5)
	 * @param int $windowSeconds Fenêtre de comptage en secondes (défaut 60)
	 * @param int $lockoutSeconds Durée de blocage si seuil atteint (défaut 900 = 15 min)
	 * @return array{attempts: int, locked: bool, retry_after: int}
	 */
	public function hit($key, $maxAttempts = 5, $windowSeconds = 60, $lockoutSeconds = 900)
	{
		$row = NeoFrag()->db	->select('attempts', 'UNIX_TIMESTAMP(first_attempt_at) AS first_attempt_ts', 'UNIX_TIMESTAMP(locked_until) AS locked_until_ts')
								->from('nf_rate_limit')
								->where('rate_key', $key)
								->row(FALSE);

		$now = time();

		// Si déjà locked et toujours dans la fenêtre de blocage
		if ($row && !empty($row['locked_until_ts']) && (int)$row['locked_until_ts'] > $now)
		{
			return [
				'attempts'    => (int)$row['attempts'],
				'locked'      => TRUE,
				'retry_after' => (int)$row['locked_until_ts'] - $now
			];
		}

		// Nouvelle fenêtre (pas de ligne, ou fenêtre dépassée) : on repart de 1 — mais le seuil vaut dès la
		// première tentative : un seuil de 1 doit bloquer tout de suite (il ne le faisait pas).
		if (!$row || ($now - (int)($row['first_attempt_ts'] ?? 0)) > (int) $windowSeconds)
		{
			NeoFrag()->db->replace('nf_rate_limit', [
				'rate_key'         => $key,
				'attempts'         => 1,
				'first_attempt_at' => date('Y-m-d H:i:s', $now),
				'locked_until'     => NULL,
			]);

			$attempts = 1;
		}
		else
		{
			$attempts = (int)$row['attempts'] + 1;
		}

		$locked = $attempts >= (int) $maxAttempts;

		if ($locked)
		{
			$lock_ts = $now + (int)$lockoutSeconds;

			NeoFrag()->db	->where('rate_key', $key)
							->update('nf_rate_limit', [
								'attempts'     => $attempts,
								'locked_until' => date('Y-m-d H:i:s', $lock_ts),
							]);

			return [
				'attempts'    => $attempts,
				'locked'      => TRUE,
				'retry_after' => (int) $lockoutSeconds
			];
		}

		if ($attempts > 1)
		{
			NeoFrag()->db	->where('rate_key', $key)
							->update('nf_rate_limit', ['attempts' => $attempts]);
		}

		return ['attempts' => $attempts, 'locked' => FALSE, 'retry_after' => 0];
	}

	/**
	 * Reset une clé (succès du login par ex.).
	 */
	public function reset($key)
	{
		NeoFrag()->db	->where('rate_key', $key)
						->delete('nf_rate_limit');
	}

	/**
	 * Cleanup périodique des entrées expirées (à appeler depuis un cron ou random check).
	 */
	public function gc()
	{
		NeoFrag()->db->execute('DELETE FROM nf_rate_limit WHERE locked_until IS NULL OR locked_until < DATE_SUB(NOW(), INTERVAL 1 DAY)');
	}

	/**
	 * L'adresse du client telle que les limites d'essais la comptent : une adresse IPv4 entière, une adresse IPv6
	 * ramenée à son réseau /64 (audit du 2026-10-09). Un abonné IPv6 dispose de tout un /64 — des milliards
	 * d'adresses — : compter chacune à part lui rendait tous ses essais à chaque adresse tirée. Une adresse IPv4
	 * écrite en IPv6 (::ffff:a.b.c.d) compte comme l'adresse IPv4.
	 */
	public static function bloc_ip(?string $ip = NULL): string
	{
		$ip ??= (string) self::client_ip();

		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === FALSE || ($binaire = inet_pton($ip)) === FALSE)
		{
			return $ip;
		}

		if (str_starts_with($binaire, str_repeat("\0", 10)."\xff\xff"))
		{
			return (string) inet_ntop(substr($binaire, 12));
		}

		return inet_ntop(substr($binaire, 0, 8).str_repeat("\0", 8)).'/64';
	}

	/**
	 * Helper pour récupérer l'IP du client (gère proxy/CDN).
	 */
	public static function client_ip()
	{
		// Sécurité : par défaut on ne fait confiance qu'à REMOTE_ADDR. Les en-têtes
		// proxy (X-Forwarded-For, CF-Connecting-IP, X-Real-IP) sont spoofables par le
		// client et permettraient de contourner le rate limiting et la banlist IP.
		// Ne les lire que derrière un proxy de confiance qui les réécrit :
		// define('NEOFRAG_TRUSTED_PROXY', TRUE) dans config/neofrag.php.
		if (defined('NEOFRAG_TRUSTED_PROXY') && NEOFRAG_TRUSTED_PROXY)
		{
			foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $h)
			{
				if (!empty($_SERVER[$h]))
				{
					$ip = trim(explode(',', (string) $_SERVER[$h])[0]);

					if (filter_var($ip, FILTER_VALIDATE_IP) !== FALSE)
					{
						return $ip;
					}
				}
			}
		}

		return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	}
}
