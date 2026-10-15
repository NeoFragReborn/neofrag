<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$rules = [
	'title' => [
		'label' => $this->lang('Titre'),
		// Le nom d'un rôle s'écrit dans la langue où l'on travaille (m32, 2026-10-15) : on le dit.
		'description' => $this->lang('Écrit pour %s. Change la langue de l\'administration pour traduire ce rôle ; les autres langues gardent leur texte.', $this->config->lang->info()->title),
		'value' => $this->form()->value('title'),
		'type'  => 'text',
		'rules' => 'required'
	]
];
