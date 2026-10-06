<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Gallery\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($settings = [])
	{
		$categories = $this->model()->get_categories();

		if (!empty($categories))
		{
			return $this->panel()
						->heading($this->lang('Nos galeries'))
						->body($this->view('index', [
							'categories' => $categories
						]), FALSE)
						->footer('<a href="'.url('gallery').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Voir notre galerie').'</a>', 'right');
		}
		else
		{
			return $this->panel()
						->heading($this->lang('Galerie'))
						->body($this->lang('Aucune catégorie pour le moment'));
		}
	}

	/**
	 * Les dernières photos en mosaïque : la plus récente en grand, les quatre suivantes à côté ; chacune mène à sa
	 * page (2026-10-06, pour le thème Pulse ; il sert à tout thème).
	 */
	public function grille($settings = [])
	{
		$this->css('galerie');

		$modele = $this->model('gallery');
		$images = $modele instanceof \NF\Widgets\Gallery\Models\Gallery ? $modele->get_dernieres_images(5) : [];

		return $this->panel()
					->heading($this->lang('Les dernières photos'))
					->body($images ? $this->view('grille', ['images' => $images]) : $this->lang('Aucune image pour le moment'))
					->footer('<a href="'.url('gallery').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Voir notre galerie').'</a>', 'right');
	}

	public function albums($settings = [])
	{
		return $this->panel()
					->heading($this->lang('Nos albums'))
					->body($this->view('gallery', [
						// Sans catégorie choisie : tous les albums (FALSE, comme l'entend le modèle).
						'gallery' => $this->model()->get_gallery($settings['category_id'] ?? FALSE)
					]), FALSE)
					->footer('<a href="'.url('gallery').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Voir notre galerie').'</a>', 'right');
	}

	public function image($settings = [])
	{
		// Sans réglages — posé par un thème, ou d'une disposition ancienne —, le checker refuse de
		// désigner un élément au hasard : c'est au rendu de s'en tirer (check-widget-contract).
		$image = $this->model()->get_random_image($settings['gallery_id'] ?? FALSE);

		if (!empty($image['file_id']))
		{
			// Le lien se fabrique APRÈS avoir trouvé une image : sur un site sans image, la version
			// précédente lisait `image_id` et `title` sur un résultat vide (vu en CI, 2026-09-22).
			$href = url('gallery/image/'.$image['image_id'].'/'.url_title($image['title']));

			return $this->panel()
						->heading($image['title'])
						->body('<a href="'.$href.'"><img class="img-fluid" src="'.NeoFrag()->model2('file', $image['file_id'])->path().'" alt="" /></a>', FALSE)
						->footer('<a href="'.$href.'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Détails').'</a>', 'right');
		}
		else
		{
			return $this->panel()
						->heading($this->lang('Image aléatoire'))
						->body($this->lang('Aucune image pour le moment'));
		}
	}

	public function slider($settings = [])
	{
		// Sans réglages — posé par un thème, ou d'une disposition ancienne —, le checker refuse de
		// désigner un élément au hasard : c'est au rendu de s'en tirer (check-widget-contract).
		$images = $this->model()->get_images($settings['gallery_id'] ?? 0);

		if (!empty($images))
		{
			return $this->panel()
						->body($this->view('slider', [
							'id'     => $settings['gallery_id'] ?? 0,
							'images' => $images
						]), FALSE);
		}
		else
		{
			return $this->panel()
						->heading($this->lang('Album'))
						->body($this->lang('Aucune image pour le moment'));
		}
	}
}
