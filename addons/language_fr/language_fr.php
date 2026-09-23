<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Addons\Language_Fr;

use NF\NeoFrag\Addons\Language;

class Language_Fr extends Language
{
	protected function __info()
	{
		return [
			'title'       => 'Français',
			'description' => $this->lang('Langue française : locales, formats de date et d’heure, et lecture des dates saisies.'),
			'icon'        => '🇫🇷',
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			]
		];
	}

	public function locale()
	{
		return [
			'fr_FR.UTF8',
			'fr.UTF8',
			'fr_FR.UTF-8',
			'fr.UTF-8',
			'French_France.1252'
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
			'long_date_time'  => 'l j F Y, H:i'
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
	 * Traduit les noms de jours/mois EN → FR dans une string de date formatée.
	 * Appelé depuis helpers/time.php::timetostr() — fix le bug "friday dernier".
	 *
	 * On ne traduit que les formes longues (l = Monday, F = January) pour éviter
	 * les collisions des formes courtes (Mar = Tue ou March selon contexte).
	 */
	public function localize_date_output($output)
	{
		return strtr($output, [
			'Monday'    => 'Lundi',
			'Tuesday'   => 'Mardi',
			'Wednesday' => 'Mercredi',
			'Thursday'  => 'Jeudi',
			'Friday'    => 'Vendredi',
			'Saturday'  => 'Samedi',
			'Sunday'    => 'Dimanche',
			'January'   => 'janvier',
			'February'  => 'février',
			'March'     => 'mars',
			'April'     => 'avril',
			'May'       => 'mai',
			'June'      => 'juin',
			'July'      => 'juillet',
			'August'    => 'août',
			'September' => 'septembre',
			'October'   => 'octobre',
			'November'  => 'novembre',
			'December'  => 'décembre',
		]);
	}
}
