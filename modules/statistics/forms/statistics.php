<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

if (($date = $this->session('statistics', 'date')) && time() - $date > strtoseconds('1 day'))
{
	$this->session	->destroy('statistics', 'start')
					->destroy('statistics', 'end');
}

$rules = [
	[
		'type'  => 'legend',
		'label' => $this->lang('Période')
	],
	'start' => [
		'type'  => 'date',
		'value' => $this->session('statistics', 'start') ?: strtotime('-1 year')
	],
	'end' => [
		'type'  => 'date',
		'value' => $this->session('statistics', 'end') ?: time()
	],
	'period' => [
		'type'   => 'select',
		'value'  => $this->session('statistics', 'period') ?: 'month',
		'values' => [
			'hour'  => $this->lang('Heure'),
			'day'   => $this->lang('Jour'),
			'week'  => $this->lang('Semaine'),
			'month' => $this->lang('Mois'),
			'year'  => $this->lang('Année')
		]
	],
	[
		'type'  => 'legend',
		'label' => $this->lang('Séries affichées')
	],
	'modules' => [
		'type'   => 'checkbox',
		'values' => []
	]
];

if ($modules = $this->session('statistics', 'modules'))
{
	$rules['modules']['checked'] = array_fill_keys($modules, TRUE);
}

foreach ($this->model()->get_statistics() as $name => $statistic)
{
	if ($modules === NULL)
	{
		$rules['modules']['checked'][$name] = TRUE;
	}

	// La couleur de la série est une pastille à côté du libellé, et non la couleur du texte : un
	// libellé jaune ou vert pâle sur une carte blanche tombait sous 2:1 (check-mise-en-page, 2026-09-23).
	$rules['modules']['values'][$name] = '<span class="nf-series-swatch" style="background-color: '.$statistic['color'].'"></span> '.$statistic['title'];
}
