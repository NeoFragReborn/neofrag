<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Addons\Language_De;

use NF\NeoFrag\Addons\Language;

class Language_De extends Language
{
	protected function __info()
	{
		return [
			'title'       => 'Deutsch',
			'description' => $this->lang('Langue allemande : locales, formats de date et d’heure, et lecture des dates saisies.'),
			'icon'        => '🇩🇪',
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
			'de_DE.UTF8',
			'de.UTF8',
			'de_DE.UTF-8',
			'de.UTF-8',
			'German_Germany.1252'
		];
	}

	public function date()
	{
		return [
			'short_date'      => 'd.m.Y',
			'long_date'       => 'l,j. F Y',
			'long_time'       => 'H:i:s',
			'short_time'      => 'H:i',
			'short_date_time' => 'd.m.Y H:i',
			'long_date_time'  => 'l, j. F Y H:i'
		];
	}

	/** Liest « 02.10.2026 », das Format der Datumsauswahl (short_date) — und weiterhin « 02/10/2026 ». */
	public function date2sql(&$date)
	{
		if (preg_match('#^(\d{2})[./](\d{2})[./](\d{4})$#', $date, $match))
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
		if (preg_match('#^(\d{2})[./](\d{2})[./](\d{4}) (\d{2}):(\d{2})$#', $datetime, $match))
		{
			$datetime = $match[3].'-'.$match[2].'-'.$match[1].' '.$match[4].':'.$match[5].':00';
		}
	}

	/**
	 * Übersetzt Wochentag-/Monatsnamen EN → DE in einem formatierten Datum-String.
	 * Wird aus helpers/time.php::timetostr() aufgerufen — behebt den "friday dernier"-Bug.
	 *
	 * Nur Langformen (l = Monday, F = January) werden übersetzt, um Kollisionen
	 * der Kurzformen zu vermeiden (Mar = Tue oder March je nach Kontext).
	 */
	public function localize_date_output($output)
	{
		return strtr($output, [
			'Monday'    => 'Montag',
			'Tuesday'   => 'Dienstag',
			'Wednesday' => 'Mittwoch',
			'Thursday'  => 'Donnerstag',
			'Friday'    => 'Freitag',
			'Saturday'  => 'Samstag',
			'Sunday'    => 'Sonntag',
			'January'   => 'Januar',
			'February'  => 'Februar',
			'March'     => 'März',
			'April'     => 'April',
			'May'       => 'Mai',
			'June'      => 'Juni',
			'July'      => 'Juli',
			'August'    => 'August',
			'September' => 'September',
			'October'   => 'Oktober',
			'November'  => 'November',
			'December'  => 'Dezember',
		]);
	}
}
