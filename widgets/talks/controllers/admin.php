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
			'talks'    => $this->db->select('talk_id', 'name')->from('nf_talks')->get(),
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
			'talks'    => $this->db	->select('talk_id', 'name')
									->from('nf_talks')
									->where('type', 'public')
									->where('audience', 'all')
									->where('deleted_at', NULL)
									->order_by('name')
									->get(),
			'settings' => $settings
		]);
	}
}
