<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Moderation\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index()
	{
		if (!$this->access('moderation', 'view_reports'))
		{
			$this->error->unauthorized();
			return;
		}
		return [];
	}

	public function _reports($page = '')
	{
		if (!$this->access('moderation', 'view_reports'))
		{
			$this->error->unauthorized();
			return;
		}
		return [$page];
	}

	public function _report_detail($id)
	{
		if (!$this->access('moderation', 'view_reports'))
		{
			$this->error->unauthorized();
			return;
		}
		return [(int)$id];
	}

	public function _sanctions($page = '')
	{
		if (!$this->access('moderation', 'view_reports'))
		{
			$this->error->unauthorized();
			return;
		}
		return [$page];
	}

	public function _sanction_detail($id)
	{
		if (!$this->access('moderation', 'view_reports'))
		{
			$this->error->unauthorized();
			return;
		}
		return [(int)$id];
	}

	public function _user_history($id)
	{
		if (!$this->access('moderation', 'view_reports'))
		{
			$this->error->unauthorized();
			return;
		}
		// Le compte de secours d'une démonstration est introuvable ici comme partout (nf_compte_masque()).
		if ((int)$id === nf_compte_masque())
		{
			return;
		}
		return [(int)$id];
	}

	public function _settings()
	{
		// Réglages = permission dédiée manage_settings (les admins globaux la possèdent).
		if (!$this->access('moderation', 'manage_settings'))
		{
			$this->error->unauthorized();
			return;
		}
		return [];
	}

	public function _report_dismiss($id)    { return $this->_check_handle($id); }
	public function _report_sanction($id)   { return $this->_check_handle($id); }

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

	private function _check_handle($id)
	{
		if (!$this->access('moderation', 'handle_reports'))
		{
			$this->error->unauthorized();
			return;
		}
		return [(int)$id];
	}

	public function _snapshot_download($id)
	{
		if (!$this->access('moderation', 'view_reports'))
		{
			$this->error->unauthorized();
			return;
		}
		return [(int)$id];
	}

	// =========================================================================
	// Banlist IPs (R2.0, 2026-05-06) — admin senior (effective_admin)
	// =========================================================================

	public function _banlist($page = '')
	{
		if (!$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}

		// Cleanup expired bans à la volée
		NeoFrag()->db->execute('DELETE FROM nf_ip_banlist WHERE expires_at IS NOT NULL AND expires_at < NOW()');

		$bans = NeoFrag()->db	->select('b.ban_id', 'b.ip', 'b.reason', 'b.expires_at', 'b.created_at', 'u.username AS banned_by_username')
								->from('nf_ip_banlist b')
								->join('nf_user u', 'u.id = b.banned_by', 'LEFT')
								->order_by('b.created_at DESC')
								->get(FALSE);

		return [$bans, $page];
	}

	public function _banlist_add()
	{
		if (!$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}
		return [];
	}

	public function _banlist_delete($ban_id)
	{
		if (!$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}

		$ban = NeoFrag()->db	->select('ban_id', 'ip')
								->from('nf_ip_banlist')
								->where('ban_id', (int)$ban_id)
								->row(FALSE);

		if (!$ban)
		{
			$this->error();
			return;
		}

		return [$ban];
	}
}
