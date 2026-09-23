<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$rules = [
	'title' => [
		'label' => $this->lang('Nom du mode'),
		'value' => $this->form()->value('title'),
		'type'  => 'text',
		'rules' => 'required'
	]
];
