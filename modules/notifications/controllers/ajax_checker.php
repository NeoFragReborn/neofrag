<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Auth checker pour /ajax/notifications/* — nécessite d'être connecté.
 */

namespace NF\Modules\Notifications\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Ajax_Checker extends Module_Checker
{
	public function _read($id)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}

		$this->ajax();

		return [(int)$id];
	}

	public function _read_all()
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}

		$this->ajax();

		return [];
	}

	/** Le push (d11) : un membre connecté, en POST, sur un site qui l'a ouvert. */
	public function _push_abonner()
	{
		return $this->_push();
	}

	public function _push_desabonner()
	{
		return $this->_push();
	}

	public function _push_essai()
	{
		return $this->_push();
	}

	private function _push()
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}

		$this->ajax();

		return [];
	}

	public function _subscribe($type, $id)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}

		$this->ajax();

		return [$type, (int)$id];
	}
}
