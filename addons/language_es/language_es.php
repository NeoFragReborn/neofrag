<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Addons\Language_Es;

use NF\NeoFrag\Addons\Language;

class Language_Es extends Language
{
	protected function __info()
	{
		return [
			'title'       => 'Español',
			'description' => $this->lang('Langue espagnole : locales, formats de date et d’heure, et lecture des dates saisies.'),
			'icon'        => '🇪🇸',
			'version'     => '1.0',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'link'        => 'https://neofr.ag',
			'depends'     => [
				'neofrag' => '0.2.0'
			]
		];
	}

	public function locale()
	{
		return [
			'es_ES.UTF8',
			'es.UTF8',
			'es_ES.UTF-8',
			'es.UTF-8',
			'Spanish_Spain.1252'
		];
	}

	public function date()
	{
		return [
			'short_date'      => 'd/m/Y',
			'long_date'       => 'j de F de Y',
			'short_time'      => 'G:i',
			'long_time'       => 'G:i:s',
			'short_date_time' => 'd/m/Y G:i',
			'long_date_time'  => 'l, j de F de Y G:i'
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
	 * Traduit les noms de jours/mois EN → ES dans une string de date formatée.
	 * Appelé depuis helpers/time.php::timetostr() — fix le bug "friday dernier".
	 *
	 * On ne traduit que les formes longues (l = Monday, F = January) pour éviter
	 * les collisions des formes courtes (Mar = Tue ou March selon contexte).
	 */
	public function localize_date_output($output)
	{
		return strtr($output, [
			'Monday'    => 'lunes',
			'Tuesday'   => 'martes',
			'Wednesday' => 'miércoles',
			'Thursday'  => 'jueves',
			'Friday'    => 'viernes',
			'Saturday'  => 'sábado',
			'Sunday'    => 'domingo',
			'January'   => 'enero',
			'February'  => 'febrero',
			'March'     => 'marzo',
			'April'     => 'abril',
			'May'       => 'mayo',
			'June'      => 'junio',
			'July'      => 'julio',
			'August'    => 'agosto',
			'September' => 'septiembre',
			'October'   => 'octubre',
			'November'  => 'noviembre',
			'December'  => 'diciembre',
		]);
	}
}
