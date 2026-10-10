<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Gallery\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function _gallery($gallery_id, $name, $page = '')
	{
		// L'adresse porte le nom court de l'album, refait quand son titre change : vérifié avec le VRAI, une ancienne
		// adresse mène à la nouvelle (m06). Un album qui n'existe pas est introuvable (il répondait 403, comme un
		// album interdit).
		$vrai = nf_titre_lu($this->db->select('name')->from('nf_gallery')->where('gallery_id', (int) $gallery_id)->row());

		if ($vrai === '')
		{
			return;
		}

		if ($this->access('gallery', 'gallery_see', $gallery_id) && ($gallery = $this->model()->check_gallery($gallery_id, $vrai)))
		{
			nf_bon_titre((string) $name, $vrai, 'gallery/album/'.(int) $gallery_id, (string) $page);

			return [
				$gallery_id,
				$gallery['category_id'],
				$gallery['image_id'],
				$vrai,
				$gallery['published'],
				$gallery['title'],
				$gallery['description'],
				$gallery['category_name'],
				$gallery['category_title'],
				$gallery['category_image'],
				$gallery['category_icon'],
				$this->module->pagination->fix_items_per_page($this->config->images_per_page)->get_data($this->model()->get_images($gallery_id), $page)
			];
		}

		$this->error->unauthorized();
	}

	public function _category($category_id, $name)
	{
		// Le nom court de la catégorie, refait quand son titre change : une ancienne adresse mène à la nouvelle (m06).
		$vrai = nf_titre_lu($this->db->select('name')->from('nf_gallery_categories')->where('category_id', (int) $category_id)->row());

		if ($vrai !== '' && ($category = $this->model()->check_category($category_id, $vrai)))
		{
			nf_bon_titre((string) $name, $vrai, 'gallery/'.(int) $category_id);

			return [$category['category_id'], $category['name'], $category['title']];
		}
	}

	public function _image($image_id, $name)
	{
		// Vérifiée avec son VRAI titre ; l'adresse au mauvais titre mène à la bonne au lieu de répondre 404 (m06).
		$titre = nf_titre_lu($this->db->select('title')->from('nf_gallery_images')->where('image_id', (int) $image_id)->row());

		if ($titre !== '' && ($image = $this->model()->check_image($image_id, url_title($titre))))
		{
			nf_bon_titre((string) $name, $titre, 'gallery/image/'.(int) $image_id);

			if (!$this->access('gallery', 'gallery_see', $image['gallery_id']))
			{
				$this->error->unauthorized();
			}

			return [
				$image['image_id'],
				$image['original_file_id'],
				$image['gallery_id'],
				$image['title'],
				$image['description'],
				$image['gallery_name'],
				$image['gallery_title']
			];
		}
	}
}
