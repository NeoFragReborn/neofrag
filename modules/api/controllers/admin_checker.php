<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Api\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index()
	{
		$this->_droit();

		return [];
	}

	public function _add()
	{
		$this->_droit();

		return [];
	}

	public function _revoke($token_id, $title)
	{
		$this->_droit();

		$cle = NeoFrag()->db->select('token_id', 'name', 'revoked_at')->from('nf_api_tokens')->where('token_id', (int) $token_id)->row();

		return is_array($cle) && $cle ? [$cle] : NULL;
	}

	private function _droit(): void
	{
		if (!$this->is_authorized('manage'))
		{
			$this->error->unauthorized();
		}
	}
}
