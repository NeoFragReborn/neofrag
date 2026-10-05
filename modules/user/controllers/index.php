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
		$this	->title($this->lang('Mon espace'))
				->icon('fas fa-house-user')
				->breadcrumb();

		return $this->_layout(function($row){
			$row->append($this	->col($this->panel()->body($this->view('espace-accueil', ['user' => $this->user])))
								->size('col-12'))
				->append($this	->col($this	->panel()
											->heading($this->lang('Messagerie'), 'far fa-envelope')
											->body($this->view('index')))
								->size('col-12 col-xl-6'))
				->append($this	->col($this->_panel_activities())
								->size('col-12 col-xl-6'));
		}, 'user');
	}

	public function security()
	{
		$this	->title($this->lang('Sécurité du compte'))
				->icon('fas fa-shield-alt')
				->breadcrumb();

		$totp_panel = $this->panel()->title($this->lang('Authentification à deux facteurs (2FA)'), 'fas fa-mobile-alt');

		if (nf_demo())
		{
			$totp_panel = $this->_panneau_demo();
		}
		else if ($this->user->totp_enabled)
		{
			$totp = new \NF\NeoFrag\Libraries\Totp_Service($this);
			$remaining = $totp->count_unused_recovery_codes($this->user->id);

			$totp_panel->body('<div class="alert alert-success"><i class="fas fa-check-circle"></i> '.$this->lang('2FA <b>activé</b> sur ton compte.').'</div><p>'.$this->lang('Codes de récupération restants : <b>%d</b> / 10', $remaining).'</p><a class="btn btn-danger" href="'.url('user/security/disable').'"><i class="fas fa-times"></i> '.$this->lang('Désactiver le 2FA').'</a>');
		}
		else
		{
			$totp_panel->body('<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> '.$this->lang('2FA <b>désactivé</b>.').'</div><p>'.$this->lang('Active le 2FA pour ajouter une couche de sécurité à ton compte. Tu auras besoin d\'une appli comme Google Authenticator, Authy ou FreeOTP.').'</p><a class="btn btn-primary" href="'.url('user/security/setup').'"><i class="fas fa-shield-alt"></i> '.$this->lang('Activer le 2FA').'</a>');
		}

		// Les connexions récentes, pour repérer un accès inattendu ; l'export et la suppression du compte
		// sont rangés dans « Confidentialité et données » depuis le chantier A (étape A1).
		$historique = $this->panel()
							->heading($this->lang('Historique des connexions'), 'fas fa-clock-rotate-left')
							->body('<p class="mb-3">'.$this->lang('Chaque connexion à ton compte : la date, l’adresse IP et le navigateur. Une connexion que tu ne reconnais pas ? Change ton mot de passe.').'</p><a class="btn btn-outline-primary" href="'.url('user/sessions').'">'.icon('fas fa-list').' '.$this->lang('Voir l’historique').'</a>');

		return $this->_layout(function($row) use ($totp_panel, $historique){
			$row->append($this->col($totp_panel, $historique)->size('col-12'));
		}, 'user/security');
	}

	/**
	 * Confidentialité et données (chantier A, étape A1) : l'export de ses données et la suppression de son
	 * compte, rangés jusqu'ici sous « Sécurité (2FA) ». Ce que le profil public montre s'y réglera (étape A2).
	 */
	public function privacy()
	{
		$this	->title($this->lang('Confidentialité et données'))
				->icon('fas fa-user-shield')
				->breadcrumb();

		$donnees = $this->panel()
						->heading($this->lang('Mes données'), 'fas fa-download')
						->body('<p>'.$this->lang('Une copie de tout ce que le site garde sur toi : ton compte, ton profil, tes connexions, tes messages et tes contributions, dans un fichier que tu peux ouvrir ou transmettre ailleurs (article 15 du RGPD).').'</p><a class="btn btn-secondary" href="'.url('user/security/export').'">'.icon('fas fa-download').' '.$this->lang('Exporter mes données (JSON)').'</a>');

		$panneaux = [$this->_formulaire_visibilite(), $donnees];

		if (!nf_demo())
		{
			$panneaux[] = $this->panel()
								->heading($this->lang('Supprimer mon compte'), 'far fa-trash-alt')
								->body('<p>'.$this->lang('Ton compte et tes données personnelles sont effacés ; tes messages restent, sous un pseudo anonyme. C’est définitif.').'</p><a class="btn btn-outline-danger" href="'.url('user/security/delete').'">'.icon('far fa-trash-alt').' '.$this->lang('Supprimer mon compte').'</a>');
		}

		return $this->_layout(function($row) use ($panneaux){
			$row->append($this->col(...$panneaux)->size('col-12'));
		}, 'user/privacy');
	}

	public function security_export()
	{
		$user = $this->user;
		$id   = (int) $user->id;

		// Le forum est un module optionnel : sur une installation qui ne l'embarque pas, ses tables
		// n'existent pas et la requete fataliserait — au beau milieu d'un export RGPD, donc sur
		// l'exercice d'un droit legal de l'utilisateur. On ne liste ses contributions que si le
		// module est effectivement installe.
		$forum = $this->db->table_exists('nf_forum_topics') && $this->db->table_exists('nf_forum_messages');

		$profil = $this->db->from('nf_user_profile')->where('id', $id)->row(FALSE);
		$profil = is_array($profil) ? $profil : [];

		// L'avatar et la couverture sont des fichiers : leur adresse, pas leur numéro.
		foreach (['avatar', 'cover'] as $image)
		{
			$chemin = !empty($profil[$image]) ? (string) $this->db->select('path')->from('nf_file')->where('id', (int) $profil[$image])->row() : '';
			$profil[$image] = $chemin !== '' ? url($chemin) : NULL;
		}

		unset($profil['id']);

		$data = [
			'export_meta' => [
				'generated_at' => date('c'),
				'site'         => $this->config->nf_name,
				'user_id'      => $user->id,
				'username'     => $user->username,
				'rgpd_notice'  => $this->lang('Cette archive contient toutes les données personnelles que ce site a collectées sur toi (Article 15 du RGPD).')
			],
			// Lu en base, en valeurs simples : la langue du modèle est un objet (son module), et son graphe
			// entier faisait échouer json_encode — l'archive partait VIDE (2026-10-05).
			'profile' => $this->db	->select('u.id', 'u.username', 'u.email', 'u.registration_date', 'u.last_activity_date', 'l.name AS language', 'u.admin', 'u.totp_enabled')
									->from('nf_user u')
									->join('nf_addon l', 'l.id = u.language', 'LEFT')
									->where('u.id', $id)
									->row(FALSE),
			// Le profil (nom, naissance, lieu, signature, liens…) manquait à l'archive jusqu'au 2026-10-05,
			// comme les comptes liés, l'historique des connexions et les notifications.
			'profil_public' => $profil,
			// Les champs definis par l'administrateur font partie des donnees personnelles : les
			// omettre rendrait l'archive incomplete au sens de l'article 15.
			'champs_personnalises' => $this->_champs_values($id),
			'comptes_lies' => $this->db	->select('ad.name AS service', 'a.key AS identifiant', 'a.username AS pseudo', 'a.avatar')
										->from('nf_user_auth a')
										->join('nf_addon ad', 'ad.id = a.authenticator_id', 'INNER')
										->where('a.user_id', $id)
										->get(),
			'historique_connexions' => $this->db	->select('date', 'ip_address', 'host_name', 'user_agent', 'referer', 'auth')
													->from('nf_session_history')
													->where('user_id', $id)
													->order_by('date DESC')
													->get(),
			// Ni le numéro de session, ni ses données : ils valent une clé d'accès, et l'archive peut traîner.
			'sessions_actives' => $this->db	->select('UNIX_TIMESTAMP(last_activity) AS last_activity')
											->from('nf_session')
											->where('user_id', $id)
											->get(FALSE),
			'notifications' => !$this->db->table_exists('nf_notifications') ? [] : $this->db	->from('nf_notifications')
																								->where('user_id', $id)
																								->get(FALSE),
			'forum_topics' => !$forum ? [] : $this->db	->select('topic_id', 'title', 'UNIX_TIMESTAMP(date) AS created_at')
														->from('nf_forum_topics')
														->where('user_id', $id)
														->get(),
			'forum_messages' => !$forum ? [] : $this->db	->select('message_id', 'topic_id', 'message', 'UNIX_TIMESTAMP(date) AS created_at')
														->from('nf_forum_messages')
														->where('user_id', $id)
														->get(),
			'comments' => $this->db	->select('comment_id', 'module', 'object_id', 'content', 'UNIX_TIMESTAMP(date) AS created_at')
									->from('nf_comment')
									->where('user_id', $id)
									->get(),
			'messages_envoyes' => $this->db	->select('t.talk_id', 't.name AS title', 't.type', 'm.content', 'UNIX_TIMESTAMP(m.date) AS sent_at')
											->from('nf_talks_messages m')
											->join('nf_talks t', 't.talk_id = m.talk_id')
											->where('m.user_id', $id)
											->where('m.deleted_at', NULL)
											->get(),
			'cookie_consent' => $this->db	->select('consent_essentials', 'consent_analytics', 'consent_marketing', 'UNIX_TIMESTAMP(created_at) AS at')
											->from('nf_cookie_consent')
											->where('user_id', $id)
											->get()
		];

		$data['autres_donnees'] = $this->_autres_donnees($id);

		// Le site range ses textes codés (« &eacute; ») : l'archive se lit en clair.
		array_walk_recursive($data, function(&$valeur){
			if (is_string($valeur))
			{
				$valeur = nf_texte_brut($valeur);
			}
		});

		// Une valeur que JSON ne sait pas écrire devient `null` au lieu de vider toute l'archive ; le journal le dit.
		$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);

		if (json_last_error() !== JSON_ERROR_NONE)
		{
			nf_journaliser_erreur('export', 'export des données incomplet : '.json_last_error_msg(), __FILE__.':'.__LINE__);
		}
		$filename = 'neofrag-export-'.url_title((string) $user->username).'-'.date('Ymd-His').'.json';

		header('Content-Type: application/json; charset=utf-8');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Length: '.strlen((string) $json));
		echo $json;
		exit;
	}

	/**
	 * Tout ce que les autres tables gardent au nom du membre : chaque table du site qui a une colonne
	 * « user_id » (celles des modules installés, d'hier comme de demain), hors celles que l'archive
	 * présente déjà et celles qui ne gardent que des secrets. Les colonnes qui portent un secret (mot de
	 * passe, jeton, empreinte) sont retirées. Au-delà de 10 000 lignes, la table le dit.
	 *
	 * @return array<string, mixed>
	 */
	private function _autres_donnees(int $user_id): array
	{
		$deja   = ['nf_user_auth', 'nf_session', 'nf_session_history', 'nf_notifications', 'nf_user_fields_values', 'nf_forum_messages', 'nf_comment', 'nf_talks_messages', 'nf_cookie_consent', 'nf_user_token', 'nf_user_totp_recovery'];
		$autres = [];

		foreach ($this->db->tables() as $table)
		{
			$colonnes = array_keys((array) $this->db->table_columns($table));

			if (in_array($table, $deja, TRUE) || !in_array('user_id', $colonnes, TRUE))
			{
				continue;
			}

			$gardees = array_values(array_filter($colonnes, fn ($c) => !preg_match('/pass|token|secret|hash|salt/i', (string) $c)));

			// L'export sert un droit légal : une table qu'on ne sait pas lire est signalée, jamais fatale.
			try
			{
				$lignes = (array) $this->db->select(...$gardees)->from($table)->where('user_id', $user_id)->limit(10001)->get(FALSE);
			}
			catch (\Throwable $e)
			{
				nf_journaliser_erreur('export', 'table '.$table.' illisible pour l’export : '.$e->getMessage(), $e->getFile().':'.$e->getLine());
				$autres[substr((string) $table, 3)] = ['erreur' => 'lecture impossible'];
				continue;
			}

			if ($lignes)
			{
				$autres[substr((string) $table, 3)] = count($lignes) > 10000 ? ['tronque' => TRUE, 'lignes' => array_slice($lignes, 0, 10000)] : $lignes;
			}
		}

		return $autres;
	}

	public function security_delete()
	{
		if (nf_demo())
		{
			notify($this->lang('Sur ce site de démonstration, le compte partagé ne se supprime pas.'), 'info');
			redirect('user/security');
		}

		// Le mot à recopier se traduit comme le reste : on ne demande pas « SUPPRIMER » à un Anglais.
		$mot  = (string) $this->lang('SUPPRIMER');
		$sans = $this->_sans_mot_de_passe();

		$this	->title($this->lang('Supprimer mon compte'))
				->icon('fas fa-trash-alt')
				->breadcrumb();

		// Sans mot de passe à taper, l'identité se confirme par le service relié (ligne 0.32) : le membre ne
		// pouvait jusqu'ici exercer son droit à l'effacement qu'en passant par un administrateur.
		if ($sans && !$this->_confirmation_recente())
		{
			$panneau = $this->_panneau_confirmation('user/security/delete', $this->lang('Pour supprimer ton compte, confirme d’abord que c’est bien toi.'));

			return $this->_layout(function($row) use ($panneau){
				$row->append($this->col($panneau)->size('col-12'));
			}, 'user/privacy');
		}

		$regles = [
			'confirm_text' => [
				'label' => $this->lang('Tape « %s » pour confirmer', $mot),
				'type'  => 'text',
				'rules' => 'required'
			]
		];

		if (!$sans)
		{
			$regles['password'] = [
				'label' => $this->lang('Confirme avec ton mot de passe'),
				'type'  => 'password',
				'rules' => 'required'
			];
		}

		$this	->form()
				->add_rules($regles)
				->add_submit($this->lang('Supprimer définitivement mon compte'), 'fas fa-trash');

		if ($this->form()->is_valid($post))
		{
			if ($post['confirm_text'] !== $mot)
			{
				$this->form()->error($this->lang('Tape exactement « %s » (en majuscules) pour confirmer.', $mot));
			}
			else if (!$sans && !$this->user->password($post['password']))
			{
				$this->form()->error($this->lang('Mot de passe incorrect.'));
			}
			else
			{
				$user_id  = (int) $this->user->id;
				$username = (string) $this->user->username;

				$this->_effacer_compte($user_id);

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
								->size('col-12')
			);
		}, 'user/privacy');
	}

	/**
	 * Ce que la suppression d'un compte efface, demandée par son membre (2026-10-05). Elle promettait
	 * « tes posts resteront mais seront anonymisés » et ne retirait que l'adresse : le pseudo restait sur
	 * chaque message, le profil (nom, naissance, lieu, signature, liens), les comptes liés, l'historique
	 * des connexions et les notifications restaient en base — et un compte Discord lié ne pouvait plus
	 * jamais servir à se réinscrire.
	 *
	 * Ce qui reste : ce que le membre a publié (sous un pseudo neutre, `supprime-<id>`), les journaux de
	 * sécurité et les consentements, qui se gardent pour prouver. Une suppression faite par un
	 * administrateur (Models\User::delete()) ne passe pas ici : elle ne fait que fermer le compte.
	 */
	private function _effacer_compte(int $user_id): void
	{
		// Les comptes externes liés : leur clé identifie la personne chez le service. Le bot Discord du site
		// apprend la déliaison et retire les rôles du membre.
		foreach ((array) $this->db->select('a.key', 'ad.name')->from('nf_user_auth a')->join('nf_addon ad', 'ad.id = a.authenticator_id', 'INNER')->where('a.user_id', $user_id)->get() as $lien)
		{
			$this->_compte_externe_change('unlinked', (string) $lien['name'], $user_id, (string) $lien['key']);
		}

		$profil = $this->db->select('avatar', 'cover')->from('nf_user_profile')->where('id', $user_id)->row(FALSE);

		// Vides, ou NULL pour les colonnes qui l'admettent (la signature et les textes ne l'admettent pas).
		$this->db	->where('id', $user_id)
					->update('nf_user_profile', array_fill_keys(['first_name', 'last_name', 'signature', 'country', 'timezone', 'location', 'quote', 'website', 'linkedin', 'github', 'instagram', 'twitch'], '') + array_fill_keys(['avatar', 'cover', 'date_of_birth', 'sex'], NULL)
						// Un compte effacé ne montre plus rien de lui.
						+ array_fill_keys(['montrer_points', 'montrer_karma', 'montrer_vip', 'montrer_age', 'montrer_statut'], 0));

		foreach (is_array($profil) ? array_filter([(int) ($profil['avatar'] ?? 0), (int) ($profil['cover'] ?? 0)]) : [] as $fichier)
		{
			$this->model2('file', $fichier)->delete();
		}

		foreach (['nf_user_auth', 'nf_session_history', 'nf_user_totp_recovery', 'nf_user_token', 'nf_user_fields_values', 'nf_notifications', 'nf_users_roles', 'nf_users_groups'] as $table)
		{
			if ($this->db->table_exists($table))
			{
				$this->db->where('user_id', $user_id)->delete($table);
			}
		}

		// Ses sessions sur les autres appareils sont fermées ; celle-ci est déconnectée, et non effacée :
		// effacée, elle emportait le message « Ton compte a été supprimé », qui ne s'affichait jamais.
		$this->db->where('user_id', $user_id)->where('id <>', (string) $this->session->id)->delete('nf_session');
		$this->session->logout();

		$this->db	->where('id', $user_id)
					->update('nf_user', [
						'username'     => 'supprime-'.$user_id,
						'email'        => 'deleted-'.$user_id.'@deleted.local',
						'password'     => '',
						'salt'         => '',
						'data'         => '',
						'totp_secret'  => NULL,
						'totp_enabled' => 0,
						'deleted'      => '1',
					]);
	}

	public function security_setup()
	{
		// Sur une démonstration, un visiteur qui activait la double authentification du compte partagé
		// en fermait l'accès à tous les autres jusqu'à la remise à zéro.
		if ($this->user->totp_enabled || nf_demo())
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
								->size('col-12')
			);
		}, 'user/security');
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
			$row->append($this->col($this->panel()->title($this->lang('Codes de récupération 2FA'), 'fas fa-key')->body($body))->size('col-12'));
		}, 'user/security');
	}

	public function security_disable()
	{
		if (!$this->user->totp_enabled || nf_demo())
		{
			redirect('user/security');
		}

		$sans = $this->_sans_mot_de_passe();

		$this	->title($this->lang('Désactiver le 2FA'))
				->icon('fas fa-shield-alt')
				->breadcrumb();

		// Un compte sans mot de passe confirme par son service (ligne 0.32) : il ne pouvait plus couper le 2FA.
		if ($sans && !$this->_confirmation_recente())
		{
			$panneau = $this->_panneau_confirmation('user/security/disable', $this->lang('Pour désactiver la double authentification, confirme d’abord que c’est bien toi.'));

			return $this->_layout(function($row) use ($panneau){
				$row->append($this->col($panneau)->size('col-12'));
			}, 'user/security');
		}

		$this	->form()
				->add_rules($sans ? [] : [
					'password' => [
						'label' => $this->lang('Confirme avec ton mot de passe'),
						'type'  => 'password',
						'rules' => 'required'
					]
				])
				->add_submit($this->lang('Désactiver le 2FA'), 'fas fa-unlock');

		if ($this->form()->is_valid($post))
		{
			if (!$sans && !$this->user->password($post['password']))
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
								->size('col-12')
			);
		}, 'user/security');
	}

	public function account()
	{
		// Le titre suit le menu (User::menu_espace()) : la même page s'appelait « Connexion » ici, « Info de
		// connexion » dans un menu et « Gérer mon compte » dans un autre.
		$this	->title($this->lang('Mon compte'))
				->icon('fas fa-user-gear')
				->breadcrumb();

		$sans_mot_de_passe = $this->_sans_mot_de_passe();

		if (nf_demo())
		{
			$contenu = $this->_panneau_demo();
		}
		// Un compte sans mot de passe ne peut pas taper « l'actuel » : il confirme son identité par son service.
		else if ($sans_mot_de_passe && !$this->_confirmation_recente())
		{
			$contenu = $this->_panneau_confirmation('user/account', $this->lang('Pour changer ton identifiant, ton adresse ou créer un mot de passe, confirme d’abord que c’est bien toi.'));
		}
		else
		{
			$contenu = $this->_formulaire_compte($sans_mot_de_passe);
		}

		// La langue et le fuseau horaire, rangés ici depuis le chantier A (le fuseau était au milieu du profil
		// public) ; sur une démonstration, le compte partagé garde les siens.
		return $this->_layout(function($row) use ($contenu){
			$row->append($this->col($contenu)->size('col-12'));

			if (!nf_demo())
			{
				$row->append($this->col($this->_formulaire_langue())->size('col-12 col-xl-6'))
					->append($this->col($this->_formulaire_fuseau())->size('col-12 col-xl-6'));
			}
		}, 'user/account');
	}

	/**
	 * La langue du membre : celle des pages et des e-mails que le site lui envoie. Elle ne se choisissait
	 * que par le sélecteur du site ; elle s'enregistre de même (settings, Ajax::languages()), et la page
	 * revient dans la langue choisie.
	 */
	private function _formulaire_langue()
	{
		$choix = [];

		foreach ($this->config->langs as $langue)
		{
			$choix[(string) $langue->info()->name] = [(string) $langue->info()->title];
		}

		return $this	->form2()
						->rule($this->form_select('langue')
									->title($this->lang('Langue du site'))
									->data($choix)
									->value((string) $this->config->lang->info()->name)
									->required()
						)
						->success(function($data){
							foreach ($this->config->langs as $langue)
							{
								if ((string) $langue->info()->name === (string) $data['langue'])
								{
									$this->user->set('language', $langue->__addon)->update();
									$this->url->redirect_http($this->url->base.$langue->info()->name.'/user/account');
								}
							}

							refresh();
						})
						->submit($this->lang('Enregistrer'))
						->panel()
						->title($this->lang('Langue'), 'fas fa-language');
	}

	/**
	 * Ce que mon profil montre aux autres (chantier A, étape A2, 2026-10-05) : mes points, mon karma et mes jours
	 * de VIP me sont réservés tant que je ne les montre pas ; mon âge et ma présence en ligne sont montrés tant que
	 * je ne les cache pas (Models\User::MONTRE_PAR_DEFAUT). Mon rang et mes badges restent publics.
	 */
	private function _formulaire_visibilite()
	{
		$choix = [];

		if ($this->module('gamification'))
		{
			$choix['points'] = $this->lang('Mes points');
			$choix['karma']  = $this->lang('Mon karma — mon rang, lui, reste visible');
			$choix['vip']    = $this->lang('Mes jours de VIP');
		}

		$choix['age']    = $this->lang('Mon âge');
		$choix['statut'] = $this->lang('Quand je suis en ligne, et ma dernière visite');

		return $this	->form2()
						->rule($this->form_checkbox('montrer')
									->title($this->lang('Montrer aux autres'))
									->data($choix)
									->inline(FALSE)
									->value(array_values(array_filter(array_keys($choix), fn ($quoi) => $this->user->montre_aux_autres($quoi))))
						)
						->success(function($data) use ($choix){
							$profil  = $this->user->profile();
							$montrer = (array) ($data['montrer'] ?? []);

							foreach (array_keys($choix) as $quoi)
							{
								$profil->set('montrer_'.$quoi, in_array($quoi, $montrer, TRUE));
							}

							$profil->commit();
							notify($this->lang('Ce que ton profil montre est enregistré.'));
							refresh();
						})
						->submit($this->lang('Enregistrer'))
						->panel()
						->title($this->lang('Ce que mon profil montre'), 'far fa-eye');
	}

	/** Le fuseau horaire du membre : les dates du site s'affichent à son heure (nf_fuseau()). */
	private function _formulaire_fuseau()
	{
		return $this	->form2('fuseau', $this->user->profile())
						->submit($this->lang('Enregistrer'))
						->panel()
						->title($this->lang('Fuseau horaire'), 'far fa-clock');
	}

	/** Le formulaire du compte : identifiant, mot de passe, adresse — sans « mot de passe actuel » pour qui n'en a pas. */
	private function _formulaire_compte(bool $sans_mot_de_passe)
	{
		return $this	->form2($sans_mot_de_passe ? 'username new_password email' : 'username current_password new_password email', $this->user)
						->success(function($user){
							if ($user->password_new)
							{
								$user->set_password($user->password_new);
							}
							else
							{
								$user->reset('password');
							}

							// La validation par e-mail porte sur l'INSCRIPTION (User::a_valider()). Confirmer une
							// nouvelle adresse — la garder en attente jusqu'au clic sur un lien — demande de la ranger
							// à part : une suite notée au tableau de bord, pas une branche vide ici.
							$user->update();

							notify($this->lang('Informations modifiées'));

							refresh();
						})
						->submit($this->lang('Modifier'))
						->panel()
						->title($this->lang('Identifiant, adresse et mot de passe'), 'fas fa-key');
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
		$this	->title($this->lang('Modifier mon profil'))
				->icon('fas fa-pen')
				->breadcrumb();

		return $this->_layout(function($row){
			$row->append($this	->col()
								->size('col-12 col-xl-7')
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
								->size('col-12 col-xl-5')
								->append($this	->form2('avatar', $this->user->profile())
												->panel()
												->title($this->lang('Avatar'), 'fas fa-user-circle')
								)
								->append($this	->form2('cover', $this->user->profile())
												->panel()
												->title($this->lang('Photo de couverture'), 'far fa-image')
								)
				);
		}, 'user/profile');
	}

	public function sessions($sessions)
	{
		$this	->title($this->lang('Historique des connexions'))
				->icon('fas fa-clock-rotate-left')
				->breadcrumb();

		return $this->_layout(function($row) use ($sessions){
			$row->append($this->col($this	->table2('session_history', $sessions, $this->lang('Aucun historique'))
											->panel())
								->size('col-12'));
		}, 'user/security');
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

			$auth = $this->collection('auth')->where('authenticator_id', $authenticator->__addon->id)->where('key', $data['id'])->row();

			// Le lien d'un membre SUPPRIMÉ est périmé : la suppression efface les comptes liés depuis le
			// 2026-10-05, mais pas avant. On l'efface, et ce compte externe redevient libre — sans quoi il
			// retombait sur le compte supprimé, refusé, et ne pouvait plus jamais servir à s'inscrire.
			if ($auth && $auth->key == $data['id'] && (!$auth->user->id || $auth->user->deleted))
			{
				$this->db->where('id', (int) $auth->id)->delete('nf_user_auth');
				$auth = $this->model2('auth');
			}

			if ($auth && $auth->key == $data['id'])
			{
				// Connecté, et ce compte externe appartient à un AUTRE membre : on refuse. On basculait
				// jusqu'ici la session sur cet autre membre — lier son Discord connectait alors au
				// compte de quelqu'un d'autre (relevé le 2026-10-01).
				if ($this->user() && $this->user->id != $auth->user->id)
				{
					notify($this->lang('Ce compte %s est déjà lié à un autre membre.', $authenticator->info()->title), 'danger');
					redirect('user/auth');
				}
				// Connecté, et c'est SON compte : une confirmation d'identité, demandée par une action sensible
				// d'un compte sans mot de passe (_confirmation_recente()). On revient à la page qui l'a demandée.
				else if ($this->user())
				{
					$this->_noter_confirmation((int) $this->user->id);
					notify($this->lang('Identité confirmée avec %s.', $authenticator->info()->title));
					redirect($this->_retour_confirmation());
				}
				else
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

						// Se connecter par le service vaut confirmation d'identité pour les dix minutes qui suivent.
						$this->_noter_confirmation((int) $user->id);
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
				// Le site a un règlement : il s'accepte AVANT que le compte soit créé, comme par le formulaire
				// d'inscription. Le compte externe créait le membre aussitôt, sans le montrer (2026-10-04).
				if ($this->_reglement_a_accepter())
				{
					$this->session->set('inscription_externe', 'authenticator', (string) $authenticator->info()->name);
					$this->session->set('inscription_externe', 'data', $data);
					$this->session->set('inscription_externe', 'expire', time() + 900);

					redirect('user/reglement');
				}

				$this->_inscription_externe($authenticator, $data);
			}
			else
			{
				notify($this->lang('Aucun membre n’a lié ce compte %s, et les inscriptions sont fermées.', $authenticator->info()->title), 'danger');
			}

			redirect();
		}

		// Une confirmation d'identité : on reviendra à la page qui l'a demandée (une page de l'espace membre).
		if ($this->user() && is_string($retour = $_GET['retour'] ?? NULL) && $this->_retour_valide($retour))
		{
			$this->session->set('confirmation_externe', 'retour', $retour);
		}

		$this->url->redirect($provider->makeAuthUrl());
	}

	/**
	 * Un compte inscrit par Discord, GitHub ou Google n'a pas de mot de passe (ligne 0.32, 2026-10-05) :
	 * il ne peut pas confirmer une action sensible en le tapant — changer d'identifiant, d'adresse, créer un
	 * mot de passe, couper la double authentification, supprimer son compte —, et il en était empêché. Il la
	 * confirme en repassant par le service qui lui est relié, comme le « mode sudo » d'autres sites : la
	 * confirmation vaut dix minutes, et se connecter par ce service en est une.
	 */
	private const CONFIRMATION_DUREE = 600;

	private function _sans_mot_de_passe(): bool
	{
		return (string) $this->user->password === '';
	}

	private function _noter_confirmation(int $user_id): void
	{
		$this->session->set('confirmation_externe', 'user_id', $user_id);
		$this->session->set('confirmation_externe', 'at', time());
	}

	private function _confirmation_recente(): bool
	{
		return $this->user()
			&& (int) $this->session('confirmation_externe', 'user_id') === (int) $this->user->id
			&& (int) $this->session('confirmation_externe', 'at') >= time() - self::CONFIRMATION_DUREE;
	}

	/** La page où revenir après une confirmation : une page de l'espace membre, et rien d'autre. */
	private function _retour_valide(string $retour): bool
	{
		return (bool) preg_match('#^user(/[a-z0-9_-]+)*$#', $retour);
	}

	private function _retour_confirmation(): string
	{
		$retour = (string) $this->session('confirmation_externe', 'retour');
		$this->session->destroy('confirmation_externe', 'retour');

		return $this->_retour_valide($retour) ? $retour : 'user/account';
	}

	/**
	 * Le panneau qui demande à un compte sans mot de passe de confirmer son identité par un service relié
	 * avant `$retour`. Sans service disponible (ses clés retirées par l'administrateur), il le dit.
	 */
	private function _panneau_confirmation(string $retour, \Stringable|string $pourquoi)
	{
		// $this->lang() rend un objet de traduction, pas une chaîne : sous strict_types, le typer `string`
		// faisait tomber la page (vu à l'épreuve de l'atelier).
		$pourquoi = (string) $pourquoi;

		$boutons = [];

		foreach ((array) $this->db	->select('ad.name')
									->from('nf_user_auth a')
									->join('nf_addon ad', 'ad.id = a.authenticator_id', 'INNER')
									->where('a.user_id', (int) $this->user->id)
									->order_by('a.id')
									->get() as $nom)
		{
			if (($a = \NF\NeoFrag\Addons\Authenticator::__load(\NeoFrag(), [(string) $nom])) instanceof \NF\NeoFrag\Addons\Authenticator && $a->is_setup())
			{
				$boutons[] = '<a class="btn btn-primary" href="'.url('user/auth/'.url_title((string) $nom)).'?retour='.rawurlencode($retour).'">'.icon((string) $a->info()->icon).' '.$this->lang('Confirmer avec %s', nf_texte($a->info()->title)).'</a>';
			}
		}

		$corps = '<p>'.$pourquoi.'</p><p class="text-muted">'.$this->lang('Ton compte n’a pas encore de mot de passe : tu te connectes par un service relié. Confirme que c’est bien toi en repassant par lui ; la confirmation vaut dix minutes.').'</p>';

		$corps .= $boutons
			? '<div class="d-flex flex-wrap gap-2">'.implode('', $boutons).'</div>'
			: '<div class="alert alert-warning mb-0">'.$this->lang('Aucun des services reliés à ton compte n’est disponible en ce moment : un administrateur du site peut t’aider.').'</div>';

		return $this->panel()
					->heading($this->lang('Confirme ton identité'), 'fas fa-user-shield')
					->body($corps);
	}

	/** Sur une démonstration, le compte partagé ne se modifie pas : un visiteur le fermait aux autres. */
	private function _panneau_demo()
	{
		return $this->panel()
					->heading($this->lang('Site de démonstration'), 'fas fa-lock')
					->body('<div class="alert alert-info mb-0">'.$this->lang('Sur ce site de démonstration, le compte est partagé par tous les visiteurs : son identifiant, son adresse, son mot de passe et sa sécurité ne se modifient pas, et il ne se supprime pas.').'</div>');
	}

	/** Le site a-t-il un règlement à faire accepter à l'inscription ? (même règle que le formulaire, ajax.php) */
	private function _reglement_a_accepter(): bool
	{
		return trim(strip_tags((string) $this->config->traduit('nf_registration_charte'))) !== '';
	}

	/**
	 * `user/reglement` — l'inscription par un compte externe, quand le site a un règlement : on le montre,
	 * et le compte n'est créé qu'une fois la case cochée (le contrôle d'accès a validé l'attente, rangée en
	 * session pour un quart d'heure au retour du connecteur).
	 */
	public function reglement($authenticator, array $data)
	{
		$this	->title($this->lang('Règlement'))
				->icon('fas fa-file-contract')
				->breadcrumb()
				->form()
				->add_rules([
					'reglement' => [
						'type'   => 'checkbox',
						'values' => ['on' => $this->lang('J\'ai lu le règlement et je l\'accepte')],
						'rules'  => 'required',
					],
				])
				->add_submit($this->lang('Créer mon compte'), 'fas fa-user-plus');

		if ($this->form()->is_valid())
		{
			$this->session->destroy('inscription_externe');

			// Le règlement a pu être retiré entre-temps : rien de plus à demander, le compte se crée.
			$this->_inscription_externe($authenticator, $data);

			redirect();
		}

		$intro = '<p>'.$this->lang('Avant de créer votre compte avec %s, lisez le règlement du site.', nf_texte($authenticator->info()->title)).'</p>'
			.'<div class="card card-body mb-3">'.bbcode($this->config->traduit('nf_registration_charte')).'</div>';

		// Sans _layout() : son menu est celui d'un compte, et le visiteur n'en a pas encore.
		return $this->row($this	->col()
								->append($this	->panel()
												->heading()
												->body($intro.$this->form()->display())
								)
								->size('col-12 col-lg-8 mx-auto')
		);
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
		$this->_noter_confirmation((int) $user->id);

		// Le message de bienvenue, comme pour une inscription par le formulaire : il ne partait pas (2026-10-04).
		if (($module_user = $this->module('user')) instanceof \NF\Modules\User\User)
		{
			$module_user->bienvenue((int) $user->id, $nom);
		}

		notify($this->lang('Votre compte a été créé avec %s, bienvenue ! Ajoutez une adresse e-mail et un mot de passe dans « Mon compte » pour pouvoir aussi vous connecter sans lui.', $authenticator->info()->title));
	}

	/**
	 * Mes comptes liés : ceux que j'ai liés, à délier, et ceux du site que je peux lier.
	 * Cette page rendait jusqu'ici le texte brut « auth ».
	 */
	public function _auth($auths)
	{
		// pagination : une ligne par fournisseur d'identité installé, la liste tient toujours sur une page.
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

		$panneau = $this->panel()
						->heading($this->lang('Mes comptes liés'), 'fas fa-link')
						->body($this->view('auth', [
							'lignes'       => $lignes,
							'a_lier'       => $fournisseurs,
							'sans_secours' => (string) $this->user->password === '',
						]));

		return $this->_layout(function($row) use ($panneau){
			$row->append($this->col($panneau)->size('col-12'));
		}, 'user/auth');
	}

	/** Délier un compte externe — sauf s'il est le seul moyen de se connecter. */
	public function _auth_unlink($lien)
	{
		$this->check_csrf('user/auth');

		$autres = (int) $this->db->select('COUNT(*)')->from('nf_user_auth')->where('user_id', (int) $this->user->id)->where('id <>', (int) $lien['id'])->row();

		if ((string) $this->user->password === '' && !$autres)
		{
			notify($this->lang('Ce compte est votre seul moyen de connexion : créez d’abord un mot de passe dans « Mon compte ».'), 'danger');
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

	/**
	 * `user/validation/{jeton}` — le lien de l'e-mail de validation : l'adresse est prouvée, le compte est
	 * ouvert et le membre connecté (le contrôle d'accès a vérifié le jeton). Mêmes gardes que le lien de
	 * mot de passe oublié : ni un compte banni, ni un compte à double authentification sans son code.
	 */
	public function validation($token)
	{
		$user = $token->delete()->user;

		// La première activité est ce qui marque un compte validé (Models\User, User::a_valider()).
		$user->set('last_activity_date', NeoFrag()->date())->update();

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('user.validated', ['user_id' => (int) $user->id, 'username' => $user->username]);

		if ($this->moderation->is_banned((int) $user->id, 'global'))
		{
			$msg = $this->moderation->block_message_for_user((int) $user->id, 'global');
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
			notify($this->lang('Votre adresse est validée : bienvenue !'));
		}

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

	/**
	 * Le PROFIL PUBLIC d'un membre (chantier A, étape A2, 2026-10-05) : sa couverture en bannière, son avatar qui
	 * la chevauche, son pseudo, son rang et sa présence ; « Contacter » et « Signaler », ou « Modifier mon profil »
	 * si c'est le mien ; puis des onglets — À propos, Activité, et ceux des modules installés
	 * (User::onglets_profil()), chacun à son adresse. Il reprenait la carte de l'espace privé : la couverture n'y
	 * paraissait pas, « Voir le profil » y menait à lui-même, dates et groupes y figuraient deux fois.
	 */
	public function _member($user, $onglet = '')
	{
		// Le profil d'un membre ne s'indexe pas : peu de texte, et un membre n'a pas à se retrouver dans
		// un moteur de recherche sans l'avoir choisi. Ses liens, eux, se suivent.
		$this->output->data->set('module', 'robots', 'noindex, follow');

		$onglets = $this->module->onglets_profil($user);
		$actif   = current(array_filter($onglets, fn ($o) => $o['onglet'] === $onglet));

		$this->css('membre');

		$this	->title($onglet === '' ? $user->username : $user->username.' — '.$actif['titre'])
				->breadcrumb($this->lang('Profil'))
				->breadcrumb($user->username, 'user/'.(int) $user->id.'/'.url_title((string) $user->username));

		if ($onglet !== '')
		{
			$this->breadcrumb($actif['titre']);
		}

		if ($onglet === 'activite')
		{
			$this->css('activities');
		}

		return $this->view('membre', [
			'user'    => $user,
			'onglets' => $onglets,
			'actif'   => $onglet,
			'contenu' => match ($onglet) {
				''         => $this->view('membre-a-propos', ['user' => $user, 'chiffres' => $this->_chiffres($user)]),
				'activite' => $this->view('activity', ['user_activity' => $this->_activites((int) $user->id, 30)]),
				default    => ($actif['contenu'])($user),
			},
		]);
	}

	/**
	 * Les chiffres de la rubrique « En chiffres » : l'inscription, la dernière visite, puis le nombre que chaque
	 * onglet de module annonce (`nombre`) — et, s'il les montre, les points et le karma.
	 *
	 * @return list<array{icone: string, titre: string, valeur: string, html?: string, prive?: bool}>
	 */
	private function _chiffres($user): array
	{
		$chiffres = [];

		if ($user->registration_date)
		{
			$chiffres[] = ['icone' => 'far fa-calendar-plus', 'titre' => (string) $this->lang('Inscrit le'), 'valeur' => timetostr($this->lang('d/m/Y'), $user->registration_date)];
		}

		if ($user->last_activity_date && $user->montre('statut'))
		{
			// time_span() rend une balise <time> : elle passe telle quelle (`html`), le reste est du texte.
			$chiffres[] = ['icone' => 'far fa-clock', 'titre' => (string) $this->lang('Dernière visite'), 'valeur' => '', 'html' => time_span($user->last_activity_date), 'prive' => !$user->montre_aux_autres('statut')];
		}

		foreach ($this->module->onglets_profil($user) as $onglet)
		{
			if (isset($onglet['nombre']))
			{
				$chiffres[] = ['icone' => (string) $onglet['icone'], 'titre' => (string) $onglet['titre'], 'valeur' => (string) (int) $onglet['nombre']];
			}
		}

		// couplage: la gamification est facultative — module() rend NULL sans elle, et instanceof la garde.
		if (($gamification = $this->module('gamification')) instanceof \NF\Modules\Gamification\Gamification)
		{
			if ($user->montre('points'))
			{
				$chiffres[] = ['icone' => 'fas fa-coins', 'titre' => (string) $this->lang('Points'), 'valeur' => (string) $gamification->get_points($user->id), 'prive' => !$user->montre_aux_autres('points')];
			}

			if ($user->montre('karma'))
			{
				$chiffres[] = ['icone' => 'fas fa-star', 'titre' => (string) $this->lang('Karma'), 'valeur' => (string) $gamification->get($user->id), 'prive' => !$user->montre_aux_autres('karma')];
			}
		}

		return $chiffres;
	}

	/**
	 * L'activité récente d'un membre, du plus récent au plus ancien : le carrefour « activity », où chaque module
	 * expose controllers/activity.php → activity($user_id, $limit), chacun selon ses droits de lecture.
	 *
	 * @return list<array<string, mixed>>
	 */
	private function _activites(int $user_id, int $nombre): array
	{
		$items = [];

		foreach (NeoFrag()->model2('addon')->get('module') as $module)
		{
			if ($controller = @$module->controller('activity'))
			{
				foreach ($controller->activity($user_id, $nombre) as $item)
				{
					$items[] = $item;
				}
			}
		}

		usort($items, fn ($a, $b) => $b['date'] <=> $a['date']);

		return array_slice($items, 0, $nombre);
	}

	private function _panel_activities($user_id = NULL)
	{
		$this->css('activities');

		return $this->panel()
					->heading($this->lang('Activité récente'))
					->body($this->view('activity', [
						'user_activity' => $this->_activites((int) ($user_id ?? $this->user->id), 15)
					]));
	}

	/**
	 * Le cadre de l'espace membre autour de la page que `$callback` remplit (User::espace()) : le même menu, au
	 * même endroit, sur toutes les pages. `$actif` est l'adresse de l'entrée du menu à marquer.
	 */
	private function _layout(callable $callback, string $actif)
	{
		$callback($row = $this->row());

		return $this->module->espace($row, $actif);
	}
}
