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
			// host_name = reverse DNS, contrôlé par le propriétaire de l'IP → échappé.
			$ip_address = $session_history->ip_address;
			return geolocalisation($ip_address).'<span data-bs-toggle="tooltip" data-original-title="'.nf_texte($session_history->host_name).'">'.nf_texte($ip_address).'</span>';
		})
		->col($this->lang('Site référent'), function($session_history){
			return ($referer = $session_history->referer) ? urltolink($referer) : $this->lang('Aucun');
		})
		->col($this->lang('Date'), 'date')
		->col($this->lang('Compte tiers'), 'auth');
