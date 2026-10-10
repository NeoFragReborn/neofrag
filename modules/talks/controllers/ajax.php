<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Talks\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

/**
 * Les adresses `ajax/talks` et `ajax/talks/older` de l'ancienne messagerie sont retirées (2026-10-09) : rien ne les
 * appelait plus, et elles rendaient les messages de n'importe quelle conversation à qui avait le droit « lire » — droit
 * que chaque conversation, même privée, accorde à tous à sa création (`Access::init()`). Un visiteur lisait ainsi un
 * groupe privé de la démonstration. Une conversation se lit par sa page, qui vérifie la participation
 * (`Models\Talks::user_can_access()`).
 */
class Ajax extends Controller_Module
{
	public function delete($message_id, $talk_id)
	{
		// Un message signalé ne s'efface pas par son auteur tant que la modération ne l'a pas examiné (audit du 2026-10-09).
		$auteur = (int) $this->db->select('user_id')->from('nf_talks_messages')->where('message_id', (int) $message_id)->row();

		if ($this->user() && $auteur === (int) $this->user->id && !$this->access('talks', 'delete', $talk_id) && $this->moderation->is_message_locked('talks', (int) $message_id))
		{
			return '<div class="alert alert-warning mb-0">'.$this->lang('Ce message est signalé : il ne peut pas être supprimé tant que la modération ne l’a pas examiné.').'</div>';
		}

		$this	->title($this->lang('Confirmation de suppression'))
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer ce message ?'));

		if ($this->form()->is_valid())
		{
			if ($this->db->select('message_id')->from('nf_talks_messages')->where('talk_id', $talk_id)->order_by('message_id DESC')->row() == $message_id)
			{
				$this->db	->where('message_id', $message_id)
							->delete('nf_talks_messages');
			}
			else
			{
				$this->db	->where('message_id', $message_id)
							->update('nf_talks_messages', [
								'message' => NULL
							]);
			}

			return 'OK';
		}

		return $this->form()->display();
	}
}
