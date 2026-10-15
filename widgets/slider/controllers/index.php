<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Slider\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($settings = [])
	{
		// Le widget lit les slides depuis la table nf_slider_slides (gérée via le module slider).
		$slides = [];

		// Le titre et la légende viennent de la table des traductions (m22, 2026-10-13) : la langue de la page, sinon
		// le français, sinon la première écrite. Jointure GAUCHE : une vue sans aucune traduction garde son image.
		if ($this->db->tables() && in_array('nf_slider_slides', $this->db->tables()))
		{
			$slides = $this->db	->select('s.image_url', 'l.title', 'l.caption', 's.link', 's.active')
								->from('nf_slider_slides s')
								->join_lang('nf_slider_slides_lang l', 'slide_id', 's.id', NULL, 'LEFT')
								->where('s.active', 1)
								->order_by('s.sort_order', 's.id')
								->get();
		}

		// Fallback : aucune slide configurée → placeholder agréable
		if (empty($slides))
		{
			$slides = [[
				'image_url' => '',
				'title'     => $this->lang('Bienvenue !'),
				'caption'   => $this->lang('Configure le slider dans l\'admin pour ajouter tes propres slides.'),
				'link'      => '',
				'active'    => 1
			]];
		}

		return $this->view('index', ['slides' => $slides]);
	}
}
