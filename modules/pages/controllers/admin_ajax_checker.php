<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Pages\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Ajax_Checker extends Module_Checker
{
	/** Palier 1 — valide la sauvegarde des blocs : admin effectif + droit de modif + page existante. */
	public function save_instances()
	{
		if (!$this->user() || !$this->access->effective_admin() || !$this->is_authorized('modify_pages'))
		{
			return;
		}

		$post    = post();
		$page_id = (int) ($post['page_id'] ?? 0);

		if (!$page_id || !$this->db->select('page_id')->from('nf_pages')->where('page_id', $page_id)->row(FALSE))
		{
			return;
		}

		return [$page_id, (string) ($post['instances'] ?? '[]')];
	}
}
