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

/**
 * La pièce jointe d'un MEMBRE (forum, messagerie) qu'on refuse : ce que is_dangerous_upload() refuse partout, et ce
 * qu'un navigateur exécute ou interprète quand le site le sert lui-même — un `.js` servi depuis notre origine
 * contournerait la politique des scripts (`script-src 'self'`), une feuille XML/XSL peut porter du HTML actif (audit du
 * 2026-10-09). L'administration, qui propose ce qu'elle veut au téléchargement, n'est pas concernée.
 */
function nf_piece_jointe_refusee(string $filename): bool
{
	return is_dangerous_upload($filename)
		|| in_array(extension($filename), ['js', 'mjs', 'cjs', 'css', 'xml', 'xsl', 'xslt', 'swf', 'shtml', 'wasm', 'map'], TRUE);
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

/**
 * Les sauvegardes à retirer de `backups/` après en avoir pris une nouvelle : jamais l'une des `$garder`
 * plus récentes, et parmi les autres, celles qui ont plus de `$jours` jours — la règle du bouton « Purger »
 * du Monitoring, appliquée d'elle-même. Chaque mise à jour par le bouton prend une sauvegarde complète
 * (16 Mo pour un site neuf) et rien ne les retirait : la vitrine en portait 23, 358 Mo (2026-10-03).
 * Au-delà des `$plafond` plus récentes, l'âge ne compte plus : des mises à jour rapprochées en laissaient
 * passer autant qu'il y en avait eu en trente jours, et la vitrine en portait 40, 663 Mo (2026-10-07).
 * Seuls les noms que fabrique la sauvegarde sont considérés.
 *
 * @param array<string, int> $sauvegardes nom du fichier => date de modification
 * @return list<string>
 */
function nf_sauvegardes_a_retirer(array $sauvegardes, int $maintenant, int $garder = 5, int $jours = 30, int $plafond = 10): array
{
	$sauvegardes = array_filter($sauvegardes, static fn ($date, $nom): bool => (bool) preg_match('/^\d{14}(-[a-f0-9]{16})?\.zip$/', (string) $nom), ARRAY_FILTER_USE_BOTH);
	arsort($sauvegardes);

	$garder  = max(0, $garder);
	$plafond = max($garder, $plafond);
	$retirer = [];
	$rang    = 0;

	foreach ($sauvegardes as $nom => $date)
	{
		if ($rang >= $plafond || ($rang >= $garder && $date < $maintenant - $jours * 86400))
		{
			$retirer[] = (string) $nom;
		}

		$rang++;
	}

	return $retirer;
}

/**
 * Une taille de fichier lisible, dans la langue de la page (`$langue`, sinon celle du site) : « 40,04 Ko » en français —
 * l'octet —, « 40.04 KB » en anglais, la virgule décimale ailleurs. Elle s'écrivait en anglais sur toutes les pages
 * (vu le 2026-10-09 sur la page des pièces jointes du forum).
 */
function human_size($bytes, $decimals = 2, ?string $langue = NULL): string
{
	if ($langue === NULL)
	{
		$config = function_exists('NeoFrag') ? NeoFrag()->config : NULL;
		$langue = $config && isset($config->lang) && is_object($config->lang) ? (string) $config->lang->info()->name : 'en';
	}

	// (string) obligatoire : le fichier est en strict_types, strlen(int|float) lèverait une TypeError
	// (human_size est appelé avec des entiers/floats : file_upload_max_size(), tailles de fichiers…).
	$bytes  = (int) $bytes;
	$factor = min(5, (int) floor((strlen((string) $bytes) - 1) / 3));
	$unites = $langue === 'fr' ? ['o', 'Ko', 'Mo', 'Go', 'To', 'Po'] : ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

	return number_format($bytes / pow(1024, $factor), (int) $decimals, $langue === 'en' ? '.' : ',', '').' '.$unites[$factor];
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
 * ne bornait le fichier : sur notre site d'essai — le seul des trois où le réglage était actif —
 * `logs/neofrag.log` avait atteint **1,7 Go** le 2026-09-20, chaque passage de la batterie en
 * ajoutant une centaine de mégaoctets.
 *
 * Le danger n'est pas le fichier, c'est le disque : les installations d'un même serveur le
 * partagent, et un site d'essai qui le remplit arrête aussi la production. Le correctif appartient au produit et non au
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

/**
 * Les fichiers orphelins d'un dossier de pièces jointes (`upload/<dossier>/`, à plat ; ligne 0.36 du tableau de bord,
 * 2026-10-09) : ceux que `nf_file` garde sans qu'aucune pièce jointe (`<table>.file_id`) ne les vise — supprimer une
 * pièce jointe laissait son fichier jusqu'à la 1.2.28 —, et ceux du disque que `nf_file` ne connaît pas (un envoi
 * interrompu). Un fichier de moins d'une heure n'en est pas encore un : il peut attendre le message qui le joindra.
 *
 * @param string $table la table des pièces jointes du module, qui vise `nf_file` par sa colonne `file_id`
 * @return list<array{file_id: ?int, path: string, name: string, size: int, date: int}>
 */
function nf_fichiers_orphelins(string $dossier, string $table): array
{
	$avant     = time() - 3600;
	$orphelins = [];
	$connus    = [];
	$prefixe   = 'upload/'.$dossier.'/';

	foreach ((array) NeoFrag()->db->select('path')->from('nf_file')->where('path LIKE', $prefixe.'%')->get() as $chemin)
	{
		$connus[(string) $chemin] = TRUE;
	}

	foreach ((array) NeoFrag()->db	->select('f.id', 'f.name', 'f.path', 'UNIX_TIMESTAMP(f.date) AS date')
									->from('nf_file f')
									->where('f.path LIKE', $prefixe.'%')
									->where('f.id NOT IN (SELECT file_id FROM '.$table.')')
									->where('f.date <', date('Y-m-d H:i:s', $avant))
									->order_by('f.id')
									->get(FALSE) as $f)
	{
		$orphelins[] = ['file_id' => (int) $f['id'], 'path' => (string) $f['path'], 'name' => (string) $f['name'], 'size' => (int) @filesize(NEOFRAG_CMS.'/'.$f['path']), 'date' => (int) $f['date']];
	}

	foreach (glob(NEOFRAG_CMS.'/'.$prefixe.'*') ?: [] as $fichier)
	{
		$chemin = $prefixe.basename($fichier);

		if (is_file($fichier) && !isset($connus[$chemin]) && !in_array(basename($fichier), ['.htaccess', 'index.html'], TRUE) && (int) @filemtime($fichier) < $avant)
		{
			$orphelins[] = ['file_id' => NULL, 'path' => $chemin, 'name' => '', 'size' => (int) @filesize($fichier), 'date' => (int) @filemtime($fichier)];
		}
	}

	return $orphelins;
}

/**
 * Efface, parmi `$chemins`, les fichiers qui sont TOUJOURS orphelins — relus au moment d'effacer : une liste affichée il
 * y a dix minutes ne fait rien disparaître qui a été joint depuis. Rien sur une démonstration. Rend leur nombre.
 *
 * @param list<string> $chemins
 */
function nf_effacer_orphelins(string $dossier, string $table, array $chemins): int
{
	if (nf_demo())
	{
		return 0;
	}

	$voulus = array_flip(array_map('strval', $chemins));
	$effaces = 0;

	foreach (nf_fichiers_orphelins($dossier, $table) as $orphelin)
	{
		if (!isset($voulus[$orphelin['path']]))
		{
			continue;
		}

		if ($orphelin['file_id'] !== NULL)
		{
			NeoFrag()->model2('file', $orphelin['file_id'])->delete();
		}
		else
		{
			@unlink(NEOFRAG_CMS.'/'.$orphelin['path']);
		}

		$effaces++;
	}

	return $effaces;
}

/**
 * Sert une pièce jointe d'un membre (forum, messagerie), une fois le contrôle d'accès de son module passé (audit de
 * sécurité du 2026-10-09) : le serveur web servait le fichier tel quel à quiconque avait son adresse, celui d'une
 * conversation privée comme d'un forum réservé. `upload/forum/` et `upload/talks/` ne sont plus servis directement.
 *
 * Une image, un PDF s'affichent ; le reste se télécharge. Jamais exécuté : type borné à ceux qui s'affichent sans
 * risque, `nosniff`, et une politique de contenu qui n'autorise aucun script.
 */
function nf_servir_piece_jointe(string $chemin, string $nom, string $type): never
{
	$fichier = NEOFRAG_CMS.'/'.$chemin;

	if (!preg_match('#^upload/(forum|talks)/[^/]+$#', $chemin) || !is_file($fichier))
	{
		http_response_code(404);
		exit;
	}

	$affichable = in_array($type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'], TRUE);
	$nom        = trim(str_replace(['"', '\\', "\r", "\n"], '', $nom)) ?: basename($chemin);

	while (ob_get_level() > 0)
	{
		ob_end_clean();
	}

	header('Content-Type: '.($affichable ? $type : 'application/octet-stream'));
	header('Content-Length: '.(int) filesize($fichier));
	header('Content-Disposition: '.($affichable ? 'inline' : 'attachment').'; filename="'.preg_replace('/[^\x20-\x7e]/', '_', $nom).'"; filename*=UTF-8\'\''.rawurlencode($nom));
	header('X-Content-Type-Options: nosniff');
	header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
	header('Cache-Control: private, max-age=86400');

	readfile($fichier);
	exit;
}
