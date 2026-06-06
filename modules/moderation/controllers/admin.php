<?php
/**
 * https://neofr.ag
 * Controller admin du module Modération.
 */

namespace NF\Modules\Moderation\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	// === Pages ===

	public function index()
	{
		$this->css('moderation');

		$stats         = $this->model()->dashboard_stats();
		$top_reported  = $this->model()->top_reported_users(10);
		$top_reporters = $this->model()->top_reporters(10);
		$recent_reports = $this->moderation->get_pending_reports([], 0, 10);

		$this->title($this->lang('Modération'))->icon('fas fa-shield-alt');

		return $this->view('admin/dashboard', [
			'stats'          => $stats,
			'top_reported'   => $top_reported,
			'top_reporters'  => $top_reporters,
			'recent_reports' => $recent_reports
		]);
	}

	public function _reports($page = '')
	{
		$this->css('moderation');
		$this	->title($this->lang('Signalements'))
				->icon('fas fa-flag')
				->breadcrumb($this->lang('Modération'), 'admin/moderation')
				->breadcrumb($this->lang('Signalements'));

		$filter = [
			'status'      => isset($_GET['status']) && in_array($_GET['status'], ['pending','reviewed','actioned','dismissed','duplicate'], TRUE) ? $_GET['status'] : '',
			'target_type' => trim((string)($_GET['target_type'] ?? '')),
			'reason'      => isset($_GET['reason']) && in_array($_GET['reason'], ['spam','harassment','illegal','nsfw','misinformation','duplicate','other'], TRUE) ? $_GET['reason'] : ''
		];
		$active_filter = array_filter($filter);

		$page_num = is_numeric($page) ? (int)$page : 0;
		$reports = $this->moderation->get_pending_reports($active_filter, $page_num, 50);

		return $this->view('admin/reports', [
			'reports'       => $reports,
			'filter'        => $filter,
			'page'          => $page_num,
			'show_reporter' => (bool)$this->access('moderation', 'see_reporter')
		]);
	}

	public function _report_detail($id)
	{
		$report = $this->moderation->get_report($id);
		if (!$report)
		{
			$this->error->notfound();
			return;
		}

		// Phase 5 privacy — si le report concerne un contenu privé (talks direct/group),
		// audit log l'accès (qui a vu, quand, quel report) + permission stricte access_private requise.
		if ($report['target_type'] === 'talks_message')
		{
			$row = $this->db->select('t.type')->from('nf_talks_messages m')
			                ->join('nf_talks t', 't.talk_id = m.talk_id')
			                ->where('m.message_id', (int)$report['target_id'])
			                ->row();
			$is_private = is_array($row) && in_array($row['type'], ['direct', 'group'], TRUE);
			if ($is_private && !$this->access('moderation', 'access_private'))
			{
				notify($this->lang('Permission insuffisante : ce signalement concerne une conversation privée. Contacte un admin senior.'), 'danger');
				redirect('admin/moderation/reports');
			}
			if ($is_private)
			{
				try
				{
					(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('moderation.private_access', [
						'report_id' => $id,
						'admin_id'  => $this->user->id,
						'target_type' => $report['target_type'],
						'target_id' => $report['target_id']
					]);
				}
				catch (\Throwable $e) {}
			}
		}

		$this->css('moderation');
		$this	->title($this->lang('Signalement #%d', $id))
				->icon('fas fa-flag')
				->breadcrumb($this->lang('Modération'), 'admin/moderation')
				->breadcrumb($this->lang('Signalements'), 'admin/moderation/reports')
				->breadcrumb('#'.$id);

		// Quality score du reporter (si connu)
		$reporter_score = $report['reporter_id'] ? $this->moderation->reporter_quality_score((int)$report['reporter_id']) : NULL;

		// Historique du user signalé
		$target_history = NULL;
		if ($report['target_user_id'])
		{
			$target_history = [
				'reports_received' => $this->moderation->get_user_reports_received((int)$report['target_user_id'], 20),
				'active_sanctions' => $this->moderation->active_sanctions((int)$report['target_user_id'])
			];
		}

		// Mark as reviewed if pending and we just opened it
		if ($report['status'] === 'pending')
		{
			// (Ne change pas auto le status, le modo doit cliquer explicitement)
		}

		return $this->view('admin/report_detail', [
			'report'         => $report,
			'reporter_score' => $reporter_score,
			'target_history' => $target_history,
			'show_reporter'  => (bool)$this->access('moderation', 'see_reporter')
		]);
	}

	public function _sanctions($page = '')
	{
		$this->css('moderation');
		$this	->title($this->lang('Sanctions'))
				->icon('fas fa-gavel')
				->breadcrumb($this->lang('Modération'), 'admin/moderation')
				->breadcrumb($this->lang('Sanctions'));

		$filter = [
			'active_only'      => !empty($_GET['active_only']),
			'pending_approval' => !empty($_GET['pending_approval']),
			'type'             => trim((string)($_GET['type'] ?? ''))
		];
		$page_num = is_numeric($page) ? (int)$page : 0;
		$sanctions = $this->model()->get_sanctions($filter, $page_num, 50);

		return $this->view('admin/sanctions', [
			'sanctions' => $sanctions,
			'filter'    => $filter,
			'page'      => $page_num
		]);
	}

	public function _sanction_detail($id)
	{
		$sanction = $this->model()->get_sanction($id);
		if (!$sanction)
		{
			$this->error->notfound();
			return;
		}

		$this->css('moderation');
		$this	->title($this->lang('Sanction #%d', $id))
				->icon('fas fa-gavel')
				->breadcrumb($this->lang('Modération'), 'admin/moderation')
				->breadcrumb($this->lang('Sanctions'), 'admin/moderation/sanctions')
				->breadcrumb('#'.$id);

		return $this->view('admin/sanction_detail', [
			'sanction' => $sanction,
			'can_approve' => $this->access('moderation', 'approve'),
			'can_revoke'  => $this->access('moderation', 'revoke')
		]);
	}

	public function _user_history($user_id)
	{
		$user = $this->db->select('id', 'username', 'email', 'admin', 'deleted', 'registration_date')
						 ->from('nf_user')
						 ->where('id', $user_id)
						 ->row();
		if (!is_array($user) || empty($user))
		{
			$this->error->notfound();
			return;
		}

		$this->css('moderation');
		$this	->title($this->lang('Historique modération de %s', $user['username']))
				->icon('fas fa-history')
				->breadcrumb($this->lang('Modération'), 'admin/moderation')
				->breadcrumb($user['username']);

		$timeline = $this->moderation->user_history($user_id);
		$active_sanctions = $this->moderation->active_sanctions($user_id);
		$reporter_score = $this->moderation->reporter_quality_score($user_id);

		return $this->view('admin/user_history', [
			'user'             => $user,
			'timeline'         => $timeline,
			'active_sanctions' => $active_sanctions,
			'reporter_score'   => $reporter_score
		]);
	}

	public function _settings()
	{
		$this	->title($this->lang('Réglages modération'))
				->icon('fas fa-cogs')
				->breadcrumb($this->lang('Modération'), 'admin/moderation')
				->breadcrumb($this->lang('Réglages'));

		// Save POST
		if (!empty($_POST['save_moderation_settings']))
		{
			$keys = [
				'nf_moderation_enabled',
				'nf_moderation_auto_escalation',
				'nf_moderation_warning_window_days',
				'nf_moderation_warning_threshold_mute',
				'nf_moderation_warning_threshold_ban',
				'nf_moderation_report_rate_limit_per_hour',
				'nf_moderation_report_flag_threshold_per_day',
				'nf_moderation_require_approval_ban_perm',
				'nf_moderation_require_approval_ban_temp',
				'nf_moderation_default_mute_duration_seconds',
				'nf_moderation_default_ban_temp_duration_seconds',
				'nf_moderation_preserve_content_snapshot'
			];
			foreach ($keys as $k)
			{
				if (isset($_POST[$k]))
				{
					$this->config($k, (string)$_POST[$k]);
				}
			}
			notify($this->lang('Réglages modération sauvegardés.'));
			redirect('admin/moderation/settings');
		}

		return $this->view('admin/settings', [
			'config' => $this->config
		]);
	}

	// === Actions admin (POST) ===

	public function _report_dismiss($id)
	{
		$report = $this->moderation->get_report($id);
		if (!$report) { notify($this->lang('Signalement introuvable.'), 'danger'); redirect('admin/moderation/reports'); }

		$note = trim((string)($_POST['note'] ?? ''));
		$this->moderation->update_report_status($id, 'dismissed', (int)$this->user->id, $note);
		notify($this->lang('Signalement marqué comme dismissed.'));
		redirect('admin/moderation/reports');
	}

	public function _report_sanction($id)
	{
		$report = $this->moderation->get_report($id);
		if (!$report) { notify($this->lang('Signalement introuvable.'), 'danger'); redirect('admin/moderation/reports'); }
		if (!$report['target_user_id']) { notify($this->lang('Pas de user cible identifié.'), 'danger'); redirect('admin/moderation/reports/'.$id); }

		$type = (string)($_POST['type'] ?? '');
		$valid_types = ['warning','mute','ban_temp','ban_perm','restrict_upload','restrict_links','restrict_avatar','restrict_signature','restrict_comment','shadow_ban'];
		if (!in_array($type, $valid_types, TRUE))
		{
			notify($this->lang('Type de sanction invalide.'), 'danger');
			redirect('admin/moderation/reports/'.$id);
		}

		// Vérification permissions selon type
		$perm_map = [
			'warning' => 'warn',
			'mute' => 'mute',
			'ban_temp' => 'ban_temp',
			'ban_perm' => 'ban_perm',
			'restrict_upload' => 'restrict',
			'restrict_links' => 'restrict',
			'restrict_avatar' => 'restrict',
			'restrict_signature' => 'restrict',
			'restrict_comment' => 'restrict',
			'shadow_ban' => 'ban_perm'
		];
		if (!$this->access('moderation', $perm_map[$type] ?? 'view_reports'))
		{
			notify($this->lang('Permission insuffisante pour ce type de sanction.'), 'danger');
			redirect('admin/moderation/reports/'.$id);
		}

		$opts = [
			'scope'             => (string)($_POST['scope'] ?? 'global'),
			'reason'            => (string)($_POST['reason'] ?? ''),
			'duration_seconds'  => isset($_POST['duration_seconds']) ? (int)$_POST['duration_seconds'] : NULL,
			'issued_by'         => (int)$this->user->id,
			'related_report_id' => (int)$id,
			'notify_user'       => (int)($_POST['notify_user'] ?? 1)
		];

		$sanction_id = $this->moderation->sanction((int)$report['target_user_id'], $type, $opts);
		if (!$sanction_id)
		{
			notify($this->lang('Échec de la création de la sanction.'), 'danger');
			redirect('admin/moderation/reports/'.$id);
		}

		// Marque le report comme actioned
		$this->moderation->update_report_status($id, 'actioned', (int)$this->user->id, NULL, $sanction_id);

		notify($this->lang('Sanction appliquée.'));
		redirect('admin/moderation/reports/'.$id);
	}

	public function _sanction_approve($id)
	{
		if ($this->moderation->approve($id, (int)$this->user->id))
		{
			notify($this->lang('Sanction approuvée.'));
		}
		else
		{
			notify($this->lang('Sanction déjà approuvée ou inexistante.'), 'danger');
		}
		redirect('admin/moderation/sanctions/'.$id);
	}

	public function _sanction_revoke($id)
	{
		$reason = trim((string)($_POST['reason'] ?? ''));
		if ($reason === '')
		{
			notify($this->lang('Une raison est requise pour révoquer une sanction.'), 'danger');
			redirect('admin/moderation/sanctions/'.$id);
		}
		if ($this->moderation->revoke($id, (int)$this->user->id, $reason))
		{
			notify($this->lang('Sanction levée.'));
		}
		else
		{
			notify($this->lang('Sanction inexistante ou déjà levée.'), 'danger');
		}
		redirect('admin/moderation/sanctions/'.$id);
	}

	public function _snapshot_download($id)
	{
		\NF\Modules\Moderation\Controllers\Index::serve_snapshot_download($this, (int)$id);
	}

	// =========================================================================
	// Banlist IPs (R2.0, 2026-05-06)
	// =========================================================================

	public function _banlist($bans, $page)
	{
		$this->title($this->lang('Banlist IPs'));

		$body  = '<div class="d-flex justify-content-between align-items-center mb-3">';
		$body .= '<p class="text-muted mb-0">'.$this->lang('Liste des IPs bloquées (rejet immédiat avant routing). Utiliser avec parcimonie : préférer la modération par compte quand possible.').'</p>';
		$body .= '<a class="btn btn-primary" href="'.url('admin/moderation/banlist/add').'">'.icon('fas fa-plus').' '.$this->lang('Ajouter une IP').'</a>';
		$body .= '</div>';

		$body .= '<table class="table table-hover table-sm"><thead><tr>'
				.'<th>'.$this->lang('IP').'</th>'
				.'<th>'.$this->lang('Raison').'</th>'
				.'<th>'.$this->lang('Banni par').'</th>'
				.'<th>'.$this->lang('Expire').'</th>'
				.'<th>'.$this->lang('Créé le').'</th>'
				.'<th class="text-right" style="width:100px;"></th>'
				.'</tr></thead><tbody>';

		if (empty($bans))
		{
			$body .= '<tr><td colspan="6" class="text-center text-muted">'.$this->lang('Aucune IP bannie').'</td></tr>';
		}
		else
		{
			foreach ($bans as $b)
			{
				$expires = empty($b['expires_at']) ? '<span class="badge badge-danger">'.$this->lang('Permanent').'</span>' : htmlspecialchars($b['expires_at']);
				$by      = !empty($b['banned_by_username']) ? htmlspecialchars($b['banned_by_username']) : '<em class="text-muted">'.$this->lang('Système').'</em>';

				$body .= '<tr>';
				$body .= '<td><code>'.htmlspecialchars($b['ip']).'</code></td>';
				$body .= '<td>'.htmlspecialchars($b['reason'] ?? '').'</td>';
				$body .= '<td>'.$by.'</td>';
				$body .= '<td>'.$expires.'</td>';
				$body .= '<td>'.htmlspecialchars($b['created_at']).'</td>';
				$body .= '<td class="text-right">';
				$body .= '<a class="btn btn-sm btn-danger" href="'.url('admin/moderation/banlist/delete/'.(int)$b['ban_id']).'" data-confirm="'.$this->lang('Supprimer le ban de cette IP ?').'" title="'.$this->lang('Supprimer le ban').'">'.icon('fas fa-trash').'</a>';
				$body .= '</td>';
				$body .= '</tr>';
			}
		}
		$body .= '</tbody></table>';

		return $this	->row(
							$this->col(
								$this->panel()
									->heading($this->lang('Banlist IPs'), 'fas fa-ban')
									->body($body, FALSE)
							)
						);
	}

	public function _banlist_add()
	{
		$this->title($this->lang('Ajouter une IP à la banlist'));

		$form = $this->form2()
			->rule($this->form_text('ip')
						->title($this->lang('IP'))
						->placeholder('1.2.3.4')
						->required())
			->rule($this->form_text('reason')
						->title($this->lang('Raison')))
			->rule($this->form_text('expires_at')
						->title($this->lang('Expire (YYYY-MM-DD HH:MM:SS, vide = permanent)')))
			->success(function($data){
				$ip = trim((string)$data['ip']);
				if (!filter_var($ip, FILTER_VALIDATE_IP))
				{
					notify($this->lang('IP invalide.'), 'danger');
					return;
				}

				$expires = trim((string)($data['expires_at'] ?? ''));

				$exists = $this->db->select('ban_id')->from('nf_ip_banlist')->where('ip', $ip)->row(FALSE);
				if ($exists)
				{
					notify($this->lang('Cette IP est déjà bannie.'), 'warning');
					redirect('admin/moderation/banlist');
				}

				$this->db->insert('nf_ip_banlist', [
					'ip'         => $ip,
					'reason'     => $data['reason'] ?: NULL,
					'banned_by'  => $this->user() ? (int)$this->user->id : NULL,
					'expires_at' => $expires ?: NULL
				]);

				(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('banlist.ip_added', [
					'ip'      => $ip,
					'reason'  => $data['reason'] ?: NULL,
					'expires' => $expires ?: NULL
				]);

				notify($this->lang('IP bannie : %s', $ip));
				redirect('admin/moderation/banlist');
			})
			->submit($this->lang('Bannir'));

		return $this	->row(
							$this->col(
								$this->panel()
									->heading($this->lang('Ajouter une IP à la banlist'), 'fas fa-plus')
									->body($form->display(), FALSE)
							)
						);
	}

	public function _banlist_delete($ban)
	{
		$this->db->where('ban_id', (int)$ban['ban_id'])->delete('nf_ip_banlist');

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('banlist.ip_removed', [
			'ip' => $ban['ip']
		]);

		notify($this->lang('IP retirée de la banlist : %s', $ban['ip']));
		redirect('admin/moderation/banlist');
	}
}
