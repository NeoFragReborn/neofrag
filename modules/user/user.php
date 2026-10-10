<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\User;

use NF\NeoFrag\Addons\Module;

/**
 * @property \NF\NeoFrag\Libraries\Email $email  la messagerie du site (validation, changement d'adresse)
 */
class User extends Module
{
	use Effacement;

	protected function __info()
	{
		return [
			'title'       => $this->lang('Utilisateur'),
			'description' => $this->lang('Gestion des utilisateurs : inscription, profil, sécurité, 2FA, RGPD.'),
			'icon'        => 'fas fa-user',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			// Infrastructure : le site ne tourne pas sans lui, l'administration ne propose donc
			// pas de l'eteindre. Reprend a l'identique l'ancien Module/Widget/Theme::$core.
			'deactivatable' => FALSE,
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'admin'       => TRUE,
			'routes'      => [
				//Index
				'sessions{pages}'                            => 'sessions',
				'security'                                   => 'security',
				'security/setup'                             => 'security_setup',
				'security/codes'                             => 'security_codes',
				'security/disable'                           => 'security_disable',
				'security/export'                            => 'security_export',
				'security/delete'                            => 'security_delete',
				'privacy'                                    => 'privacy',
				'notifications{pages}'                       => 'notifications',
				'notifications/preferences'                  => 'notifications_preferences',
				'auth{pages}'                                => '_auth',
				'reglement'                                  => 'reglement',
				// La première route qui répond l'emporte : les deux mots avant le jeton, qu'il prendrait pour lui.
				'adresse/renvoyer'                           => '_adresse_renvoyer',
				'adresse/annuler'                            => '_adresse_annuler',
				'adresse/{key_id}'                           => '_adresse',
				'auth/unlink/{id}'                           => '_auth_unlink',
				'sessions/fermer-autres'                     => '_sessions_fermer_autres',
				'sessions/fermer/{key_id}'                   => '_session_fermer',
				'{id}/{url_title}'                           => '_member',
				'{id}/{url_title}/{url_title}'               => '_member',
				'ajax/{id}/{url_title}'                      => '_member',
				'ajax/lost-password/{url_title}'             => '_lost_password',
				'ajax/relais/{key_id}/{key_id}'              => '_relais',
				'ajax/tuile/{id}/{id}/{id}'                  => '_tuile',

				//Admin
				'admin{pages}'                                   => 'index',
				'admin/audit-log{pages}'                         => '_audit_log',
				'admin/export/(csv|json)'                        => '_export',
				'admin/groups/add'                               => '_groups_add',
				'admin/groups/edit/(admins|members|visitors)'    => '_groups_edit',
				'admin/groups/edit/{url_title}-{id}/{url_title}' => '_groups_edit',
				'admin/groups/edit/{id}/{url_title}'             => '_groups_edit',
				'admin/groups/delete/{id}/{url_title}'           => '_groups_delete',
				'admin/ajax/groups/sort'                         => '_groups_sort',
				'admin/fields'                                   => '_fields',
				'admin/fields/add'                               => '_fields_add',
				'admin/fields/edit/{id}/{url_title}'             => '_fields_edit',
				'admin/fields/delete/{id}/{url_title}'           => '_fields_delete',
				'admin/ajax/fields/sort'                         => '_fields_sort',
				'admin/sessions{pages}'                          => '_sessions',
				'admin/sessions/delete/{url_title}'              => '_sessions_delete',
				'admin/totp-reset/{id}/{url_title}'              => '_totp_reset',
				'admin/delete/{id}/{url_title}'               => '_delete',
				'admin/{id}/{url_title}'                       => '_edit'
			]
		];
	}

	public function __init()
	{
		// Migration MP → Talks unifié : le listener legacy `mp.reply.created` a été retiré.
		// Les notifications email pour les MP user-to-user sont maintenant gérées par
		// modules/talks/talks.php (listener `talks.message.created` filtré sur type=direct/group).

		// Le fuseau horaire du membre s'applique à l'ouverture de la session (core/session.php), et
		// non plus ici : ce __init() ne s'exécute que sur les pages du module user.
	}

	/** Le lien de validation d'une inscription reste valable deux jours : on ne lit pas toujours ses e-mails dans l'heure. */
	public const VALIDATION_DUREE = 172800;

	/**
	 * La validation de l'inscription par e-mail (*Paramètres → Inscription*) : le lien part à l'adresse du
	 * membre, qui ne peut pas se connecter avant de l'avoir ouvert. Un nouveau jeton remplace les précédents
	 * (Models\User::token()). Envoyé à l'inscription, puis de nouveau à une tentative de connexion.
	 *
	 * @return bool l'e-mail est parti
	 */
	public function envoyer_validation($user): bool
	{
		// `$this->email`, comme les autres modules (forum, modération) : l'inscription et le renvoi ont déjà
		// leurs limites de débit (par adresse IP, par membre).
		return (bool) $this	->email
							->template('user.registration', [
								'username'       => $user->username,
								// Une adresse ABSOLUE : un lien relatif se résoudrait contre le domaine du client mail.
								'validation_url' => absolute_url('user/validation/'.$user->token('validation')),
							])
							->to($user->email)
							->send();
	}

	/**
	 * Un compte qui doit encore valider son adresse : la validation est allumée, et le compte n'a jamais été
	 * ouvert (sa première connexion pose `last_activity_date`). Un compte sans adresse ne peut rien valider.
	 */
	public function a_valider($user): bool
	{
		return (bool) $this->config->nf_registration_validation && !$user->last_activity_date && (string) $user->email !== '';
	}

	/**
	 * Le mot de passe d'un membre, redemandé avant une action sensible : changer d'adresse ou de mot de passe, activer ou
	 * couper la double authentification, lier un service, supprimer son compte. Cinq erreurs en quinze minutes bloquent
	 * la confirmation de ce compte un quart d'heure (audit du 2026-10-09) : une session volée essayait sinon autant de
	 * mots de passe qu'elle voulait.
	 *
	 * @return string|null NULL s'il est juste, sinon l'erreur à afficher
	 */
	public function confirmation_refusee($user, string $saisie): ?string
	{
		$limite = new \NF\NeoFrag\Libraries\Rate_Limit($this);
		$cle    = 'confirmation:uid:'.(int) $user->id;

		if (!($etat = $limite->check($cle))['allowed'])
		{
			return (string) $this->lang('Trop de tentatives. Réessayez dans %d minute(s).', (int) ceil($etat['retry_after'] / 60));
		}

		if ($user->password($saisie))
		{
			$limite->reset($cle);

			return NULL;
		}

		$limite->hit($cle, 5, 900, 900);

		return (string) $this->lang('Mot de passe incorrect');
	}

	/**
	 * Les comptes inactifs (décision du 2026-10-09, entrée d04) : sans visite depuis `nf_comptes_inactifs_ans`
	 * années (3 par défaut, réglable dans Paramètres, 0 : jamais), un compte est effacé — l'effacement commun, celui que
	 * demande un membre. Un courriel prévient le membre un mois avant, dans sa langue ; une visite d'ici là annule tout.
	 * Jamais un administrateur ; jamais sur une démonstration. Cinquante comptes par jour au plus, de chaque côté :
	 * la tâche passe par le ménage du jour (Session::_menage_du_jour()), à la première visite.
	 */
	public function menage_des_comptes_inactifs(): void
	{
		$ans = (int) $this->config->nf_comptes_inactifs_ans;

		if ($ans <= 0 || nf_demo())
		{
			return;
		}

		$vu      = 'COALESCE(u.last_activity_date, u.registration_date)';
		$limite  = date('Y-m-d H:i:s', strtotime('-'.$ans.' years'));
		$bientot = date('Y-m-d H:i:s', strtotime('-'.$ans.' years +1 month'));

		// 1. Prévenir : la dernière visite remonte à la durée moins un mois, et le membre n'a pas été prévenu depuis.
		foreach ((array) $this->db	->select('u.id', 'u.username', 'u.email', 'UNIX_TIMESTAMP('.$vu.') AS vu')
									->from('nf_user u')
									->join('nf_user_inactivite i', 'i.user_id = u.id', 'LEFT')
									->where('u.deleted', FALSE)
									->where('u.admin', FALSE)
									->where($vu.' <', $bientot)
									->where('(i.prevenu_le IS NULL OR i.prevenu_le < '.$vu.')')
									->order_by('u.id')
									->limit(50)
									->get(FALSE) as $membre)
		{
			$efface_le = max(strtotime('+'.$ans.' years', (int) $membre['vu']), strtotime('+1 month'));

			if (filter_var((string) $membre['email'], FILTER_VALIDATE_EMAIL))
			{
				nf_dans_la_langue_du_membre((int) $membre['id'], fn () => $this->email
					->template('user.inactivite', [
						'username'      => $membre['username'],
						'date'          => nf_date($efface_le),
						'connexion_url' => absolute_url('user/login'),
					])
					->to((string) $membre['email'])
					->send());
			}

			$this->db->replace('nf_user_inactivite', ['user_id' => (int) $membre['id'], 'prevenu_le' => date('Y-m-d H:i:s')]);
		}

		// 2. Effacer : sans visite depuis la durée, prévenu après sa dernière visite, et depuis un mois au moins.
		foreach ((array) $this->db	->select('u.id', 'u.username')
									->from('nf_user u')
									->join('nf_user_inactivite i', 'i.user_id = u.id', 'INNER')
									->where('u.deleted', FALSE)
									->where('u.admin', FALSE)
									->where($vu.' <', $limite)
									->where('i.prevenu_le >= '.$vu)
									->where('i.prevenu_le <', date('Y-m-d H:i:s', strtotime('-1 month')))
									->order_by('u.id')
									->limit(50)
									->get(FALSE) as $membre)
		{
			$this->_effacer_donnees((int) $membre['id']);

			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('user.inactif_efface', ['user_id' => (int) $membre['id'], 'username' => (string) $membre['username']]);
		}
	}

	/** Le lien qui confirme une nouvelle adresse e-mail reste valable deux jours, comme celui de l'inscription. */
	public const ADRESSE_DUREE = 172800;

	/**
	 * Une nouvelle adresse e-mail demandée dans « Mon compte » (2026-10-09) : prise telle quelle, une faute de frappe
	 * coupait le membre de tout ce que le site lui envoie. Elle attend dans nf_user_email_change le clic sur le lien
	 * envoyé À ELLE (Index::_adresse()), et l'ancienne adresse est prévenue de la demande — pas d'un simple renvoi du
	 * lien. Une nouvelle demande remplace la précédente.
	 *
	 * @return bool le lien est parti
	 */
	public function demander_adresse($user, string $email, bool $prevenir = TRUE): bool
	{
		$jeton = unique_id();

		$this->db->where('user_id', (int) $user->id)->delete('nf_user_email_change');
		$this->db->insert('nf_user_email_change', ['user_id' => (int) $user->id, 'email' => $email, 'token' => $jeton]);

		$parti = (bool) $this	->email
								->template('user.email_change', [
									'username'         => $user->username,
									'email'            => $email,
									// Une adresse ABSOLUE : un lien relatif se résoudrait contre le domaine du client mail.
									'confirmation_url' => absolute_url('user/adresse/'.$jeton),
								])
								->to($email)
								->send();

		if ($prevenir && (string) $user->email !== '')
		{
			$this	->email
					->template('user.email_change_notice', ['username' => $user->username, 'email' => $email])
					->to((string) $user->email)
					->send();
		}

		return $parti;
	}

	/**
	 * La nouvelle adresse qui attend sa confirmation : `['email' => …, 'created_at' => …]`, ou NULL sans demande en
	 * cours — une demande de plus de deux jours ne compte plus (le ménage du jour l'efface, Session::_menage_du_jour()).
	 *
	 * @return array{email: string, created_at: string}|null
	 */
	public function adresse_en_attente($user): ?array
	{
		$ligne = $this->db->select('email', 'created_at')->from('nf_user_email_change')->where('user_id', (int) $user->id)->row(FALSE);

		if (!$ligne || strtotime((string) $ligne['created_at']) < time() - self::ADRESSE_DUREE)
		{
			return NULL;
		}

		return ['email' => (string) $ligne['email'], 'created_at' => (string) $ligne['created_at']];
	}

	/**
	 * La longueur minimale d'un nouveau mot de passe (ligne 0.33, 2026-10-05) : ni l'inscription, ni le
	 * changement, ni la réinitialisation n'en exigeaient — « a » passait. Les mots de passe existants restent
	 * valables ; seuls les nouveaux se mesurent.
	 */
	public const MOT_DE_PASSE_MIN = 10;

	/** Les plus courants de dix caractères ou plus, en minuscules : ils tombent dans les premiers essais d'un robot. */
	private const MOTS_DE_PASSE_COURANTS = [
		'1234567890', '0123456789', '1234512345', '1111111111', '0000000000', 'azertyuiop', 'qwertyuiop', 'aqwzsxedc',
		'motdepasse', 'motdepasse1', 'password12', 'password123', 'password1234', 'passw0rd123', 'azerty1234', 'azerty123456',
		'qwerty1234', 'qwerty123456', '123456789a', 'iloveyou123', 'soleil1234', 'football123', 'abcdefghij', 'abcd123456',
	];

	/**
	 * Pourquoi un NOUVEAU mot de passe est refusé — `court` ou `facile` —, ou NULL s'il convient. Facile : le
	 * pseudo lui-même, moins de trois caractères différents (« aaaaaaaaaa »), ou l'un des plus courants.
	 * Les formulaires (password_required, new_password) disent le motif dans la langue de la page.
	 */
	public static function mot_de_passe_refuse(string $mot_de_passe, string $pseudo = ''): ?string
	{
		if (mb_strlen($mot_de_passe) < self::MOT_DE_PASSE_MIN)
		{
			return 'court';
		}

		$minuscules = mb_strtolower($mot_de_passe);

		if (($pseudo !== '' && mb_strtolower($pseudo) === $minuscules)
			|| count(array_unique(mb_str_split($minuscules))) < 3
			|| in_array($minuscules, self::MOTS_DE_PASSE_COURANTS, TRUE))
		{
			return 'facile';
		}

		return NULL;
	}

	/**
	 * Le MENU de l'espace membre, déclaré ici une seule fois (chantier A, étape A1, 2026-10-05). Il était écrit
	 * quatre fois, avec quatre vocabulaires — « Info de connexion », « Gérer mon compte », « Connexion » pour une
	 * même page — et rendu tantôt à gauche, tantôt en barre repliée en haut, tantôt pas du tout. Le cadre de
	 * l'espace (espace()), le widget « Espace membre », la barre du haut des thèmes et le menu de la vitrine le
	 * lisent tous ; menu_compact() en garde l'essentiel pour les menus déroulants.
	 *
	 * Les modules installés y ajoutent leurs pages par une méthode espace_membre($user) de leur classe, qui rend
	 * des entrées de même forme (`ordre` les range entre elles). Une entrée : `url`, `titre`, `icone`, et au
	 * besoin `badge` (un nombre à signaler) et `compact` (montrée aussi dans les menus courts).
	 *
	 * @return array<string, list<array<string, mixed>>> les groupes « espace », « reglages », « modules », « fin »
	 */
	public function menu_espace(): array
	{
		$user = NeoFrag()->user;
		$menu = [
			'espace' => [
				['url' => 'user', 'titre' => (string) $this->lang('Mon espace'), 'icone' => 'fas fa-house-user', 'compact' => TRUE],
				['url' => 'user/'.(int) $user->id.'/'.url_title((string) $user->username), 'titre' => (string) $this->lang('Voir mon profil'), 'icone' => 'far fa-eye', 'compact' => TRUE],
			],
			'reglages' => [
				['url' => 'user/profile', 'titre' => (string) $this->lang('Modifier mon profil'), 'icone' => 'fas fa-pen', 'compact' => TRUE],
				['url' => 'user/account', 'titre' => (string) $this->lang('Mon compte'), 'icone' => 'fas fa-user-gear', 'compact' => TRUE],
				['url' => 'user/security', 'titre' => (string) $this->lang('Sécurité'), 'icone' => 'fas fa-shield-halved'],
				['url' => 'user/auth', 'titre' => (string) $this->lang('Mes comptes liés'), 'icone' => 'fas fa-link'],
				['url' => 'user/privacy', 'titre' => (string) $this->lang('Confidentialité et données'), 'icone' => 'fas fa-user-shield'],
				['url' => 'user/notifications/preferences', 'titre' => (string) $this->lang('Préférences de notifications'), 'icone' => 'fas fa-sliders'],
			],
			'modules' => [],
			'fin' => [
				// Avec le jeton de session : sans lui, la déconnexion se confirme (Index::logout()).
				['url' => 'user/logout?_='.nf_jeton_csrf(), 'titre' => (string) $this->lang('Déconnexion'), 'icone' => 'fas fa-right-from-bracket', 'compact' => TRUE],
			],
		];

		foreach (NeoFrag()->model2('addon')->get('module') as $module)
		{
			if ($module instanceof Module && $module !== $this && method_exists($module, 'espace_membre'))
			{
				foreach ((array) $module->espace_membre($user) as $entree)
				{
					if (is_array($entree) && !empty($entree['url']) && !empty($entree['titre']))
					{
						$menu['modules'][] = $entree + ['icone' => 'fas fa-circle', 'ordre' => 50];
					}
				}
			}
		}

		usort($menu['modules'], fn ($a, $b) => (int) $a['ordre'] <=> (int) $b['ordre']);

		return $menu;
	}

	/**
	 * L'essentiel du menu, pour les menus courts (barre du haut des thèmes, widget, vitrine) : les entrées
	 * marquées `compact`, dans l'ordre du menu, Déconnexion en dernier.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function menu_compact(): array
	{
		$entrees = [];

		foreach ($this->menu_espace() as $groupe)
		{
			foreach ($groupe as $entree)
			{
				if (!empty($entree['compact']))
				{
					$entrees[] = $entree;
				}
			}
		}

		return $entrees;
	}

	/**
	 * Les entrées d'un menu déroulant Bootstrap (`.dropdown-menu`), pour la barre du haut des thèmes et le menu
	 * de la vitrine : l'essentiel du menu (menu_compact()), l'administration pour qui y a accès, puis la
	 * déconnexion — sauf si le thème la montre déjà à côté du menu (`$deconnexion = FALSE`).
	 */
	public function menu_deroulant(bool $deconnexion = TRUE): string
	{
		$entree = fn (string $url, string $icone, string $titre, int $badge = 0) => '<a class="dropdown-item" href="'.url($url).'">'.icon($icone).' '.nf_texte($titre)
			.($badge ? ' <span class="badge text-bg-danger ms-1">'.$badge.'</span>' : '').'</a>';

		$html   = '';
		$sortie = NULL;

		foreach ($this->menu_compact() as $e)
		{
			if (str_starts_with((string) $e['url'], 'user/logout'))
			{
				$sortie = $e;
				continue;
			}

			$html .= $entree((string) $e['url'], (string) $e['icone'], (string) $e['titre'], (int) ($e['badge'] ?? 0));
		}

		if (NeoFrag()->access->effective_admin())
		{
			$html .= '<div class="dropdown-divider"></div>'.$entree('admin', 'fas fa-gauge-high', (string) $this->lang('Administration'));
		}

		if ($deconnexion && $sortie)
		{
			$html .= '<div class="dropdown-divider"></div>'.$entree((string) $sortie['url'], (string) $sortie['icone'], (string) $sortie['titre']);
		}

		return $html;
	}

	/**
	 * Les ONGLETS du profil public de `$membre` (chantier A, étape A2, 2026-10-05) : « À propos » et « Activité »,
	 * puis ceux que les modules installés apportent par une méthode profil_membre($membre) de leur classe — un
	 * module qui n'a rien à montrer de ce membre n'en rend aucun : pas d'onglet vide.
	 *
	 * Un onglet : `onglet` (le dernier segment de son adresse, `user/<id>/<pseudo>/<onglet>` ; vide pour le
	 * premier), `titre`, `icone`, `ordre` qui range ceux des modules entre eux, au besoin `nombre` (montré à côté
	 * du titre), et `contenu`, une fonction qui rend la page de l'onglet, appelée seulement quand il est ouvert —
	 * celle des deux premiers est dans le contrôleur.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function onglets_profil($membre): array
	{
		// Le vérificateur de l'adresse et la page les demandent tous deux : les modules ne comptent qu'une fois.
		if (isset($this->_onglets_profil[$cle = (int) $membre->id]))
		{
			return $this->_onglets_profil[$cle];
		}

		$onglets = [];

		foreach (NeoFrag()->model2('addon')->get('module') as $module)
		{
			if ($module instanceof Module && $module !== $this && method_exists($module, 'profil_membre'))
			{
				foreach ((array) $module->profil_membre($membre) as $onglet)
				{
					if (is_array($onglet) && is_string($onglet['onglet'] ?? NULL) && preg_match('/^[a-z0-9-]+$/', $onglet['onglet'])
						&& $onglet['onglet'] !== 'activite' && !empty($onglet['titre']) && is_callable($onglet['contenu'] ?? NULL))
					{
						$onglets[$onglet['onglet']] = $onglet + ['icone' => 'fas fa-circle', 'ordre' => 50];
					}
				}
			}
		}

		usort($onglets, fn ($a, $b) => (int) $a['ordre'] <=> (int) $b['ordre']);

		return $this->_onglets_profil[$cle] = array_merge([
			['onglet' => '',         'titre' => (string) $this->lang('À propos'), 'icone' => 'far fa-id-card', 'ordre' => 0],
			['onglet' => 'activite', 'titre' => (string) $this->lang('Activité'), 'icone' => 'fas fa-bolt',    'ordre' => 10],
		], $onglets);
	}

	/** @var array<int, list<array<string, mixed>>> les onglets déjà rassemblés, par membre */
	private array $_onglets_profil = [];

	/**
	 * Le cadre de l'espace membre : le menu, puis la page. Une colonne à gauche sur ordinateur, une bande
	 * d'onglets qui défile en haut au téléphone — au même endroit sur toutes les pages, celles des modules
	 * comprises (`$this->module('user')->espace($panneau, 'notifications')`). `$actif` est l'adresse de
	 * l'entrée à marquer.
	 */
	public function espace($contenu, string $actif)
	{
		$this	->css('user-space')
				->js('espace');

		$contenus = is_array($contenu) ? $contenu : [$contenu];

		return $this->row()
					->append($this	->col($this->view('espace-menu', ['menu' => $this->menu_espace(), 'actif' => $actif]))
									->size('col-12 col-lg-3 nf-espace-colonne-menu'))
					->append($this	->col(...$contenus)
									->size('col-12 col-lg-9 nf-espace-colonne-page'));
	}

	/**
	 * Le message de bienvenue (*Paramètres → Inscription*), envoyé par la messagerie au membre qui vient
	 * de s'inscrire, dans la langue de la page où il s'est inscrit.
	 *
	 * Trois défauts corrigés le 2026-10-04 : le message, écrit dans l'éditeur riche, arrivait en HTML dans
	 * une messagerie qui l'échappe — ses balises s'affichaient (il est mis en texte, Security::texte_depuis_html()) ;
	 * son titre, que le formulaire range codé (`communaut&eacute;`), s'affichait codé (il est décodé) ;
	 * et l'inscription par Discord, GitHub ou Google ne l'envoyait pas. Une erreur de la messagerie ne
	 * bloque jamais l'inscription.
	 */
	public function bienvenue(int $user_id, string $pseudo): void
	{
		$config  = $this->config;
		$auteur  = (int) $config->nf_welcome_user_id;
		$titre   = trim(html_entity_decode($config->traduit('nf_welcome_title'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		$contenu = $config->traduit('nf_welcome_content');

		if (!$config->nf_welcome || !$auteur || $titre === '' || trim(strip_tags($contenu)) === '' || $auteur === $user_id || !($talks = $this->module('talks')))
		{
			return;
		}

		try
		{
			require_once NEOFRAG_CMS.'/modules/talks/security.php';

			$texte  = \NF\Modules\Talks\Security::texte_depuis_html(str_replace('[pseudo]', '@'.$pseudo, $contenu));
			$modele = $talks->model('talks');

			if ($modele instanceof \NF\Modules\Talks\Models\Talks && ($talk_id = $modele->create_conversation($auteur, 'direct', $titre, '', [$user_id])))
			{
				$modele->send_message($talk_id, $auteur, $texte);
			}
		}
		catch (\Throwable $e)
		{
			// Silencieux pour le membre ; le journal le garde.
			nf_journaliser_erreur('bienvenue', 'message de bienvenue non envoyé : '.$e->getMessage(), $e->getFile().':'.$e->getLine());
		}
	}
}
