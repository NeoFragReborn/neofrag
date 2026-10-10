<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Auth checker user-side du module Modération.
 * Bloque l'accès aux pages /moderation/* si l'user n'a pas la permission view_reports.
 */

namespace NF\Modules\Moderation\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()             { return $this->_check(); }

	/** « Mes sanctions » : à tout membre connecté, pour les siennes. */
	public function _mes_sanctions()
	{
		if (!$this->user())
		{
			$this->error->unconnected();
			return;
		}

		return [];
	}

	/** Une médiation proposée : à celui qui a fait le signalement, et à lui seul (Moderation::proposer_mediation()). */
	public function _mediation($id)
	{
		if (!$this->user())
		{
			$this->error->unconnected();
			return;
		}

		// Introuvable (rien de rendu) pour tout autre que lui : qu'une médiation existe ne regarde que lui.
		$report = $this->moderation->get_report((int) $id);

		if ($report && (int) $report['reporter_id'] === (int) $this->user->id && !empty($report['mediation_le']))
		{
			return [(int) $id];
		}
	}
	public function _reports($page = '') { return $this->_check([$page]); }
	public function _report_detail($id)  { return $this->_check([(int)$id]); }
	public function _sanctions($page = '') { return $this->_check([$page]); }
	public function _sanction_detail($id)  { return $this->_check([(int)$id]); }
	// Le compte de secours d'une démonstration est introuvable ici comme partout (nf_compte_masque()).
	public function _user_history($id)   { return ($args = $this->_check([(int)$id])) && (int)$id !== nf_compte_masque() ? $args : NULL; }

	// Classer et sanctionner demandent de traiter les signalements, comme dans l'administration : le droit de les voir
	// suffisait ici (audit du 2026-10-09).
	public function _report_dismiss($id)    { return $this->_check([(int)$id]) && $this->access('moderation', 'handle_reports') ? [(int)$id] : $this->_refus(); }
	public function _report_sanction($id)   { return $this->_check([(int)$id]) && $this->access('moderation', 'handle_reports') ? [(int)$id] : $this->_refus(); }
	public function _report_mediation($id)  { return $this->_check([(int)$id]) && $this->access('moderation', 'mediation') ? [(int)$id] : $this->_refus(); }
	public function _snapshot_download($id) { return $this->_check([(int)$id]); }
	public function _sanction_approve($id)
	{
		if (!$this->access('moderation', 'approve'))
		{
			$this->error->unauthorized();
			return;
		}
		return [(int)$id];
	}
	public function _sanction_revoke($id)
	{
		if (!$this->access('moderation', 'revoke'))
		{
			$this->error->unauthorized();
			return;
		}
		return [(int)$id];
	}

	public function _settings()
	{
		if (!$this->user())
		{
			redirect('user');
		}
		if (!$this->access('moderation', 'manage_settings'))
		{
			$this->error->unauthorized();
			return;
		}
		return [];
	}

	private function _refus()
	{
		$this->error->unauthorized();
	}

	private function _check(array $args = [])
	{
		// Doit être connecté + avoir au moins view_reports
		if (!$this->user())
		{
			redirect('user');
		}
		if (!$this->access('moderation', 'view_reports'))
		{
			$this->error->unauthorized();
			return;
		}
		return $args;
	}
}
