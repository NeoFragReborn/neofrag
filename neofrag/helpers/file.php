<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

function relative_path($file): string
{
	$file  = substr(str_replace('\\', '/', $file), strlen(NEOFRAG_CMS));

	return (substr($file, 0, 1) == '/' ? '.' : './').$file;
}

function extension($file): string
{
	// parse_url() rend FALSE sur une adresse qu'il ne sait pas lire (« /fr/a:80 », « /:80 », « ///x ») et
	// NULL quand il n'y a pas de chemin (« //x »). Or c'est l'adresse DEMANDÉE qui arrive ici, via
	// Url::__construct() : un robot en envoie de cette forme, et pathinfo() exigeant une chaîne, la
	// requête finissait en erreur 500 au lieu du 404 attendu. On retombe alors sur la chaîne brute,
	// débarrassée de sa requête et de son ancre.
	$path = parse_url((string) $file, PHP_URL_PATH);

	if (!is_string($path))
	{
		$path = strtok((string) $file, '?#') ?: '';
	}

	return strtolower(pathinfo($path, PATHINFO_EXTENSION));
}

function get_mime_by_extension($extension)
{
	$mimes = [
		'bmp'   => 'image/bmp',
		'css'   => 'text/css',
		'eot'   => 'application/vnd.ms-fontobject"',
		'gif'   => 'image/gif',
		'jpeg'  => 'image/jpeg',
		'jpg'   => 'image/jpeg',
		'js'    => 'application/x-javascript',
		'json'  => 'application/json',
		// Le manifeste d'application. Son type propre : servi en `application/json`, certains
		// navigateurs refusent de l'interpreter comme un manifeste.
		'webmanifest' => 'application/manifest+json',
		'html'  => 'text/html',
		'otf'   => 'application/x-font-opentype',
		'png'   => 'image/png',
		'svg'   => 'image/svg+xml',
		'swf'   => 'application/x-shockwave-flash',
		'ttf'   => 'application/x-font-ttf',
		'woff'  => 'application/x-font-woff',
		'woff2' => 'application/font-woff2',
		'zip'   => 'application/zip'
	];

	return $mimes[$extension];
}

function detect_mime_type(string $path): string
{
	if (!is_file($path))
	{
		return '';
	}

	// finfo lit les magic bytes du contenu réel — ne jamais se fier au type annoncé par le client.
	//
	// Pas de `finfo_close()` : depuis PHP 8.5, `finfo_open()` rend un OBJET, libéré tout seul quand
	// la variable sort du champ de visibilité, et l'appel explicite est déprécié. Il produisait les
	// quatre avertissements de dépréciation de la suite de tests. Le supprimer reste correct sur
	// PHP 8.2 à 8.4, que le projet prend aussi en charge : la ressource y est libérée de la même
	// façon en fin de fonction.
	if (function_exists('finfo_open') && ($finfo = finfo_open(FILEINFO_MIME_TYPE)))
	{
		$mime = finfo_file($finfo, $path);

		if (is_string($mime) && $mime !== '')
		{
			return $mime;
		}
	}

	if (function_exists('mime_content_type'))
	{
		return (string)mime_content_type($path);
	}

	return '';
}

function is_dangerous_upload(string $filename): bool
{
	// Extensions jamais légitimes en upload utilisateur (exécutables/scripts/XSS stocké).
	static $blocked = [
		'php', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'phtml', 'pht', 'phar', 'phps',
		'cgi', 'pl', 'py', 'sh', 'asp', 'aspx', 'jsp', 'jspx', 'htaccess', 'htm', 'html',
		'svg', 'xhtml', 'exe', 'com', 'bat'
	];

	return in_array(extension($filename), $blocked, TRUE);
}

function file_upload_max_size()
{
	static $max_size = -1;

	if ($max_size < 0)
	{
		$max_size = min(array_filter(array_map(function($a){
			$size = ini_get($a);

			$unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
			$size = preg_replace('/[^0-9\.]/', '', $size);

			return round($size * ($unit ? pow(1024, stripos('bkmgtpezy', $unit[0])) : 1));
		}, ['post_max_size', 'upload_max_filesize'])));
	}

	return $max_size;
}

function human_size($bytes, $decimals = 2): string
{
	// (string) obligatoire : le fichier est en strict_types, strlen(int|float) lèverait une TypeError
	// (human_size est appelé avec des entiers/floats : file_upload_max_size(), tailles de fichiers…).
	$bytes  = (int)$bytes;
	$size   = str_split('KMGTP');
	$factor = (int)floor((strlen((string)$bytes) - 1) / 3);
	return sprintf('%.'.$decimals.'f', $bytes / pow(1024, $factor)).' '.($factor ? $size[$factor - 1] : '').'B';
}

function image_resize($filename, $width, $height = NULL)
{
	// Hébergement sans extension GD : ne pas appeler imagecreatetruecolor()… (Fatal). On garde
	// l'image d'origine non redimensionnée (dégradé, mais pas de crash de l'upload).
	if (!extension_loaded('gd'))
	{
		return $filename;
	}

	$info = getimagesize($filename);
	$w    = $info[0];
	$h    = $info[1];
	$type = $info[2];
	$mime = $info['mime'];

	if ($height === NULL)
	{
		$height = ceil($h * $width / $w);
	}

	if ($w <= $width && $h <= $height)
	{
		return;
	}

	$resize = imagecreatetruecolor($width, $height);

	if ($mime == 'image/png')
	{
		$image = imagecreatefrompng($filename);
	}
	else if ($mime == 'image/jpeg')
	{
		$image = imagecreatefromjpeg($filename);
	}
	else if ($mime == 'image/gif')
	{
		$image = imagecreatefromgif($filename);
	}
	else
	{
		return;
	}

	if ($type == IMAGETYPE_GIF || $type == IMAGETYPE_PNG)
	{
		$current_transparent = imagecolortransparent($image);
		if ($current_transparent != -1)
		{
			$transparent_color = imagecolorsforindex($image, $current_transparent);
			$current_transparent = imagecolorallocate($resize, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
			imagefill($resize, 0, 0, $current_transparent);
			imagecolortransparent($resize, $current_transparent);
		}
		else if ($type == IMAGETYPE_PNG)
		{
			imagealphablending($resize, FALSE);
			imagefill($resize, 0, 0, imagecolorallocatealpha($resize, 0, 0, 0, 127));
			imagesavealpha($resize, TRUE);
		}
	}

	imagecopyresampled($resize, $image, 0, 0, 0, 0, $width, $height, $w, $h);

	if ($mime == 'image/png')
	{
		imagepng($resize, $filename);
	}
	else if ($mime == 'image/jpeg')
	{
		imagejpeg($resize, $filename, 100);
	}
	else if ($mime == 'image/gif')
	{
		imagegif($resize, $filename);
	}
}

/**
 * Détecte un GIF ANIMÉ (≥ 2 frames) par lecture binaire : compte les blocs « Graphic Control
 * Extension » (00 21 F9 04 …) suivis d'un séparateur d'image/extension. Sans dépendance ni GD.
 */
function is_animated_gif($filename): bool
{
	if (!is_file($filename) || ($data = @file_get_contents($filename)) === FALSE || strncmp($data, 'GIF', 3) !== 0)
	{
		return FALSE;
	}

	return preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $data) >= 2;
}

/**
 * Normalise une image uploadée : la DÉCODE puis la RÉ-ENCODE via GD. Le fichier écrit ne contient
 * plus que des pixels — tout le reste est perdu : métadonnées EXIF, code/script ajouté après les
 * données image, charge utile polyglotte (faux .jpg/.png qui cachent du code). C'est la défense de
 * fond contre les uploads malveillants déguisés en image (l'en-tête magic-bytes peut être valide
 * sans que le fichier soit « que » une image). Réduit aussi à la boîte max (jamais d'agrandissement),
 * ce qui borne le stockage. Renvoie TRUE si ré-encodée, FALSE sinon (pas de GD, ou image illisible —
 * on retombe alors sur les autres couches : magic-bytes, extension, en-têtes de service nosniff).
 */
function image_normalize($filename, $max_width, $max_height = NULL): bool
{
	if (!extension_loaded('gd') || !($info = @getimagesize($filename)))
	{
		return FALSE;
	}

	$w    = $info[0];
	$h    = $info[1];
	$mime = $info['mime'];

	if ($mime == 'image/png')
	{
		$src = @imagecreatefrompng($filename);
	}
	else if ($mime == 'image/jpeg')
	{
		$src = @imagecreatefromjpeg($filename);
	}
	else if ($mime == 'image/gif')
	{
		// GIF animé : GD ne lit que la 1re frame → il APLATIRAIT l'animation. Imagick (si présent)
		// redimensionne TOUTES les frames + ré-encode (= strip payload, comme GD). Sinon on préserve
		// l'original intact s'il tient déjà dans la boîte (pas de réduction nécessaire) — la sécurité
		// repose alors sur l'en-tête `nosniff` du service d'images, exactement comme le no-op « sans GD ».
		// Au-delà de la boîte (sans Imagick), on retombe sur l'aplatissement GD (anim perdue, stockage borné).
		if (is_animated_gif($filename))
		{
			$mh = $max_height ?: $max_width;

			if (extension_loaded('imagick'))
			{
				try
				{
					$im = (new \Imagick($filename))->coalesceImages();
					foreach ($im as $frame)
					{
						$frame->thumbnailImage($max_width, $mh, TRUE); // bestfit, jamais d'agrandissement
					}
					$out = $im->deconstructImages();
					$out->writeImages($filename, TRUE);
					$im->clear();
					$out->clear();
					return TRUE;
				}
				catch (\Throwable $e)
				{
					// Imagick a échoué → on retombe sur GD ci-dessous.
				}
			}

			if ($w <= $max_width && $h <= $mh)
			{
				return TRUE; // tient déjà dans la boîte → on garde l'animation intacte
			}
		}

		$src = @imagecreatefromgif($filename);
	}
	else
	{
		return FALSE;
	}

	if (!$src)
	{
		return FALSE;
	}

	$max_height = $max_height ?: $max_width;
	$scale      = min($max_width / $w, $max_height / $h, 1); // jamais agrandir
	$tw         = max(1, (int)round($w * $scale));
	$th         = max(1, (int)round($h * $scale));

	$dst = imagecreatetruecolor($tw, $th);

	if ($mime == 'image/png' || $mime == 'image/gif')
	{
		imagealphablending($dst, FALSE);
		imagesavealpha($dst, TRUE);
		imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
	}

	imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);

	if ($mime == 'image/png')
	{
		imagepng($dst, $filename);
	}
	else if ($mime == 'image/jpeg')
	{
		imagejpeg($dst, $filename, 90);
	}
	else
	{
		imagegif($dst, $filename);
	}

	// Pas d'`imagedestroy()` : depuis PHP 8.0 les images sont des OBJETS, libérés par le
	// ramasse-miettes, et l'appel n'a plus aucun effet — PHP 8.5 le déprécie donc.
	return TRUE;
}

/**
 * Garde un journal sous une taille fixée, en conservant UNE génération précédente.
 *
 * Pourquoi cette fonction existe
 * ------------------------------
 * `NEOFRAG_LOGS` consigne, pour CHAQUE page servie, toutes ses requêtes SQL et ses en-têtes. Rien
 * ne bornait le fichier : sur l'atelier — le seul des trois sites où le réglage est actif —
 * `logs/neofrag.log` avait atteint **1,7 Go** le 2026-09-20, chaque passage de la batterie en
 * ajoutant une centaine de mégaoctets.
 *
 * Le danger n'est pas le fichier, c'est le disque : les trois installations le partagent, et un
 * atelier qui le remplit arrête aussi la production. Le correctif appartient au produit et non au
 * serveur — n'importe qui activant `NEOFRAG_LOGS` sur son hébergement aura le même problème, et
 * n'a pas forcément `logrotate`.
 *
 * `.1` est écrasé à chaque bascule : on garde de quoi lire ce qui vient de se passer, pas un
 * historique. Un journal de débogage n'est pas une archive.
 *
 * @return bool vrai si la bascule a eu lieu
 */
function nf_log_rotate(string $fichier, int $max_octets): bool
{
	if ($max_octets < 1 || !is_file($fichier))
	{
		return FALSE;
	}

	clearstatcache(TRUE, $fichier);

	if ((int) @filesize($fichier) < $max_octets)
	{
		return FALSE;
	}

	// `rename` est atomique : une requête concurrente qui écrit encore dans l'ancien descripteur
	// n'écrit pas dans le vide — sa ligne finit dans `.1`.
	return @rename($fichier, $fichier.'.1');
}
