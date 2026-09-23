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

	$config = NeoFrag()->config;
	$langue = isset($config->lang) && is_object($config->lang) ? (string) $config->lang->info()->name : 'fr';

	// Les NOMS de jours et de mois viennent d'ICU (extension intl, exigée par le produit), dans la
	// langue du site : `date()` les écrit toujours en anglais, et la traduction qui suivait ne
	// connaissait que les formes COMPLÈTES — « 30 aug 2026 », « 16 sep 2026 » sur un site français
	// (signalé le 2026-09-23). ICU connaît aussi les abréviations (« 16 sept. 2026 »),
	// et la casse de chaque langue : tout mettre en minuscules écrivait « märz » en allemand.
	if (class_exists('IntlDateFormatter'))
	{
		static $formateurs = [];

		$locale = ['fr' => 'fr_FR', 'en' => 'en_GB', 'de' => 'de_DE', 'es' => 'es_ES', 'it' => 'it_IT', 'pt' => 'pt_PT'][$langue] ?? $langue;
		$noms   = ['D' => 'EEE', 'l' => 'EEEE', 'M' => 'MMM', 'F' => 'MMMM'];
		$output = '';

		for ($i = 0, $n = strlen($format); $i < $n; $i++)
		{
			$c = $format[$i];

			// Un caractère échappé (`\à`, `\l\e`) s'écrit tel quel, comme le fait date().
			if ($c === '\\' && $i + 1 < $n)
			{
				$output .= $format[++$i];
				continue;
			}

			if (isset($noms[$c]))
			{
				$formateurs[$locale.$c] ??= new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, date_default_timezone_get(), \IntlDateFormatter::GREGORIAN, $noms[$c]);
				$output .= (string) $formateurs[$locale.$c]->format($timestamp);
				continue;
			}

			$output .= date($c, $timestamp);
		}

		$output = preg_replace('/ +/', ' ', $output) ?? $output;

		return utf8_string(mb_strtoupper(mb_substr($output, 0, 1)).mb_substr($output, 1));
	}

	$output = date($format, $timestamp);

	// Sans intl : la traduction des noms complets que porte l'addon de langue.
	$lang_addon = $config->lang ?? NULL;
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
