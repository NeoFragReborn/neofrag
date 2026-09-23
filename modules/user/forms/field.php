<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: NeoFrag Reborn
 *
 * Définition d'un champ de profil.
 *
 * Le NOM TECHNIQUE ne figure pas ici : il est dérivé du libellé à la création, puis immuable. Le
 * proposer à la saisie inviterait à le changer, et le changer perdrait le lien avec les valeurs
 * déjà saisies par les membres.
 */

use NF\Modules\User\Models\Fields;

$types = [
	'text'     => $this->lang('Texte court'),
	'textarea' => $this->lang('Texte long'),
	'select'   => $this->lang('Liste déroulante'),
	'radio'    => $this->lang('Choix unique'),
	'checkbox' => $this->lang('Case à cocher'),
	'url'      => $this->lang('Adresse web'),
	'number'   => $this->lang('Nombre'),
	'date'     => $this->lang('Date'),
];

$rules = [
	'label' => [
		'label' => $this->lang('Libellé'),
		'value' => $this->form()->value('label'),
		'rules' => 'required'
	],
	'description' => [
		'label'       => $this->lang('Aide affichée sous le champ'),
		'description' => $this->lang('Facultatif.'),
		'value'       => $this->form()->value('description')
	],
	'type' => [
		'label'  => $this->lang('Type'),
		'values' => $types,
		'value'  => $this->form()->value('type') ?: 'text',
		'type'   => 'select',
		'rules'  => 'required'
	],
	'options' => [
		'label'       => $this->lang('Choix proposés'),
		'description' => $this->lang('Un par ligne. Ne sert qu\'aux types « %s » et « %s ».', $types['select'], $types['radio']),
		'value'       => $this->form()->value('options'),
		'type'        => 'textarea',
		'rows'        => 5,
		'check'       => function($options){
			$type = post('type');

			if (in_array($type, Fields::TYPES_A_OPTIONS, TRUE) && count(Fields::options_en_tableau($options)) < 2)
			{
				// Un choix unique n'est pas un choix : sans ce refus, le membre verrait une liste
				// à une seule entrée, qu'il ne pourrait ni éviter ni comprendre.
				return $this->lang('Indiquez au moins deux choix, un par ligne.');
			}
		}
	],
	'required' => [
		'checked' => ['on' => $this->form()->value('required')],
		'values'  => ['on' => $this->lang('Réponse obligatoire')],
		'type'    => 'checkbox'
	],
	'public' => [
		'checked'     => ['on' => $this->form()->value('public')],
		'values'      => ['on' => $this->lang('Afficher sur la fiche publique du membre')],
		'description' => $this->lang('Décoché, le champ n\'est visible que par le membre et par l\'administration.'),
		'type'        => 'checkbox'
	]
];
