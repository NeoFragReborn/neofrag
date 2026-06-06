<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 *
 * R1 (2026-05-05) — pointe maintenant vers la nouvelle vue matricielle (admin/access/matrix/...)
 * au lieu de l'ancien admin/access/edit/{path} (legacy supprimé).
 *
 * Signature identique pour rétro-compat des 10 call-sites existants.
 *
 * URLs générées :
 *   button_access(5, 'category')          → admin/access/matrix/{moduleCourant}/category/5
 *   button_access(3, 'gallery')           → admin/access/matrix/{moduleCourant}/gallery/3
 *   button_access(0, '', 'forum', '')     → admin/access/matrix/forum (matrice globale)
 */

namespace NF\NeoFrag\Libraries\Buttons;

use NF\NeoFrag\Library;

class Access extends Library
{
	public function __invoke($id, $access = '', $module = '', $title = '')
	{
		$module_name = $module ?: $this->output->module()->info()->name;

		// Si scope spécifié (type + id valide) → URL matrice avec scope
		// Sinon → URL globale (matrice complète du module)
		if ($access && (int)$id > 0)
		{
			$url = 'admin/access/matrix/'.$module_name.'/'.$access.'/'.(int)$id;
		}
		else
		{
			$url = 'admin/access/matrix/'.$module_name;
		}

		return $this->button()
					->tooltip($title ?: $this->lang('Permissions'))
					->url($url)
					->icon('fas fa-unlock-alt')
					->color('success')
					->compact()
					->outline();
	}
}
