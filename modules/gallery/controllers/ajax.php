<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Gallery\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Ajax extends Controller_Module
{
	public function post($gallery_id)
	{
		// Une sanction qui ferme l'envoi (galerie, fichiers, site) : l'avis à la place du formulaire.
		if ($bloque = $this->moderation->is_blocked_for((int) $this->user->id, 'gallery.upload'))
		{
			return $this->modal($this->lang('Poster une image'), 'far fa-image')
						->body($this->moderation->avis($bloque))
						->close();
		}

		return $this->form2('post')
					->compact()
					->success(function($data, $form) use ($gallery_id){
						if ($refus = $this->moderation->lien_refuse((int) $this->user->id, (string) $data['title'], (string) $data['description']))
						{
							// L'image déjà reçue ne reste pas orpheline : elle repart avec le refus.
							$data['image']->delete();
							$form->error($refus['message']);
							return;
						}

						$this->model()->add_image(	$data['image']->id,
													$gallery_id,
													$data['title'],
													$data['description']);

						notify($this->lang('Image postée dans l\'album avec succès !'));
						refresh();
					})
					->modal($this->lang('Poster une image'), 'far fa-image')
					->cancel();
	}

	public function image($image)
	{
		return $this->modal($image['title'], 'far fa-image')
					->large()
					->body($this->view('image', [
						'original_file_id' => $image['original_file_id']
					]), FALSE)
					->close();
	}
}
