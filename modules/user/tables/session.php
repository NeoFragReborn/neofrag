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
			// host_name = reverse DNS, contrôlé par le propriétaire de l'IP → échappé.
			$ip_address = $session->data->session?->ip_address;
			return geolocalisation($ip_address).'<span data-bs-toggle="tooltip" data-original-title="'.htmlspecialchars((string)($session->data->session?->host_name ?? ''), ENT_QUOTES).'">'.htmlspecialchars((string)$ip_address, ENT_QUOTES).'</span>';
		})
		->col($this->lang('Site référent'), function($session){
			return ($referer = $session->data->session?->referer) ? urltolink($referer) : $this->lang('Aucun');
		})
		->col($this->lang('Date'), 'last_activity');
