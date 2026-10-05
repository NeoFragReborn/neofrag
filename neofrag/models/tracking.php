<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Models;

use NF\NeoFrag\Loadables\Model2;

class Tracking extends Model2
{
	/** La table du cœur, d'où qu'on charge le modèle : sans elle, le chargeur préfixe celle du module appelant (voir Models\File, 2026-10-05). */
	public $__table = 'tracking';

	static public function __schema()
	{
		return [
			'id'       => self::field()->primary(),
			'user'     => self::field()->depends('user/user')->default(NeoFrag()->user),
			'model'    => self::field()->text(100)->null(),
			'model_id' => self::field()->int()->null(),
			'date'     => self::field()->datetime()
		];
	}
}
