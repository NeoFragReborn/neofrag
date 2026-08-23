<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

function now($timestamp = NULL): string
{
	return timetostr('Y-m-d H:i:s', $timestamp);
}

function strtoseconds($string)
{
	return strtotime($string, 0);
}

function timetostr($format, $timestamp = NULL): string
{
	// Le fichier est en strict_types : on caste explicitement car les appelants passent souvent
	// des objets stringables (Lang pour $format, Date pour $timestamp) — sinon strtotime()/date()
	// lèvent une TypeError. (string)/(int) déclenchent leur __toString comme avant le strict_types.
	$format = (string)$format;

	if ($timestamp === NULL)
	{
		$timestamp = time();
	}

	if (is_a($timestamp, 'NF\NeoFrag\Libraries\Date'))
	{
		// Un objet Date a un __toString LOCALISÉ (« Le 1 mai 2026 à 09:00 ») que strtotime() ne sait
		// pas parser → 0 → 01/01/1970. On lit son timestamp Unix directement (comme time_span()).
		$timestamp = $timestamp->timestamp();
	}
	else if (!is_numeric($timestamp))
	{
		$timestamp = strtotime((string)$timestamp);
	}

	$timestamp = (int)$timestamp;

	if (is_windows())
	{
		$format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
	}

	$output = date($format, $timestamp);

	// Localisation : si l'addon language actif a une méthode localize_date_output(),
	// l'utiliser pour traduire les noms de jours/mois en EN → langue cible.
	// Fix le bug "friday dernier" (date() PHP natif retourne toujours en EN).
	$lang_addon = NeoFrag()->config->lang ?? NULL;
	if (is_object($lang_addon) && method_exists($lang_addon, 'localize_date_output'))
	{
		$output = $lang_addon->localize_date_output($output);
	}

	return utf8_string(ucfirst(preg_replace('/ +/', ' ', strtolower($output))));
}

function time_span($timestamp): string
{
	if (!is_a($timestamp, 'NF\NeoFrag\Libraries\Date') && !is_numeric($timestamp))
	{
		$timestamp = strtotime($timestamp);
	}

	return (string)NeoFrag()->date($timestamp);
}
