<?php
declare(strict_types=1);
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
		$recent_reports = $this->moderation->get_pending_reports(['status' => 'pending'], 0, 10); // il montrait tous les statuts

		$this->title($this->lang('Modération'))->icon('fas fa-shield-alt');

		// Les actions de la page vivent dans la BARRE D'OUTILS, comme sur tous les autres écrans
		// d'administration. Elles etaient en bas a droite du contenu : meme type de bouton, place
		// ailleurs d'une page a l'autre — c'est ce qui donne l'impression d'incoherence.
		$this->add_action($this->button($this->lang('Sanctions'), 'fas fa-gavel', 'secondary')->url('admin/moderation/sanctions'));

		if ($this->access('moderation', 'manage_settings'))
		{
			$this->add_action($this->button($this->lang('Réglages'), 'fas fa-cogs', 'secondary')->url('admin/moderation/settings'));
		}

		// La liste des adresses IP bannies : aucun lien n'y menait (audit du 2026-10-09).
		if ($this->access->effective_admin())
		{
			$this->add_action($this->button($this->lang('Adresses IP bannies'), 'fas fa-network-wired', 'secondary')->url('admin/moderation/banlist'));
		}

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

		$page_num = (preg_match('#page/(\d+)#', (string) $page, $numero) ? max(0, (int) $numero[1] - 1) : 0); // « page/N » (N dès 1) : `is_numeric('page/2')` laissait toujours la page 1
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
			$this->error();
			return;
		}

		// Phase 5 privacy — si le report concerne un contenu privé (talks direct/group),
		// audit log l'accès (qui a vu, quand, quel report) + permission stricte access_private requise.
		if ($report['target_type'] === 'talks_message')
		{
			$row = $this->db->select('t.type')->from('nf_talks_messages m')
			                ->join('nf_talks t', 't.talk_id = m.talk_id')
			                ->where('m.message_id', (int)$report['target_id'])
			                ->row(FALSE);
			// row(FALSE) : sans lui, une colonne seule rend sa VALEUR, `is_array()` répondait toujours non, et un
			// modérateur sans le droit access_private ouvrait le signalement d'un message privé, sans trace au
			// journal d'audit (relevé le 2026-10-03).
			$is_private = is_array($row) && in_array($row['type'] ?? '', ['direct', 'group'], TRUE);
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
			'csrf'           => $this->csrf_token(),
			'formulaire_sanction' => (string) $this->view('admin/sanction_form', ['action' => url('admin/moderation/reports/'.(int) $report['id'].'/sanction'), 'csrf' => $this->csrf_token()]),
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
		$page_num = (preg_match('#page/(\d+)#', (string) $page, $numero) ? max(0, (int) $numero[1] - 1) : 0); // « page/N » (N dès 1) : `is_numeric('page/2')` laissait toujours la page 1
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
			$this->error();
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
			'csrf' => $this->csrf_token(),
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
			$this->error();
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

		// Sanctionner directement, sans signalement, qui en a le droit (2026-10-09) ; jamais soi-même.
		$peut = (int) $user_id !== (int) $this->user->id && array_filter(array_unique(\NF\Modules\Moderation\Moderation::DROITS_DES_TYPES), fn ($droit) => $this->access('moderation', $droit));

		return $this->view('admin/user_history', [
			'user'             => $user,
			'timeline'         => $timeline,
			'active_sanctions' => $active_sanctions,
			'reporter_score'   => $reporter_score,
			'formulaire_sanction' => $peut ? (string) $this->view('admin/sanction_form', ['action' => url('admin/moderation/users/'.(int) $user_id.'/sanction'), 'csrf' => $this->csrf_token()]) : '',
		]);
	}

	/** Sanctionner un membre depuis son historique (Moderation::sanctionner()). */
	public function _user_sanction($user_id)
	{
		$this->check_csrf('admin/moderation/users/'.(int) $user_id);

		$issue = $this->module->sanctionner((int) $user_id, $_POST);
		notify($issue['message'], $issue['ok'] ? 'success' : 'danger');
		redirect('admin/moderation/users/'.(int) $user_id);
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
			// Un formulaire de réglages sans jeton se soumettait depuis une autre page (audit du 2026-10-09).
			$this->check_csrf('admin/moderation/settings');
			$this->module->enregistrer_reglages($_POST);
			notify($this->lang('Réglages modération sauvegardés.'));
			redirect('admin/moderation/settings');
		}

		return $this->view('admin/settings', [
			'csrf'   => $this->csrf_token(),
			'config' => $this->config
		]);
	}

	// === Actions admin (POST) ===

	public function _report_dismiss($id)
	{
		$this->check_csrf('admin/moderation/reports');

		$issue = $this->module->classer((int) $id, (string)($_POST['note'] ?? ''));
		notify($issue['message'], $issue['ok'] ? 'success' : 'danger');
		redirect('admin/moderation/reports'.($issue['ok'] ? '' : '/'.(int) $id));
	}

	public function _report_sanction($id)
	{
		$this->check_csrf('admin/moderation/reports');

		$issue = $this->module->prononcer((int) $id, $_POST);
		notify($issue['message'], $issue['ok'] ? 'success' : 'danger');
		redirect('admin/moderation/reports/'.(int) $id);
	}

	/** Proposer une médiation depuis un signalement (Moderation::proposer_mediation()). */
	public function _report_mediation($id)
	{
		$this->check_csrf('admin/moderation/reports/'.(int) $id);

		$issue = $this->module->proposer_mediation((int) $id);
		notify($issue['message'], $issue['ok'] ? 'success' : 'danger');
		redirect('admin/moderation/reports/'.(int) $id);
	}

	public function _sanction_approve($id)
	{
		$this->check_csrf('admin/moderation/sanctions');

		if ($this->moderation->approve($id, (int)$this->user->id))
		{
			notify($this->lang('Sanction approuvée.'));
		}
		else
		{
			notify($this->moderation->refus_d_approbation((int) $id, (int)$this->user->id) ?? $this->lang('Sanction déjà approuvée ou inexistante.'), 'danger');
		}
		redirect('admin/moderation/sanctions/'.$id);
	}

	public function _sanction_revoke($id)
	{
		$this->check_csrf('admin/moderation/sanctions');

		$reason = trim((string)($_POST['reason'] ?? ''));
		if ($reason === '')
		{
			notify($this->lang('Une raison est requise pour révoquer une sanction.'), 'danger');
			redirect('admin/moderation/sanctions/'.$id);
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
		$this->title($this->lang('Banlist IPs'))->icon('fas fa-ban');

		// Une carte d'administration ordinaire : l'aide et le tableau dans un corps qui a ses marges, le bouton dans
		// l'en-tête. Le corps était déclaré sans marge, comme pour un tableau seul : la phrase et le bouton touchaient
		// les bords de la carte (signalé le 2026-10-10).
		$aide    = '<p class="small text-body-secondary mb-3">'.$this->lang('Liste des IPs bloquées (rejet immédiat avant routing). Utiliser avec parcimonie : préférer la modération par compte quand possible.').'</p>';
		$ajouter = '<a class="btn btn-primary btn-sm" href="'.url('admin/moderation/banlist/add').'">'.icon('fas fa-plus').' '.$this->lang('Ajouter une IP').'</a>';

		if (empty($bans))
		{
			return $this->admin_card('fas fa-ban', $this->lang('Banlist IPs'), $aide.$this->admin_empty('fas fa-ban', (string) $this->lang('Aucune IP bannie')), '', $ajouter);
		}

		$lignes = '';

		foreach ($bans as $b)
		{
			$expires = empty($b['expires_at']) ? '<span class="badge text-bg-danger">'.$this->lang('Permanent').'</span>' : nf_date_heure($b['expires_at']);
			$by      = !empty($b['banned_by_username']) ? nf_texte($b['banned_by_username']) : '<em class="text-muted">'.$this->lang('Système').'</em>';

			$lignes .= '<tr>'
				.'<td><code>'.nf_texte($b['ip']).'</code></td>'
				.'<td>'.nf_texte($b['reason'] ?? '').'</td>'
				.'<td>'.$by.'</td>'
				.'<td>'.$expires.'</td>'
				.'<td>'.nf_date_heure($b['created_at']).'</td>'
				.'<td class="text-end"><a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/moderation/banlist/delete/'.(int)$b['ban_id']).'" data-confirm="'.$this->lang('Supprimer le ban de cette IP ?').'" title="'.$this->lang('Supprimer le ban').'">'.icon('far fa-trash-alt').'</a></td>'
				.'</tr>';
		}

		$tableau = '<div class="table-responsive"><table class="table table-hover table-sm align-middle mb-0"><thead><tr>'
			.'<th>'.$this->lang('IP').'</th>'
			.'<th>'.$this->lang('Raison').'</th>'
			.'<th>'.$this->lang('Banni par').'</th>'
			.'<th>'.$this->lang('Expire').'</th>'
			.'<th>'.$this->lang('Créé le').'</th>'
			.'<th class="text-end" style="width:100px;"></th>'
			.'</tr></thead><tbody>'.$lignes.'</tbody></table></div>';

		return $this->admin_card('fas fa-ban', $this->lang('Banlist IPs'), $aide.$tableau, '', $ajouter);
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

				// Sa propre adresse : l'administrateur se bannissait de tout le site, administration comprise.
				if ($ip === \NF\NeoFrag\Libraries\Rate_Limit::client_ip())
				{
					notify($this->lang('C’est ton adresse : tu te bannirais toi-même.'), 'danger');
					return;
				}

				// Une échéance lisible et à venir, mise en forme pour la base : un texte libre passait tel quel, et une
				// date invalide donnait « IP bannie » sans rien enregistrer (audit du 2026-10-09).
				$expires = trim((string)($data['expires_at'] ?? ''));

				if ($expires !== '')
				{
					$echeance = strtotime($expires);

					if ($echeance === FALSE || $echeance <= time())
					{
						notify($this->lang('Date d’expiration illisible ou passée.'), 'danger');
						return;
					}

					$expires = date('Y-m-d H:i:s', min($echeance, strtotime('2037-12-31 23:59:59')));
				}

				$exists = $this->db->select('ban_id')->from('nf_ip_banlist')->where('ip', $ip)->row(FALSE);
				if ($exists)
				{
					notify($this->lang('Cette IP est déjà bannie.'), 'warning');
					redirect('admin/moderation/banlist');
				}

				if (!$this->db->insert('nf_ip_banlist', [
					'ip'         => $ip,
					'reason'     => $data['reason'] ?: NULL,
					'banned_by'  => $this->user() ? (int)$this->user->id : NULL,
					'expires_at' => $expires ?: NULL
				]))
				{
					notify($this->lang('L’adresse n’a pas pu être enregistrée.'), 'danger');
					return;
				}

				(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('banlist.ip_added', [
					'ip'      => $ip,
					'reason'  => $data['reason'] ?: NULL,
					'expires' => $expires ?: NULL
				]);

				notify($this->lang('IP bannie : %s', $ip));
				redirect('admin/moderation/banlist');
			})
			->submit($this->lang('Bannir'));

		return $this->admin_back('admin/moderation/banlist')
			.$this->admin_card('fas fa-plus', $this->lang('Ajouter une IP à la banlist'), $form->display());
	}

	public function _banlist_delete($ban)
	{
		$this->check_csrf('admin/moderation/banlist');

		$this->db->where('ban_id', (int)$ban['ban_id'])->delete('nf_ip_banlist');

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('banlist.ip_removed', [
			'ip' => $ban['ip']
		]);

		notify($this->lang('IP retirée de la banlist : %s', $ban['ip']));
		redirect('admin/moderation/banlist');
	}
}
