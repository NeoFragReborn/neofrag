<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\User\Models;

use NF\NeoFrag\Loadables\Model2;

/**
 * Un lien envoyé par e-mail (mot de passe oublié, validation d'inscription) ; Models\User::token() le crée.
 *
 * @property \NF\NeoFrag\Models\User         $user  le membre à qui il est envoyé
 * @property string                          $type  `mot_de_passe` ou `validation`
 * @property \NF\NeoFrag\Libraries\Date|null $date  sa création
 */
class Token extends Model2
{
	static public function __schema()
	{
		return [
			'id'   => self::field()->text(32)->primary(),
			'user' => self::field()->depends('user/user'),
			// À quoi sert le lien (audit du 2026-10-09) : le lien « mot de passe oublié » (une heure, pour choisir un
			// nouveau mot de passe) ouvrait aussi la validation d'inscription (deux jours, qui connecte sans rien demander).
			'type' => self::field()->enum('mot_de_passe', 'validation'),
			'date' => self::field()->datetime()
		];
	}
}
