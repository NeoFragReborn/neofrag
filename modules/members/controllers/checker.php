<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Members\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index($page = '')
	{
		return [$this->module('user')->collection('user')->where('deleted', FALSE)->where('_.id !=', nf_compte_masque())->order_by('username')->paginate($page, 24)];
	}

	public function _group()
	{
		$args = func_get_args();
		$page = array_pop($args);

		if (($group = $this->groups->check_group($args)) && $group['users'])
		{
			return [$group['title'], $this->module('user')->collection('user')->where('id', $group['users'])->where('deleted', FALSE)->where('_.id !=', nf_compte_masque())->order_by('username')->paginate($page, 24)];
		}
	}
}
