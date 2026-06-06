<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Partners\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index()
	{
		$partners = $this->model()->get_partners();

		if (!empty($partners))
		{
			return $this->panel()
						->heading($this->lang('Nos partenaires'), 'far fa-handshake')
						->body($this->view('index', [
							'partners' => $partners
						]));
		}
		else
		{
			return $this->panel()
						->heading($this->lang('Nos partenaires'), 'far fa-handshake')
						->body('<div class="text-center">'.$this->lang('Aucun partenaire').'</div>')
						->color('info');
		}
	}
}
