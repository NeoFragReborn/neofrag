<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag;

// Instanciée par le chargeur de index.php, qui lui pose `__debug` en mode débogage : sans cet attribut, PHP 8.2
// journalisait une dépréciation à chaque réinstallation d'un thème (l'appel `->api()->scss()` de Theme::install(),
// vu le 2026-10-06 sur l'atelier).
#[\AllowDynamicProperties]
class Api
{
	protected $_controller;

	public function __construct($controller)
	{
		$this->_controller = $controller;
	}

	public function __call($name, $args)
	{
		ob_start();
		$result = call_user_func_array([$this->_controller, $name], $args);
		ob_end_clean();

		return $result;
	}
}
