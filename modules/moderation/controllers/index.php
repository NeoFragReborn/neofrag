<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Controller user-side du module Modération (theme dungeon, depuis l'espace membre).
 * Les modos peuvent travailler depuis leur espace membre sans passer par le panel admin.
 *
 * Réutilise la même logique métier que controllers/admin.php (mêmes pages : dashboard,
 * reports, sanctions, user_history) mais render dans le theme du site (dungeon),
 * pas dans le theme admin.
 *
 * Les permissions sont les mêmes : un user sans `view_reports` est rejeté.
 */

namespace NF\Modules\Moderation\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index()
	{
		$this->css('moderation');

		$stats         = $this->model()->dashboard_stats();
		$top_reported  = $this->model()->top_reported_users(10);
		$top_reporters = $this->model()->top_reporters(10);
		$recent_reports = $this->moderation->get_pending_reports([], 0, 10);

		$this->title($this->lang('Modération'))->icon('fas fa-shield-alt')->breadcrumb();

		return $this->view('admin/dashboard', [
			'stats'          => $stats,
			'top_reported'   => $top_reported,
			'top_reporters'  => $top_reporters,
			'recent_reports' => $recent_reports,
			'_user_side'     => TRUE
		]);
	}

	public function _reports($page = '')
	{
		$this->css('moderation');
		$this	->title($this->lang('Signalements'))
				->icon('fas fa-flag')
				->breadcrumb($this->lang('Modération'), 'moderation')
				->breadcrumb($this->lang('Signalements'));

		$filter = [
			'status'      => isset($_GET['status']) && in_array($_GET['status'], ['pending','reviewed','actioned','dismissed','duplicate'], TRUE) ? $_GET['status'] : '',
			'target_type' => trim((string)($_GET['target_type'] ?? '')),
			'reason'      => isset($_GET['reason']) && in_array($_GET['reason'], ['spam','harassment','illegal','nsfw','misinformation','duplicate','other'], TRUE) ? $_GET['reason'] : ''
		];
		$active_filter = array_filter($filter);

		$page_num = is_numeric($page) ? (int)$page : 0;
		$reports = $this->moderation->get_pending_reports($active_filter, $page_num, 50);

		// La vue check `url('admin/moderation/...')` ; on la passe en mode user via flag $_user_side
		// pour que les liens internes pointent vers /moderation/* au lieu de /admin/moderation/*
		return $this->view('admin/reports', [
			'reports'       => $reports,
			'filter'        => $filter,
			'page'          => $page_num,
			'show_reporter' => TRUE,
			'_user_side'    => TRUE
		]);
	}

	public function _report_detail($id)
	{
		$report = $this->moderation->get_report($id);
		if (!$report)
		{
			$this->error();
			return;
		}

		// Privacy : audit log + permission stricte si conv privée
		if ($report['target_type'] === 'talks_message')
		{
			$row = $this->db->select('t.type')->from('nf_talks_messages m')
			                ->join('nf_talks t', 't.talk_id = m.talk_id')
			                ->where('m.message_id', (int)$report['target_id'])
			                ->row(FALSE);
			$is_private = is_array($row) && in_array($row['type'], ['direct', 'group'], TRUE);
			if ($is_private && !$this->access('moderation', 'access_private'))
			{
				notify($this->lang('Permission insuffisante : ce signalement concerne une conversation privée.'), 'danger');
				redirect('moderation/reports');
			}
			if ($is_private)
			{
				try
				{
					(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('moderation.private_access', [
						'report_id'   => $id,
						'admin_id'    => $this->user->id,
						'target_type' => $report['target_type'],
						'target_id'   => $report['target_id']
					]);
				}
				catch (\Throwable $e) {}
			}
		}

		$this->css('moderation');
		$this	->title($this->lang('Signalement #%d', $id))
				->icon('fas fa-flag')
				->breadcrumb($this->lang('Modération'), 'moderation')
				->breadcrumb($this->lang('Signalements'), 'moderation/reports')
				->breadcrumb('#'.$id);

		$reporter_score = $report['reporter_id'] ? $this->moderation->reporter_quality_score((int)$report['reporter_id']) : NULL;

		$target_history = NULL;
		if ($report['target_user_id'])
		{
			$target_history = [
				'reports_received' => $this->moderation->get_user_reports_received((int)$report['target_user_id'], 20),
				'active_sanctions' => $this->moderation->active_sanctions((int)$report['target_user_id'])
			];
		}

		return $this->view('admin/report_detail', [
			'report'         => $report,
			'csrf'           => $this->csrf_token(),
			'reporter_score' => $reporter_score,
			'target_history' => $target_history,
			'show_reporter'  => TRUE,
			'_user_side'     => TRUE
		]);
	}

	public function _sanctions($page = '')
	{
		$this->css('moderation');
		$this	->title($this->lang('Sanctions'))
				->icon('fas fa-gavel')
				->breadcrumb($this->lang('Modération'), 'moderation')
				->breadcrumb($this->lang('Sanctions'));

		$filter = [
			'active_only'      => !empty($_GET['active_only']),
			'pending_approval' => !empty($_GET['pending_approval']),
			'type'             => trim((string)($_GET['type'] ?? ''))
		];
		$page_num = is_numeric($page) ? (int)$page : 0;
		$sanctions = $this->model()->get_sanctions($filter, $page_num, 50);

		return $this->view('admin/sanctions', [
			'sanctions'  => $sanctions,
			'filter'     => $filter,
			'page'       => $page_num,
			'_user_side' => TRUE
		]);
	}

	public function _sanction_detail($id)
	{
		$sanction = $this->model()->get_sanction($id);
		if (!$sanction)
		{
			$this->error();
			return;
		}

		$this->css('moderation');
		$this	->title($this->lang('Sanction #%d', $id))
				->icon('fas fa-gavel')
				->breadcrumb($this->lang('Modération'), 'moderation')
				->breadcrumb($this->lang('Sanctions'), 'moderation/sanctions')
				->breadcrumb('#'.$id);

		return $this->view('admin/sanction_detail', [
			'sanction'    => $sanction,
			'csrf'        => $this->csrf_token(),
			'can_approve' => (bool)$this->access('moderation', 'approve'),
			'can_revoke'  => (bool)$this->access('moderation', 'revoke'),
			'_user_side'  => TRUE
		]);
	}

	public function _user_history($user_id)
	{
		$user = $this->db->select('id', 'username', 'email', 'admin', 'deleted', 'registration_date')
						 ->from('nf_user')
						 ->where('id', $user_id)
						 ->row(FALSE);
		if (!is_array($user) || empty($user))
		{
			$this->error();
			return;
		}

		$this->css('moderation');
		$this	->title($this->lang('Historique modération de %s', $user['username']))
				->icon('fas fa-history')
				->breadcrumb($this->lang('Modération'), 'moderation')
				->breadcrumb($user['username']);

		$timeline         = $this->moderation->user_history($user_id);
		$active_sanctions = $this->moderation->active_sanctions($user_id);
		$reporter_score   = $this->moderation->reporter_quality_score($user_id);

		return $this->view('admin/user_history', [
			'user'             => $user,
			'timeline'         => $timeline,
			'active_sanctions' => $active_sanctions,
			'reporter_score'   => $reporter_score,
			'_user_side'       => TRUE
		]);
	}

	public function _settings()
	{
		$this->_demo_refuse();

		$this->css('moderation');
		$this	->title($this->lang('Réglages modération'))
				->icon('fas fa-cogs')
				->breadcrumb($this->lang('Modération'), 'moderation')
				->breadcrumb($this->lang('Réglages'));

		// Même logique de sauvegarde que le panel admin (controllers/admin.php::_settings).
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
			redirect('moderation/settings');
		}

		return $this->view('admin/settings', [
			'config'     => $this->config,
			'_user_side' => TRUE
		]);
	}

	// === Actions (POST) — délègue aux méthodes admin existantes ===

	public function _report_dismiss($id)
	{
		$this->_demo_refuse();

		$this->check_csrf('moderation/reports');

		$report = $this->moderation->get_report($id);
		if (!$report) { notify($this->lang('Signalement introuvable.'), 'danger'); redirect('moderation/reports'); }

		$note = trim((string)($_POST['note'] ?? ''));
		$this->moderation->update_report_status($id, 'dismissed', (int)$this->user->id, $note);
		notify($this->lang('Signalement marqué comme dismissed.'));
		redirect('moderation/reports');
	}

	public function _report_sanction($id)
	{
		$this->_demo_refuse();

		$this->check_csrf('moderation/reports');

		$report = $this->moderation->get_report($id);
		if (!$report) { notify($this->lang('Signalement introuvable.'), 'danger'); redirect('moderation/reports'); }
		if (!$report['target_user_id']) { notify($this->lang('Pas de user cible identifié.'), 'danger'); redirect('moderation/reports/'.$id); }

		$type = (string)($_POST['type'] ?? '');
		$valid_types = ['warning','mute','ban_temp','ban_perm','restrict_upload','restrict_links','restrict_avatar','restrict_signature','restrict_comment','shadow_ban'];
		if (!in_array($type, $valid_types, TRUE))
		{
			notify($this->lang('Type de sanction invalide.'), 'danger');
			redirect('moderation/reports/'.$id);
		}

		$perm_map = [
			'warning' => 'warn', 'mute' => 'mute', 'ban_temp' => 'ban_temp', 'ban_perm' => 'ban_perm',
			'restrict_upload' => 'restrict', 'restrict_links' => 'restrict', 'restrict_avatar' => 'restrict',
			'restrict_signature' => 'restrict', 'restrict_comment' => 'restrict', 'shadow_ban' => 'ban_perm'
		];
		if (!$this->access('moderation', $perm_map[$type] ?? 'view_reports'))
		{
			notify($this->lang('Permission insuffisante pour ce type de sanction.'), 'danger');
			redirect('moderation/reports/'.$id);
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
			notify($this->lang('Échec de la création de la sanction (permissions hiérarchiques ou auto-protection).'), 'danger');
			redirect('moderation/reports/'.$id);
		}

		$this->moderation->update_report_status($id, 'actioned', (int)$this->user->id, NULL, $sanction_id);
		notify($this->lang('Sanction appliquée.'));
		redirect('moderation/reports/'.$id);
	}

	public function _sanction_approve($id)
	{
		$this->_demo_refuse();

		$this->check_csrf('moderation/sanctions');

		if ($this->moderation->approve($id, (int)$this->user->id))
		{
			notify($this->lang('Sanction approuvée.'));
		}
		else
		{
			notify($this->lang('Sanction déjà approuvée ou inexistante.'), 'danger');
		}
		redirect('moderation/sanctions/'.$id);
	}

	public function _sanction_revoke($id)
	{
		$this->_demo_refuse();

		$this->check_csrf('moderation/sanctions');

		$reason = trim((string)($_POST['reason'] ?? ''));
		if ($reason === '')
		{
			notify($this->lang('Une raison est requise pour révoquer une sanction.'), 'danger');
			redirect('moderation/sanctions/'.$id);
		}
		if ($this->moderation->revoke($id, (int)$this->user->id, $reason))
		{
			notify($this->lang('Sanction levée.'));
		}
		else
		{
			notify($this->lang('Sanction inexistante ou déjà levée.'), 'danger');
		}
		redirect('moderation/sanctions/'.$id);
	}

	public function _snapshot_download($id)
	{
		$this->_demo_refuse();

		self::serve_snapshot_download($this, (int)$id);
	}

	/**
	 * Sert un fichier de copie défensive avec auth + audit log si conv privée.
	 * Méthode statique partagée entre admin.php et index.php (user-side).
	 * Le checker a déjà validé `view_reports`. Ici on fait l'audit + check privacy talks.
	 */
	public static function serve_snapshot_download($controller, int $snapshot_id): void
	{
		$snap = $controller->db->select('s.id', 's.report_id', 's.original_name', 's.mime_type', 's.file_size', 's.backup_path', 's.sha256_hash',
			'r.target_type', 'r.target_id')
			->from('nf_reports_attachments_snapshot s')
			->join('nf_reports r', 'r.id = s.report_id')
			->where('s.id', $snapshot_id)
			->row(FALSE);

		if (!is_array($snap) || empty($snap))
		{
			$controller->error();
			return;
		}

		// Privacy : si conv privée talks, exiger access_private + audit log
		if ($snap['target_type'] === 'talks_message')
		{
			$row = $controller->db->select('t.type')->from('nf_talks_messages m')
				->join('nf_talks t', 't.talk_id = m.talk_id')
				->where('m.message_id', (int)$snap['target_id'])
				->row(FALSE);
			$is_private = is_array($row) && in_array($row['type'], ['direct', 'group'], TRUE);
			if ($is_private && !$controller->access('moderation', 'access_private'))
			{
				$controller->error->unauthorized();
				return;
			}
			if ($is_private)
			{
				try
				{
					(new \NF\NeoFrag\Libraries\Audit_Log($controller))->log('moderation.snapshot_download_private', [
						'snapshot_id' => $snapshot_id,
						'report_id'   => $snap['report_id'],
						'admin_id'    => (int)$controller->user->id,
						'file_name'   => $snap['original_name']
					]);
				}
				catch (\Throwable $e) {}
			}
		}

		$docroot   = realpath(NEOFRAG_PATH);
		$file_path = $docroot.'/backups/'.ltrim((string)$snap['backup_path'], '/');
		if (!is_file($file_path) || !is_readable($file_path))
		{
			$controller->error();
			return;
		}

		// Anti path traversal : on s'assure que file_path est bien dans backups/moderation/
		$expected_prefix = $docroot.'/backups/moderation/reports/';
		if (strpos(realpath($file_path), $expected_prefix) !== 0)
		{
			$controller->error->unauthorized();
			return;
		}

		// Audit log standard pour tout download
		try
		{
			(new \NF\NeoFrag\Libraries\Audit_Log($controller))->log('moderation.snapshot_download', [
				'snapshot_id' => $snapshot_id,
				'report_id'   => $snap['report_id'],
				'admin_id'    => (int)$controller->user->id,
				'file_name'   => $snap['original_name']
			]);
		}
		catch (\Throwable $e) {}

		$mime = $snap['mime_type'] ?: 'application/octet-stream';
		$dl_name = preg_replace('/[\r\n"]/', '_', (string)$snap['original_name']) ?: 'snapshot';

		// Pas de buffer NeoFrag : on output direct
		while (ob_get_level() > 0) ob_end_clean();
		header('Content-Type: '.$mime);
		header('Content-Disposition: attachment; filename="'.$dl_name.'"');
		header('Content-Length: '.filesize($file_path));
		header('X-Content-Type-Options: nosniff');
		header('Cache-Control: private, no-cache, no-store, must-revalidate');
		readfile($file_path);
		exit;
	}

	/**
	 * Sur la démonstration, le panneau des modérateurs se consulte sans agir : une sanction, un
	 * réglage ou un instantané touchaient des tables et des fichiers que la remise à zéro ne restaure
	 * pas — un visiteur bannissait un compte de démo pour de bon (audit du 2026-10-02).
	 */
	private function _demo_refuse(): void
	{
		if (nf_demo())
		{
			notify($this->lang('Action désactivée sur le site de démonstration.'), 'warning');
			redirect('moderation');
		}
	}
}
