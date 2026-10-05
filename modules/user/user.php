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
