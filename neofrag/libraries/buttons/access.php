<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 *
 * R1 (2026-05-05) — pointe vers la vue matricielle (admin/access/matrix/...)
 * au lieu de l'ancien admin/access/edit/{path} (legacy supprimé).
 *
 * R1.10 (2026-06-13) — ouvre désormais la matrice en MODALE AJAX (admin/ajax/access/matrix-modal/...)
 * sans quitter la page courante. La page pleine reste le fallback no-JS (href = data-fallback) :
 * sans JavaScript, le clic navigue vers admin/access/matrix/... comme avant.
 *
 * Signature identique pour rétro-compat des 10 call-sites existants.
 *
 * URLs générées :
 *   button_access(5, 'category')          → modale admin/ajax/access/matrix-modal/{moduleCourant}/category/5
 *   button_access(3, 'gallery')           → modale admin/ajax/access/matrix-modal/{moduleCourant}/gallery/3
 *   button_access(0, '', 'forum', '')     → modale admin/ajax/access/matrix-modal/forum (matrice globale)
 */

namespace NF\NeoFrag\Libraries\Buttons;

use NF\NeoFrag\Library;

class Access extends Library
{
	public function __invoke($id, $access = '', $module = '', $title = '')
	{
		$module_name = $module ?: $this->output->module()->info()->name;

		// Scope spécifié (type + id valide) → suffixe /type/id ; sinon matrice globale du module.
		$scope = ($access && (int)$id > 0) ? '/'.$access.'/'.(int)$id : '';

		return $this->button()
					->tooltip($title ?: $this->lang('Permissions'))
					->icon('fas fa-unlock-alt')
					->color('secondary')
					->compact()
					->outline()
					->modal_ajax('admin/ajax/access/matrix-modal/'.$module_name.$scope)
					->url('admin/access/matrix/'.$module_name.$scope);
	}
}
