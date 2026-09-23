<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Loadables\Controllers;

use NF\NeoFrag\Loadables\Controller;

/**
 * Le contrôleur de module proxie les méthodes de son addon via __call en retournant $this → chaînables.
 *
 * @method static title(string $title)
 * @method static subtitle(string $subtitle)
 * @method static icon(string $icon)
 * @method static meta_description(string $description)
 * @method static add_action(string $url, string $title = '', string $icon = '')
 */
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
