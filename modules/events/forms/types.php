<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$rules = [
	'title' => [
		'label'   => $this->lang('Titre'),
		// Le nom d'un type s'écrit dans la langue où l'on travaille (m32, 2026-10-15) : on le dit.
		'description' => $this->lang('Écrit pour %s. Change la langue de l\'administration pour traduire ce type ; les autres langues gardent leur texte.', $this->config->lang->info()->title),
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
