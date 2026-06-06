<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

function is_windows()
{
	return strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
}

// Site de démonstration : NEOFRAG_DEMO=TRUE (config/neofrag.php) bloque les actions
// destructrices/de configuration (le contenu reste modifiable, l'auto-reset le nettoie).
function nf_demo()
{
	return defined('NEOFRAG_DEMO') && NEOFRAG_DEMO;
}

function get_system($cmd, ...$args)
{
	if ($args)
	{
		array_walk($args, function(&$a){
			if (!is_int($a))
			{
				$a = '"'.addcslashes($a, '"').'"';
			}
		});

		$cmd = sprintf(...array_merge([$cmd], $args));
	}

	$proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

	$get = function($i) use ($pipes){
		$output = stream_get_contents($pipes[$i]);
		fclose($pipes[$i]);
		return $output;
	};

	$output = $get(1).$get(2);

	proc_close($proc);

	return utf8_string(rtrim($output, "\r\n"), 'IBM850');
}
