<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Api\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	/*
	 * Toute adresse sous `api/v1` est confiée au contrôleur : c'est lui qui répond, en JSON, qu'une
	 * adresse est inconnue. Un checker qui refusait ici rendrait la page 404 du thème à un programme.
	 */
	public function _v1(...$segments)
	{
		return $segments;
	}
}
