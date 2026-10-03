<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Members\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index($members)
	{
		// L'annuaire ne s'indexe pas, comme les profils : une liste de noms, et un membre n'a pas à se
		// retrouver dans un moteur de recherche sans l'avoir choisi. Ses liens se suivent.
		$this->output->data->set('module', 'robots', 'noindex, follow');
		$this->css('members');

		return $this->array()
					->append($this->view('members', ['members' => $members->get()]))
					->append($members->pagination->get_pagination());
	}

	public function _group($title, $members)
	{
		return $this->array()
					->append($this->panel()->body('<h2 class="m-0">'.$this->lang('Groupe').' <small>'.$title.'</small>'.$this->button()->tooltip($this->lang('Voir tous les membres'))->icon('fas fa-times')->url('members')->color('danger float-end')->compact()->outline().'</h2>')->style('mb-4'))
					->append($this->index($members));
	}
}
