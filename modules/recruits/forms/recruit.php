<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$rules = [
	'title' => [
		'label'       => $this->lang('Intitulé de l\'offre'),
		'value'       => $this->form()->value('title'),
		'type'        => 'text',
		'rules'       => 'required'
	],
	'team_id' => [
		'label'       => $this->lang('Associer à l\'équipe'),
		'value'       => $this->form()->value('team_id'),
		'values'      => $this->form()->value('teams'),
		'type'        => 'select',
		'size'        => 'col-4',
		'description' => $this->lang('Laisser vide pour ne pas associer d\'équipe.<br />Si une candidature est acceptée, le joueur sera automatiquement ajoutée dans l\'équipe sélectionnée avec le rôle associé')
	],
	'role' => [
		'label'       => $this->lang('Rôle proposé'),
		'value'       => $this->form()->value('role'),
		'type'        => 'text',
		'icon'        => 'fas fa-sitemap',
		'description' => $this->lang('Exemple: Joueurs, Manager, etc...'),
		'size'        => 'col-4',
		'rules'       => 'required'
	],
	'icon' => [
		'label'       => $this->lang('Icône'),
		'value'       => $this->form()->value('icon'),
		'default'     => 'fas fa-bullhorn',
		'type'        => 'iconpicker'
	],
	'size' => [
		'label'       => $this->lang('Nombre de place'),
		'value'       => $this->form()->value('size') ?: '1',
		'type'        => 'number',
		'size'        => 'col-2',
		'rules'       => 'required'
	],
	'date_end' => [
		'label'       => $this->lang('Date de clôture'),
		'value'       => $this->form()->value('date_end'),
		'type'        => 'date',
		'check'       => function($value){
			if ($value && strtotime($value) < strtotime(date('Y-m-d')))
			{
				return $this->lang('Vraiment ?! 2.1 Gigowatt !');
			}
		},
		'size'        => 'col-4',
		'description' => $this->lang('Laisser vide pour créer une offre permanente')
	],
	'image' => [
		'label'       => $this->lang('Image'),
		'value'       => $this->form()->value('image_id'),
		'type'        => 'file',
		'upload'      => 'news',
		'info'        => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
		'check'       => function($filename, $ext){
			if (!in_array($ext, ['gif', 'jpeg', 'jpg', 'png']))
			{
				return $this->lang('Veuiller choisir un fichier d\'image');
			}
		}
	],
	'introduction' => [
		'label'       => $this->lang('Introduction'),
		'value'       => $this->form()->value('introduction'),
		'type'        => 'editor',
		'rules'       => 'required'
	],
	'description' => [
		'label'       => $this->lang('Description du poste'),
		'value'       => $this->form()->value('description'),
		'type'        => 'editor'
	],
	'requierments' => [
		'label'       => $this->lang('Profil recherché'),
		'value'       => $this->form()->value('requierments'),
		'type'        => 'editor'
	],
	'closed' => [
		'type'        => 'checkbox',
		'checked'     => ['on' => $this->form()->value('closed')],
		'values'      => ['on' => $this->lang('Fermer le dépôt des candidatures')]
	]
];
