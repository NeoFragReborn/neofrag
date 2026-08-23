<?php
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
			'title'       => 'Modération',
			'description' => $this->lang('Système de modération étendu : signalements, sanctions (avertissement, mute, ban, restrictions), historique et traçabilité.'),
			'icon'        => 'fas fa-shield-alt',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
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
				'admin/settings'                        => '_settings',

				// Actions admin (POST)
				'admin/reports/{id}/dismiss'            => '_report_dismiss',
				'admin/reports/{id}/sanction'           => '_report_sanction',
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
				'reports{page}'                         => '_reports',
				'reports/{id}'                          => '_report_detail',
				'sanctions{page}'                       => '_sanctions',
				'sanctions/{id}'                        => '_sanction_detail',
				'users/{id}'                            => '_user_history',
				'settings'                              => '_settings',

				// Actions user-side (POST)
				'reports/{id}/dismiss'                  => '_report_dismiss',
				'reports/{id}/sanction'                 => '_report_sanction',
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

		// Listener 1 : notif email au user sanctionné (sauf shadow_ban) — modo anonymisé sauf si reveal_handler
		$this->events->on('moderation.sanction.created', function($payload){
			$sanction_id = (int)($payload['sanction_id'] ?? 0);
			$user_id     = (int)($payload['user_id'] ?? 0);
			if (!$sanction_id || !$user_id) return;

			$sanction = $this->db	->select('s.*', 'iu.username as issuer')
									->from('nf_sanctions s')
									->join('nf_user iu', 'iu.id = s.issued_by', 'LEFT')
									->where('s.id', $sanction_id)
									->row(FALSE);
			if (!is_array($sanction) || empty($sanction) || empty($sanction['notify_user']) || $sanction['type'] === 'shadow_ban')
			{
				return;
			}
			// Pas de notif tant que requires_approval n'est pas satisfait
			if (!empty($sanction['requires_approval']) && empty($sanction['approved_at']))
			{
				return;
			}

			$user = $this->db->select('username', 'email')->from('nf_user')->where('id', $user_id)->where('email !=', '')->row(FALSE);
			if (!is_array($user) || empty($user) || empty($user['email'])) return;

			// Anonymisation modo : par défaut "Équipe de modération", visible nom seulement si reveal_handler
			$issuer_label = $this->lang('L\'équipe de modération');
			if (!empty($sanction['issuer']) && $sanction['issued_by'])
			{
				$has_reveal = (bool)$this->access('moderation', 'reveal_handler', 0, NULL, (int)$sanction['issued_by']);
				if ($has_reveal)
				{
					$issuer_label = '@'.$sanction['issuer'];
				}
			}

			$expires_str = !empty($sanction['expires_at']) ? $sanction['expires_at'] : $this->lang('permanent');

			try
			{
				$this->email->template('moderation.sanction', [
								'username'      => $user['username'],
								'sanction_type' => $sanction['type'],
								'reason'        => $sanction['reason'],
								'duration'      => $expires_str
							])
							->to($user['email'])
							->send();
			}
			catch (\Throwable $e) {}
		});

		// Listener 2 : notif email au reporter quand son report est traité (actioned ou dismissed)
		// (Hook via update_report_status dans la lib — émet un event explicite à ajouter là-bas)

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

			if ($count >= $ban_th)
			{
				$this->moderation->sanction($user_id, 'ban_temp', [
					'scope'              => 'global',
					'reason'              => $this->lang('Escalade auto : %d avertissements en %d jours', $count, $window_days),
					'duration_seconds'    => (int)$this->config->nf_moderation_default_ban_temp_duration_seconds ?: 604800,
					'issued_by'           => 0,
					'requires_approval'   => 1
				]);
			}
			else if ($count >= $mute_th)
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
		       . ' data-target-type="'.htmlspecialchars($type).'"'
		       . ' data-target-id="'.htmlspecialchars((string)$id).'"'
		       . ' data-url="'.htmlspecialchars($url).'"';

		return '<a href="#" class="btn btn-sm btn-link text-muted nf-report-btn" '.$attrs.' data-bs-toggle="tooltip" title="'.htmlspecialchars($this->lang('Signaler ce contenu')).'">'
		     . '<i class="fas fa-flag"></i>'
		     . '</a>';
	}
}
