<?php
/**
 * https://neofr.ag
 * Form 2FA — étape 2 du login. Demande le code TOTP (ou code de récupération).
 */

$pending_user_id = $this->session('totp', 'pending_user_id');
$pending_expires = $this->session('totp', 'pending_expires');
$pending_remember = $this->session('totp', 'pending_remember');

if (!$pending_user_id || !$pending_expires || $pending_expires < time())
{
	$this->session->destroy('totp', 'pending_user_id');
	$this->session->destroy('totp', 'pending_expires');
	$this->session->destroy('totp', 'pending_remember');
	refresh();
	return;
}

$this	->compact()
		->rule($this->form_text('code')
					->title($this->lang('Code à 6 chiffres ou code de récupération'))
					->required()
					->info($this->lang('Ouvre ton application d\'authentification (Google Authenticator, Authy, FreeOTP...) et saisis le code affiché.'))
		)
		->success(function($data, $form) use ($pending_user_id, $pending_remember){
			$rateLimit = new \NF\NeoFrag\Libraries\Rate_Limit($this);
			$ip        = \NF\NeoFrag\Libraries\Rate_Limit::client_ip();
			$ipKey     = 'totp:ip:'.$ip;
			$userKey   = 'totp:user:'.$pending_user_id;

			$ipCheck   = $rateLimit->check($ipKey);
			$userCheck = $rateLimit->check($userKey);

			if (!$ipCheck['allowed'] || !$userCheck['allowed'])
			{
				$retryMin = max($ipCheck['retry_after'], $userCheck['retry_after']);
				$form->error($this->lang('Trop de tentatives. Réessayez dans %d minute(s).', ceil($retryMin / 60)));
				return;
			}

			$user = $this->db	->collection('user')
								->where('id', $pending_user_id)
								->where('deleted', FALSE)
								->row();

			if (!$user())
			{
				$form->error($this->lang('Session expirée. Reconnecte-toi.'));
				$this->session->destroy('totp', 'pending_user_id');
				return;
			}

			$totp = new \NF\NeoFrag\Libraries\Totp_Service($this);
			$code = trim($data['code']);

			$verified = FALSE;

			// Si le code ressemble à un TOTP (6 chiffres) → vérifier TOTP
			if (preg_match('/^\d{6}$/', $code))
			{
				$verified = $totp->verify($this->crypt->decrypt_secret($user->totp_secret), $code);
			}
			// Sinon, tenter un code de récupération
			else
			{
				$verified = $totp->verify_and_consume_recovery_code($user->id, $code);
			}

			$auditLog = new \NF\NeoFrag\Libraries\Audit_Log($this);

			if (!$verified)
			{
				$rateLimit->hit($ipKey,   10, 300, 1800);
				$rateLimit->hit($userKey, 5,  300, 900);
				$auditLog->log('login.totp_failed', ['user_id' => $user->id, 'username' => $user->username, 'success' => FALSE]);
				$form->error($this->lang('Code invalide.'));
				return;
			}

			// Succès : finalize login
			$rateLimit->reset($ipKey);
			$rateLimit->reset($userKey);

			$this->session->destroy('totp', 'pending_user_id');
			$this->session->destroy('totp', 'pending_expires');
			$this->session->destroy('totp', 'pending_remember');

			$auditLog->log('login.totp_success', ['user_id' => $user->id, 'username' => $user->username]);

			$this->session->login($user, (bool)$pending_remember);
			refresh();
		})
		->submit($this->lang('Vérifier'));
