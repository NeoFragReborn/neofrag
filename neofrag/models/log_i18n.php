<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Models;

use NF\NeoFrag\Loadables\Model2;

class Log_I18n extends Model2
{
	/** La table du cœur, d'où qu'on charge le modèle : sans elle, le chargeur préfixe celle du module appelant (voir Models\File, 2026-10-05). */
	public $__table = 'log_i18n';

	static public function __schema()
	{
		return [
			'id'       => self::field()->primary(),
			'language' => self::field()->text(2),
			'key'      => self::field()->text(32),
			'locale'   => self::field()->text(),
			'file'     => self::field()->text(100)
		];
	}
}
