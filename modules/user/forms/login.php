<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$this	->compact()
		->rule($this->form_text('login')
					->title('Pseudo ou adresse email')
					->addon('fas fa-user')
					->required()
		)
		->rule($this->form_password('password')
					->title('Mot de passe')
					->required()
		)
		->rule($this->form_checkbox('remember')
					->value(['on'])
					->data([
						'on' => $this->lang('Se souvenir de moi')
					])
		)
		->success(function($data, $form){
			$rateLimit = new \NF\NeoFrag\Libraries\Rate_Limit($this);
			$ip        = \NF\NeoFrag\Libraries\Rate_Limit::client_ip();
			$ipKey     = 'login:ip:'.$ip;
			$userKey   = 'login:user:'.strtolower($data['login']);

			$ipCheck   = $rateLimit->check($ipKey);
			$userCheck = $rateLimit->check($userKey);

			if (!$ipCheck['allowed'] || !$userCheck['allowed'])
			{
				$retryMin = max($ipCheck['retry_after'], $userCheck['retry_after']);
				$form->error($this->lang('Trop de tentatives. Réessayez dans %d minute(s).', ceil($retryMin / 60)));
				return;
			}

			$user = $this->db	->collection('user')
								->where('deleted', FALSE)
								->where('username', $data['login'], 'OR', 'email', $data['login'])
								->row();

			$auditLog = new \NF\NeoFrag\Libraries\Audit_Log($this);

			if ($user() && $user->password($data['password']))
			{
				$rateLimit->reset($ipKey);
				$rateLimit->reset($userKey);

				// Phase 4 modération — check is_banned() avant login
				if ($this->moderation->is_banned((int)$user->id, 'global'))
				{
					$msg = $this->moderation->block_message_for_user((int)$user->id, 'global');
					$auditLog->log('login.banned_blocked', ['user_id' => $user->id, 'username' => $user->username]);
					$form->error($msg ?: $this->lang('Ce compte est banni.'));
					return;
				}

				if ($this->config->nf_registration_validation && !$user->last_activity_date)
				{
					//Vous devez valider votre inscription, recevoir un nouveau mail de validation
					//TODO
				}
				else if ($user->totp_enabled)
				{
					// 2FA activé : on stocke le user_id en pending et on bascule sur le form TOTP
					$this->session->set('totp', 'pending_user_id', $user->id);
					$this->session->set('totp', 'pending_remember', in_array('on', $data['remember']) ? 1 : 0);
					$this->session->set('totp', 'pending_expires', time() + 300);
					$auditLog->log('login.password_ok_totp_pending', ['user_id' => $user->id, 'username' => $user->username]);
					refresh();
				}
				else
				{
					$auditLog->log('login.success', ['user_id' => $user->id, 'username' => $user->username]);
					$this->session->login($user, in_array('on', $data['remember']));
					refresh();
				}
			}
			else
			{
				$rateLimit->hit($ipKey,   10, 300, 1800);
				$rateLimit->hit($userKey, 5,  300, 900);

				$auditLog->log('login.failed', ['username' => $data['login'], 'success' => FALSE]);

				$form->error($this->lang('Identifiants invalides'));
			}
		})
		->submit($this->lang('Se connecter'));
