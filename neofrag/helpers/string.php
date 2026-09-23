<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

//Camelcase to Underscored
function cc2u($string): string
{
	return strtolower(preg_replace('/([^A-Z])([A-Z])/', '\\1_\\2', $string));
}

//Underscored to lower-camelcase
function u2lcc($string): string
{
	$string = strtolower(preg_replace('/_+/', '_', trim($string, '_')));
	if (preg_match_all('/_(.?)/', $string, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER))
	{
		$count = 0;
		foreach ($matches as $match)
		{
			$string = substr_replace($string, strtoupper($match[1][0]), $match[0][1] - $count++, 2);
		}
	}

	return $string;
}

//Underscored to upper-camelcase
function u2ucc($string): string
{
	return ucfirst(u2lcc($string));
}

function in_string($needle, $haystack, $strict = TRUE): bool
{
	$needle = (string)$needle;

	if (is_empty($needle))
	{
		return FALSE;
	}

	if ($strict)
	{
		return strpos($haystack, $needle) !== FALSE;
	}
	else
	{
		return stripos($haystack, $needle) !== FALSE;
	}
}

function is_empty($data): bool
{
	if (is_array($data))
	{
		return !$data;
	}
	else
	{
		return (string)$data === '';
	}
}

function url_title($string): string
{
	static $strings = [];

	$string = (string)$string;

	if (!array_key_exists($string, $strings))
	{
		$output = strip_tags(utf8_html_entity_decode($string));

		if (function_exists('transliterator_transliterate'))
		{
			$output = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove', $output);
		}
		else
		{
			static $a, $b;

			if ($a === NULL)
			{
				$chars = [
					'a'  => 'ÀÁÂÃÄÅÆàáâãäå',
					'ae' => 'æ',
					'c'  => 'Çç',
					'e'  => 'ÈÉÊËèéêë',
					'i'  => 'ÌÍÎÏìíîï',
					'n'  => 'Ññ',
					'o'  => 'ÒÓÔÕÖòóôõö',
					'oe' => 'Œœ',
					'u'  => 'ÙÚÛÜùúûü',
					'y'  => 'Ýýÿ',
					'-'  => '_ '
				];

				$a = $b = [];
				foreach ($chars as $key => $value)
				{
					foreach (preg_split('/(?<!^)(?!$)/u', $value) as $char)
					{
						$a[] = $char;
						$b[] = $key;
					}
				}
			}

			$output = str_replace($a, $b, $output);
		}

		$strings[$string] = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($output)), '-');
	}

	return $strings[$string];
}

function str_nat($a, $b, $data = NULL): int
{
	if ($data === NULL || !is_callable($data))
	{
		$data = function($a){
			return $a;
		};
	}

	return strnatcasecmp((string) url_title($data($a)), (string) url_title($data($b)));
}

function escape_html_tags($string, $callback): string
{
	$offset = 0;
	$string = '>'.$string;

	while ($offset < strlen($string) && preg_match('_>([^<]+(</)?)_', $string, $match, PREG_OFFSET_CAPTURE, $offset))
	{
		$offset = $match[1][1];

		if (!isset($match[2]))
		{
			$replacement = $callback($match[1][0]);
			$string      = substr_replace($string, $replacement, $offset, strlen($match[1][0]));
			$offset     += strlen($replacement);
		}
		else
		{
			$offset += strlen($match[1][0]);
		}
	}

	return substr($string, 1);
}

function strtoarray($delimiter, $string, $limit = PHP_INT_MAX): array
{
	return !is_empty($string) ? explode($delimiter, $string, $limit) : [];
}

function strtolink($string, $is_html = FALSE)
{
	if ($is_html)
	{
		return escape_html_tags($string, 'strtolink');
	}

	//regex by @diegoperini
	$string = preg_replace_callback('_(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?_iuS', function($match){
		return '<a href="'.$match[0].'">'.str_shortener($match[0], 50).'</a>';
	}, $string);

	return preg_replace_callback('_@((?:&quot;(.+?)&quot;)|([^@\s]+))_', function($match){
		static $users;

		if ($users === NULL)
		{
			foreach (NeoFrag()->db->select('id', 'username')->from('nf_user')->where('deleted', FALSE)->get() as $user)
			{
				$users[$user['id']] = $user['username'];
			}
		}

		$username = !empty($match[3]) ? $match[3] : $match[2];

		return ($user_id = array_search($username, $users)) !== FALSE ? NeoFrag()->user->link($user_id, $username, '@') : $match[0];
	}, $string);
}

function unique_id($list = []): string
{
	// CSPRNG obligatoire : ces IDs servent de secrets (ID de session, tokens de reset
	// mot de passe / validation email, jetons CSRF) — uniqid() était prédictible.
	do
	{
		$id = bin2hex(random_bytes(16));
	}
	while ($list && in_array($id, $list));

	return $id;
}

function is_valid_email($email): bool
{
	return filter_var($email, FILTER_VALIDATE_EMAIL) !== FALSE;
}

function is_valid_url($url): bool
{
	$url = (string)$url; // strict_types : éviter la TypeError si un non-string (ex. ID int) arrive ici
	return preg_match('/^(tel|geo):.+/', $url) || filter_var($url, FILTER_VALIDATE_URL) !== FALSE;
}

function utf8_htmlentities($string, $flags = ENT_COMPAT): string
{
	// (string) : appelé avec des objets stringables (Label, Lang…) ; strict_types ferait sinon
	// lever une TypeError à htmlentities().
	return htmlentities((string)$string, $flags, 'UTF-8');
}

function utf8_html_entity_decode($string, $flags = ENT_COMPAT): string
{
	return html_entity_decode((string)$string, $flags, 'UTF-8');
}

function utf8_string($string, $default = '')
{
	$encoding = mb_detect_encoding($string, 'auto', TRUE);

	if ($encoding != 'UTF-8')
	{
		$string = mb_convert_encoding($string, 'UTF-8', $encoding ?: $default ?: 'ASCII');
	}

	return $string;
}

function str_shortener($string, $max_length, $end = '&#8230;'): string
{
	if (strlen($string) <= $max_length)
	{
		return $string;
	}
	else
	{
		if (utf8_html_entity_decode($end) === $end)
		{
			$max_length -= strlen($end);
		}

		for ($i = $max_length; $i > 1; $i--)
		{
			if (in_array(substr($string, $i, 1), str_split(' .,;:!?-_"')) &&
				in_array(substr(url_title($string), $i - 1, 1), array_merge(range('a', 'z'), range('A', 'Z'), range(0, 9))))
			{
				return substr($string, 0, $i).$end;
			}
		}

		return substr($string, 0, $max_length).$end;
	}
}

// Rend du contenu riche stocké (HTML de l'éditeur TinyMCE) de façon sûre : auto-lien des URLs/@mentions
// puis sanitization serveur (allow-list, anti XSS stocké). Garde le nom historique `bbcode()` car
// appelé par de nombreuses vues ; la conversion BBCode→HTML legacy a été retirée (plus aucun contenu
// BBCode : fork vidé, l'éditeur produit du HTML).
function bbcode($string): string
{
	// custom_emojis() APRÈS sanitize_html : on injecte un <img> à src contrôlée (notre upload) pour des
	// noms allowlistés (DB) → pas re-filtré par HTMLPurifier, et no-op s'il n'y a aucun emoji custom.
	return custom_emojis(sanitize_html(nl2br(strtolink((string)$string, TRUE))));
}

/** Map des emojis custom : ':nom:' → balise <img>. Chargée une fois par requête (cache statique). */
function custom_emojis_map(): array
{
	static $map = NULL;

	if ($map === NULL)
	{
		$map = [];

		try
		{
			foreach (NeoFrag()->db->select('name', 'image_id')->from('nf_custom_emojis')->get(FALSE) as $e)
			{
				if (!empty($e['image_id']) && ($url = NeoFrag()->model2('file', (int)$e['image_id'])->path()))
				{
					$token       = ':'.$e['name'].':';
					$map[$token] = '<img class="nf-emoji" src="'.$url.'" alt="'.htmlspecialchars((string) ($token), ENT_QUOTES).'" title="'.htmlspecialchars((string) ($token), ENT_QUOTES).'" style="height:1.4em;width:auto;vertical-align:text-bottom;">';
				}
			}
		}
		catch (\Throwable $e)
		{
			$map = [];
		}
	}

	return $map;
}

/** Remplace les ':nom:' déclarés par leur <img>. $map injectable (tests) ; sinon chargée depuis la DB. */
function custom_emojis($html, ?array $map = NULL): string
{
	$map = $map ?? custom_emojis_map();

	return $map ? strtr((string)$html, $map) : (string)$html;
}

/**
 * Marque les mots cherchés dans un extrait, et cadre l'extrait sur la première occurrence.
 *
 * Deux défauts bien réels tenaient dans la dernière ligne, et ils rendaient une page d'ERREUR au
 * visiteur qui cherchait un mot courant — `/fr/search?q=le` répondait 404 sur une installation de
 * démonstration parfaitement saine :
 *
 *   - `strpos()` rend FALSE quand le mot ne figure pas dans CE champ-ci, ce qui arrive tout le
 *     temps puisque la requête SQL cherche dans PLUSIEURS colonnes. Un sujet intitulé « Salut tout
 *     le monde ! » répond à « le » par son TITRE, et le message affiché sous lui ne contient pas le
 *     mot. `substr($s, FALSE)` lève alors une TypeError en PHP 8 ; le dispatcher l'attrape, vide la
 *     sortie et rend une page d'erreur à la place des résultats ;
 *   - MySQL compare **sans les accents**, PHP non : `'éléphant' LIKE '%ele%'` est VRAI côté base et
 *     faux pour `/ele/i`. La ligne remontait donc dans les résultats sans qu'un seul mot n'y soit
 *     marqué — et on retombait sur le même FALSE. Sur un site francophone, c'est la règle plus que
 *     l'exception.
 *
 * Le second est corrigé à la source : le motif accepte les variantes accentuées de chaque lettre,
 * ce qui aligne le marquage sur ce que la base a réellement trouvé. Le premier reste légitime — le
 * mot peut n'être que dans le titre — et l'extrait commence alors au début du texte, ce qu'un
 * lecteur comprend, plutôt que de faire échouer la page entière.
 *
 * @param string[] $keywords mots cherchés, tels que saisis
 */
function highlight($string, $keywords, $max_length = 256): string
{
	// Équivalences de `utf8mb4_general_ci`, réduites aux lettres latines qu'un francophone tape.
	// La table est indexée par CHAQUE variante : chercher « éléphant » doit marquer « elephant »
	// aussi bien que l'inverse, exactement comme la base les confond.
	static $classes = NULL;

	if ($classes === NULL)
	{
		$classes = [];

		foreach (['aàáâãäå', 'cç', 'eèéêë', 'iìíîï', 'nñ', 'oòóôõö', 'uùúûü', 'yýÿ'] as $groupe)
		{
			foreach (preg_split('//u', $groupe, -1, PREG_SPLIT_NO_EMPTY) as $lettre)
			{
				$classes[$lettre] = $groupe;
			}
		}
	}

	$motifs = [];

	foreach ((array)$keywords as $mot)
	{
		if (($mot = (string)$mot) === '')
		{
			continue;
		}

		$motif = '';

		foreach (preg_split('//u', $mot, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $lettre)
		{
			$bas    = mb_strtolower($lettre, 'UTF-8');
			$motif .= isset($classes[$bas]) ? '['.$classes[$bas].']' : preg_quote($lettre, '/');
		}

		$motifs[] = $motif;
	}

	$texte = htmlspecialchars((string) (utf8_html_entity_decode(strip_tags(bbcode($string)))), ENT_COMPAT, 'UTF-8');

	// Sans ce garde, une liste de mots vide produisait le motif `//i` — une alternance vide, qui
	// marque CHAQUE position du texte.
	if ($motifs)
	{
		$texte = preg_replace('/'.implode('|', $motifs).'/iu', '<mark>\0</mark>', $texte) ?? $texte;
	}

	$texte = nl2br($texte);
	$debut = strpos($texte, '<mark>');

	return str_shortener($debut === FALSE ? $texte : substr($texte, $debut), $max_length);
}

function version_format($version): string
{
	$rc = '';

	if (preg_match('/(.*?) (RC\d+)?$/', $version, $match))
	{
		list(, $version, $rc) = $match;
	}

	return strtolower(trim(preg_replace('/[^\d.]/', '', $version), '.').$rc);
}

function print_number($number, $decimals = 0)
{
	return (string)(float)$number === (string)$number ? number_format($number, $decimals, ',', '&nbsp;') : $number;
}
