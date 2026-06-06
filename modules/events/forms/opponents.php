<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$rules = [
	'title' => [
		'label'  => $this->lang('Nom'),
		'value'  => $this->form()->value('title'),
		'type'   => 'text',
		'rules'  => 'required'
	],
	'image' => [
		'label'  => $this->lang('Image'),
		'value'  => $this->form()->value('image_id'),
		'type'   => 'file',
		'upload' => 'opponents',
		'info'   => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
		'check'  => function($filename, $ext){
			if (!in_array($ext, ['gif', 'jpeg', 'jpg', 'png']))
			{
				return $this->lang('Veuiller choisir un fichier d\'image');
			}
		}
	],
	'country' => [
		'label'  => $this->lang('Pays'),
		'value'  => $this->form()->value('country'),
		'values' => get_countries(),
		'type'   => 'select'
	],
	'website' => [
		'label'  => $this->lang('Site web'),
		'value'  => $this->form()->value('website'),
		'type'   => 'url'
	]
];
