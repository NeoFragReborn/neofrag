<?php
declare(strict_types=1);
/**
 * Le formulaire d'un préfixe de sujet (2026-10-01) : son titre par défaut, sa couleur, sa
 * place dans la liste, et une traduction facultative par langue active.
 */

$rules = [
	'title' => [
		'label'       => $this->lang('Titre'),
		'value'       => $this->form()->value('title'),
		'type'        => 'text',
		'rules'       => 'required',
		'description' => $this->lang('Affiché dans toutes les langues qui n’ont pas leur traduction ci-dessous.')
	],
	'color' => [
		'label'  => $this->lang('Couleur'),
		'value'  => $this->form()->value('color') ?: 'secondary',
		'type'   => 'select',
		'values' => [
			'primary'   => $this->lang('Couleur principale'),
			'success'   => $this->lang('Vert'),
			'warning'   => $this->lang('Orange'),
			'danger'    => $this->lang('Rouge'),
			'info'      => $this->lang('Bleu clair'),
			'secondary' => $this->lang('Gris'),
			'dark'      => $this->lang('Noir')
		],
		'rules'  => 'required'
	],
	'order' => [
		'label' => $this->lang('Ordre'),
		'value' => (string) ((int) $this->form()->value('order')),
		'type'  => 'number'
	]
];

// Une traduction par langue active, facultative : sans elle, le titre ci-dessus s'affiche.
foreach (is_iterable($this->config->langs ?? NULL) ? $this->config->langs : [] as $langue)
{
	if (!is_object($langue) || !method_exists($langue, 'info'))
	{
		continue;
	}

	$code       = (string) $langue->info()->name;
	$traduction = ((array) $this->form()->value('traductions'))[$code] ?? [];

	$rules['title_'.$code] = [
		'label' => $this->lang('Titre — %s', $langue->info()->title),
		'value' => $traduction['title'] ?? '',
		'type'  => 'text'
	];
}
