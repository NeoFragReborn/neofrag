<?php
declare(strict_types=1);
namespace NF\Widgets\Newsletter\Controllers;
use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($config = [])
	{
		return $this->signup($config);
	}

	public function signup($config = [])
	{
		$module = $this->module('newsletter');

		// Sans le module, ni la table des abonnés ni l'adresse d'inscription n'existent.
		if (!$module instanceof \NF\Modules\Newsletter\Newsletter || !$module->is_enabled())
		{
			return '';
		}

		$nb = (int)NeoFrag()->db->select('COUNT(*)')->from('nf_newsletter_subscribers')->where('confirmed', 1)->row();

		// L'envoi va à `newsletter/subscribe`, avec le jeton de session du module : posté sur la page
		// `newsletter`, le champ n'était pas lu par son formulaire (cf. Index::_subscribe du module).
		$body = '<p class="mb-2"><small>'.$this->lang('Reçois nos actus directement par email.').'</small></p>';
		$body .= '<form method="post" action="'.url('newsletter/subscribe').'">';
		$body .= '<input type="hidden" name="_" value="'.htmlspecialchars($module->jeton_widget()).'">';
		$body .= '<div class="input-group input-group-sm">';
		$body .= '<input type="email" name="email" class="form-control" placeholder="'.$this->lang('ton@email.fr').'" aria-label="'.$this->lang('Adresse email').'" required>';
		$body .= '<button type="submit" class="btn btn-primary" aria-label="'.$this->lang('S\'inscrire').'"><i class="fas fa-envelope"></i></button>';
		$body .= '</div>';
		$body .= '</form>';
		if ($nb > 0)
		{
			$body .= '<small class="text-muted d-block mt-2">'.$this->lang('%d abonné déjà inscrit|%d abonnés déjà inscrits', $nb, $nb).'</small>';
		}

		return $this->panel()
					->heading($this->lang('Newsletter'), 'far fa-envelope')
					->body($body);
	}
}
