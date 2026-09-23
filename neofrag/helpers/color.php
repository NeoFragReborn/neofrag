<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

/**
 * Couleur de la palette, convertie ou non.
 *
 * @param bool $signaler Emettre un avertissement quand la couleur est inconnue. Mettre FALSE pour
 *                       un simple TEST : voir `is_color()`, qui existe pour ce cas.
 */
function get_colors($name = NULL, $convert = TRUE, $signaler = TRUE)
{
	$colors = [
		'default'	=> '#007bff',
		'primary'   => '#007bff',
		'secondary' => '#6c757d',
		'success'   => '#28a745',
		'danger'    => '#dc3545',
		'warning'   => '#ffc107',
		'info'      => '#17a2b8',
		'light'     => '#f8f9fa',
		'dark'      => '#343a40',
		'link'      => ''
	];

	if ($name === NULL)
	{
		return array_filter($colors);
	}
	else if (!is_empty($name))
	{
		list($color) = explode(' ', $name, 2);

		if (array_key_exists($color, $colors))
		{
			return $convert ? $colors[$color] : $name;
		}
		else if ($convert && preg_match('/^#([a-f0-9]{3}){1,2}/i', $name))
		{
			return $name;
		}

		if ($signaler)
		{
			trigger_error('Invalid color: '.$name, E_USER_WARNING);
		}
	}
}

/**
 * La couleur est-elle exploitable, sans rien signaler si elle ne l'est pas ?
 *
 * `get_colors()` remplit deux rôles à la fois : convertir une couleur, et dire si elle est valide.
 * Or il SIGNALE toute couleur inconnue — ce qui est juste pour une conversion, où l'on veut savoir
 * qu'un appelant demande une couleur qui n'existe pas, mais faux pour un simple test.
 *
 * Quatre appelants l'utilisaient pourtant comme test, sous la forme `get_colors($x) ? $x : 'defaut'`.
 * Chaque valeur repoussée par ce garde-fou journalisait donc un avertissement, alors que le repli
 * sur la valeur par défaut est exactement le comportement voulu. La production en journalisait à
 * chaque rendu des pages concernées (« Invalid color: orange », « Invalid color: red »).
 *
 * La logique n'est pas recopiée ici : on appelle `get_colors()` en le rendant muet, pour que les
 * deux fonctions ne puissent jamais diverger.
 */
function is_color($name): bool
{
	// Écart VOLONTAIRE avec l'ancien test : `get_colors(NULL)` rend la palette entière, donc une
	// valeur vraie. Les appelants écrivant `is_color($x) ? $x : 'defaut'` auraient alors retenu
	// NULL comme couleur, au lieu de leur valeur de repli. Une couleur absente n'est pas une
	// couleur valide.
	if ($name === NULL)
	{
		return FALSE;
	}

	return (bool) get_colors($name, TRUE, FALSE);
}

/**
 * Les classes Bootstrap 5 d'une pastille (`.badge`) de couleur contextuelle.
 *
 * Bootstrap 4 écrivait `badge-danger` ; Bootstrap 5 a retiré ces classes. La pastille « douce » que
 * dessinaient les thèmes — fond pâle, texte coloré — y est `bg-danger-subtle text-danger-emphasis`,
 * dont les couleurs sont tirées de la palette du thème par css/nf-bs5-bridge.css. `light` et `dark`
 * restent des pastilles pleines. Une couleur inconnue donne une pastille neutre.
 *
 * Les noms de couleur CSS courants sont ramenés à leur couleur contextuelle : les groupes de
 * modération étaient livrés en `orange` et `red`, deux noms que Bootstrap ne connaît pas — leur
 * pastille sortait sans aucune couleur, texte blanc sur fond blanc (check-mise-en-page, 2026-09-23).
 */
function badge_class($color): string
{
	$alias = [
		'default' => 'primary', 'blue' => 'primary', 'red' => 'danger', 'orange' => 'warning', 'yellow' => 'warning',
		'green' => 'success', 'cyan' => 'info', 'teal' => 'info', 'gray' => 'secondary', 'grey' => 'secondary',
		'black' => 'dark', 'white' => 'light',
	];
	$color = $alias[strtolower((string) $color)] ?? (string) $color;

	if (in_array($color, ['light', 'dark'], TRUE))
	{
		return 'text-bg-'.$color;
	}

	return in_array($color, ['primary', 'secondary', 'success', 'danger', 'warning', 'info'], TRUE)
		? 'bg-'.$color.'-subtle text-'.$color.'-emphasis'
		: 'bg-secondary-subtle text-secondary-emphasis';
}

/* =========================================================================
   Contraste : quelle couleur de texte poser SUR une couleur de fond ?

   Les themes declarent un jeton `--*-on-accent` : la couleur du texte des boutons principaux,
   des pastilles et des entetes peints a la couleur d'accent. Cette valeur etait ecrite en dur,
   et valait `#ffffff` dans cinq themes sur six — y compris ceux dont l'accent est CLAIR. Du
   blanc sur le turquoise `#2dd4bf` donne un contraste d'environ 1,75:1, tres loin des 4,5:1 que
   demande la norme : le bouton principal de chaque page etait illisible.

   Figer une couleur sombre a la place n'aurait fait que deplacer le probleme, l'accent etant
   reglable par l'administrateur du site. La couleur est donc CALCULEE depuis la luminance reelle
   du fond.
   ======================================================================== */

/**
 * Luminance relative d'une couleur, selon la formule de la norme WCAG 2.1.
 *
 * @param string $couleur Couleur hexadécimale, avec ou sans `#`, sur trois ou six chiffres.
 * @return float          Entre 0 (noir) et 1 (blanc), ou -1 si la couleur est illisible.
 */
function couleur_luminance(string $couleur): float
{
	$hex = ltrim(trim($couleur), '#');

	if (strlen($hex) === 3)
	{
		$hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
	}

	if (!preg_match('/^[0-9a-f]{6}$/i', $hex))
	{
		return -1.0;
	}

	$canaux = [];

	foreach ([0, 2, 4] as $decalage)
	{
		$valeur = hexdec(substr($hex, $decalage, 2)) / 255;

		// Linéarisation sRGB : l'œil ne perçoit pas la clarté de façon proportionnelle.
		$canaux[] = $valeur <= 0.04045 ? $valeur / 12.92 : (($valeur + 0.055) / 1.055) ** 2.4;
	}

	return 0.2126 * $canaux[0] + 0.7152 * $canaux[1] + 0.0722 * $canaux[2];
}

/**
 * Rapport de contraste entre deux couleurs, selon la norme WCAG 2.1.
 *
 * @return float De 1 (identiques) à 21 (noir sur blanc).
 */
function couleur_contraste(string $premiere, string $seconde): float
{
	$a = couleur_luminance($premiere);
	$b = couleur_luminance($seconde);

	if ($a < 0 || $b < 0)
	{
		return 1.0;
	}

	return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
}

/**
 * Couleur de texte lisible sur le fond donné : la plus contrastée des deux proposées.
 *
 * @param string $fond   Couleur de fond, en hexadécimal.
 * @param string $clair  Couleur de texte claire (défaut : blanc).
 * @param string $sombre Couleur de texte sombre (défaut : un noir légèrement bleuté, plus doux
 *                       qu'un noir pur sur les fonds colorés).
 */
function couleur_lisible_sur(string $fond, string $clair = '#ffffff', string $sombre = '#0b1418'): string
{
	// Une couleur qu'on ne sait pas lire (nom CSS, rgb(), variable) : on garde le clair, qui est
	// le comportement historique, plutôt que de risquer pire.
	if (couleur_luminance($fond) < 0)
	{
		return $clair;
	}

	return couleur_contraste($fond, $clair) >= couleur_contraste($fond, $sombre) ? $clair : $sombre;
}
