<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Collectives\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		$this->error->unconnected();

		return [$this->_modele()->du_membre((int) $this->user->id)];
	}

	/**
	 * La page d'un collectif : à ses membres, et à qui gère les collectifs sur le site. Pour tout autre, elle n'existe
	 * pas (404) — un collectif est privé, jusqu'à son nom.
	 */
	public function _collective($id, $title)
	{
		return $this->_collectif((int) $id);
	}

	public function _add_member($id, $title)
	{
		return $this->_collectif((int) $id);
	}

	public function _rank($id, $title, $user_id)
	{
		return ($c = $this->_collectif((int) $id)) ? [...$c, (int) $user_id] : NULL;
	}

	public function _remove($id, $title, $user_id)
	{
		return ($c = $this->_collectif((int) $id)) ? [...$c, (int) $user_id] : NULL;
	}

	public function _leave($id, $title)
	{
		return $this->_collectif((int) $id);
	}

	/** @return array{0: array<string, mixed>, 1: ?string, 2: bool}|null Le collectif, mon rang, et si je gère les collectifs du site. */
	private function _collectif(int $id): ?array
	{
		$this->error->unconnected();

		$modele       = $this->_modele();
		$collectif    = $modele->un($id);
		$gestionnaire = (bool) $this->access('collectives', 'manage_collectives');
		$rang         = $collectif ? $modele->rang($id, (int) $this->user->id) : NULL;

		return $collectif && ($rang !== NULL || $gestionnaire) ? [$collectif, $rang, $gestionnaire] : NULL;
	}

	private function _modele(): \NF\Modules\Collectives\Models\Collectives
	{
		$modele = $this->model();

		if (!$modele instanceof \NF\Modules\Collectives\Models\Collectives)
		{
			throw new \LogicException('modèle des collectifs introuvable');
		}

		return $modele;
	}
}
