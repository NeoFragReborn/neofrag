<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$rules = [
	'title' => [
		'label' => $this->lang('Titre'),
		'type'  => 'text',
		'value' => $this->form()->value('title'),
		'rules' => 'required'
	],
	'description' => [
		'label' => $this->lang('Description'),
		'type'  => 'textarea',
		'value' => $this->form()->value('description')
	]
];
