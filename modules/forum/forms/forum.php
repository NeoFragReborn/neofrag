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
		'description' => $this->lang('Affiché dans toutes les langues qui n’ont pas leur traduction ci-dessous.')
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

// Une traduction par langue active, facultative : sans elle, le titre ci-dessus s'affiche (2026-10-01).
foreach (is_iterable($this->config->langs ?? NULL) ? $this->config->langs : [] as $langue)
{
	if (!is_object($langue) || !method_exists($langue, 'info'))
	{
		continue;
	}

	$code        = (string) $langue->info()->name;
	$traduction  = ((array) $this->form()->value('traductions'))[$code] ?? [];

	$rules['title_'.$code] = [
		'label' => $this->lang('Titre — %s', $langue->info()->title),
		'value' => $traduction['title'] ?? '',
		'type'  => 'text'
	];

	$rules['description_'.$code] = [
		'label' => $this->lang('Description — %s', $langue->info()->title),
		'value' => $traduction['description'] ?? '',
		'type'  => 'text'
	];
}
