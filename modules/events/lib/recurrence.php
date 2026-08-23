<?php
/**
 * https://neofr.ag
 * Calcul PUR (locator-free) des dates d'une série récurrente. Sortie consommée par le contrôleur
 * d'ajout d'événement pour matérialiser N occurrences (cf. modules/events/controllers/admin.php).
 */

namespace NF\Modules\Events\Lib;

class Recurrence
{
	const MAX_OCCURRENCES = 52;

	const INTERVALS = [
		'daily'   => '+%d day',
		'weekly'  => '+%d week',
		'monthly' => '+%d month',
	];

	/**
	 * Dates des occurrences d'une série, durée de chaque occurrence préservée (date_end - date).
	 *
	 * @param  string      $date      début de la 1re occurrence ('Y-m-d H:i:s')
	 * @param  string|null $date_end  fin de la 1re occurrence ('' / NULL = pas de durée)
	 * @param  string      $freq      '' (aucune) | 'daily' | 'weekly' | 'monthly'
	 * @param  int         $count     nombre total d'occurrences (borné [1, MAX_OCCURRENCES])
	 * @return array<int, array{0:string, 1:string}>  liste de [date, date_end] ('' si pas de fin)
	 */
	public static function dates($date, $date_end, $freq, $count)
	{
		$base = strtotime((string) $date);

		if ($base === FALSE)
		{
			return [];
		}

		// Fréquence inconnue (ou aucune) → une seule occurrence, quel que soit $count.
		$count = isset(self::INTERVALS[$freq]) ? max(1, min(self::MAX_OCCURRENCES, (int) $count)) : 1;

		$end      = $date_end ? strtotime((string) $date_end) : FALSE;
		$duration = ($end !== FALSE && $end > $base) ? $end - $base : NULL;

		$result = [];

		for ($i = 0; $i < $count; $i++)
		{
			$start = $i === 0 ? $base : strtotime(sprintf(self::INTERVALS[$freq], $i), $base);

			$result[] = [
				date('Y-m-d H:i:s', $start),
				$duration !== NULL ? date('Y-m-d H:i:s', $start + $duration) : '',
			];
		}

		return $result;
	}
}
