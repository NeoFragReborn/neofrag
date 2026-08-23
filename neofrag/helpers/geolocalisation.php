<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

function geolocalisation($address_ip): string
{
	if (!is_empty($address_ip))
	{
		NeoFrag()->js('geolocalisation');
		return '<img src="'.image('ajax-loader.gif').'" style="margin-right: 10px;" data-geolocalisation="'.htmlspecialchars((string)$address_ip, ENT_QUOTES).'" alt="" />';
	}
	else
	{
		return '<img src="'.image('icons/user-silhouette-question.png').'" alt="" />';
	}
}
