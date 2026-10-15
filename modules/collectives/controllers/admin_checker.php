<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Collectives\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index($page = '')
	{
		$modele = $this->model();

		if (!$modele instanceof \NF\Modules\Collectives\Models\Collectives)
		{
			throw new \LogicException('modèle des collectifs introuvable');
		}

		return [$this->module->pagination->fix_items_per_page(30)->get_data($modele->liste(), $page)];
	}

	public function _add()
	{
		return [NULL];
	}

	public function _edit($id, $title)
	{
		return $this->_un((int) $id);
	}

	public function _delete($id, $title)
	{
		return $this->_un((int) $id);
	}

	private function _un(int $id): ?array
	{
		$modele = $this->model();

		return $modele instanceof \NF\Modules\Collectives\Models\Collectives && ($c = $modele->un($id)) ? [$c] : NULL;
	}
}
