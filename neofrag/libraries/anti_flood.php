<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;

class Anti_Flood extends Library
{
	public function __invoke($message = '', $time = ''): static
	{
		if (($date = $this->session('anti_flood', 'actions', $this->__id())) && $date->timestamp() > $this->date()->sub($time ?: '+10 minutes')->timestamp())
		{
			if (($attempts = $this->session('anti_flood', 'attempts') ?: 0) < 10)
			{
				$this->session->set('anti_flood', 'attempts', $attempts + 1);
				// Le message par défaut n'avait jamais été écrit : les six langues affichaient la clé, « anti_flood ».
				// Et la réponse de fenêtre (JSON) s'affichait telle quelle sur une page : on y revient (recette, 2026-10-16).
				notify($message ?: $this->lang('Tu viens d’envoyer ce formulaire : patiente quelques minutes avant de recommencer.'), 'danger');
				$this->url->ajax() ? $this->output->json() : refresh();
			}
			else
			{
				//TODO Ban current user for flooding
				$this->session->set('anti_flood', 'attempts', 0);
				refresh();
			}
		}
		else
		{
			// `attempts`, le compteur que lit la branche du dessus (`attempt` ne le remettait jamais à zéro).
			$this->session	->set('anti_flood', 'actions', $this->__id(), $this->date())
							->set('anti_flood', 'attempts', 0);
		}

		return $this;
	}
}
