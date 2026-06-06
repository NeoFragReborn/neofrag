<?php
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

		if ($this->db->tables() && in_array('nf_slider_slides', $this->db->tables()))
		{
			$slides = $this->db	->select('image_url', 'title', 'caption', 'link', 'active')
								->from('nf_slider_slides')
								->where('active', 1)
								->order_by('sort_order', 'id')
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
