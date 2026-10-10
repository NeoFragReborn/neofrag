<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$this	->col(function($session){
			return user_agent($session->data->session?->user_agent);
		})
		->col($this->lang('Adresse IP'), function($session){
			// host_name = reverse DNS, contrôlé par le propriétaire de l'IP → échappé. Plus de drapeau devant l'adresse :
			// pour l'obtenir, le navigateur de l'administrateur envoyait les adresses IP des membres à un service tiers
			// (neofr.ag), sans que personne ne l'ait demandé — retiré le 2026-10-08.
			// Sur une démonstration, chacun est administrateur : les adresses des autres visiteurs ne se montrent pas
			// (audit du 2026-10-09 — toutes les sessions, anonymes comprises, étaient lisibles).
			if (nf_demo())
			{
				return '—';
			}

			$ip_address = $session->data->session?->ip_address;
			return '<span data-bs-toggle="tooltip" title="'.nf_texte($session->data->session?->host_name ?? '').'">'.nf_texte($ip_address).'</span>';
		})
		->col($this->lang('Site référent'), function($session){
			return nf_demo() ? '—' : (($referer = $session->data->session?->referer) ? urltolink($referer) : $this->lang('Aucun'));
		})
		->col($this->lang('Date'), 'last_activity');
