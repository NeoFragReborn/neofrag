<?php
declare(strict_types=1);
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
		// Un commentaire signalé ne s'efface pas par son auteur tant que la modération ne l'a pas examiné (audit du 2026-10-09).
		$auteur = (int) $this->db->select('user_id')->from('nf_comment')->where('id', (int) $comment_id)->row();

		if ($this->user() && $auteur === (int) $this->user->id && !$this->access->effective_admin() && $this->moderation->is_message_locked('comment', (int) $comment_id))
		{
			return '<div class="alert alert-warning mb-0">'.$this->lang('Ce commentaire est signalé : il ne peut pas être supprimé tant que la modération ne l’a pas examiné.').'</div>';
		}

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
