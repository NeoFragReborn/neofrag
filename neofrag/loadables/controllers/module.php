<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Loadables\Controllers;

use NF\NeoFrag\Loadables\Controller;

abstract class Module extends Controller
{
	use \NF\NeoFrag\Traits\Admin_Helpers;

	public function __construct($caller)
	{
		parent::__construct($this->module = $caller);
	}

	public function __call($name, $args)
	{
		if (method_exists($this->module, $name))
		{
			call_user_func_array([$this->module, $name], $args);
			return $this;
		}

		return parent::__call($name, $args);
	}
}
