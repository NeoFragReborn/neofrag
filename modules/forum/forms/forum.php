<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$rules = [
	'title' => [
		'label' => $this->lang('Titre'),
		'value' => $this->form()->value('title'),
		'type'  => 'text',
		'rules' => 'required'
	],
	'category' => [
		'label'  => $this->lang('Catégorie'),
		'value'  => $this->form()->value('category_id'),
		'values' => $this->form()->value('categories'),
		'type'   => 'select',
		'rules'  => 'required'
	],
	'description' => [
		'label' => $this->lang('Description'),
		'value' => $this->form()->value('description'),
		'type'  => 'text'
	],
	'url' => [
		'label' => $this->lang('Rediriger vers'),
		'value' => $this->form()->value('url'),
		'type'  => 'url'
	]
];
