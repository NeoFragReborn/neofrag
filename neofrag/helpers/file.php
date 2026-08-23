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
	return strtolower(pathinfo(parse_url($file, PHP_URL_PATH), PATHINFO_EXTENSION));
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
	if (function_exists('finfo_open') && ($finfo = finfo_open(FILEINFO_MIME_TYPE)))
	{
		$mime = finfo_file($finfo, $path);
		finfo_close($finfo);

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

	imagedestroy($src);
	imagedestroy($dst);

	return TRUE;
}
