<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Slider\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		$this	->subtitle($this->lang('Slides du widget slider'))
				->icon('fas fa-images');

		$slides = $this->model()->get_slides(FALSE);

		// Une seule invitation à créer, jamais deux. Quand la liste est vide, c'est l'état vide qui
		// la porte : c'étaient deux boutons pour exactement la même action. C'est la barre d'outils
		// qui l'emporte, comme sur la régie publicitaire, les dons, les paiements et la boutique —
		// quatre des six écrans concernés font déjà ainsi, et leur état vide se contente
		// d'annoncer qu'il n'y a rien.
		$this->add_action($this->button($this->lang('Ajouter une slide'), 'fas fa-plus', 'primary')->url('admin/slider/add'));

		// Enveloppe partagée : même carte et même état vide que les autres écrans d'administration.
		// L'écran d'édition du slider (plus bas) utilisait déjà admin_card() — la liste, elle, avait
		// une carte quand elle était vide et aucune enveloppe dès qu'il y avait des slides.
		$corps = $this->view('admin/index', [
			'slides' => $slides,
			'csrf'   => $this->csrf_token(),
			'vide'   => $this->admin_empty(
				'fas fa-images',
				$this->lang('Aucune slide pour le moment.'),
				$this->lang('Le widget slider affichera un placeholder par défaut tant qu\'aucune slide n\'est ajoutée.')
			),
		]);

		return $this->admin_card('fas fa-images', $this->lang('Slider'), $corps,
			$slides ? count($slides).' '.$this->lang(count($slides) > 1 ? 'slides' : 'slide') : '');
	}

	public function _add()
	{
		return $this->_form(NULL);
	}

	public function _edit($id)
	{
		return $this->_form((int)$id);
	}

	private function _form($id)
	{
		$slide = $id ? $this->model()->get_slide($id) : NULL;
		if ($id && !$slide)
		{
			$this->error();
			return;
		}

		$this	->subtitle($id ? $this->lang('Modifier la slide') : $this->lang('Nouvelle slide'))
				->icon('fas fa-image')
				->breadcrumb($this->lang('Slides'), 'admin/slider');

		$form = $this->form()
			->add_rules([
				'image' => [
					'label'       => $this->lang('Uploader une image'),
					'type'        => 'file',
					'upload'      => 'slider',
					'info'        => $this->lang('JPEG, PNG, GIF ou WebP — max %d Mo. Si tu uploades, le champ "URL de l\'image" sera remplacé par le chemin de l\'image uploadée.', file_upload_max_size() / 1024 / 1024),
					'check'       => function($filename, $ext){
						if (!in_array(strtolower($ext), ['gif', 'jpeg', 'jpg', 'png', 'webp']))
						{
							return $this->lang('Format non autorisé. Utilise JPEG, PNG, GIF ou WebP.');
						}
					}
				],
				'image_url' => [
					'label'       => $this->lang('— ou URL de l\'image (alternative à l\'upload)'),
					'value'       => $slide['image_url'] ?? '',
					'description' => $this->lang('Si tu n\'uploades pas, colle ici un chemin interne (ex : <code>upload/slider/img.jpg</code>) ou URL externe complète.')
				],
				'title' => [
					'label' => $this->lang('Titre'),
					'value' => $slide['title'] ?? '',
					'description' => $this->lang('Affiché en gras au-dessus du sous-titre.')
				],
				'caption' => [
					'label' => $this->lang('Sous-titre / texte'),
					'value' => $slide['caption'] ?? '',
					'type'  => 'textarea'
				],
				'link' => [
					'label' => $this->lang('Lien (optionnel)'),
					'value' => $slide['link'] ?? '',
					'description' => $this->lang('URL où la slide pointe au clic. Laisse vide si la slide n\'est pas cliquable.')
				],
				'active' => [
					'type'    => 'checkbox',
					'checked' => ['on' => $slide === NULL ? TRUE : !empty($slide['active'])],
					'values'  => ['on' => $this->lang('Slide active (sinon non affichée dans le widget)')]
				]
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		if ($form->is_valid($post))
		{
			$image_url = trim((string)($post['image_url'] ?? ''));

			// Si fichier uploadé, son objet File est dans $post['image']. On extrait le path.
			if (!empty($post['image']) && is_object($post['image']) && !empty($post['image']->id))
			{
				$image_url = (string)$post['image']->path;
			}

			if ($image_url === '')
			{
				notify($this->lang('Tu dois soit uploader une image, soit fournir une URL'), 'danger');
				return $this->panel()
							->heading($id ? $this->lang('Modifier la slide') : $this->lang('Nouvelle slide'), 'fas fa-image')
							->body($form->display());
			}

			$data = [
				'image_url' => $image_url,
				'title'     => trim((string)$post['title']),
				'caption'   => trim((string)$post['caption']),
				'link'      => trim((string)$post['link']),
				'active'    => in_array('on', (array)$post['active']) ? 1 : 0
			];

			if ($id)
			{
				$this->model()->update_slide($id, $data);
				notify($this->lang('Slide modifiée'));
			}
			else
			{
				$this->model()->add_slide($data);
				notify($this->lang('Slide ajoutée'));
			}

			redirect('admin/slider');
		}

		return $this->admin_card('fas fa-image', $id ? $this->lang('Modifier la slide') : $this->lang('Nouvelle slide'), $form->display());
	}

	public function _delete($id)
	{
		$this->check_csrf('admin/slider');

		$slide = $this->model()->get_slide((int)$id);
		if (!$slide)
		{
			$this->error();
			return;
		}

		$this->model()->delete_slide((int)$id);
		notify($this->lang('Slide supprimée'));
		redirect('admin/slider');
	}

	public function _toggle($id)
	{
		$this->check_csrf('admin/slider');

		$slide = $this->model()->get_slide((int)$id);
		if (!$slide)
		{
			$this->error();
			return;
		}

		$this->model()->toggle_slide((int)$id);
		notify($this->lang('Slide %s', empty($slide['active']) ? $this->lang('activée') : $this->lang('désactivée')));
		redirect('admin/slider');
	}

	public function _move()
	{
		// Endpoint POST simple pour reorder via UI
		$ids = isset($_POST['order']) && is_array($_POST['order']) ? $_POST['order'] : [];
		if (!empty($ids))
		{
			$this->model()->reorder($ids);
			notify($this->lang('Ordre mis à jour'));
		}
		redirect('admin/slider');
	}
}
