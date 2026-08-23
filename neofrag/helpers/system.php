<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

function is_windows(): bool
{
	return strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
}

// Site de démonstration : NEOFRAG_DEMO=TRUE (config/neofrag.php) bloque les actions
// destructrices/de configuration (le contenu reste modifiable, l'auto-reset le nettoie).
function nf_demo(): bool
{
	return defined('NEOFRAG_DEMO') && NEOFRAG_DEMO;
}

