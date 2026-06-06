<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Newsletter\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index()
	{
		return [];
	}

	public function _campaigns()
	{
		$campaigns = NeoFrag()->db	->select('c.*', 'u.username')
									->from('nf_newsletter_campaigns c')
									->join('nf_user u', 'c.user_id = u.id', 'LEFT')
									->order_by('c.id DESC')
									->get();
		return [$campaigns];
	}

	public function _subscribers()
	{
		$subs = NeoFrag()->db	->select('id', 'email', 'confirmed', 'UNIX_TIMESTAMP(created_at) AS ts')
								->from('nf_newsletter_subscribers')
								->order_by('id DESC')
								->get();
		return [$subs];
	}

	public function _compose()
	{
		return [];
	}

	public function _subscriber_delete($id)
	{
		return [(int)$id];
	}
}
