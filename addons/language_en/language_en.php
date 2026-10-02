<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Addons\Language_En;

use NF\NeoFrag\Addons\Language;

class Language_En extends Language
{
	protected function __info()
	{
		return [
			'title'       => 'English',
			'description' => $this->lang('Langue anglaise : locales, formats de date et d’heure, et lecture des dates saisies.'),
			'icon'        => '🇬🇧',
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			]
		];
	}

	public function locale()
	{
		return [
			'en_GB.UTF8',
			'en_US.UTF8',
			'en.UTF8',
			'en_GB.UTF-8',
			'en_US.UTF-8',
			'en.UTF-8',
			'English_Australia.1252'
		];
	}

	/**
	 * Les formats COURTS sont ceux du sélecteur de dates (flatpickr) et de la saisie : date2sql() et
	 * datetime2sql() doivent les relire. En « m/d/Y g:i A », le 2 octobre se relisait 10 février, et
	 * flatpickr, qui ne connaît ni « g » ni « A », écrivait « 10/02/2026 g:05 A » (2026-10-02). Ils
	 * suivent ceux des traductions anglaises du produit (« d/m/Y H:i ») ; les formats longs, qui ne
	 * servent qu'à l'affichage, gardent l'usage anglais.
	 */
	public function date()
	{
		return [
			'short_date'      => 'd/m/Y',
			'long_date'       => 'F j, Y',
			'short_time'      => 'H:i',
			'long_time'       => 'g:i:s A',
			'short_date_time' => 'd/m/Y H:i',
			'long_date_time'  => 'l, F j, Y g:i A'
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
}
