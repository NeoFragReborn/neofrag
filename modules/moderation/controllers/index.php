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
		$recent_reports = $this->moderation->get_pending_reports(['status' => 'pending'], 0, 10); // il montrait tous les statuts

		$this->title($this->lang('Modération'))->icon('fas fa-shield-alt')->breadcrumb();

		return $this->view('admin/dashboard', [
			'stats'          => $stats,
			'top_reported'   => $top_reported,
			'top_reporters'  => $top_reporters,
			'recent_reports' => $recent_reports,
			'_user_side'     => TRUE
		]);
	}

	/**
	 * « Mes sanctions » (2026-10-09) : ce que la modération a prononcé contre le membre depuis un an, en cours ou passé,
	 * avec son motif — un avertissement dont l'e-mail ne partait pas restait invisible. Dans le cadre de l'espace membre.
	 */
	public function _mes_sanctions()
	{
		$this->title($this->lang('Mes sanctions'))->icon('fas fa-gavel')->breadcrumb();

		$liste = $this->view('mes_sanctions', ['sanctions' => $this->module->mes_sanctions((int) $this->user->id)]);

		return ($espace = $this->module('user')) instanceof \NF\Modules\User\User ? $espace->espace($liste, 'moderation/mes-sanctions') : $liste;
	}

	/**
	 * Une médiation proposée sur l'un de ses signalements (Moderation::proposer_mediation()), pour celui qui l'a fait : ce
	 * qu'elle implique — le membre signalé saura qui l'a signalé —, et sa réponse. Accepter ouvre la conversation ;
	 * refuser laisse le signalement anonyme (Moderation::repondre_mediation()).
	 */
	public function _mediation($id)
	{
		if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST')
		{
			$this->_demo_refuse();

			$this->check_csrf('moderation/mediation/'.(int) $id);

			$reponse = $_POST['reponse'] ?? '';
			$issue   = in_array($reponse, ['oui', 'non'], TRUE)
				? $this->module->repondre_mediation((int) $id, $reponse === 'oui')
				: ['ok' => FALSE, 'message' => (string) $this->lang('Choisis d’accepter ou de refuser.')];

			notify($issue['message'], $issue['ok'] ? 'success' : 'danger');
			redirect(!empty($issue['adresse']) ? $issue['adresse'] : 'moderation/mediation/'.(int) $id);
		}

		$this->title($this->lang('Proposition de médiation'))->icon('fas fa-handshake')->breadcrumb();

		$report = $this->moderation->get_report((int) $id);

		// La conversation ouverte, si elle existe encore : son adresse porte son titre (la messagerie le vérifie).
		$nom = !empty($report['mediation_talk_id']) ? $this->db->select('name')->from('nf_talks')->where('talk_id', (int) $report['mediation_talk_id'])->row() : NULL;

		$page = $this->view('mediation', [
			'report'       => $report,
			'conversation' => is_string($nom) ? 'talks/'.(int) $report['mediation_talk_id'].'/'.url_title($nom) : '',
			'csrf'         => nf_jeton_csrf(),
		]);

		return ($espace = $this->module('user')) instanceof \NF\Modules\User\User ? $espace->espace($page, 'moderation/mediation/'.(int) $id) : $page;
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

		$page_num = (preg_match('#page/(\d+)#', (string) $page, $numero) ? max(0, (int) $numero[1] - 1) : 0); // « page/N » (N dès 1) : `is_numeric('page/2')` laissait toujours la page 1
		$reports = $this->moderation->get_pending_reports($active_filter, $page_num, 50);

		// La vue check `url('admin/moderation/...')` ; on la passe en mode user via flag $_user_side
		// pour que les liens internes pointent vers /moderation/* au lieu de /admin/moderation/*
		return $this->view('admin/reports', [
			'reports'       => $reports,
			'filter'        => $filter,
			'page'          => $page_num,
			'show_reporter' => (bool)$this->access('moderation', 'see_reporter'), // forcé à TRUE ici, sans le droit
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
			$row = $this->db->select('t.type', 't.audience')->from('nf_talks_messages m')
			                ->join('nf_talks t', 't.talk_id = m.talk_id')
			                ->where('m.message_id', (int)$report['target_id'])
			                ->row(FALSE);
			// Le salon de l'équipe (audience « staff ») n'est ouvert qu'aux administrateurs : privé lui aussi (2026-10-09).
			$is_private = is_array($row) && (in_array($row['type'] ?? '', ['direct', 'group'], TRUE) || ($row['audience'] ?? '') === 'staff');
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
			'formulaire_sanction' => (string) $this->view('admin/sanction_form', ['action' => url('moderation/reports/'.(int) $report['id'].'/sanction'), 'csrf' => $this->csrf_token()]),
			'reporter_score' => $reporter_score,
			'target_history' => $target_history,
			'show_reporter'  => (bool)$this->access('moderation', 'see_reporter'),
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
		$page_num = (preg_match('#page/(\d+)#', (string) $page, $numero) ? max(0, (int) $numero[1] - 1) : 0); // « page/N » (N dès 1) : `is_numeric('page/2')` laissait toujours la page 1
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
			// Un formulaire de réglages sans jeton se soumettait depuis une autre page (audit du 2026-10-09).
			$this->check_csrf('moderation/settings');
			$this->module->enregistrer_reglages($_POST);
			notify($this->lang('Réglages modération sauvegardés.'));
			redirect('moderation/settings');
		}

		return $this->view('admin/settings', [
			'csrf'   => $this->csrf_token(),
			'config'     => $this->config,
			'_user_side' => TRUE
		]);
	}

	// === Actions (POST) — délègue aux méthodes admin existantes ===

	public function _report_dismiss($id)
	{
		$this->_demo_refuse();

		$this->check_csrf('moderation/reports');

		$issue = $this->module->classer((int) $id, (string)($_POST['note'] ?? ''));
		notify($issue['message'], $issue['ok'] ? 'success' : 'danger');
		redirect('moderation/reports'.($issue['ok'] ? '' : '/'.(int) $id));
	}

	public function _report_sanction($id)
	{
		$this->_demo_refuse();

		$this->check_csrf('moderation/reports');

		$issue = $this->module->prononcer((int) $id, $_POST);
		notify($issue['message'], $issue['ok'] ? 'success' : 'danger');
		redirect('moderation/reports/'.(int) $id);
	}

	/** Proposer une médiation depuis un signalement (Moderation::proposer_mediation()). */
	public function _report_mediation($id)
	{
		$this->_demo_refuse();

		$this->check_csrf('moderation/reports/'.(int) $id);

		$issue = $this->module->proposer_mediation((int) $id);
		notify($issue['message'], $issue['ok'] ? 'success' : 'danger');
		redirect('moderation/reports/'.(int) $id);
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
			notify($this->moderation->refus_d_approbation((int) $id, (int)$this->user->id) ?? $this->lang('Sanction déjà approuvée ou inexistante.'), 'danger');
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
		// Le refus se dit (refus_de_levee()) : une sanction qui vous vise, ou trop haut placée.
		$refus = $this->moderation->refus_de_levee((int) $id, (int)$this->user->id);

		if ($refus === NULL && $this->moderation->revoke($id, (int)$this->user->id, $reason))
		{
			notify($this->lang('Sanction levée.'));
		}
		else
		{
			notify($refus ?? $this->lang('Sanction inexistante ou déjà levée.'), 'danger');
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
			$row = $controller->db->select('t.type', 't.audience')->from('nf_talks_messages m')
				->join('nf_talks t', 't.talk_id = m.talk_id')
				->where('m.message_id', (int)$snap['target_id'])
				->row(FALSE);
			$is_private = is_array($row) && (in_array($row['type'] ?? '', ['direct', 'group'], TRUE) || ($row['audience'] ?? '') === 'staff');
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

		$docroot   = realpath(NEOFRAG_CMS); // NEOFRAG_PATH n'existait pas (audit du 2026-10-09)
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
