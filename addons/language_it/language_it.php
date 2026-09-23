<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Addons\Language_It;

use NF\NeoFrag\Addons\Language;

class Language_It extends Language
{
	protected function __info()
	{
		return [
			'title'       => 'Italiano',
			'description' => $this->lang('Langue italienne : locales, formats de date et d’heure, et lecture des dates saisies.'),
			'icon'        => '🇮🇹',
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			]
		];
	}

	public function locale()
	{
		return [
			'it_IT.UTF8',
			'it.UTF8',
			'it_IT.UTF-8',
			'it.UTF-8',
			'Italian_Italy.1252'
		];
	}

	public function date()
	{
		return [
			'short_date'      => 'd/m/Y',
			'long_date'       => 'j F Y',
			'short_time'      => 'H:i',
			'long_time'       => 'H:i:s',
			'short_date_time' => 'd/m/Y H:i',
			'long_date_time'  => 'l, j F Y H:i'
		];
	}

	public function date2sql(&$date)
	{
		if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $date, $match))
		{
			$date = $match[3].'-'.$match[2].'-'.$match[1];
		}
	}

	public function time2sql(&$time)
	{
		if (preg_match('#^(\d{2}):(\d{2})$#', $time, $match))
		{
			$time = $match[1].':'.$match[2].':00';
		}
	}

	public function datetime2sql(&$datetime)
	{
		if (preg_match('#^(\d{2})/(\d{2})/(\d{4}) (\d{2}):(\d{2})$#', $datetime, $match))
		{
			$datetime = $match[3].'-'.$match[2].'-'.$match[1].' '.$match[4].':'.$match[5].':00';
		}
	}

	/**
	 * Traduit les noms de jours/mois EN → IT dans une string de date formatée.
	 * Appelé depuis helpers/time.php::timetostr() — fix le bug "friday dernier".
	 *
	 * On ne traduit que les formes longues (l = Monday, F = January) pour éviter
	 * les collisions des formes courtes (Mar = Tue ou March selon contexte).
	 */
	public function localize_date_output($output)
	{
		return strtr($output, [
			'Monday'    => 'lunedì',
			'Tuesday'   => 'martedì',
			'Wednesday' => 'mercoledì',
			'Thursday'  => 'giovedì',
			'Friday'    => 'venerdì',
			'Saturday'  => 'sabato',
			'Sunday'    => 'domenica',
			'January'   => 'gennaio',
			'February'  => 'febbraio',
			'March'     => 'marzo',
			'April'     => 'aprile',
			'May'       => 'maggio',
			'June'      => 'giugno',
			'July'      => 'luglio',
			'August'    => 'agosto',
			'September' => 'settembre',
			'October'   => 'ottobre',
			'November'  => 'novembre',
			'December'  => 'dicembre',
		]);
	}
}
