<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Addons\Language_Pt;

use NF\NeoFrag\Addons\Language;

class Language_Pt extends Language
{
	protected function __info()
	{
		return [
			'title'       => 'Português',
			'description' => $this->lang('Langue portugaise : locales, formats de date et d’heure, et lecture des dates saisies.'),
			'icon'        => '🇵🇹',
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			]
		];
	}

	public function locale()
	{
		return [
			'pt_PT.UTF8',
			'pt.UTF8',
			'pt_PT.UTF-8',
			'pt.UTF-8',
			'Portuguese_Portugal.1252'
		];
	}

	public function date()
	{
		return [
			'short_date'      => 'd/m/Y',
			'long_date'       => 'j de F de Y',
			'short_time'      => 'H:i',
			'long_time'       => 'H:i:s',
			'short_date_time' => 'd/m/Y H:i',
			'long_date_time'  => 'l, j de F de Y H:i'
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
	 * Traduit les noms de jours/mois EN → PT dans une string de date formatée.
	 * Appelé depuis helpers/time.php::timetostr() — fix le bug "friday dernier".
	 *
	 * On ne traduit que les formes longues (l = Monday, F = January) pour éviter
	 * les collisions des formes courtes (Mar = Tue ou March selon contexte).
	 */
	public function localize_date_output($output)
	{
		return strtr($output, [
			'Monday'    => 'segunda-feira',
			'Tuesday'   => 'terça-feira',
			'Wednesday' => 'quarta-feira',
			'Thursday'  => 'quinta-feira',
			'Friday'    => 'sexta-feira',
			'Saturday'  => 'sábado',
			'Sunday'    => 'domingo',
			'January'   => 'janeiro',
			'February'  => 'fevereiro',
			'March'     => 'março',
			'April'     => 'abril',
			'May'       => 'maio',
			'June'      => 'junho',
			'July'      => 'julho',
			'August'    => 'agosto',
			'September' => 'setembro',
			'October'   => 'outubro',
			'November'  => 'novembro',
			'December'  => 'dezembro',
		]);
	}
}
