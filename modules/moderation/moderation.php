<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Module Modération — système de modération étendu site-wide.
 * Phase 2 (2026-05-04) : module admin avec dashboard / reports / sanctions / users / settings.
 *
 * Frontend user-side (modal "Signaler") sera intégré en Phase 3.
 */

namespace NF\Modules\Moderation;

use NF\NeoFrag\Addons\Module;

class Moderation extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Modération'),
			'description' => $this->lang('Système de modération étendu : signalements, sanctions (avertissement, mute, ban, restrictions), historique et traçabilité.'),
			'icon'        => 'fas fa-shield-alt',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'admin'       => TRUE,
			'depends'     => [
				'neofrag' => '1.0.0'
			],
			'routes'      => [
				// === Pages admin (theme admin) ===
				'admin'                                 => 'index',
				'admin/reports{page}'                   => '_reports',
				'admin/reports/{id}'                    => '_report_detail',
				'admin/sanctions{page}'                 => '_sanctions',
				'admin/sanctions/{id}'                  => '_sanction_detail',
				'admin/users/{id}'                      => '_user_history',
				'admin/users/{id}/sanction'             => '_user_sanction',
				'admin/settings'                        => '_settings',

				// Actions admin (POST)
				'admin/reports/{id}/dismiss'            => '_report_dismiss',
				'admin/reports/{id}/sanction'           => '_report_sanction',
				'admin/reports/{id}/mediation'          => '_report_mediation',
				'admin/sanctions/{id}/approve'          => '_sanction_approve',
				'admin/sanctions/{id}/revoke'           => '_sanction_revoke',
				'admin/snapshot/download/{id}'          => '_snapshot_download',


				// Banlist IPs (R2.0, 2026-05-06)
				'admin/banlist{page}'                   => '_banlist',
				'admin/banlist/add'                     => '_banlist_add',
				'admin/banlist/delete/{id}'             => '_banlist_delete',

				// === User-side AJAX (modal report) ===
				'ajax/report'                           => '_ajax_report_submit',
				'ajax/report-modal'                     => '_ajax_report_modal',

				// === Panel modération user-side (theme dungeon, depuis espace membre) ===
				''                                      => 'index',
				'mes-sanctions'                         => '_mes_sanctions',
				'mediation/{id}'                        => '_mediation',
				'reports{page}'                         => '_reports',
				'reports/{id}'                          => '_report_detail',
				'sanctions{page}'                       => '_sanctions',
				'sanctions/{id}'                        => '_sanction_detail',
				'users/{id}'                            => '_user_history',
				'settings'                              => '_settings',

				// Actions user-side (POST)
				'reports/{id}/dismiss'                  => '_report_dismiss',
				'reports/{id}/sanction'                 => '_report_sanction',
				'reports/{id}/mediation'                => '_report_mediation',
				'sanctions/{id}/approve'                => '_sanction_approve',
				'sanctions/{id}/revoke'                 => '_sanction_revoke',
				'snapshot/download/{id}'                => '_snapshot_download'
			]
		];
	}

	public function permissions()
	{
		// Toutes les permissions ont `admin => TRUE` → un user qui a au moins une de
		// ces permissions accède au panel admin du module Modération (sans être admin global).
		//
		// IMPORTANT — Anonymat (anti-vendetta) :
		// Les modos VOIENT toujours qui report qui (info essentielle pour traiter).
		// L'anonymat va dans l'AUTRE SENS : c'est l'identité du modo qui est masquée
		// vis-à-vis du user sanctionné et du reporter (notifs email = "Équipe de modération").
		// Seul un modo avec la permission `reveal_handler` apparaît avec son nom dans les notifs.
		return [
			'default' => [
				'access' => [[
					'title'  => $this->lang('Modération'),
					'icon'   => 'fas fa-shield-alt',
					'access' => [
						'view_reports' => [
							'title' => $this->lang('Voir les signalements'),
							'icon'  => 'fas fa-flag',
							'admin' => TRUE
						],
						'handle_reports' => [
							'title' => $this->lang('Traiter les signalements (ignorer, dismiss, marquer reviewed)'),
							'icon'  => 'fas fa-clipboard-check',
							'admin' => TRUE
						],
						'warn' => [
							'title' => $this->lang('Émettre un avertissement'),
							'icon'  => 'fas fa-exclamation-triangle',
							'admin' => TRUE
						],
						'mute' => [
							'title' => $this->lang('Mute temporaire (empêcher de poster)'),
							'icon'  => 'fas fa-volume-mute',
							'admin' => TRUE
						],
						'restrict' => [
							'title' => $this->lang('Restrictions partielles (upload, liens, avatar, signature, comment)'),
							'icon'  => 'fas fa-lock',
							'admin' => TRUE
						],
						'mediation' => [
							'title' => $this->lang('Ouvrir une médiation (talk entre reporter, signalé et modo)'),
							'icon'  => 'fas fa-handshake',
							'admin' => TRUE
						],
						'ban_temp' => [
							'title' => $this->lang('Ban temporaire (modo senior+)'),
							'icon'  => 'fas fa-clock',
							'admin' => TRUE
						],
						'ban_perm' => [
							'title' => $this->lang('Ban définitif (admin senior)'),
							'icon'  => 'fas fa-ban',
							'admin' => TRUE
						],
						'approve' => [
							'title' => $this->lang('Approuver une sanction (validation hiérarchique)'),
							'icon'  => 'fas fa-check-double',
							'admin' => TRUE
						],
						'revoke' => [
							'title' => $this->lang('Lever une sanction'),
							'icon'  => 'fas fa-undo',
							'admin' => TRUE
						],
						'access_private' => [
							'title' => $this->lang('Accéder au contenu privé (MP/groupes) sur signalement'),
							'icon'  => 'fas fa-eye',
							'admin' => TRUE
						],
						'reveal_handler' => [
							'title' => $this->lang('Identité visible aux users sanctionnés (sinon "Équipe de modération")'),
							'icon'  => 'fas fa-id-card',
							'admin' => TRUE
						],
						'see_reporter' => [
							'title' => $this->lang('Voir l\'identité du signalant'),
							'icon'  => 'fas fa-user-secret',
							'admin' => TRUE
						],
						'manage_settings' => [
							'title' => $this->lang('Gérer les réglages du module'),
							'icon'  => 'fas fa-cogs',
							'admin' => TRUE
						]
					]
				]]
			]
		];
	}

	public function __init()
	{
		// Phase 5 : listeners events pour notifications + escalade auto.
		if (empty($this->events))
		{
			return;
		}

		// Prévenir le membre sanctionné — sauf shadow ban, silencieux par nature —, dans SA langue, par courriel et sur le
		// site ; à la création si la sanction s'applique tout de suite, à sa validation sinon. Il ne recevait qu'un
		// courriel, dans la langue du modérateur, avec le code brut du type (`ban_temp`) et la date SQL, et rien quand la
		// sanction était validée après coup — les bannissements de l'escalade ne se disaient jamais (audit du 2026-10-09).
		$prevenir = function($payload){
			$sanction_id = (int)($payload['sanction_id'] ?? 0);
			$user_id     = (int)($payload['user_id'] ?? 0);
			if (!$sanction_id || !$user_id) return;

			$sanction = $this->db->select('type', 'scope', 'reason', 'expires_at', 'notify_user', 'requires_approval', 'approved_at')->from('nf_sanctions')->where('id', $sanction_id)->row(FALSE);
			if (!is_array($sanction) || empty($sanction) || empty($sanction['notify_user']) || $sanction['type'] === 'shadow_ban')
			{
				return;
			}
			// Pas de notif tant que requires_approval n'est pas satisfait
			if (!empty($sanction['requires_approval']) && empty($sanction['approved_at']))
			{
				return;
			}

			$user = $this->db->select('username', 'email')->from('nf_user')->where('id', $user_id)->row(FALSE);
			if (!is_array($user) || empty($user)) return;

			nf_dans_la_langue_du_membre($user_id, function () use ($sanction, $user, $user_id){
				$quoi = $this->libelle('sanction', $sanction['type']).($sanction['scope'] !== 'global' ? ' — '.$this->libelle('portee', $sanction['scope']) : '');
				$fin  = !empty($sanction['expires_at']) ? (string) timetostr($this->lang('d/m/Y à H:i'), $sanction['expires_at']) : (string) $this->lang('permanent');

				if (!empty($user['email']))
				{
					try
					{
						$this->email->template('moderation.sanction', [
										'username'      => $user['username'],
										'sanction_type' => $quoi,
										'reason'        => $sanction['reason'],
										'duration'      => $fin
									])
									->to($user['email'])
									->send();
					}
					catch (\Throwable $e) {}
				}

				if ($notifications = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications']))
				{
					$notifications->push($user_id, 'moderation_sanction', (string) $this->lang('Sanction de modération : %s (%s)', $quoi, !empty($sanction['expires_at']) ? (string) $this->lang('jusqu’au %s', $fin) : $fin));
				}
			});
		};

		$this->events->on('moderation.sanction.created', $prevenir);
		$this->events->on('moderation.sanction.approved', $prevenir);

		// Prévenir qui a signalé, sur le site et dans sa langue, quand son signalement est traité : personne ne l'écoutait,
		// et il ne savait jamais ce qu'il était advenu (audit du 2026-10-09). Sans dire la sanction : elle ne le regarde pas.
		$this->events->on('moderation.report.handled', function($payload){
			if (!in_array($payload['status'] ?? '', ['actioned', 'dismissed'], TRUE)) return;

			$reporter_id = (int) $this->db->select('reporter_id')->from('nf_reports')->where('id', (int)($payload['report_id'] ?? 0))->row();
			if (!$reporter_id || !($notifications = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications']))) return;

			nf_dans_la_langue_du_membre($reporter_id, function () use ($notifications, $reporter_id, $payload){
				$notifications->push($reporter_id, 'moderation_signalement', $payload['status'] === 'actioned'
					? (string) $this->lang('Ton signalement a été examiné : la modération est intervenue. Merci.')
					: (string) $this->lang('Ton signalement a été examiné : la modération n’a pas jugé nécessaire d’intervenir. Merci.'), '', (int)($payload['handler_id'] ?? 0) ?: NULL);
			});
		});

		// Listener 3 : escalade auto warnings → mute / ban
		$this->events->on('moderation.sanction.created', function($payload){
			if (empty($this->config->nf_moderation_auto_escalation)) return;
			if (($payload['type'] ?? '') !== 'warning') return;
			$user_id = (int)($payload['user_id'] ?? 0);
			if (!$user_id) return;

			$window_days = (int)$this->config->nf_moderation_warning_window_days ?: 30;
			$mute_th     = (int)$this->config->nf_moderation_warning_threshold_mute ?: 3;
			$ban_th      = (int)$this->config->nf_moderation_warning_threshold_ban ?: 5;
			$cutoff      = date('Y-m-d H:i:s', time() - $window_days * 86400);

			$count = (int)$this->db	->select('COUNT(*)')
									->from('nf_sanctions')
									->where('user_id', $user_id)
									->where('type', 'warning')
									->where('revoked_at', NULL)
									->where('created_at >', $cutoff)
									->row();

			// Rien de plus si un muet ou un bannissement court déjà, ou attend sa validation : chaque avertissement au-delà
			// du seuil en recréait un (audit du 2026-10-09).
			$en_cours = fn (array $types) => (bool) $this->db	->select('COUNT(*)')
														->from('nf_sanctions')
														->where('user_id', $user_id)
														->where('type', $types)
														->where('revoked_at', NULL)
														->where('(expires_at IS NULL OR expires_at > NOW())')
														->row();

			if ($count >= $ban_th && !$en_cours(['ban_temp', 'ban_perm']))
			{
				$this->moderation->sanction($user_id, 'ban_temp', [
					'scope'              => 'global',
					'reason'              => $this->lang('Escalade auto : %d avertissements en %d jours', $count, $window_days),
					'duration_seconds'    => (int)$this->config->nf_moderation_default_ban_temp_duration_seconds ?: 604800,
					'issued_by'           => 0,
					'requires_approval'   => 1
				]);
			}
			else if ($count >= $mute_th && $count < $ban_th && !$en_cours(['mute', 'ban_temp', 'ban_perm']))
			{
				$this->moderation->sanction($user_id, 'mute', [
					'scope'              => 'global',
					'reason'              => $this->lang('Escalade auto : %d avertissements en %d jours', $count, $window_days),
					'duration_seconds'    => (int)$this->config->nf_moderation_default_mute_duration_seconds ?: 86400,
					'issued_by'           => 0,
					'requires_approval'   => 0
				]);
			}
		});
	}

	public function display()
	{
		// Badge avec count de reports en attente sur l'icône admin
		$count = (int)$this->db->select('COUNT(*)')->from('nf_reports')->where('status', 'pending')->row();
		return $count > 0 ? '<span class="float-end badge text-bg-warning">'.$count.'</span>' : '';
	}

	/**
	 * Le libellé traduit d'un code de la modération : statut d'un signalement, raison, type de cible,
	 * type et portée d'une sanction. L'administration affichait les codes eux-mêmes (`pending`,
	 * `forum_message`, `ban_temp`), dans toutes les langues. Un code inconnu est rendu tel quel.
	 */
	public function libelle(string $famille, $code): string
	{
		$code = (string) $code;

		$libelles = [
			'statut' => [
				'pending'   => $this->lang('En attente'),
				'reviewed'  => $this->lang('Examiné'),
				'actioned'  => $this->lang('Sanctionné'),
				'dismissed' => $this->lang('Classé sans suite'),
				'duplicate' => $this->lang('Doublon'),
			],
			'raison' => [
				'spam'           => $this->lang('Spam'),
				'harassment'     => $this->lang('Harcèlement'),
				'illegal'        => $this->lang('Contenu illégal'),
				'nsfw'           => $this->lang('Contenu explicite'),
				'misinformation' => $this->lang('Désinformation'),
				'duplicate'      => $this->lang('Doublon / hors sujet'),
				'other'          => $this->lang('Autre'),
			],
			'cible' => [
				'forum_message' => $this->lang('Message du forum'),
				'forum_topic'   => $this->lang('Sujet du forum'),
				'talks_message' => $this->lang('Message privé'),
				'comment'       => $this->lang('Commentaire'),
				'profile'       => $this->lang('Profil'),
				'guestbook'     => $this->lang('Message du livre d’or'),
				'gallery_image' => $this->lang('Image de la galerie'),
				'classified'    => $this->lang('Petite annonce'),
				'bug_ticket'    => $this->lang('Ticket'),
				'bug_comment'   => $this->lang('Commentaire d’un ticket'),
			],
			'sanction' => [
				'warning'            => $this->lang('Avertissement'),
				'mute'               => $this->lang('Mute'),
				'ban_temp'           => $this->lang('Ban temporaire'),
				'ban_perm'           => $this->lang('Ban définitif'),
				'restrict_upload'    => $this->lang('Restriction : fichiers'),
				'restrict_links'     => $this->lang('Restriction : liens'),
				'restrict_avatar'    => $this->lang('Restriction : avatar'),
				'restrict_signature' => $this->lang('Restriction : signature'),
				'restrict_comment'   => $this->lang('Restriction : commentaires'),
				'shadow_ban'         => $this->lang('Shadow ban'),
			],
			'portee' => [
				'global'    => $this->lang('Tout le site'),
				'forum'     => $this->lang('Forum'),
				'talks'     => $this->lang('Discussions'),
				'comments'  => $this->lang('Commentaires'),
				'gallery'    => $this->lang('Galerie'),
				'guestbook'  => $this->lang('Livre d\'or'),
				'profile'    => $this->lang('Profil'),
				'bugtracker' => $this->lang('Tickets'),
				'recruits'   => $this->lang('Recrutement'),
			],
		];

		return (string) ($libelles[$famille][$code] ?? $code);
	}

	/**
	 * Helper : rend un bouton "Signaler" + injecte le JS du module si pas déjà fait.
	 *
	 * Usage dans une vue :
	 *   echo $this->module('moderation')->report_button('forum_message', $msg_id, $url, $current_user_id, $author_id);
	 *
	 * Retourne '' si l'user n'est pas connecté OU est l'auteur du contenu (pas signaler soi-même).
	 *
	 * @param string $type   target_type (ex: 'forum_message', 'talks_message', 'comment')
	 * @param int|string $id target_id
	 * @param string $url    URL de la page de contexte
	 * @param int|null $current_user_id
	 * @param int|null $author_id si on connaît l'auteur, on évite de proposer "se signaler soi-même"
	 * @return string HTML
	 */
	public function report_button($type, $id, string $url = '', ?int $current_user_id = NULL, ?int $author_id = NULL): string
	{
		// Pas de bouton si pas connecté
		if (!$this->user()) return '';
		if ($current_user_id === NULL) $current_user_id = (int)$this->user->id;
		// Pas de bouton si auteur du contenu
		if ($author_id !== NULL && $author_id === $current_user_id) return '';

		// Charger CSS + JS du module sur la page (idempotent : NeoFrag déduplique)
		$this->css('moderation')->js('moderation');

		$attrs = 'data-moderation-report'
		       . ' data-target-type="'.nf_texte($type).'"'
		       . ' data-target-id="'.nf_texte($id).'"'
		       . ' data-url="'.nf_texte($url).'"';

		return '<a href="#" class="btn btn-sm btn-link text-muted nf-report-btn" '.$attrs.' data-bs-toggle="tooltip" title="'.nf_texte($this->lang('Signaler ce contenu')).'">'
		     . '<i class="fas fa-flag"></i>'
		     . '</a>';
	}

	/** Chaque type de sanction, et la permission qu'il demande. */
	const DROITS_DES_TYPES = [
		'warning'            => 'warn',
		'mute'               => 'mute',
		'ban_temp'           => 'ban_temp',
		'ban_perm'           => 'ban_perm',
		'restrict_upload'    => 'restrict',
		'restrict_links'     => 'restrict',
		'restrict_avatar'    => 'restrict',
		'restrict_signature' => 'restrict',
		'restrict_comment'   => 'restrict',
		'shadow_ban'         => 'ban_perm'
	];

	/**
	 * Le signalement `$report_id`, s'il existe et que le modérateur courant peut le traiter : pas un signalement qui le
	 * vise lui-même (audit du 2026-10-09). Rend [signalement, NULL], ou [NULL, ce qui l'en empêche].
	 *
	 * @return array{0: ?array<string, mixed>, 1: ?string}
	 */
	private function _signalement_traitable(int $report_id): array
	{
		$report = $this->moderation->get_report($report_id);

		if (!$report)
		{
			return [NULL, (string) $this->lang('Signalement introuvable.')];
		}

		if ($report['target_user_id'] && (int) $report['target_user_id'] === (int) $this->user->id)
		{
			return [NULL, (string) $this->lang('Ce signalement te concerne : un autre modérateur doit le traiter.')];
		}

		return [$report, NULL];
	}

	/**
	 * Prononcer une sanction depuis un signalement — le même geste dans l'administration et dans l'espace des
	 * modérateurs, qui recopiaient chacun le code (2026-10-09).
	 *
	 * @return array{ok: bool, message: string}
	 */
	public function prononcer(int $report_id, array $post): array
	{
		[$report, $refus] = $this->_signalement_traitable($report_id);

		if ($refus !== NULL)
		{
			return ['ok' => FALSE, 'message' => $refus];
		}

		if (!$report['target_user_id'])
		{
			return ['ok' => FALSE, 'message' => (string) $this->lang('Pas de user cible identifié.')];
		}

		$issue = $this->sanctionner((int) $report['target_user_id'], $post, $report_id);

		if ($issue['ok'])
		{
			$this->moderation->update_report_status($report_id, 'actioned', (int)$this->user->id, NULL, $issue['sanction_id']);
		}

		return ['ok' => $issue['ok'], 'message' => $issue['message']];
	}

	/**
	 * Prononcer une sanction contre un membre — depuis un signalement (`$report_id`), ou directement depuis son
	 * historique (2026-10-09 : on ne sanctionnait que depuis un signalement). Le type demande son droit
	 * (DROITS_DES_TYPES) ; la bibliothèque refuse de sanctionner soi-même ou plus haut placé.
	 *
	 * @param array<string, mixed> $post les champs du formulaire (views/admin/sanction_form.tpl.php)
	 * @return array{ok: bool, message: string, sanction_id: int}
	 */
	public function sanctionner(int $user_id, array $post, int $report_id = 0): array
	{
		$type = (string)($post['type'] ?? '');

		if (!isset(self::DROITS_DES_TYPES[$type]))
		{
			return ['ok' => FALSE, 'message' => (string) $this->lang('Type de sanction invalide.'), 'sanction_id' => 0];
		}

		if (!$this->access('moderation', self::DROITS_DES_TYPES[$type]))
		{
			return ['ok' => FALSE, 'message' => (string) $this->lang('Permission insuffisante pour ce type de sanction.'), 'sanction_id' => 0];
		}

		// La durée : en secondes (posée par le script de la page), sinon en heures — sans script, elle se perdait.
		$duree = (int)($post['duration_seconds'] ?? 0) ?: (int)($post['duration_seconds_h'] ?? 0) * 3600;

		$sanction_id = $this->moderation->sanction($user_id, $type, [
			'scope'             => (string)($post['scope'] ?? 'global'),
			'reason'            => (string)($post['reason'] ?? ''),
			'duration_seconds'  => $duree > 0 ? $duree : NULL,
			'issued_by'         => (int)$this->user->id,
			'related_report_id' => $report_id ?: NULL,
			// Une case décochée n'est pas envoyée : elle valait 1, et le membre était toujours prévenu (audit du 2026-10-09).
			'notify_user'       => !empty($post['notify_user']) ? 1 : 0
		]);

		if (!$sanction_id)
		{
			return ['ok' => FALSE, 'message' => (string) $this->lang('Échec de la création de la sanction (permissions hiérarchiques ou auto-protection).'), 'sanction_id' => 0];
		}

		return ['ok' => TRUE, 'message' => (string) $this->lang('Sanction appliquée.'), 'sanction_id' => (int) $sanction_id];
	}

	/**
	 * Classer un signalement sans suite.
	 *
	 * @return array{ok: bool, message: string}
	 */
	public function classer(int $report_id, string $note): array
	{
		[$report, $refus] = $this->_signalement_traitable($report_id);

		if ($refus !== NULL)
		{
			return ['ok' => FALSE, 'message' => $refus];
		}

		$this->moderation->update_report_status($report_id, 'dismissed', (int)$this->user->id, trim($note));

		return ['ok' => TRUE, 'message' => (string) $this->lang('Signalement classé sans suite.')];
	}

	/**
	 * Proposer une médiation : une conversation privée entre le modérateur, le membre signalé et celui qui l'a signalé,
	 * pour régler un conflit sans sanction. Le membre signalé y voit qui l'a signalé : la conversation ne s'ouvre
	 * qu'avec l'accord de ce dernier (repondre_mediation() ; décision du 2026-10-09). Une proposition par
	 * signalement : celui qui a refusé n'est pas relancé.
	 *
	 * @return array{ok: bool, message: string}
	 */
	public function proposer_mediation(int $report_id): array
	{
		[$report, $refus] = $this->_signalement_traitable($report_id);

		if ($refus !== NULL)
		{
			return ['ok' => FALSE, 'message' => $refus];
		}

		if (!$report['reporter_id'] || !$report['target_user_id'] || (int) $report['reporter_id'] === (int) $report['target_user_id'])
		{
			return ['ok' => FALSE, 'message' => (string) $this->lang('Une médiation demande un membre qui signale et un membre signalé.')];
		}

		if (!in_array($report['status'], ['pending', 'reviewed'], TRUE))
		{
			return ['ok' => FALSE, 'message' => (string) $this->lang('Ce signalement est déjà traité.')];
		}

		if (!empty($report['mediation_le']))
		{
			return ['ok' => FALSE, 'message' => (string) $this->lang('Une médiation a déjà été proposée pour ce signalement.')];
		}

		// La conversation se crée dans la messagerie : sans elle, la proposition ne mènerait nulle part.
		if (!$this->module('talks')) // couplage: talks — la médiation est une conversation de la messagerie
		{
			return ['ok' => FALSE, 'message' => (string) $this->lang('La médiation demande la messagerie, qui n’est pas installée.')];
		}

		$this->db->where('id', $report_id)->update('nf_reports', [
			'mediation_par' => (int) $this->user->id,
			'mediation_le'  => date('Y-m-d H:i:s'),
		]);

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('moderation.mediation.proposed', [
			'report_id'    => $report_id,
			'moderator_id' => (int) $this->user->id,
		]);

		$reporter_id = (int) $report['reporter_id'];

		if ($notifications = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications']))
		{
			nf_dans_la_langue_du_membre($reporter_id, function () use ($notifications, $reporter_id, $report_id){
				$notifications->push($reporter_id, 'moderation_signalement', (string) $this->lang('Un modérateur te propose une médiation au sujet de ton signalement : à toi de décider.'), 'moderation/mediation/'.$report_id);
			});
		}

		return ['ok' => TRUE, 'message' => (string) $this->lang('Médiation proposée : elle s’ouvrira si celui qui a signalé l’accepte. Tu seras prévenu de sa réponse.')];
	}

	/**
	 * La réponse de celui qui a signalé à une proposition de médiation (proposer_mediation()). Son accord ouvre la
	 * conversation, avec le modérateur qui l'a proposée ; son refus la clôt, et le signalement suit son cours sans que le
	 * membre signalé sache qui l'a fait. Le modérateur apprend la réponse.
	 *
	 * @return array{ok: bool, message: string, adresse?: string}
	 */
	public function repondre_mediation(int $report_id, bool $accord): array
	{
		$report = $this->moderation->get_report($report_id);

		if (!$report || (int) $report['reporter_id'] !== (int) $this->user->id || empty($report['mediation_le']))
		{
			return ['ok' => FALSE, 'message' => (string) $this->lang('Aucune médiation ne t’est proposée ici.')];
		}

		if (!empty($report['mediation_accord']))
		{
			return ['ok' => FALSE, 'message' => (string) $this->lang('Tu as déjà répondu à cette proposition.')];
		}

		if (!in_array($report['status'], ['pending', 'reviewed'], TRUE))
		{
			return ['ok' => FALSE, 'message' => (string) $this->lang('Ton signalement a été traité entre-temps : la médiation n’a plus lieu d’être.')];
		}

		$moderateur_id = (int) $report['mediation_par'];
		$talk_id       = $accord ? $this->moderation->open_mediation($report_id, $moderateur_id) : 0;

		if ($accord && !$talk_id)
		{
			return ['ok' => FALSE, 'message' => (string) $this->lang('La médiation n’a pas pu s’ouvrir : réessaie plus tard.')];
		}

		$this->db->where('id', $report_id)->update('nf_reports', [
			'mediation_accord'     => $accord ? 'oui' : 'non',
			'mediation_reponse_le' => date('Y-m-d H:i:s'),
			'mediation_talk_id'    => $talk_id ?: NULL,
		]);

		if (!$accord)
		{
			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('moderation.mediation.refused', ['report_id' => $report_id]);
		}

		// L'adresse de la conversation porte son titre : la messagerie le vérifie.
		$adresse = $talk_id ? 'talks/'.$talk_id.'/'.url_title((string) $this->db->select('name')->from('nf_talks')->where('talk_id', $talk_id)->row()) : '';

		if ($moderateur_id && ($notifications = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications'])))
		{
			nf_dans_la_langue_du_membre($moderateur_id, function () use ($notifications, $moderateur_id, $report_id, $accord, $adresse){
				$notifications->push($moderateur_id, 'moderation_mediation', $accord
					? (string) $this->lang('Médiation acceptée (signalement #%d) : la conversation est ouverte.', $report_id)
					: (string) $this->lang('Médiation refusée (signalement #%d) : le signalement reste à traiter.', $report_id), $accord ? $adresse : 'moderation/reports/'.$report_id);
			});
		}

		return $accord
			? ['ok' => TRUE, 'message' => (string) $this->lang('Médiation acceptée : la conversation est ouverte.'), 'adresse' => $adresse]
			: ['ok' => TRUE, 'message' => (string) $this->lang('C’est noté : pas de médiation. Ton signalement reste anonyme et la modération le traite.')];
	}

	/**
	 * Les médiations proposées à `$user_id` sur ses signalements, qui attendent sa réponse — l'entrée de son espace
	 * membre, qu'il ait coupé ou non les notifications de ses signalements.
	 */
	public function mediations_en_attente(int $user_id): int
	{
		return (int) $this->db	->select('COUNT(*)')
								->from('nf_reports')
								->where('reporter_id', $user_id)
								->where('mediation_le IS NOT NULL')
								->where('mediation_accord', NULL)
								->where('status', ['pending', 'reviewed'])
								->row();
	}

	/**
	 * Les réglages de la modération, enregistrés depuis l'administration ou l'espace des modérateurs, qui recopiaient
	 * chacun le code. Une case décochée n'est pas envoyée par le navigateur : elle vaut « non » — on ne l'enregistrait
	 * que si elle était envoyée, et aucune case ne pouvait se décocher (audit du 2026-10-09). Les nombres sont bornés.
	 */
	public function enregistrer_reglages(array $post): void
	{
		foreach (['nf_moderation_auto_escalation', 'nf_moderation_require_approval_ban_perm', 'nf_moderation_require_approval_ban_temp', 'nf_moderation_preserve_content_snapshot'] as $case)
		{
			$this->config($case, !empty($post[$case]) ? '1' : '0');
		}

		foreach ([
			'nf_moderation_warning_window_days'               => 1,
			'nf_moderation_warning_threshold_mute'            => 1,
			'nf_moderation_warning_threshold_ban'             => 1,
			'nf_moderation_report_rate_limit_per_hour'        => 1,
			'nf_moderation_report_flag_threshold_per_day'     => 1,
			'nf_moderation_default_mute_duration_seconds'     => 60,
			'nf_moderation_default_ban_temp_duration_seconds' => 3600
		] as $nombre => $minimum)
		{
			if (isset($post[$nombre]) && is_scalar($post[$nombre]))
			{
				$this->config($nombre, (string) max($minimum, (int) $post[$nombre]));
			}
		}
	}

	/**
	 * Les notifications que ce module envoie sur le site, et qu'un membre peut couper (Notifications::types()) : les
	 * sanctions qui le visent — le courriel, lui, part toujours — et l'issue de ses signalements ; pour qui peut proposer
	 * une médiation, la réponse qu'on y fait.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function types_de_notification(): array
	{
		return array_merge([
			['type' => 'moderation_sanction',    'titre' => (string) $this->lang('Les sanctions de modération qui me visent'), 'email' => FALSE, 'ordre' => 89],
			['type' => 'moderation_signalement', 'titre' => (string) $this->lang('L’issue de mes signalements'), 'email' => FALSE, 'ordre' => 90],
		], $this->access('moderation', 'mediation') ? [
			['type' => 'moderation_mediation',   'titre' => (string) $this->lang('Les réponses à mes propositions de médiation'), 'email' => FALSE, 'ordre' => 91],
		] : []);
	}

	/**
	 * La modération dans le menu de l'espace membre (User::menu_espace(), chantier A), pour qui a le droit de
	 * lire les signalements, avec le nombre de ceux qui attendent — comme le widget « Espace membre ».
	 *
	 * @return list<array<string, mixed>>
	 */
	public function espace_membre($user): array
	{
		$entrees = [];

		// « Mes sanctions », pour qui en a eu une dans l'année (2026-10-09) : un avertissement dont l'e-mail ne partait pas
		// restait invisible au membre. Un shadow ban, silencieux par nature, ne compte pas.
		if ($this->mes_sanctions((int) $user->id))
		{
			$entrees[] = ['url' => 'moderation/mes-sanctions', 'titre' => (string) $this->lang('Mes sanctions'), 'icone' => 'fas fa-gavel', 'ordre' => 95];
		}

		// Une médiation proposée sur l'un de ses signalements attend sa réponse : elle se trouve ici même si les
		// notifications de ses signalements sont coupées.
		if ($en_attente = $this->mediations_en_attente((int) $user->id))
		{
			$premier = (int) $this->db	->select('id')
										->from('nf_reports')
										->where('reporter_id', (int) $user->id)
										->where('mediation_le IS NOT NULL')
										->where('mediation_accord', NULL)
										->where('status', ['pending', 'reviewed'])
										->order_by('mediation_le')
										->row();

			$entrees[] = ['url' => 'moderation/mediation/'.$premier, 'titre' => (string) $this->lang('Médiation proposée'), 'icone' => 'fas fa-handshake', 'ordre' => 94, 'badge' => $en_attente];
		}

		if ($this->access('moderation', 'view_reports'))
		{
			$en_attente = (int) $this->db->select('COUNT(*)')->from('nf_reports')->where('status', 'pending')->row();
			$entrees[]  = ['url' => 'moderation', 'titre' => (string) $this->lang('Modération'), 'icone' => 'fas fa-shield-alt', 'badge' => $en_attente, 'compact' => TRUE, 'ordre' => 90];
		}

		return $entrees;
	}

	/**
	 * Les sanctions prononcées contre un membre depuis un an, celles qui valent ou ont valu — une sanction qui attend sa
	 * validation n'a encore rien changé —, jamais un shadow ban. Les plus récentes d'abord.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function mes_sanctions(int $user_id): array
	{
		$lignes = (array) $this->db	->select('type', 'scope', 'reason', 'starts_at', 'expires_at', 'revoked_at', 'requires_approval', 'approved_at', 'created_at')
									->from('nf_sanctions')
									->where('user_id', $user_id)
									->where('type <>', 'shadow_ban')
									->where('created_at >', date('Y-m-d H:i:s', strtotime('-1 year')))
									->order_by('created_at DESC')
									->get(FALSE);

		return array_values(array_filter($lignes, static fn ($l): bool => is_array($l) && (empty($l['requires_approval']) || !empty($l['approved_at']))));
	}
}
