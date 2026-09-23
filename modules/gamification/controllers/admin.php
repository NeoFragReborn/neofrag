<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Gamification — page admin : barème customisable (poids du karma + gains/plafonds des points).
 * Stocké en config (nf_settings) ; lu par le module avec valeurs par défaut.
 */

namespace NF\Modules\Gamification\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		$this->subtitle($this->lang('Barème'))->icon('fas fa-star');

		// clé config => [libellé, défaut] — chaque libellé passe par lang() ICI, en littéral : c'est ce
		// que `check-langs` sait relever. Traduit plus bas depuis une variable, il restait invisible au
		// contrôle, et sa traduction n'existait que par hasard.
		$fields = [
			'gam_karma_reaction'        => [$this->lang('Karma — par réaction reçue'),            5],
			'gam_karma_content'         => [$this->lang('Karma — par contenu publié'),            1],
			'gam_karma_seniority'       => [$this->lang('Karma — par mois d\'ancienneté'),        2],
			'gam_pt_comment'            => [$this->lang('Points — commentaire posté'),            5],
			'gam_pt_forum_message'      => [$this->lang('Points — message forum'),                3],
			'gam_pt_forum_topic'        => [$this->lang('Points — sujet forum créé'),             10],
			'gam_pt_reaction_received'  => [$this->lang('Points — réaction reçue'),               2],
			'gam_pt_reaction_given'     => [$this->lang('Points — réaction donnée'),              1],
			'gam_pt_news'               => [$this->lang('Points — news / article publié'),        20],
			'gam_pt_login'              => [$this->lang('Points — connexion quotidienne'),        5],
			'gam_cap_comment'           => [$this->lang('Plafond/jour — commentaire'),            50],
			'gam_cap_forum_message'     => [$this->lang('Plafond/jour — message forum'),          60],
			'gam_cap_forum_topic'       => [$this->lang('Plafond/jour — sujet forum'),            30],
			'gam_cap_reaction_received' => [$this->lang('Plafond/jour — réaction reçue (0=illimité)'), 0],
			'gam_cap_reaction_given'    => [$this->lang('Plafond/jour — réaction donnée'),        20],
			'gam_cap_news'              => [$this->lang('Plafond/jour — news / article (0=illimité)'), 0],
			'gam_cap_login'             => [$this->lang('Plafond/jour — connexion'),              5],
		];

		$rules = [];

		foreach ($fields as $key => list($label, $default))
		{
			$rules[$key] = [
				// On passe par le lecteur du module, seul endroit qui connaisse le piège : un réglage
				// absent rend FALSE (et non NULL ni ''), que le test d'origine ne rattrapait pas. Les
				// dix-sept champs s'affichaient donc vides, et « Enregistrer » aurait écrit 0 partout.
				'label' => $label,
				'value' => $this->module('gamification')->cfg($key, $default),
				'type'  => 'number',
				'size'  => 'col-md-3'
			];
		}

		$form = $this	->form()
						->add_rules($rules)
						->add_submit($this->lang('Enregistrer'))
						->save();

		if ($form->is_valid($post))
		{
			foreach (array_keys($fields) as $key)
			{
				$this->config($key, max(0, (int)$post[$key]), 'int');
			}

			notify($this->lang('Barème de gamification mis à jour'));
			redirect('admin/gamification');
		}

		// `nf-form-grid` : la trentaine de champs de ce barème s'empilait un par ligne, chacun
		// occupant toute la largeur pour un champ de nombre large de trois caractères. On
		// déroulait sur plus de mille pixels de haut avec les trois quarts de l'écran vides.
		return $this->admin_card('fas fa-star', $this->lang('Barème — karma & points'),
			'<div class="nf-form-grid">'.$form->display().'</div>');
	}
}
