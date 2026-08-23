<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

function array_last_key($array)
{
	$keys = array_keys($array);

	return end($keys);
}

if (!function_exists('array_last'))
{
	// PHP 8.5 fournit array_last() nativement (même sémantique : dernière valeur). Sans ce garde,
	// redéclarer la fonction ferait fataliser le boot (« Cannot redeclare array_last ») sur PHP 8.5+.
	function array_last($array)
	{
		return end($array);
	}
}

function array_offset_left($array, $offset = 1): array
{
	return array_slice($array, $offset);
}

function array_offset_right($array, $length = 1): array
{
	return array_slice($array, 0, -$length);
}

function array_natsort(&$array, $data = NULL): void
{
	uasort($array, function($a, $b) use ($data){
		return str_nat($a, $b, $data);
	});
}
