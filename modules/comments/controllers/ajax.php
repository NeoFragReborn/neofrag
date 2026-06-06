<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Comments\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Ajax extends Controller_Module
{
	public function delete($comment_id, $module_id, $module)
	{
		$this	->title($this->lang('Confirmation de suppression'))
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer ce commentaire ?'));

		if ($this->form()->is_valid())
		{
			// Soft-delete : le commentaire part à la corbeille (restaurable), le contenu
			// est conservé. Le threading tient (la ligne reste, rendue « Message supprimé »).
			$this->module('comments')->model()->soft_delete($comment_id, $this->user() ? (int)$this->user->id : NULL);

			return 'OK';
		}

		return $this->form()->display();
	}
}
