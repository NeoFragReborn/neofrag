<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Webhooks\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index($page = '')
	{
		return [NeoFrag()->db->select('*')->from('nf_webhooks')->order_by('title ASC')->get()];
	}

	public function _add() { return [NULL]; }

	public function _edit($id, $title)
	{
		$h = NeoFrag()->db->select('*')->from('nf_webhooks')->where('id', $id)->row();
		return $h ? [$h] : NULL;
	}

	public function _test($id, $title)
	{
		$h = NeoFrag()->db->select('*')->from('nf_webhooks')->where('id', $id)->row();
		return $h ? [$h] : NULL;
	}

	public function _delete($id, $title)
	{
		$h = NeoFrag()->db->select('id', 'title')->from('nf_webhooks')->where('id', $id)->row();
		return $h ? [$h] : NULL;
	}
}
