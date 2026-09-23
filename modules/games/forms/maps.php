<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$rules = [
	'game_id' => [
		'label'  => $this->lang('Jeu'),
		'value'  => $this->form()->value('game_id'),
		'values' => $this->form()->value('games'),
		'type'   => 'select',
		'rules'  => 'required'
	],
	'title' => [
		'label' => $this->lang('Nom de la carte'),
		'value' => $this->form()->value('title'),
		'type'  => 'text',
		'rules' => 'required'
	],
	'image' => [
		'label' => $this->lang('Image'),
		'value' => $this->form()->value('image_id'),
		'upload'=> 'games/maps',
		'type'  => 'file',
		'info'  => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
		'check'  => function($filename, $ext){
			if (!in_array($ext, ['gif', 'jpeg', 'jpg', 'png']))
			{
				return $this->lang('Veuiller choisir un fichier d\'image');
			}
		}
	]
];
