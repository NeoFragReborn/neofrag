<?php
declare(strict_types=1);
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
		// Le nom du groupe visé dans la langue de l'administration (join_lang) : MAX() en prenait un d'une langue au hasard.
		$campaigns = NeoFrag()->db	->select('c.*', 'u.username', 'gl.title AS segment_group_title')
									->from('nf_newsletter_campaigns c')
									->join('nf_user u', 'c.user_id = u.id', 'LEFT')
									->join_lang('nf_groups_lang gl', 'group_id', 'c.segment_group_id', NULL, 'LEFT')
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

	public function _campaign_send($id)
	{
		return [(int)$id];
	}

	public function _campaign_cancel($id)
	{
		return [(int)$id];
	}

	public function _templates()
	{
		return [NeoFrag()->db->select('*')->from('nf_newsletter_templates')->order_by('name ASC')->get()];
	}

	public function _template_add()
	{
		return [NULL];
	}

	public function _template_edit($id)
	{
		$t = NeoFrag()->db->select('*')->from('nf_newsletter_templates')->where('id', $id)->row();
		return $t ? [$t] : NULL;
	}

	public function _template_delete($id)
	{
		return [(int)$id];
	}
}
