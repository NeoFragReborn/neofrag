<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Auth checker pour /ajax/reactions/* — réagir nécessite d'être connecté.
 */

namespace NF\Modules\Reactions\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Ajax_Checker extends Module_Checker
{
	public function _toggle($type, $id)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}

		$this->ajax();

		// On ne réagit qu'à ce qu'on peut voir : la réponse rendait les compteurs — donc l'existence — d'un message de forum
		// VIP ou d'une actualité dépubliée, et son auteur recevait notification et karma (audit du 2026-10-09).
		if (!\NF\NeoFrag\Addons\Module::content_visible_of((string) $type, (int) $id))
		{
			$this->error();
			return;
		}

		return [$type, (int)$id];
	}
}
