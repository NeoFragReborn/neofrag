<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\User;

use NF\NeoFrag\Addons\Module;

class User extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Utilisateur'),
			'description' => $this->lang('Gestion des utilisateurs : inscription, profil, sécurité, 2FA, RGPD.'),
			'icon'        => 'fas fa-user',
			'link'        => 'https://neofr.ag',
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
				'auth{pages}'                                => '_auth',
				'reglement'                                  => 'reglement',
				'auth/unlink/{id}'                           => '_auth_unlink',
				'sessions/delete/{key_id}'                   => '_session_delete',
				'{id}/{url_title}'                           => '_member',
				'ajax/{id}/{url_title}'                      => '_member',
				'ajax/lost-password/{url_title}'             => '_lost_password',

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
								'validation_url' => absolute_url('user/validation/'.$user->token()),
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
			],
			'modules' => [],
			'fin' => [
				['url' => 'user/logout', 'titre' => (string) $this->lang('Déconnexion'), 'icone' => 'fas fa-right-from-bracket', 'compact' => TRUE],
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
			if ($e['url'] === 'user/logout')
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
