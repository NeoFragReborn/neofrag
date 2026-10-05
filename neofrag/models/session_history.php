<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Models;

use NF\NeoFrag\Loadables\Model2;

class Session_History extends Model2
{
	/** La table du cœur, d'où qu'on charge le modèle : sans elle, le chargeur préfixe celle du module appelant (voir Models\File, 2026-10-05). */
	public $__table = 'session_history';

	static public function __schema()
	{
		return [
			'id'         => self::field()->primary(),
			'user'       => self::field()->depends('user/user'),
			'ip_address' => self::field()->text(39),
			'host_name'  => self::field()->text(100),
			'referer'    => self::field()->text(100),
			'user_agent' => self::field()->text(250),
			'auth'       => self::field()->serialized(),
			'date'       => self::field()->datetime()
		];
	}
}
