<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Newsletter\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		return [];
	}

	public function _confirm($token)
	{
		$sub = NeoFrag()->db	->select('id', 'email', 'confirmed')
								->from('nf_newsletter_subscribers')
								->where('token', $token)
								->row();

		if (empty($sub))
		{
			return;
		}

		return [$sub];
	}

	public function _unsubscribe($token)
	{
		$sub = NeoFrag()->db	->select('id', 'email')
								->from('nf_newsletter_subscribers')
								->where('token', $token)
								->row();

		if (empty($sub))
		{
			return;
		}

		return [$sub];
	}
}
