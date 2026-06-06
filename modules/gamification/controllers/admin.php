<?php
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

		// clé config => [libellé, défaut]
		$fields = [
			'gam_karma_reaction'        => ['Karma — par réaction reçue',            5],
			'gam_karma_content'         => ['Karma — par contenu publié',            1],
			'gam_karma_seniority'       => ['Karma — par mois d\'ancienneté',        2],
			'gam_pt_comment'            => ['Points — commentaire posté',            5],
			'gam_pt_forum_message'      => ['Points — message forum',                3],
			'gam_pt_forum_topic'        => ['Points — sujet forum créé',             10],
			'gam_pt_reaction_received'  => ['Points — réaction reçue',               2],
			'gam_pt_reaction_given'     => ['Points — réaction donnée',              1],
			'gam_pt_news'               => ['Points — news / article publié',        20],
			'gam_pt_login'              => ['Points — connexion quotidienne',        5],
			'gam_cap_comment'           => ['Plafond/jour — commentaire',            50],
			'gam_cap_forum_message'     => ['Plafond/jour — message forum',          60],
			'gam_cap_forum_topic'       => ['Plafond/jour — sujet forum',            30],
			'gam_cap_reaction_received' => ['Plafond/jour — réaction reçue (0=illimité)', 0],
			'gam_cap_reaction_given'    => ['Plafond/jour — réaction donnée',        20],
			'gam_cap_news'              => ['Plafond/jour — news / article (0=illimité)', 0],
			'gam_cap_login'             => ['Plafond/jour — connexion',              5],
		];

		$rules = [];
		foreach ($fields as $key => list($label, $default))
		{
			$value = $this->config->{$key};

			$rules[$key] = [
				'label' => $this->lang($label),
				'value' => ($value === NULL || $value === '') ? $default : $value,
				'type'  => 'number',
				'size'  => 'col-md-4'
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

		return $this->admin_card('fas fa-star', $this->lang('Barème — karma & points'), $form->display());
	}
}
