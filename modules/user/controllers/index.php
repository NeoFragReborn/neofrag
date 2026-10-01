<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 *
 * couplage(forum): l'export RGPD liste les contributions forum de l'utilisateur, mais seulement
 * si les tables existent (`table_exists` en amont). Sans le module forum, l'export rend deux
 * listes vides — il ne doit jamais echouer, c'est l'exercice d'un droit legal (corrige le
 * 2026-09-15 : il n'avait aucune garde).
 */

namespace NF\Modules\User\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index()
	{
		return $this->title($this->lang('Mon activité'))
					->icon('far fa-star')
					->row([
						$this->col(
							$this	->panel()
									->heading($this->lang('Mon profil'))
									->body($this->user->view('profile')),
							$this->_panel_navigation()
						)->size('col-12 col-lg-4'),
						$this->col(
							$this->row($this->col($this->panel()->body($this->_panel_infos()))),
							$this	->row()
									->append($this	->col()
													->size('col-12 col-lg-6')
													->append($this	->panel()
																	->heading($this->lang('Messagerie'))
																	->body($this->view('index'))
													)
									)
									->append($this	->col()
													->size('col-12 col-lg-6')
													->append($this->_panel_activities())
									)
						)->size('col-12 col-lg-8')
					]);
	}

	public function security()
	{
		$this	->title($this->lang('Sécurité du compte'))
				->icon('fas fa-shield-alt')
				->breadcrumb();

		$totp_panel = $this->panel()->title($this->lang('Authentification à deux facteurs (2FA)'), 'fas fa-mobile-alt');

		if ($this->user->totp_enabled)
		{
			$totp = new \NF\NeoFrag\Libraries\Totp_Service($this);
			$remaining = $totp->count_unused_recovery_codes($this->user->id);

			$totp_panel->body('<div class="alert alert-success"><i class="fas fa-check-circle"></i> '.$this->lang('2FA <b>activé</b> sur ton compte.').'</div><p>'.$this->lang('Codes de récupération restants : <b>%d</b> / 10', $remaining).'</p><a class="btn btn-danger" href="'.url('user/security/disable').'"><i class="fas fa-times"></i> '.$this->lang('Désactiver le 2FA').'</a>');
		}
		else
		{
			$totp_panel->body('<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> '.$this->lang('2FA <b>désactivé</b>.').'</div><p>'.$this->lang('Active le 2FA pour ajouter une couche de sécurité à ton compte. Tu auras besoin d\'une appli comme Google Authenticator, Authy ou FreeOTP.').'</p><a class="btn btn-primary" href="'.url('user/security/setup').'"><i class="fas fa-shield-alt"></i> '.$this->lang('Activer le 2FA').'</a>');
		}

		$rgpd_panel = $this->panel()->title($this->lang('Mes données (RGPD)'), 'fas fa-user-shield')
									->body('<p>'.$this->lang('Conformément au RGPD, tu peux à tout moment :').'</p><ul><li>'.$this->lang('Récupérer une copie complète de tes données personnelles').'</li><li>'.$this->lang('Demander la suppression de ton compte (droit à l\'oubli)').'</li></ul><a class="btn btn-secondary" href="'.url('user/security/export').'"><i class="fas fa-download"></i> '.$this->lang('Exporter mes données (JSON)').'</a> <a class="btn btn-outline-danger" href="'.url('user/security/delete').'"><i class="far fa-trash-alt"></i> '.$this->lang('Supprimer mon compte').'</a>');

		return $this->_layout(function($row) use ($totp_panel, $rgpd_panel){
			$row->append($this->col($totp_panel, $rgpd_panel)->size('col-12 col-lg-8 mx-auto'));
		});
	}

	public function security_export()
	{
		$user = $this->user;

		// Le forum est un module optionnel : sur une installation qui ne l'embarque pas, ses tables
		// n'existent pas et la requete fataliserait — au beau milieu d'un export RGPD, donc sur
		// l'exercice d'un droit legal de l'utilisateur. On ne liste ses contributions que si le
		// module est effectivement installe.
		$forum = $this->db->table_exists('nf_forum_topics') && $this->db->table_exists('nf_forum_messages');

		$data = [
			'export_meta' => [
				'generated_at' => date('c'),
				'site'         => $this->config->nf_name,
				'user_id'      => $user->id,
				'username'     => $user->username,
				'rgpd_notice'  => $this->lang('Cette archive contient toutes les données personnelles que ce site a collectées sur toi (Article 15 du RGPD).')
			],
			'profile' => [
				'id'                 => $user->id,
				'username'           => $user->username,
				'email'              => $user->email,
				'registration_date'  => $user->registration_date,
				'last_activity_date' => $user->last_activity_date,
				'language'           => $user->language,
				'admin'              => (bool)$user->admin,
				'totp_enabled'       => (bool)$user->totp_enabled
			],
			// Les champs definis par l'administrateur font partie des donnees personnelles : les
			// omettre rendrait l'archive incomplete au sens de l'article 15.
			'champs_personnalises' => $this->_champs_values((int) $user->id),
			'sessions_actives' => $this->db	->select('id', 'UNIX_TIMESTAMP(last_activity) AS last_activity', 'data')
											->from('nf_session')
											->where('user_id', $user->id)
											->get(),
			'forum_topics' => !$forum ? [] : $this->db	->select('topic_id', 'title', 'UNIX_TIMESTAMP(date) AS created_at')
														->from('nf_forum_topics')
														->where('user_id', $user->id)
														->get(),
			'forum_messages' => !$forum ? [] : $this->db	->select('message_id', 'topic_id', 'message', 'UNIX_TIMESTAMP(date) AS created_at')
														->from('nf_forum_messages')
														->where('user_id', $user->id)
														->get(),
			'comments' => $this->db	->select('comment_id', 'module', 'object_id', 'content', 'UNIX_TIMESTAMP(date) AS created_at')
									->from('nf_comment')
									->where('user_id', $user->id)
									->get(),
			'messages_envoyes' => $this->db	->select('t.talk_id', 't.name AS title', 't.type', 'm.content', 'UNIX_TIMESTAMP(m.date) AS sent_at')
											->from('nf_talks_messages m')
											->join('nf_talks t', 't.talk_id = m.talk_id')
											->where('m.user_id', $user->id)
											->where('m.deleted_at', NULL)
											->get(),
			'cookie_consent' => $this->db	->select('consent_essentials', 'consent_analytics', 'consent_marketing', 'UNIX_TIMESTAMP(created_at) AS at')
											->from('nf_cookie_consent')
											->where('user_id', $user->id)
											->get()
		];

		$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$filename = 'neofrag-export-'.$user->username.'-'.date('Ymd-His').'.json';

		header('Content-Type: application/json; charset=utf-8');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Length: '.strlen($json));
		echo $json;
		exit;
	}

	public function security_delete()
	{
		// Le mot à recopier se traduit comme le reste : on ne demande pas « SUPPRIMER » à un Anglais.
		$mot = (string) $this->lang('SUPPRIMER');

		$this	->title($this->lang('Supprimer mon compte'))
				->icon('fas fa-trash-alt')
				->breadcrumb()
				->form()
				->add_rules([
					'confirm_text' => [
						'label' => $this->lang('Tape « %s » pour confirmer', $mot),
						'type'  => 'text',
						'rules' => 'required'
					],
					'password' => [
						'label' => $this->lang('Confirme avec ton mot de passe'),
						'type'  => 'password',
						'rules' => 'required'
					]
				])
				->add_submit($this->lang('Supprimer définitivement mon compte'), 'fas fa-trash');

		if ($this->form()->is_valid($post))
		{
			if ($post['confirm_text'] !== $mot)
			{
				$this->form()->error($this->lang('Tape exactement « %s » (en majuscules) pour confirmer.', $mot));
			}
			else if (!$this->user->password($post['password']))
			{
				$this->form()->error($this->lang('Mot de passe incorrect.'));
			}
			else
			{
				$user_id = $this->user->id;
				$username = $this->user->username;

				// Soft delete : marque deleted=TRUE, supprime sessions, anonymise email
				$this->user	->set('deleted', 1)
							->set('email', 'deleted-'.$user_id.'@deleted.local')
							->set('totp_secret', NULL)
							->set('totp_enabled', 0);
				$this->user->update();

				// Cleanup données liées
				$this->db->where('user_id', $user_id)->delete('nf_session');
				$this->db->where('user_id', $user_id)->delete('nf_user_totp_recovery');

				(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('user.account_deleted', ['user_id' => $user_id, 'username' => $username]);

				notify($this->lang('Ton compte a été supprimé. À bientôt !'));
				redirect('//');
			}
		}

		$intro = '<div class="alert alert-danger"><b>'.$this->lang('⚠️ Action irréversible.').'</b> '.$this->lang('Cette action va supprimer ton compte. Tes posts forum, commentaires et messages privés resteront mais seront anonymisés. Tu ne pourras plus te reconnecter.').'</div>';

		return $this->_layout(function($row) use ($intro){
			$row->append($this	->col()
								->append($this	->panel()
												->heading()
												->body($intro.$this->form()->display())
								)
								->size('col-12 col-lg-6 mx-auto')
			);
		});
	}

	public function security_setup()
	{
		if ($this->user->totp_enabled)
		{
			redirect('user/security');
		}

		$totp = new \NF\NeoFrag\Libraries\Totp_Service($this);

		// Secret pending en session (TTL 15 min)
		$pending_secret = $this->session('totp_setup', 'secret');
		$pending_expires = $this->session('totp_setup', 'expires');

		if (!$pending_secret || !$pending_expires || $pending_expires < time())
		{
			$pending_secret = $totp->generate_secret();
			$this->session->set('totp_setup', 'secret', $pending_secret);
			$this->session->set('totp_setup', 'expires', time() + 900);
		}

		$qr_data_uri = $totp->qr_code_svg_data_uri($this->config->nf_name ?: 'NeoFrag', $this->user->username, $pending_secret);

		$this	->title($this->lang('Activer le 2FA'))
				->icon('fas fa-shield-alt')
				->breadcrumb()
				->form()
				->add_rules([
					'qr' => [
						'label' => $this->lang('Étape 1 — Scanne ce QR code'),
						'type'  => 'free',
						'value' => '<img src="'.$qr_data_uri.'" alt="QR code" style="max-width:200px"><br><small>'.$this->lang('Ou saisis manuellement le code :').' <code>'.$pending_secret.'</code></small>'
					],
					'code' => [
						'label' => $this->lang('Étape 2 — Saisis le code à 6 chiffres généré par ton appli'),
						'type'  => 'text',
						'rules' => 'required'
					]
				])
				->add_submit($this->lang('Activer'), 'fas fa-lock');

		if ($this->form()->is_valid($post))
		{
			if (!$totp->verify($pending_secret, $post['code']))
			{
				$this->form()->error($this->lang('Code invalide. Réessaie avec le code actuel de ton appli.'));
			}
			else
			{
				$codes = $totp->generate_recovery_codes(10);

				$this->user	->set('totp_secret', $this->crypt->encrypt_secret($pending_secret))
							->set('totp_enabled', 1)
							->update();

				$totp->store_recovery_codes($this->user->id, $codes['hashed']);

				$this->session->destroy('totp_setup', 'secret');
				$this->session->destroy('totp_setup', 'expires');
				$this->session->set('totp_setup', 'plain_codes', $codes['plain']);
				$this->session->set('totp_setup', 'codes_expires', time() + 600);

				(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('totp.enabled');

				notify($this->lang('2FA activé !'));
				redirect('user/security/codes');
			}
		}

		return $this->_layout(function($row){
			$row->append($this	->col()
								->append($this	->panel()
												->heading()
												->body($this->form()->display())
								)
								->size('col-12 col-lg-8 mx-auto')
			);
		});
	}

	public function security_codes()
	{
		$plain_codes = $this->session('totp_setup', 'plain_codes');
		$codes_expires = $this->session('totp_setup', 'codes_expires');

		if (!$plain_codes || !$codes_expires || $codes_expires < time())
		{
			redirect('user/security');
		}

		$this->session->destroy('totp_setup', 'plain_codes');
		$this->session->destroy('totp_setup', 'codes_expires');

		$this	->title($this->lang('Codes de récupération'))
				->icon('fas fa-key')
				->breadcrumb();

		$body = '<div class="alert alert-warning"><b>⚠️ '.$this->lang('Sauvegarde ces codes dans un endroit sûr.').'</b> '.$this->lang('Ils te permettent de te connecter si tu perds l\'accès à ton appli d\'authentification. Chaque code n\'est utilisable qu\'une seule fois.').'</div>';
		$body .= '<div class="alert alert-danger">'.$this->lang('Ces codes ne te seront <b>plus jamais affichés</b>. Imprime-les ou copie-les maintenant.').'</div>';
		$body .= '<pre style="font-size:1.2em;line-height:2em">'.implode("\n", $plain_codes).'</pre>';
		$body .= '<a class="btn btn-primary" href="'.url('user/security').'">'.$this->lang('J\'ai sauvegardé mes codes').'</a>';

		return $this->_layout(function($row) use ($body){
			$row->append($this->col($this->panel()->title($this->lang('Codes de récupération 2FA'), 'fas fa-key')->body($body))->size('col-12 col-lg-8 mx-auto'));
		});
	}

	public function security_disable()
	{
		if (!$this->user->totp_enabled)
		{
			redirect('user/security');
		}

		$this	->title($this->lang('Désactiver le 2FA'))
				->icon('fas fa-shield-alt')
				->breadcrumb()
				->form()
				->add_rules([
					'password' => [
						'label' => $this->lang('Confirme avec ton mot de passe'),
						'type'  => 'password',
						'rules' => 'required'
					]
				])
				->add_submit($this->lang('Désactiver le 2FA'), 'fas fa-unlock');

		if ($this->form()->is_valid($post))
		{
			if (!$this->user->password($post['password']))
			{
				$this->form()->error($this->lang('Mot de passe incorrect.'));
			}
			else
			{
				$this->user	->set('totp_secret', NULL)
							->set('totp_enabled', 0)
							->update();

				$this->db	->where('user_id', $this->user->id)
							->delete('nf_user_totp_recovery');

				(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('totp.disabled');

				notify($this->lang('2FA désactivé.'));
				redirect('user/security');
			}
		}

		return $this->_layout(function($row){
			$row->append($this	->col()
								->append($this	->panel()
												->heading()
												->body($this->form()->display())
								)
								->size('col-12 col-lg-6 mx-auto')
			);
		});
	}

	public function account($sessions)
	{
		return $this->row([
						$this->col(
							$this	->panel()
									->heading($this->lang('Mon profil'))
									->body($this->user->view('profile')),
							$this->_panel_navigation()
						)->size('col-12 col-lg-4'),
						$this->col(
							$this->title($this->lang('Connexion'))
								->icon('fas fa-sign-in-alt')
								->breadcrumb()
								->form2('username current_password new_password email', $this->user)
								->success(function($user){
									if ($user->password_new)
									{
										$user->set_password($user->password_new);
									}
									else
									{
										$user->reset('password');
									}

									if ($user->has_changed('email') && $this->config->nf_registration_validation)
									{
										//TODO
									}

									$user->update();

									notify($this->lang('Informations modifiées'));

									refresh();
								})
								->submit($this->lang('Modifier'))
								->panel()
								->title($this->lang('Info de connexion'))
						)->size('col-12 col-lg-8')
					]);

					/* TODO
					->row()
					->append(
						$this	->col()
								->size('col-12 col-lg-6')
								->append(
									$this
								)
					)
					->append(
						$this	->col()
								->size('col-12 col-lg-6')
								->append(
									$this	->table2($sessions)
											->col(function($session){
												return user_agent($session->data->session->user_agent);
											})
											->col($this->lang('Adresse IP'), function($session){
												// host_name = reverse DNS, contrôlé par le propriétaire de l'IP → échappé.
												$ip_address = $session->data->session->ip_address;
												return geolocalisation($ip_address).'<span data-bs-toggle="tooltip" data-original-title="'.htmlspecialchars((string)$session->data->session->host_name, ENT_QUOTES).'">'.htmlspecialchars((string)$ip_address, ENT_QUOTES).'</span>';
											})
											->col($this->lang('Site référent'), function($session){
												return $session->data->session->referer ? urltolink($session->data->session->referer) : $this->lang('Aucun');
											})
											->col($this->lang('Date'), function($session){
												return $session->data->session->date;
											})
											->col($this->lang('Compte tiers'), function($session){
												return $session->auth ? $session->auth : '';
											})
											->delete()
											->panel()
											->title('Sessions actives', 'fas fa-globe')
								)
								->append(
									$this	->form2()
											->rule($this->form_checkbox('delete')
														->data([
															'account'   => 'Je souhaite supprimer mon compte',
															//'keep_data' => 'J\'accepte que mes contributions soient conservées de façon anonyme'
														])
											)
											->form('current_password')
											->success(function($data){
												if (in_array('account', $data['delete']))
												{
													//TODO
													if (1 || in_array('keep_data', $data['delete']))
													{
														$this->user->set('deleted', TRUE)->update();
													}
													else
													{
														$this->user->delete();
													}

													NeoFrag()->collection('session')->where('user_id', $this->user->id)->update([
														'user_id' => NULL
													]);

													notify($this->lang('Compte supprimé'));

													redirect();
												}
											})
											->submit($this->lang('Supprimer'), 'danger')
											->panel()
											->title('Supprimer mon compte', 'fas fa-times')
								)
					);*/
	}

	/**
	 * Les champs de profil définis par l'administrateur, rendus dans un panneau à part.
	 *
	 * À part, et non mêlés au formulaire de profil livré, pour une raison simple : celui-ci est lié
	 * au modèle `Profile`, dont les seize colonnes sont fixes. Un champ ajouté par un administrateur
	 * n'est pas une colonne ; le mêler au formulaire du modèle demanderait à ce dernier d'accepter
	 * des clés qu'il ne connaît pas.
	 *
	 * Rend une chaîne vide quand aucun champ n'est défini : le panneau ne doit pas apparaître pour
	 * rien sur un site qui n'en a pas.
	 */
	/**
	 * Les valeurs des champs définis par l'administrateur, pour un membre.
	 *
	 * Passe par une variable annotée : `model()` rend un `Loadables\\Model` aux yeux de l'analyse
	 * statique, qui ne connaît donc aucune de ses méthodes.
	 *
	 * @return array<string, string>
	 */
	private function _champs_values(int $user_id): array
	{
		/** @var \NF\Modules\User\Models\Fields $fields */
		$fields = $this->model('fields');

		return $fields->get_values($user_id);
	}

	private function _champs_personnalises()
	{
		/** @var \NF\Modules\User\Models\Fields $fields */
		$fields = $this->model('fields');
		$champs = $fields->get_fields();

		if (!$champs)
		{
			return '';
		}

		$valeurs = $this->_champs_values((int) $this->user->id);
		$regles  = [];

		foreach ($champs as $champ)
		{
			$regle = [
				'label'       => $champ['label'],
				'description' => $champ['description'],
				'value'       => $valeurs[$champ['name']] ?? '',
			];

			if ($champ['required'])
			{
				$regle['rules'] = 'required';
			}

			$choix = \NF\Modules\User\Models\Fields::options_en_tableau($champ['options']);

			switch ($champ['type'])
			{
				case 'textarea':
					$regle['type'] = 'textarea';
					$regle['rows'] = 4;
					break;

				case 'select':
					$regle['type']   = 'select';
					$regle['values'] = ['' => ''] + array_combine($choix, $choix);
					break;

				case 'radio':
					$regle['type']   = 'radio';
					$regle['values'] = array_combine($choix, $choix);
					break;

				case 'checkbox':
					// Une case cochee vaut « on » ; decochee, rien n'est poste et la valeur s'efface.
					$regle['type']    = 'checkbox';
					$regle['values']  = ['on' => $champ['label']];
					$regle['checked'] = ['on' => ($valeurs[$champ['name']] ?? '') !== ''];
					unset($regle['label']);
					break;

				case 'number':
					$regle['type'] = 'number';
					break;

				case 'date':
					$regle['type'] = 'date';
					break;

				case 'url':
					$regle['check'] = function($valeur){
						if (!is_empty($valeur) && !filter_var($valeur, FILTER_VALIDATE_URL))
						{
							return $this->lang('Cette adresse est invalide');
						}
					};
					break;
			}

			$regles[$champ['name']] = $regle;
		}

		$this	->form()
				->add_rules($regles)
				->add_submit($this->lang('Valider'));

		if ($this->form()->is_valid($post))
		{
			$saisies = [];

			foreach ($champs as $champ)
			{
				$brut = $post[$champ['name']] ?? '';

				// Une case a cocher poste un tableau : on la ramene a « on » ou a rien.
				$saisies[$champ['name']] = $champ['type'] === 'checkbox'
					? (in_array('on', (array) $brut, TRUE) ? 'on' : '')
					: (is_array($brut) ? '' : (string) $brut);
			}

			/** @var \NF\Modules\User\Models\Fields $fields */
			$fields = $this->model('fields');
			$fields->set_values((int) $this->user->id, $saisies);

			notify($this->lang('Profil modifié'));

			refresh();
		}

		return $this->panel()
					->heading($this->lang('Informations complémentaires'), 'fas fa-list-ul')
					->body($this->form()->display());
	}

	public function profile()
	{
		$this	->title($this->lang('Profil'))
				->icon('fas fa-pencil-alt')
				->breadcrumb();

		return $this->_layout(function($row){
			$row->append($this	->col()
								->size('col-12 col-lg-7')
								->append($this	->form2('profile', $this->user->profile())
												->panel()
								)
								->append($this	->form2('profile_socials', $this->user->profile())
												->panel()
												->title($this->lang('Liens'), 'fas fa-globe')
								)
								->append_if(($champs = $this->_champs_personnalises()) !== '', $champs)
				)
				->append($this	->col()
								->size('col-12 col-lg-5')
								->append($this	->form2('avatar', $this->user->profile())
												->panel()
												->title($this->lang('Avatar'), 'fas fa-user-circle')
								)
								->append($this	->form2('cover', $this->user->profile())
												->panel()
												->title($this->lang('Photo de couverture'), 'far fa-image')
								)
				);
		});
	}

	public function sessions($sessions)
	{
		return $this->row([
						$this->col(
							$this	->panel()
									->heading($this->lang('Mon profil'))
									->body($this->user->view('profile')),
							$this->_panel_navigation()
						)->size('col-12 col-lg-4'),
						$this->col(
							$this	->title($this->lang('Historique des sessions'))
									->icon('fas fa-history')
									->breadcrumb()
									->table2('session_history', $sessions, $this->lang('Aucun historique'))
									->panel()
						)->size('col-12 col-lg-8')
					]);
	}

	public function _session_delete($session_id)
	{
		$this	->title($this->lang('Confirmation de suppression'))
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer la session de l\'utilisateur <b>%s</b> ?'));

		if ($this->form()->is_valid())
		{
			$this->db	->where('id', $session_id)
						->delete('nf_session');

			return 'OK';
		}

		return $this->form()->display();
	}

	public function auth($authenticator)
	{
		$service = new \SocialConnect\Auth\Service(
			new \SocialConnect\Common\Http\Client\Curl,
			new \NF\NeoFrag\Libraries\Social_Connect_Session($this->session), [
				'redirectUri' => $authenticator->static_url(),
				'provider'    => [
					$name = str_replace('_', '-', $authenticator->info()->name) => $authenticator->config()
				]
			]
		);

		$provider = $service->getProvider($name);

		if ($callback = $authenticator->data($params))
		{
			$data = array_merge(array_fill_keys(['id', 'username', 'avatar'], ''), $callback($provider->getIdentity($provider->getAccessTokenByRequestParameters($params))));

			if (($auth = $this->collection('auth')->where('authenticator_id', $authenticator->__addon->id)->where('key', $data['id'])->row()) && $auth->key == $data['id'])
			{
				// Connecté, et ce compte externe appartient à un AUTRE membre : on refuse. On basculait
				// jusqu'ici la session sur cet autre membre — lier son Discord connectait alors au
				// compte de quelqu'un d'autre (relevé le 2026-10-01).
				if ($this->user() && $this->user->id != $auth->user->id)
				{
					notify($this->lang('Ce compte %s est déjà lié à un autre membre.', $authenticator->info()->title), 'danger');
					redirect('user/auth');
				}
				else if ($this->user->id != $auth->user->id)
				{
					$auth	->set_if($data['username'], 'username', $data['username'])
							->set_if($data['avatar'],   'avatar',   $data['avatar'])
							->update();

					// Mêmes gardes que la voie mot de passe (forms/login.php) : le lien
					// social ne doit ouvrir ni un compte banni, ni un compte 2FA sans
					// second facteur.
					$user = $auth->user;

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
				}
			}
			else if ($this->user())
			{
				$auth	->set('user',          $this->user)
						->set('authenticator', $authenticator->__addon)
						->set('key',           $data['id'])
						->set_if($data['username'], 'username', $data['username'])
						->set_if($data['avatar'],   'avatar',   $data['avatar'])
						->create();

				$this->_compte_externe_change('linked', (string) $authenticator->info()->name, (int) $this->user->id, (string) $data['id']);

				notify($this->lang('Votre compte %s est lié : vous pourrez vous connecter avec lui.', $authenticator->info()->title));
				redirect('user/auth');
			}
			else if ($this->config->nf_registration_status)
			{
				$this->_inscription_externe($authenticator, $data);
			}
			else
			{
				notify($this->lang('Aucun membre n’a lié ce compte %s, et les inscriptions sont fermées.', $authenticator->info()->title), 'danger');
			}

			redirect();
		}

		$this->url->redirect($provider->makeAuthUrl());
	}

	/**
	 * S'inscrire par un compte externe (2026-10-01) : un compte Discord que personne n'a
	 * lié crée un membre, lié d'emblée, et le connecte. Le pseudo vient du compte externe, rendu
	 * unique au besoin ; ni mot de passe ni adresse : le membre se connecte par ce compte, et peut
	 * ajouter les deux dans son profil. Mêmes limites que l'inscription par formulaire : inscriptions
	 * ouvertes, trois par adresse IP par demi-heure.
	 *
	 * Jusqu'ici, un compte externe inconnu recevait « Compte inconnu », et ses données, rangées en
	 * session, n'étaient relues par rien.
	 */
	private function _inscription_externe($authenticator, array $data): void
	{
		$limite = new \NF\NeoFrag\Libraries\Rate_Limit($this);
		$cle    = 'register:ip:'.\NF\NeoFrag\Libraries\Rate_Limit::client_ip();

		if (!($etat = $limite->check($cle))['allowed'])
		{
			notify($this->lang('Trop d\'inscriptions récentes depuis cette IP. Réessaye dans %d minute(s).', ceil($etat['retry_after'] / 60)), 'danger');
			return;
		}

		$limite->hit($cle, 3, 1800, 1800);

		$base = mb_substr(trim((string) preg_replace('/[^\p{L}\p{N}_.\-]+/u', '', (string) $data['username'])), 0, 90) ?: 'membre';
		$nom  = $base;

		for ($n = 2; !$this->db->from('nf_user')->where('username', $nom)->where('deleted', FALSE)->empty(); $n++)
		{
			$nom = $base.$n;
		}

		$user = $this->model2('user')
					->set('username', $nom)
					->set('email', '')
					->set('password', '')
					->set('salt', '')
					->create();

		$this->model2('auth')
			->set('user',          $user)
			->set('authenticator', $authenticator->__addon)
			->set('key',           $data['id'])
			->set_if($data['username'], 'username', $data['username'])
			->set_if($data['avatar'],   'avatar',   $data['avatar'])
			->create();

		$this->_compte_externe_change('linked', (string) $authenticator->info()->name, (int) $user->id, (string) $data['id']);

		if (($wh = $this->module('webhooks')) instanceof \NF\Modules\Webhooks\Webhooks)
		{
			$wh->trigger('user.registered', ['user_id' => (int) $user->id, 'username' => $nom]);
		}

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('user.registered.external', ['user_id' => (int) $user->id, 'username' => $nom, 'details' => $authenticator->info()->name]);

		$this->session->login($user);

		notify($this->lang('Votre compte a été créé avec %s, bienvenue ! Ajoutez une adresse e-mail et un mot de passe dans votre profil pour pouvoir aussi vous connecter sans lui.', $authenticator->info()->title));
	}

	/**
	 * Mes comptes liés : ceux que j'ai liés, à délier, et ceux du site que je peux lier.
	 * Cette page rendait jusqu'ici le texte brut « auth ».
	 */
	public function _auth($auths)
	{
		$this->title($this->lang('Mes comptes liés'))->icon('fas fa-link')->breadcrumb();

		$lies = (array) $this->db	->select('a.id', 'a.key', 'a.username', 'a.avatar', 'ad.name')
									->from('nf_user_auth a')
									->join('nf_addon ad', 'ad.id = a.authenticator_id', 'INNER')
									->where('a.user_id', (int) $this->user->id)
									->order_by('a.id')
									->get();

		$fournisseurs = [];

		foreach (NeoFrag()->model2('addon')->get('authenticator')->filter('is_setup') as $a)
		{
			$fournisseurs[(string) $a->info()->name] = ['titre' => (string) $a->info()->title, 'icone' => (string) $a->info()->icon, 'couleur' => (string) $a->info()->color, 'lier' => url('user/auth/'.url_title((string) $a->info()->name))];
		}

		$lignes = [];

		foreach ($lies as $l)
		{
			// Un compte lié garde le nom et l'icône de son fournisseur même si celui-ci n'est plus
			// configuré (clés retirées) : on ne peut plus s'y connecter, mais on doit le reconnaître.
			$a = \NF\NeoFrag\Addons\Authenticator::__load(\NeoFrag(), [(string) $l['name']]);

			$lignes[] = [
				'fournisseur' => $fournisseurs[(string) $l['name']] ?? ($a instanceof \NF\NeoFrag\Addons\Authenticator ? ['titre' => (string) $a->info()->title, 'icone' => (string) $a->info()->icon, 'couleur' => '', 'lier' => ''] : ['titre' => ucfirst((string) $l['name']), 'icone' => 'fas fa-link', 'couleur' => '', 'lier' => '']),
				'pseudo'      => (string) ($l['username'] ?? ''),
				'avatar'      => (string) ($l['avatar'] ?? ''),
				'delier'      => $this->csrf_url('user/auth/unlink/'.(int) $l['id']),
			];

			unset($fournisseurs[(string) $l['name']]);
		}

		return $this->panel()
					->heading($this->lang('Mes comptes liés'), 'fas fa-link')
					->body($this->view('auth', [
						'lignes'       => $lignes,
						'a_lier'       => $fournisseurs,
						'sans_secours' => (string) $this->user->password === '',
					]));
	}

	/** Délier un compte externe — sauf s'il est le seul moyen de se connecter. */
	public function _auth_unlink($lien)
	{
		$this->check_csrf('user/auth');

		$autres = (int) $this->db->select('COUNT(*)')->from('nf_user_auth')->where('user_id', (int) $this->user->id)->where('id <>', (int) $lien['id'])->row();

		if ((string) $this->user->password === '' && !$autres)
		{
			notify($this->lang('Ce compte est votre seul moyen de connexion : définissez d’abord un mot de passe dans votre profil.'), 'danger');
			redirect('user/auth');
		}

		$cle = (string) $this->db->select('key')->from('nf_user_auth')->where('id', (int) $lien['id'])->row();

		$this->db->where('id', (int) $lien['id'])->where('user_id', (int) $this->user->id)->delete('nf_user_auth');
		$this->_compte_externe_change('unlinked', (string) $lien['name'], (int) $this->user->id, $cle);

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('user.auth.unlinked', ['details' => (string) $lien['name']]);

		notify($this->lang('Compte délié.'));
		redirect('user/auth');
	}

	/**
	 * Un compte Discord lié ou délié ici : le bot Discord du site l'apprend par le fil de
	 * l'API, et donne ou retire aussitôt les rôles du membre. Les autres comptes externes (GitHub,
	 * Google) ne concernent aucun bot.
	 *
	 * couplage(api): facultatif — sans le module api, `Module::__load` rend NULL et rien n'est inscrit.
	 */
	private function _compte_externe_change(string $quoi, string $authentificateur, int $user_id, string $cle): void
	{
		if ($authentificateur !== 'discord' || $cle === '')
		{
			return;
		}

		$evenement = 'user.discord.'.$quoi;
		$charge    = ['user_id' => $user_id, 'discord_id' => $cle];

		$this->events->fire($evenement, $charge);

		if (($api = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['api'])) instanceof \NF\Modules\Api\Api)
		{
			$api->consigner($evenement, $charge);
		}
	}

	public function lost_password($token)
	{
		$this->session->append('modals', 'ajax/user/lost-password/'.$token->id);
		redirect();
	}

	// Entrées NON-AJAX de la connexion / inscription. Les thèmes exposent `user/login` et
	// `user/registration` en href des boutons d'en-tête (le clic normal ouvre la modale via
	// `data-modal-ajax`, ces URLs sont le repli sans JS / clic milieu / lien copié). Le routage
	// automatique cherche la méthode dans CE contrôleur (cf. neofrag/core/output.php) : sans
	// elles, seul `ajax/user/*` répondait et les deux URLs renvoyaient 404.
	// Même idiome que `lost_password()` : on programme la modale, puis retour à la page précédente.
	public function login()
	{
		$this->session->append('modals', 'ajax/user/auth');
		redirect_back();
	}

	public function registration()
	{
		$this->session->append('modals', 'ajax/user/register');
		redirect_back();
	}

	public function logout()
	{
		$this->session->logout();
		redirect();
	}

	public function _member($user)
	{
		return $this->title($user->username)
					->breadcrumb($this->lang('Profil'))
					->breadcrumb($user->username)
					->row()
					->append($this	->col()
									->size('col-12 col-lg-4 user-col')
									->append($this	->panel()
													->body($user->view('profile'))
									)
					)
					->append($this	->col()
									->size('col-12 col-lg-8')
									->append($this	->panel()
													->body($this->_panel_infos($user))
									)
									->append($this->_panel_activities($user->id))
									->append($this->panel_back())
					);
	}

	public function _panel_profile(&$user_profile = NULL)
	{
		$this->css('profile');

		return $this->panel()
					->heading('Mon profil', 'fas fa-user')
					->body($this->view('profile', $user_profile = $this->model()->get_user_profile($this->user->id)))
					->size('col-12 col-md-4 col-lg-3');
	}

	public function _panel_navigation($output = 'vertical')
	{
		// Le menu est présent sur toutes les pages de l'espace membre : le charger ici suffit à
		// couvrir l'ensemble de l'espace, sur les sept thèmes.
		$this->css('user-space');

		$navigation_links = [
			['title' => $this->lang('Mon espace'),         'icon' => 'fas fa-user',         'url' => 'user'],
			['title' => $this->lang('Info de connexion'),  'icon' => 'fas fa-sign-in-alt',  'url' => 'user/account'],
			['title' => $this->lang('Éditer mon profil'),  'icon' => 'fas fa-pencil-alt',   'url' => 'user/profile'],
			['title' => $this->lang('Messagerie privée'),  'icon' => 'far fa-envelope',     'url' => 'talks?type=private']
		];
		if ($this->access('moderation', 'view_reports'))
		{
			$navigation_links[] = ['title' => $this->lang('Modération'), 'icon' => 'fas fa-shield-alt', 'url' => 'moderation'];
		}
		$navigation_links = array_merge($navigation_links, [
			['title' => $this->lang('Gérer mes sessions'), 'icon' => 'fas fa-globe',         'url' => 'user/sessions'],
			['title' => $this->lang('Sécurité (2FA)'),     'icon' => 'fas fa-shield-alt',    'url' => 'user/security'],
			['title' => $this->lang('Mes comptes liés'),   'icon' => 'fas fa-link',          'url' => 'user/auth'],
			['title' => $this->lang('Déconnexion'),        'icon' => 'fas fa-times',         'url' => 'user/logout']
		]);

		$navigation = ['panel' => TRUE, 'links' => $navigation_links];
		return $this->widget('navigation')->output($output, $navigation);
	}

	public function _panel_infos($user = NULL)
	{
		return $this->view('infos', [
			'user' => $user ?: $this->user
		]);
	}

	private function _panel_activities($user_id = NULL)
	{
		$this->css('activities');

		if ($user_id === NULL)
		{
			$user_id = $this->user->id;
		}

		// Carrefour « activity » : chaque module expose controllers/activity.php → activity($user_id, $limit)
		$items = [];

		foreach (NeoFrag()->model2('addon')->get('module') as $module)
		{
			if ($controller = @$module->controller('activity'))
			{
				foreach ($controller->activity($user_id, 10) as $item)
				{
					$items[] = $item;
				}
			}
		}

		usort($items, function($a, $b){
			return $b['date'] <=> $a['date'];
		});

		return $this->panel()
					->heading($this->lang('Activité récente'))
					->body($this->view('activity', [
						'user_activity' => array_slice($items, 0, 15)
					]));
	}

	private function _layout($callback)
	{
		$callback($row = $this->row());

		return $this->array()
					->append($this->row($this->col($this->_panel_navigation('index'))))
					->append($row);
	}
}
