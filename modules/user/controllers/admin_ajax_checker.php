<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\User\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Ajax_Checker extends Module_Checker
{
	public function _groups_sort()
	{
		if (($check = post_check('id', 'position')) && ($group = NeoFrag('NF\NeoFrag\Core\Groups')->check_group([$check['id']])) && $group['auto'] != 'neofrag')
		{
			// La position arrive du navigateur en texte ; array_slice() exige un entier (strict_types).
			return array_merge($check, ['position' => (int) $check['position']]);
		}
	}
}
