<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Gallery\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function albums($settings = [])
	{
		// Reglages absents : un widget peut etre pose sans passer par son formulaire (install()
		// d'un theme, ajout en Live Editor, disposition ancienne). Cf. tools/check-widget-reglages.php.
		$settings = (array) $settings + ['category_id' => ''];

		if (in_array($settings['category_id'], array_map(function($a){
			return $a['category_id'];
		}, $this->model()->get_categories())))
		{
			return [
				'category_id' => $settings['category_id']
			];
		}
	}

	public function image($settings = [])
	{
		// Reglages absents : un widget peut etre pose sans passer par son formulaire (install()
		// d'un theme, ajout en Live Editor, disposition ancienne). Cf. tools/check-widget-reglages.php.
		$settings = (array) $settings + ['gallery_id' => ''];

		if (in_array($settings['gallery_id'], array_merge(array_map(function($a){
			return $a['gallery_id'];
		}, $this->model()->get_gallery()), [0])))
		{
			return [
				'gallery_id' => $settings['gallery_id']
			];
		}
	}

	public function slider($settings = [])
	{
		// Reglages absents : un widget peut etre pose sans passer par son formulaire (install()
		// d'un theme, ajout en Live Editor, disposition ancienne). Cf. tools/check-widget-reglages.php.
		$settings = (array) $settings + ['gallery_id' => ''];

		if (in_array($settings['gallery_id'], array_map(function($a){
			return $a['gallery_id'];
		}, $this->model()->get_gallery())))
		{
			return [
				'gallery_id' => $settings['gallery_id']
			];
		}
	}
}
