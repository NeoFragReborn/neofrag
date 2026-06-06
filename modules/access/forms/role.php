<?php
/**
 * https://neofr.ag
 * R1.4 — Form rules pour création/édition d'un rôle.
 *
 * Args attendus passés via add_rules('role', [...]) :
 *   - parent_choices : array [role_id => title] des rôles candidats parent
 *   - is_edit        : (bool) true si édition, false si création
 *   - built_in       : (bool) true si le rôle édité est built-in (alors name est readonly)
 *   - current        : array du rôle (pour pré-remplir en mode édition)
 */

$current   = $this->form()->value('current')        ?: [];
$is_edit   = (bool)$this->form()->value('is_edit');
$built_in  = (bool)$this->form()->value('built_in');

$rules = [
	'name' => [
		'label' => $this->lang('Nom technique'),
		'info'  => $this->lang('Identifiant interne (lettres, chiffres, _ et -). Ex: "moderator_lead". Doit être unique.'),
		'value' => $current['name'] ?? '',
		'rules' => 'required|alpha_dash|min_length=3|max_length=100'.($is_edit ? '|readonly' : '')
	],
	'title' => [
		'label' => $this->lang('Titre affiché'),
		'info'  => $this->lang('Visible dans l\'admin et sur les profils.'),
		'value' => $current['title'] ?? '',
		'rules' => 'required|max_length=100'
	],
	'description' => [
		'label' => $this->lang('Description'),
		'info'  => $this->lang('Optionnel. Courte note pour expliquer à quoi sert ce rôle.'),
		'value' => $current['description'] ?? '',
		'type'  => 'textarea'
	],
	'color' => [
		'label' => $this->lang('Couleur'),
		'info'  => $this->lang('Pour l\'affichage dans l\'admin.'),
		'value' => $current['color'] ?? 'secondary',
		'type'  => 'select',
		'values' => [
			'primary'   => $this->lang('Primary (bleu)'),
			'secondary' => $this->lang('Secondary (gris)'),
			'success'   => $this->lang('Success (vert)'),
			'danger'    => $this->lang('Danger (rouge)'),
			'warning'   => $this->lang('Warning (orange)'),
			'info'      => $this->lang('Info (cyan)'),
			'dark'      => $this->lang('Dark (noir)'),
			'light'     => $this->lang('Light (blanc)')
		]
	],
	'icon' => [
		'label'   => $this->lang('Icône'),
		'info'    => $this->lang('Classe Font Awesome. Ex: "fas fa-user-shield".'),
		'value'   => $current['icon'] ?? 'fas fa-user-shield',
		'default' => 'fas fa-user-shield',
		'type'    => 'iconpicker'
	],
	'parent_role_id' => [
		'label'  => $this->lang('Hérite de'),
		'info'   => $this->lang('Le rôle parent dont les permissions sont automatiquement héritées. Optionnel.'),
		'value'  => $current['parent_role_id'] ?? '',
		'values' => $this->form()->value('parent_choices') ?: ['' => '— '.$this->lang('Aucun').' —'],
		'type'   => 'select'
	]
];

// Si built_in : on garde name readonly + on enlève la possibilité de changer le parent
if ($built_in)
{
	$rules['name']['rules'] .= '|disabled';
	$rules['parent_role_id']['rules'] = 'disabled';
	$rules['parent_role_id']['info'] = $this->lang('Les rôles built-in n\'ont pas de parent éditable.');
}
