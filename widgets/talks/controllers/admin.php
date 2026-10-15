<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Talks\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index($settings = [])
	{
		return $this->view('admin', [
			'talks'    => $this->db->select('t.talk_id', 'COALESCE(tl.name, t.name) AS name')->from('nf_talks t')->join_lang('nf_talks_lang tl', 'talk_id', 't.talk_id', NULL, 'LEFT')->get(),
			'settings' => $settings
		]);
	}

	/**
	 * Le salon dont on montre les derniers messages : un salon public, ouvert à tous les membres (ni conversation
	 * privée, ni salon du staff).
	 */
	public function salon($settings = [])
	{
		return $this->view('admin', [
			'talks'    => $this->db	->select('t.talk_id', 'COALESCE(tl.name, t.name) AS name')
									->from('nf_talks t')
									->join_lang('nf_talks_lang tl', 'talk_id', 't.talk_id', NULL, 'LEFT')
									->where('t.type', 'public')
									->where('t.audience', 'all')
									->where('t.deleted_at', NULL)
									->order_by('name')
									->get(),
			'settings' => $settings
		]);
	}
}
