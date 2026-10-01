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
	'image' => [
		'label'  => $this->lang('Image (optionnelle)'),
		'value'  => $this->form()->value('image_id'),
		'type'   => 'file',
		'upload' => 'forum',
		'info'   => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
		'check'  => function($filename, $ext){
			if (!in_array($ext, ['gif', 'jpeg', 'jpg', 'png', 'webp']))
			{
				return $this->lang('Veuiller choisir un fichier d\'image');
			}
		}
	],
	'vip_only' => [
		'type'        => 'checkbox',
		'checked'     => ['on' => $this->form()->value('vip_only')],
		'values'      => ['on' => $this->lang('Réservé aux membres VIP')],
		'description' => $this->lang('La catégorie et ses forums ne sont accessibles qu\'aux membres au statut VIP. Les administrateurs voient tout.')
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
}
