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
	public function _reports($page = '') { return $this->_check([$page]); }
	public function _report_detail($id)  { return $this->_check([(int)$id]); }
	public function _sanctions($page = '') { return $this->_check([$page]); }
	public function _sanction_detail($id)  { return $this->_check([(int)$id]); }
	public function _user_history($id)   { return $this->_check([(int)$id]); }

	public function _report_dismiss($id)    { return $this->_check([(int)$id]); }
	public function _report_sanction($id)   { return $this->_check([(int)$id]); }
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
