<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Statistics\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Ajax_Checker extends Module_Checker
{
	public function index()
	{
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
		}

		if ($check = post_check(['modules', 'start', 'end', 'period'], $this->form()->token('sq6fswkfb81n0lu4cb7eyb3tuixcovla')))
		{
			$this->extension('json');

			$periods = [
				'hour'  => [function($a){ return 'DATE_FORMAT('.$a.', "%Y-%m-%d %H")'; },                                     'Y-m-d H', new \DateInterval('PT1H'), function(&$a) { $a = $a->setTime(0, 0); }],
				'day'   => [function($a){ return 'DATE_FORMAT('.$a.', "%Y-%m-%d")'; },                                        'Y-m-d',   new \DateInterval('P1D')],
				'week'  => [function($a){ return 'CONCAT_WS("-", DATE_FORMAT('.$a.', "%x"), LPAD(WEEK('.$a.', 3), 2, 0))'; }, 'o-W',     new \DateInterval('P1W'),  function(&$a) { $a = $a->modify('previous week'); }],
				'month' => [function($a){ return 'DATE_FORMAT('.$a.', "%Y-%m")'; },                                           'Y-m',     new \DateInterval('P1M'),  function(&$a) { $a = $a->modify('first day of'); }],
				'year'  => [function($a){ return 'DATE_FORMAT('.$a.', "%Y")'; },                                              'Y',       new \DateInterval('P1Y'),  function(&$a) { $a = $a->modify('01 january'); }]
			];

			// Les dates arrivent dans le format de la langue (« 02.10.2026 » en allemand) : date2sql() les
			// remet en Y-m-d. Une date illisible retombe sur la période par défaut (un an jusqu'à
			// aujourd'hui) au lieu de faire tomber la requête.
			$debut = (string) $check['start'];
			$fin   = (string) $check['end'];
			$this->config->lang->date2sql($debut);
			$this->config->lang->date2sql($fin);

			$start = date_create_from_format('!Y-m-d', $debut) ?: date_create('-1 year midnight');
			$end   = date_create_from_format('!Y-m-d', $fin) ?: date_create('today');

			// Une plage bornée, et un nombre de points borné selon le pas : « de l'an 1 à l'an 9999,
			// heure par heure » générait des millions de points jusqu'à épuiser la mémoire (audit du
			// 2026-10-02 ; sur la démo, tout visiteur est administrateur). Un pas inconnu devient le mois.
			$check['period'] = isset($periods[$check['period']]) ? $check['period'] : 'month';
			$start = max($start, date_create('2000-01-01'));
			$end   = min($end, date_create('+1 year midnight'));

			if ($end < $start)
			{
				[$start, $end] = [$end, $start];
			}

			$jours_max = ['hour' => 31, 'day' => 1100, 'week' => 7300][$check['period']] ?? 18300;

			if ($start->diff($end)->days > $jours_max)
			{
				$start = (clone $end)->modify('-'.$jours_max.' days');
			}

			$this->session	->set('statistics', 'period', $check['period'])
							->set('statistics', 'start', $start->getTimestamp())
							->set('statistics', 'end', $end->getTimestamp())
							->set('statistics', 'date', time());

			return [$this->model()->get_statistics(array_filter($check['modules'])), $start, $end->setTime(23, 59, 59), $periods[$check['period']]];
		}
	}
}
