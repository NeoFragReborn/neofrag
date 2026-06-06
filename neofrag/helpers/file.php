<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

function relative_path($file)
{
	$file  = substr(str_replace('\\', '/', $file), strlen(NEOFRAG_CMS));

	return (substr($file, 0, 1) == '/' ? '.' : './').$file;
}

function extension($file)
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

function human_size($bytes, $decimals = 2)
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
