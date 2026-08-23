<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Pages\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin_Ajax extends Controller_Module
{
	/**
	 * Palier 1 page-builder — enregistre la liste ordonnée des blocs d'une page (composer).
	 * `$instances` = JSON `[{block, settings}, …]` ; les blocs inconnus et les settings hors
	 * `fields` sont écartés côté modèle (set_instances).
	 */
	public function save_instances($page_id, $instances)
	{
		$list = json_decode($instances, TRUE);

		$this->model()->set_instances($page_id, is_array($list) ? $list : []);

		return $this->json([
			'ok'    => TRUE,
			'count' => count($this->model()->get_instances($page_id))
		]);
	}
}
