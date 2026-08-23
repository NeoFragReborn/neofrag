<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\User\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Ajax extends Controller_Module
{
	public function _member($user)
	{
		return $user->view('profile');
	}

	public function auth()
	{
		$authenticators = NeoFrag()	->model2('addon')
									->get('authenticator')
									->filter('is_setup')
									->sort(function($a, $b){
										return $a->settings()->order - $b->settings()->order;
									});

		if (!$authenticators->empty())
		{
			return $this->modal('Connexion rapide', 'fas fa-user-circle')
						->large()
						->body($this->view('authenticators', [
							'authenticators' => $authenticators
						]))
						->button($this	->button()
										->title($this->lang('Mot de passe oublié ?'))
										->color('link')
										->modal_ajax('ajax/user/lost-password')
						)
						->button_if($this->config->nf_registration_status, $this->button()
																				->title($this->lang('Créer un compte'))
																				->color('secondary')
																				->modal_ajax('ajax/user/register')
						)
						->button($this	->button()
										->title('Se connecter')
										->color('primary')
										->modal_ajax('ajax/user/login')
						);
		}
		else
		{
			return $this->login();
		}
	}

	public function login()
	{
		$pending = $this->session('totp', 'pending_user_id');
		$expires = $this->session('totp', 'pending_expires');

		if ($pending && $expires && $expires >= time())
		{
			return $this->form2('login_totp')
						->modal('Validation 2FA', 'fas fa-shield-alt');
		}

		return $this->form2('login')
					->modal('Se connecter', 'fas fa-sign-in-alt')
					->button_prepend_if($this->config->nf_registration_status, $this->button()
																					->title($this->lang('Créer un compte'))
																					->color('secondary')
																					->modal_ajax('ajax/user/register')
					)
					->button_prepend($this	->button()
											->title($this->lang('Mot de passe oublié ?'))
											->color('link')
											->modal_ajax('ajax/user/lost-password')
					);
	}

	public function register()
	{
		return $this->form2(!empty($this->config->nf_registration_charte) ? 'username password_required email charte' : 'username password_required email', $this->model2('user'))
					->compact()
					->captcha()
					->success(function($user, $form){
						// R2.0 — Rate limit anti-bot/spam : 3 inscriptions par IP / 30 min
						$rateLimit = new \NF\NeoFrag\Libraries\Rate_Limit($this);
						$ip        = \NF\NeoFrag\Libraries\Rate_Limit::client_ip();
						$rl_key    = 'register:ip:'.$ip;
						$rl_check  = $rateLimit->check($rl_key);
						if (!$rl_check['allowed'])
						{
							$form->error($this->lang('Trop d\'inscriptions récentes depuis cette IP. Réessaye dans %d minute(s).', ceil($rl_check['retry_after'] / 60)));
							return;
						}
						$rateLimit->hit($rl_key, 3, 1800, 1800);

						if ($this->config->nf_registration_validation)
						{
							$sent = $this	->anti_flood()
											->email
											->template('user.registration', [
												'username'       => $user->username,
												// URL ABSOLUE : un lien relatif dans un email est résolu contre le domaine du
												// client mail → cassé. (Même chose pour reset_url plus bas.)
												'validation_url' => absolute_url('user/validation/'.$user->token())
											])
											->to($user->email)
											->send();

							if ($sent)
							{
								notify($this->lang('Message envoyé'));
								$this->modal->dispose();
							}
							else
							{
								$form->error($this->lang('Une erreur s\'est produite lors de l\'envoi du message'));
								return;
							}
						}

						$user->set_password($user->password)->create();

						if ($wh = $this->module('webhooks'))
						{
							$wh->trigger('user.registered', [
								'user_id'  => (int)$user->id,
								'username' => $user->username
							]);
						}

						// Welcome message via talks (post-migration MP → Talks unifié).
						// Crée une conversation direct entre nf_welcome_user_id et le nouvel user.
						if ($this->config->nf_welcome && $this->config->nf_welcome_user_id && !empty($this->config->nf_welcome_title) && !empty($this->config->nf_welcome_content))
						{
							$welcome_uid = (int)$this->config->nf_welcome_user_id;
							$content     = str_replace('[pseudo]', '@'.$user->username, $this->config->nf_welcome_content);

							try
							{
								$talk_id = $this->module('talks')->model()->create_conversation(
									$welcome_uid,
									'direct',
									(string)$this->config->nf_welcome_title,
									'',
									[(int)$user->id]
								);
								if ($talk_id)
								{
									$this->module('talks')->model()->send_message($talk_id, $welcome_uid, $content);
								}
							}
							catch (\Throwable $e)
							{
								// Welcome silencieux : ne pas bloquer l'inscription si le talks crash
							}
						}

						notify($this->lang('Votre compte à bien été créé, bienvenue !'));

						$this->session->login($user);

						refresh();
					})
					->modal($this->lang('Créer un compte'), 'fas fa-sign-in-alt fa-rotate-90')
					->cancel();
	}

	public function lost_password()
	{
		return $this->form2()
					->compact()
					->rule($this->form_email('email')
								->title($this->lang('Adresse email'))
								->required()
					)
					->success(function($data, $form){
						// R2.0 — Rate limit anti-énumération comptes : 5 demandes par IP / heure, 3 par email / heure
						$rateLimit  = new \NF\NeoFrag\Libraries\Rate_Limit($this);
						$ip         = \NF\NeoFrag\Libraries\Rate_Limit::client_ip();
						$ip_key     = 'lost_password:ip:'.$ip;
						$mail_key   = 'lost_password:email:'.strtolower($data['email']);
						$ip_check   = $rateLimit->check($ip_key);
						$mail_check = $rateLimit->check($mail_key);
						if (!$ip_check['allowed'] || !$mail_check['allowed'])
						{
							$retry = max($ip_check['retry_after'], $mail_check['retry_after']);
							$form->error($this->lang('Trop de demandes récentes. Réessaye dans %d minute(s).', ceil($retry / 60)));
							return;
						}
						$rateLimit->hit($ip_key,   5, 3600, 3600);
						$rateLimit->hit($mail_key, 3, 3600, 3600);

						$user = $this->db	->collection('user')
											->where('deleted', FALSE)
											->where('email', $data['email'])
											->row();

						if (!$user())
						{
							$form->error($this->lang('Addresse email introuvable'));
						}
						else
						{
							$sent = $this	->anti_flood()
											->email
											->template('user.lost_password', [
												'username'  => $user->username,
												'reset_url' => absolute_url('user/lost-password/'.$user->token())
											])
											->to($data['email'])
											->send();

							if ($sent)
							{
								notify($this->lang('Message envoyé'));
								$this->modal->dispose();
							}
							else
							{
								$form->error($this->lang('Une erreur s\'est produite lors de l\'envoi du message'));
							}
						}
					})
					->modal($this->lang('Récupération de mot de passe'), 'fas fa-unlock-alt')
					->cancel();
	}

	public function _lost_password($token)
	{
		return $this->form2('password_required')
					->compact()
					->success(function($data) use ($token){
						$user = $token->delete()->user;

						$user	->set_password($data['password'])
								->update();

						notify($this->lang('Nouveau mot de passe enregistré'));

						// Mêmes gardes que la voie mot de passe : le reset prouve le
						// contrôle de l'email, pas le second facteur ni la levée d'un ban.
						if ($this->moderation->is_banned((int)$user->id, 'global'))
						{
							$msg = $this->moderation->block_message_for_user((int)$user->id, 'global');
							notify($msg ?: $this->lang('Ce compte est banni.'), 'danger');
						}
						else if ($user->totp_enabled)
						{
							$this->session->set('totp', 'pending_user_id', $user->id);
							$this->session->set('totp', 'pending_remember', 0);
							$this->session->set('totp', 'pending_expires', time() + 300);
							$this->session->append('modals', 'ajax/user/login');
						}
						else
						{
							$this->session->login($user);
						}

						refresh();
					})
					->modal($this->lang('Réinitialisation de mot de passe'), 'fas fa-unlock-alt')
					->cancel();
	}
}
