<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Gallery\Models;

use NF\NeoFrag\Loadables\Model;

class Gallery extends Model
{
	/**
	 * Les albums que le visiteur a le droit de voir, selon les règles du module : hors corbeille,
	 * publiés et parus (publication programmée), et ouverts à son groupe (`gallery.gallery_see`).
	 *
	 * Le widget n'en appliquait aucune : la liste des albums, l'image tirée au hasard et le
	 * diaporama montraient aussi les albums brouillons, programmés, à la corbeille ou réservés à un
	 * groupe — à tous les visiteurs (relevé le 2026-10-04). En administration (réglage du widget dans
	 * le Live Editor), un album non publié reste proposable, comme dans le module.
	 *
	 * @return list<int>
	 */
	public function albums_visibles(): array
	{
		$this->db	->select('gallery_id')
					->from('nf_gallery')
					->where('deleted_at', NULL);

		if (!$this->url->admin)
		{
			$this->db->where('published', TRUE)->where('date <=', date('Y-m-d H:i:s'));
		}

		// Une requête à UNE colonne rend des scalaires (`Db::get()`).
		return array_values(array_filter(array_map('intval', (array) $this->db->get()), function(int $gallery_id): bool {
			return (bool) $this->access('gallery', 'gallery_see', $gallery_id);
		}));
	}

	public function get_gallery($category_id = FALSE)
	{
		$visibles = $this->albums_visibles();

		$this->db	->select('g.*', 'gl.title', 'gl.description', 'g.image_id as image', 'COUNT(DISTINCT gi.image_id) as images')
					->from('nf_gallery g')
					->join('nf_gallery_lang gl',            'g.gallery_id  = gl.gallery_id')
					->join('nf_gallery_images gi',          'g.gallery_id  = gi.gallery_id')
					->where('gl.lang', $this->config->lang->info()->name)
					->where('g.gallery_id', $visibles)
					->group_by('g.gallery_id')
					->order_by('g.gallery_id DESC');

		if (!empty($category_id))
		{
			$this->db->where('g.category_id', $category_id);
		}

		return $this->db->get();
	}

	public function get_random_image($gallery_id = FALSE)
	{
		$visibles = $this->albums_visibles();

		$this->db	->from('nf_gallery_images')
					->where('gallery_id', $visibles)
					->order_by('RAND()');

		if (!empty($gallery_id) || ($gallery_id > 0))
		{
			$this->db->where('gallery_id', $gallery_id);
		}

		return $this->db->row();
	}

	public function get_images($gallery_id)
	{
		if (!in_array((int) $gallery_id, $this->albums_visibles(), TRUE))
		{
			return [];
		}

		return $this->db->from('nf_gallery_images')
						->where('gallery_id', $gallery_id)
						->order_by('date DESC')
						->get();
	}

	public function get_categories()
	{
		$visibles = $this->albums_visibles();

		return $this->db->select('c.category_id', 'c.image_id', 'c.icon_id', 'c.name', 'cl.title', 'COUNT(g.gallery_id) as nb_gallery')
						->from('nf_gallery_categories c')
						->join_lang('nf_gallery_categories_lang cl', 'category_id', 'c.category_id')
						->join('nf_gallery g', 'c.category_id = g.category_id')
						// Comptées et listées : les seules catégories qui ont un album visible.
						->where('g.gallery_id', $visibles)
						->group_by('c.category_id')
						->order_by('cl.title')
						->get();
	}
}
