<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Models;

use NF\NeoFrag\Loadables\Model2;

class Session extends Model2
{
	/** La table du cœur, d'où qu'on charge le modèle : sans elle, le chargeur préfixe celle du module appelant (voir Models\File, 2026-10-05). */
	public $__table = 'session';

	static public function __schema()
	{
		return [
			'id'            => self::field()->text(32)->primary(),
			'user'          => self::field()->depends('user/user')->null(),
			'remember'      => self::field()->bool(),
			'last_activity' => self::field()->datetime(),
			'data'          => self::field()->serialized()
		];
	}
}
