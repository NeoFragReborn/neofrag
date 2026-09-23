<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$rules = [
	'title' => [
		'label'   => $this->lang('Titre'),
		'value'   => $this->form()->value('title'),
		'type'    => 'text',
		'rules'   => 'required'
	],
	'type' => [
		'label'   => $this->lang('Type'),
		'value'   => $this->form()->value('type') ?: 0,
		'values'  => $this->model('types')->get_types_list(),
		'type'    => 'radio',
		'rules'   => 'required'
	],
	'color' => [
		'label'   => $this->lang('Couleur'),
		'value'   => $this->form()->value('color'),
		'type'    => 'colorpicker'
	],
	'icon' => [
		'label'   => $this->lang('Icône'),
		'value'   => $this->form()->value('icon'),
		'default' => 'far fa-clock',
		'type'    => 'iconpicker'
	]
];
