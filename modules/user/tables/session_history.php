<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$this	->col(function($session_history){
			return user_agent($session_history->user_agent);
		})
		->col($this->lang('Adresse IP'), function($session_history){
			// host_name = reverse DNS, contrôlé par le propriétaire de l'IP → échappé. Plus de drapeau : cf. session.php.
			// Sur une démonstration, le compte est partagé : son historique montrerait les adresses des autres visiteurs.
			if (nf_demo())
			{
				return '—';
			}

			$ip_address = $session_history->ip_address;
			return '<span data-bs-toggle="tooltip" title="'.nf_texte($session_history->host_name).'">'.nf_texte($ip_address).'</span>';
		})
		->col($this->lang('Site référent'), function($session_history){
			return nf_demo() ? '—' : (($referer = $session_history->referer) ? urltolink($referer) : $this->lang('Aucun'));
		})
		->col($this->lang('Date'), 'date')
		->col($this->lang('Compte tiers'), 'auth');
