<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Gallery\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Ajax_Checker extends Module_Checker
{
	public function post($gallery_id, $name)
	{
		// Poster demande le droit de poster, pas seulement celui de voir : la page ne montre le bouton qu'à ceux-là.
		if ($this->access('gallery', 'gallery_see', $gallery_id) && $this->access('gallery', 'gallery_post', $gallery_id) && ($gallery = $this->model()->check_gallery($gallery_id, $name)))
		{
			return [$gallery['gallery_id']];
		}
	}

	public function image($image_id, $title)
	{
		// Le droit de voir l'album, comme la page de l'image (checker.php) : la fenêtre rendait l'original de n'importe
		// quelle image à qui en devinait le numéro et le titre (audit du 2026-10-09).
		if (($image = $this->model()->check_image($image_id, $title)) && $this->access('gallery', 'gallery_see', $image['gallery_id']))
		{
			return [$image];
		}
	}
}
