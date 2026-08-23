<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * « Sudo » webmaster : un mot de passe DISTINCT du login qui garde les actions sensibles
 * (édition/suppression de fichiers source via Monitoring, opérations destructrices…). Même si
 * une session admin est compromise, l'attaquant n'a pas ce mot de passe → pas d'élévation.
 *
 * Le hash argon2id vit dans config/webmaster.php (jamais en base, et le gestionnaire de fichiers
 * s'interdit lui-même `config/` → le garde-fou ne peut être ni lu ni désactivé via l'outil).
 *
 * La lib renvoie des résultats STRUCTURÉS (pas de texte) : les messages utilisateur sont produits
 * par l'appelant, qui a le bon contexte de langue.
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;

class Webmaster extends Library
{
	const SUDO_TTL    = 900;         // fenêtre sudo : 15 min
	const MIN_LENGTH  = 8;
	const SESSION_KEY = 'webmaster';

	private function _config_file(): string
	{
		return NEOFRAG_CMS.'/config/webmaster.php';
	}

	/** Lit le hash depuis config/webmaster.php (include en portée locale), NULL si non configuré. */
	private function _hash(): ?string
	{
		if (is_file($file = $this->_config_file()))
		{
			$webmaster = [];
			include $file;

			$hash = $webmaster['hash'] ?? NULL;

			return is_string($hash) && $hash !== '' ? $hash : NULL;
		}

		return NULL;
	}

	public function is_configured(): bool
	{
		return $this->_hash() !== NULL;
	}

	/** Définit (ou remplace) le mot de passe webmaster. Renvoie FALSE si trop court ou écriture KO. */
	public function set(string $plain): bool
	{
		$plain = trim($plain);

		if (strlen($plain) < self::MIN_LENGTH)
		{
			return FALSE;
		}

		$php = "<?php\n\n"
			."// Hash argon2id du mot de passe webmaster (sudo). Généré par NeoFrag — ne pas éditer à la main.\n"
			."\$webmaster['hash'] = ".var_export(password_hash($plain, PASSWORD_ARGON2ID), TRUE).";\n";

		if (@file_put_contents($this->_config_file(), $php, LOCK_EX) === FALSE)
		{
			return FALSE;
		}

		@chmod($this->_config_file(), 0600);

		(new Audit_Log($this))->log('webmaster.password_set');

		return TRUE;
	}

	public function verify(string $plain): bool
	{
		return ($hash = $this->_hash()) !== NULL && password_verify($plain, $hash);
	}

	/** Ouvre la fenêtre sudo pour la session courante. */
	public function grant_sudo(): void
	{
		$this->session->set(self::SESSION_KEY, 'sudo_until', time() + self::SUDO_TTL);
	}

	public function sudo_active(): bool
	{
		return (int)$this->session(self::SESSION_KEY, 'sudo_until') > time();
	}

	/** Secondes restantes de la fenêtre sudo (0 si fermée). */
	public function sudo_remaining(): int
	{
		return max(0, (int)$this->session(self::SESSION_KEY, 'sudo_until') - time());
	}

	public function revoke(): void
	{
		$this->session->destroy(self::SESSION_KEY, 'sudo_until');
	}

	/**
	 * Tente d'ouvrir le sudo via le mot de passe (rate-limité par IP + audité).
	 *
	 * @return array{ok: bool, locked: bool, retry_after: int}
	 */
	public function attempt(string $plain): array
	{
		$rl  = new Rate_Limit($this);
		$key = 'webmaster:'.Rate_Limit::client_ip();

		$check = $rl->check($key);

		if (empty($check['allowed']))
		{
			return ['ok' => FALSE, 'locked' => TRUE, 'retry_after' => (int)($check['retry_after'] ?? 0)];
		}

		if (!$this->verify($plain))
		{
			$hit = $rl->hit($key, 5, 300, 900);
			(new Audit_Log($this))->log('webmaster.sudo_failed', ['success' => FALSE]);

			return ['ok' => FALSE, 'locked' => !empty($hit['locked']), 'retry_after' => (int)($hit['retry_after'] ?? 0)];
		}

		$rl->reset($key);
		$this->grant_sudo();
		(new Audit_Log($this))->log('webmaster.sudo_granted');

		return ['ok' => TRUE, 'locked' => FALSE, 'retry_after' => 0];
	}
}
