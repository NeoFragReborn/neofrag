<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$rules = [
	'title' => [
		'label'       => $this->lang('Titre'),
		'value'       => $this->form()->value('title'),
		'type'        => 'text',
		'rules'       => 'required',
		'description' => $this->lang('Écrit pour %s. Change la langue de l\'administration pour traduire ce salon ; les autres langues gardent leur texte.', $this->config->lang->info()->title)
	],
	'description' => [
		'label' => $this->lang('Description'),
		'value' => $this->form()->value('description'),
		'type'  => 'textarea'
	]
];
