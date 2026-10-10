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
		// Pas coché d'avance (2026-10-08) : rester connecté un an sur cet appareil se choisit, cela ne se subit pas.
		->rule($this->form_checkbox('remember')
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

				// L'adresse n'est pas encore validée : pas de connexion. Un nouveau lien part — trois par heure au
				// plus —, et on le dit. Cette branche était vide : le membre restait devant le formulaire, sans un mot.
				if (($module_user = $this->module('user')) instanceof \NF\Modules\User\User && $module_user->a_valider($user))
				{
					$cle_lien = 'validation:user:'.(int) $user->id;
					$envoye   = $rateLimit->check($cle_lien)['allowed'] && $module_user->envoyer_validation($user);

					if ($envoye)
					{
						$rateLimit->hit($cle_lien, 3, 3600, 3600);
					}

					$auditLog->log('login.unvalidated', ['user_id' => $user->id, 'username' => $user->username]);
					$form->error($envoye
						? $this->lang('Votre inscription n\'est pas encore validée : un nouveau lien vient de partir à %s. Ouvrez-le pour activer votre compte.', nf_texte($user->email))
						: $this->lang('Votre inscription n\'est pas encore validée : ouvrez le lien envoyé à %s. Un nouveau lien pourra partir un peu plus tard.', nf_texte($user->email)));
					return;
				}
				else if ($user->totp_enabled)
				{
					// 2FA activé : on stocke le user_id en pending et on bascule sur le form TOTP
					$this->session->set('totp', 'pending_user_id', $user->id);
					$this->session->set('totp', 'pending_remember', in_array('on', $data['remember']) ? 1 : 0);
					$this->session->set('totp', 'pending_expires', time() + 300);
					$auditLog->log('login.password_ok_totp_pending', ['user_id' => $user->id, 'username' => $user->username]);

					// Le code se demande aussitôt : la fenêtre du mot de passe se ferme et celle du code s'ouvre à sa place
					// (js/modal.js, `ouvrir`). La page se rechargeait sans rien rouvrir, et il fallait recliquer sur
					// « Se connecter » pour voir apparaître le code (signalé sur la démo, 2026-10-08). Sans JavaScript, la
					// fenêtre du code s'ouvre au rechargement, comme après une connexion par un compte externe.
					if ($this->url->ajax())
					{
						$this->output->json(['modal' => 'dispose', 'ouvrir' => url('ajax/user/login')]);
					}

					$this->session->append('modals', 'ajax/user/login');
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
