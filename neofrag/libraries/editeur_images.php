<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Les images COLLÉES (Ctrl+V) ou GLISSÉES dans l'éditeur riche (TinyMCE), envoyées au site.
 *
 * Le défaut (signalé le 2026-10-04, réponse du forum) : TinyMCE garde une image collée sous une adresse
 * `blob:` le temps de l'envoyer. Aucun envoi n'était configuré, et la politique de contenu refusait
 * `blob:` aux images : l'image s'affichait cassée dans l'éditeur, puis partait à l'enregistrement en
 * `data:` (TinyMCE convertit ce qu'il n'a pas envoyé) — que l'assainisseur retire. L'image était perdue.
 *
 * Désormais chaque éditeur confie l'image à `ajax/user/editeur-image` (le contrôleur ajax du module
 * user, toujours présent) et l'insère par l'adresse que le site lui rend. Cette classe porte tout ce
 * qui décide : qui peut envoyer, ce qu'est une image acceptable, son ré-encodage, son chemin, et les
 * réglages TinyMCE. Elle est PURE — sauf `tinymce()`, qui lit la session — : les décisions de sécurité
 * s'éprouvent sans le CMS (tests/Unit/EditeurImagesTest.php).
 *
 * Les garde-fous, dans l'ordre où ils jouent :
 *   1. un membre connecté, hors démonstration (la remise à zéro ne restaure pas le disque), qui
 *      présente le jeton CSRF de sa session ;
 *   2. un débit borné par membre (DEBITS) ;
 *   3. 5 Mo au plus, comme une pièce jointe du forum ;
 *   4. le VRAI type, lu dans les premiers octets — jamais l'extension ni l'en-tête du navigateur :
 *      JPEG, PNG, GIF ou WebP, rien d'autre (SVG exclu : il porte du script) ;
 *   5. aucun code dans le fichier (PHP, script) : un polyglotte image + PHP est refusé ;
 *   6. des dimensions bornées, lues AVANT le décodage — une « bombe » de quelques Ko qui se déclare
 *      immense saturerait la mémoire ;
 *   7. le RÉ-ENCODAGE par GD : seuls les pixels passent, ni métadonnées EXIF, ni commentaire, ni octets
 *      ajoutés. L'orientation EXIF d'une photo est appliquée avant, sans quoi elle s'afficherait couchée ;
 *   8. un nom aléatoire (128 bits) sous `upload/editeur/AAAA/MM/`, une extension choisie par le site
 *      (jpg, png ou webp). `upload/.htaccess` et les configurations nginx/Caddy livrées n'y exécutent rien.
 */

namespace NF\NeoFrag\Libraries;

final class Editeur_Images
{
	/** Les types acceptés, reconnus à leurs premiers octets, et l'extension du fichier ÉCRIT : un GIF devient un PNG — sauf
	 * un GIF animé qui se nettoie (gif_anime()), qui reste un GIF. */
	public const TYPES = [
		'image/jpeg' => 'jpg',
		'image/png'  => 'png',
		'image/gif'  => 'png',
		'image/webp' => 'webp',
	];

	/** 5 Mo : la taille par défaut d'une pièce jointe du forum. */
	public const TAILLE_MAX = 5242880;

	/**
	 * Le plus grand nombre de pixels décodés : GD réserve environ cinq octets par pixel. 12,6 Mpx
	 * (4096 × 3072) laissent passer la photo d'un téléphone (4032 × 3024) et une capture d'écran 4K, pour
	 * une soixantaine de Mo — ce que tient un hébergement mutualisé à 128 Mo.
	 */
	public const PIXELS_MAX = 12582912;

	/** Le plus grand côté accepté, quel que soit le nombre de pixels. */
	public const COTE_MAX_SOURCE = 8192;

	/** Le plus grand côté ENREGISTRÉ : au-delà, l'image est réduite, proportions gardées. */
	public const COTE_MAX = 2000;

	public const DOSSIER = 'upload/editeur';

	/** Les envois permis par membre : [nombre, fenêtre en secondes]. Au-delà, la clé reste fermée une fenêtre. */
	public const DEBITS = [
		'minutes' => [20, 600],
		'jour'    => [100, 86400],
	];

	/** Ce que TinyMCE accepte de prendre pour une image (par l'extension du fichier glissé). */
	public const EXTENSIONS_TINYMCE = 'jpeg,jpg,jpe,jfi,jif,jfif,png,gif,webp';

	/**
	 * Traduit un message, au nom du cœur (`neofrag/langs/`). Hors du CMS — les tests chargent cette classe
	 * seule —, rend le texte source mis en forme. Le nom `lang` n'est pas un hasard : check-langs et
	 * check-textes-en-dur reconnaissent ses appels.
	 */
	private static function lang(string $texte, ...$args): string
	{
		return function_exists('NeoFrag') ? (string) NeoFrag()->lang($texte, ...$args) : vsprintf($texte, $args);
	}

	/** Le message, traduit, d'un code de refus — montré au membre par l'éditeur. */
	public static function message(string $code): string
	{
		return match ($code)
		{
			'anonyme'    => self::lang('Connectez-vous pour ajouter une image.'),
			'demo'       => self::lang('Envoi de fichiers désactivé sur le site de démonstration.'),
			'jeton'      => self::lang('Votre session a expiré : rechargez la page, puis ajoutez l\'image de nouveau.'),
			'debit'      => self::lang('Trop d\'images envoyées en peu de temps : réessayez dans quelques minutes.'),
			'absent'     => self::lang('Aucune image n\'a été reçue.'),
			'taille'     => self::lang('Image trop lourde : %s au plus.', human_size(self::TAILLE_MAX, 0)),
			'type'       => self::lang('Seules les images JPEG, PNG, GIF et WebP sont acceptées.'),
			'code'       => self::lang('Ce fichier contient autre chose qu\'une image : il est refusé.'),
			'dimensions' => self::lang('Image trop grande : %d pixels de côté et %d mégapixels au plus.', self::COTE_MAX_SOURCE, (int) floor(self::PIXELS_MAX / 1000000)),
			'ecriture'   => self::lang('L\'image n\'a pas pu être enregistrée sur le site.'),
			default      => self::lang('Cette image n\'a pas pu être lue.'),
		};
	}

	/** Le statut HTTP d'un code de refus. */
	public static function statut(string $code): int
	{
		return match ($code)
		{
			'anonyme', 'demo', 'jeton' => 403,
			'debit'                    => 429,
			'taille'                   => 413,
			'type'                     => 415,
			'ecriture'                 => 500,
			default                    => 422,
		};
	}

	/**
	 * Le refus qui tombe AVANT de lire le fichier, ou NULL. L'ordre compte : un visiteur n'apprend rien
	 * de la démonstration ni du jeton.
	 */
	public static function refus_requete(bool $connecte, bool $demo, bool $jeton_valide): ?string
	{
		if (!$connecte)
		{
			return 'anonyme';
		}

		if ($demo)
		{
			return 'demo';
		}

		return $jeton_valide ? NULL : 'jeton';
	}

	/** Le vrai type d'un fichier, lu dans ses premiers octets ; NULL hors des quatre acceptés. */
	public static function type_reel(string $chemin): ?string
	{
		$flux = @fopen($chemin, 'rb');

		if ($flux === FALSE)
		{
			return NULL;
		}

		$tete = (string) fread($flux, 16);
		fclose($flux);

		return match (TRUE)
		{
			str_starts_with($tete, "\xFF\xD8\xFF")                                => 'image/jpeg',
			str_starts_with($tete, "\x89PNG\r\n\x1A\n")                           => 'image/png',
			str_starts_with($tete, 'GIF87a'), str_starts_with($tete, 'GIF89a')     => 'image/gif',
			str_starts_with($tete, 'RIFF') && substr($tete, 8, 4) === 'WEBP'      => 'image/webp',
			default                                                               => NULL,
		};
	}

	/**
	 * Le fichier porte-t-il du code ? Une image n'en contient jamais : un fichier qui a des octets d'image
	 * ET un `<?php` est un polyglotte, refusé — même si le ré-encodage l'aurait neutralisé.
	 *
	 * Les motifs longs se cherchent partout : ils ne surgissent pas par hasard dans des données
	 * compressées. Les courts (`<?=`, `onerror=`) surgiraient dans une grande image une fois sur quelques
	 * milliers : on ne les cherche que là où se cache une charge — l'en-tête et les métadonnées (les
	 * premiers Ko), et la queue du fichier.
	 */
	public static function contient_du_code(string $chemin): bool
	{
		$contenu = (string) @file_get_contents($chemin, FALSE, NULL, 0, self::TAILLE_MAX + 1);

		if (preg_match('/<\?php|<script\b|<iframe\b|javascript:/i', $contenu))
		{
			return TRUE;
		}

		$bords = substr($contenu, 0, 4096).substr($contenu, -4096);

		return (bool) preg_match('/<\?=|\bon(?:error|load|click|mouseover)\s*=/i', $bords);
	}

	/**
	 * Le contrôle d'un fichier reçu (une entrée de `$_FILES` : son chemin temporaire et son code d'erreur).
	 * Rend le type et les dimensions, ou le code du refus. Le contrôleur vérifie à part que le fichier
	 * vient bien d'un envoi HTTP (`is_uploaded_file()`).
	 *
	 * @return array{type: string, largeur: int, hauteur: int}|string
	 */
	public static function controler(string $chemin, int $erreur = UPLOAD_ERR_OK): array|string
	{
		if (in_array($erreur, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], TRUE))
		{
			return 'taille';
		}

		if ($erreur === UPLOAD_ERR_NO_FILE || $chemin === '' || !is_file($chemin))
		{
			return 'absent';
		}

		if ($erreur !== UPLOAD_ERR_OK || !is_readable($chemin))
		{
			return 'illisible';
		}

		$taille = (int) filesize($chemin);

		if ($taille <= 0)
		{
			return 'absent';
		}

		if ($taille > self::TAILLE_MAX)
		{
			return 'taille';
		}

		if (($type = self::type_reel($chemin)) === NULL)
		{
			return 'type';
		}

		if (self::contient_du_code($chemin))
		{
			return 'code';
		}

		// getimagesize() lit l'en-tête sans décoder : les dimensions se jugent avant d'allouer la mémoire.
		$infos = @getimagesize($chemin);

		if (!is_array($infos) || ($infos['mime'] ?? '') !== $type || $infos[0] < 1 || $infos[1] < 1)
		{
			return 'illisible';
		}

		[$largeur, $hauteur] = $infos;

		if ($largeur > self::COTE_MAX_SOURCE || $hauteur > self::COTE_MAX_SOURCE || $largeur * $hauteur > self::PIXELS_MAX)
		{
			return 'dimensions';
		}

		return ['type' => $type, 'largeur' => $largeur, 'hauteur' => $hauteur];
	}

	/**
	 * L'orientation EXIF d'un JPEG (1 à 8), lue sans l'extension `exif` — absente de bien des hébergements.
	 * 1 (rien à faire) quand elle manque ou que le fichier ne se lit pas comme attendu.
	 */
	public static function orientation_jpeg(string $chemin): int
	{
		$flux = @fopen($chemin, 'rb');

		if ($flux === FALSE)
		{
			return 1;
		}

		try
		{
			if (fread($flux, 2) !== "\xFF\xD8")
			{
				return 1;
			}

			// Les segments d'en-tête, jusqu'au début des données (SOS) : APP1 « Exif » porte l'orientation.
			for ($segments = 0; $segments < 64 && !feof($flux); $segments++)
			{
				$marqueur = (string) fread($flux, 2);

				if (strlen($marqueur) < 2 || $marqueur[0] !== "\xFF" || in_array(ord($marqueur[1]), [0xDA, 0xD9], TRUE))
				{
					return 1;
				}

				$longueur = self::entier('n', (string) fread($flux, 2));

				if ($longueur === NULL || $longueur < 2)
				{
					return 1;
				}

				$donnees = (string) fread($flux, $longueur - 2);

				if (ord($marqueur[1]) === 0xE1 && str_starts_with($donnees, "Exif\0\0"))
				{
					return self::orientation_tiff(substr($donnees, 6));
				}
			}

			return 1;
		}
		finally
		{
			fclose($flux);
		}
	}

	/** L'orientation (étiquette 0x0112) du premier répertoire d'un bloc TIFF ; 1 à défaut. */
	private static function orientation_tiff(string $tiff): int
	{
		$ordre = substr($tiff, 0, 2);

		if ($ordre !== 'II' && $ordre !== 'MM')
		{
			return 1;
		}

		[$court, $long] = $ordre === 'II' ? ['v', 'V'] : ['n', 'N'];

		$repertoire = self::entier($long, substr($tiff, 4, 4));
		$nombre     = $repertoire === NULL ? NULL : self::entier($court, substr($tiff, $repertoire, 2));

		for ($i = 0; $nombre !== NULL && $i < min($nombre, 256); $i++)
		{
			$entree = (int) $repertoire + 2 + $i * 12;

			if (self::entier($court, substr($tiff, $entree, 2)) === 0x0112)
			{
				$valeur = self::entier($court, substr($tiff, $entree + 8, 2));

				return $valeur !== NULL && $valeur >= 1 && $valeur <= 8 ? $valeur : 1;
			}
		}

		return 1;
	}

	/** Un entier lu dans des octets (`unpack`), ou NULL s'il en manque. */
	private static function entier(string $format, string $octets): ?int
	{
		$attendu = in_array($format, ['n', 'v'], TRUE) ? 2 : 4;

		if (strlen($octets) < $attendu || ($valeur = unpack($format, $octets)) === FALSE)
		{
			return NULL;
		}

		return (int) $valeur[1];
	}

	/**
	 * Décode l'image et l'ÉCRIT À NEUF dans $destination : seuls les pixels passent. L'orientation EXIF
	 * d'un JPEG est appliquée, l'image est réduite à $cote_max (jamais agrandie), la transparence d'un PNG,
	 * d'un GIF ou d'un WebP est gardée. Un GIF animé que chemin() garde en GIF est nettoyé bloc par bloc
	 * (gif_anime()) ; sinon il n'en garde que sa première image — GD ne lit pas l'animation — et s'écrit en PNG. Rend FALSE si l'image ne se décode pas ou ne s'écrit pas ; rien
	 * n'est alors laissé à $destination.
	 */
	public static function reencoder(string $source, string $type, string $destination, int $cote_max = self::COTE_MAX): bool
	{
		if (!extension_loaded('gd') || !isset(self::TYPES[$type]) || !self::memoire_suffisante($source))
		{
			return FALSE;
		}

		// Un GIF animé que chemin() a gardé en GIF : nettoyé bloc par bloc (gif_anime()), et sa première image doit se
		// décoder. Sans quoi rien n'est écrit, comme pour toute image illisible.
		if ($type === 'image/gif' && str_ends_with($destination, '.gif'))
		{
			$propre = self::gif_anime((string) file_get_contents($source), $cote_max);

			if ($propre === NULL || !(@imagecreatefromstring($propre) instanceof \GdImage))
			{
				return FALSE;
			}

			return file_put_contents($destination, $propre) === strlen($propre) || (@unlink($destination) && FALSE);
		}

		$image = match ($type)
		{
			'image/jpeg' => @imagecreatefromjpeg($source),
			'image/png'  => @imagecreatefrompng($source),
			'image/gif'  => @imagecreatefromgif($source),
			'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : FALSE,
			default      => FALSE,
		};

		if (!$image instanceof \GdImage)
		{
			return FALSE;
		}

		if ($type === 'image/jpeg')
		{
			$image = self::redresser($image, self::orientation_jpeg($source));
		}

		$largeur = imagesx($image);
		$hauteur = imagesy($image);
		$echelle = min(1, $cote_max / max($largeur, $hauteur));
		$cible_l = max(1, (int) round($largeur * $echelle));
		$cible_h = max(1, (int) round($hauteur * $echelle));

		// Toujours une toile neuve, même sans réduction : rien de l'image d'origine n'y est recopié que ses pixels.
		$toile = imagecreatetruecolor($cible_l, $cible_h);

		if ($type !== 'image/jpeg')
		{
			imagealphablending($toile, FALSE);
			imagesavealpha($toile, TRUE);
			imagefill($toile, 0, 0, (int) imagecolorallocatealpha($toile, 0, 0, 0, 127));
		}

		imagecopyresampled($toile, $image, 0, 0, 0, 0, $cible_l, $cible_h, $largeur, $hauteur);

		$ecrit = match (self::TYPES[$type])
		{
			'jpg'  => imagejpeg($toile, $destination, 85),
			'webp' => function_exists('imagewebp') && imagewebp($toile, $destination, 85),
			default => imagepng($toile, $destination, 6),
		};

		// Pas d'`imagedestroy()` : depuis PHP 8.0 les images sont des objets, libérés seuls (déprécié en 8.5).
		if (!$ecrit || !is_array(@getimagesize($destination)))
		{
			@unlink($destination);
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Applique une orientation EXIF : l'image s'affiche ensuite droite sans ses métadonnées.
	 * 2 miroir, 3 demi-tour, 4 miroir vertical, 6 quart de tour horaire, 8 antihoraire ; 5 et 7 sont un
	 * quart de tour suivi d'un miroir. `imagerotate()` tourne dans le sens antihoraire : -90 est horaire.
	 */
	private static function redresser(\GdImage $image, int $orientation): \GdImage
	{
		$angle = match ($orientation)
		{
			3       => 180,
			5, 6    => -90,
			7, 8    => 90,
			default => 0,
		};

		if ($angle !== 0 && ($tournee = imagerotate($image, $angle, 0)) instanceof \GdImage)
		{
			$image = $tournee;
		}

		if (in_array($orientation, [2, 5, 7], TRUE))
		{
			imageflip($image, IMG_FLIP_HORIZONTAL);
		}
		else if ($orientation === 4)
		{
			imageflip($image, IMG_FLIP_VERTICAL);
		}

		return $image;
	}

	/**
	 * Y a-t-il la mémoire de décoder cette image ? GD réserve environ cinq octets par pixel, pour la
	 * source et pour la toile. Une limite trop basse est relevée si l'hébergement le permet ; sinon
	 * l'envoi est refusé, plutôt que de finir en erreur fatale.
	 */
	private static function memoire_suffisante(string $source): bool
	{
		$infos = @getimagesize($source);

		if (!is_array($infos))
		{
			return FALSE;
		}

		$besoin = (int) ($infos[0] * $infos[1] * 5 + self::COTE_MAX * self::COTE_MAX * 5 + 16 * 1048576);
		$limite = self::octets((string) ini_get('memory_limit'));

		if ($limite < 0 || memory_get_usage() + $besoin <= $limite)
		{
			return TRUE;
		}

		return @ini_set('memory_limit', (string) (memory_get_usage() + $besoin)) !== FALSE;
	}

	/** Une taille de php.ini (« 128M ») en octets ; -1 pour « sans limite ». */
	private static function octets(string $valeur): int
	{
		$valeur = trim($valeur);

		if ($valeur === '' || $valeur === '-1')
		{
			return -1;
		}

		$nombre = (int) $valeur;

		return match (strtolower(substr($valeur, -1)))
		{
			'g'     => $nombre * 1073741824,
			'm'     => $nombre * 1048576,
			'k'     => $nombre * 1024,
			default => $nombre,
		};
	}

	/**
	 * Le chemin relatif du fichier à écrire : `upload/editeur/AAAA/MM/<32 caractères hexadécimaux>.<ext>`.
	 * Le nom est aléatoire ; `$empreinte` (le SHA-256 du fichier reçu) le rend fixe — la même image renvoyée
	 * par un programme (un message modifié sur Discord) retrouve alors le même fichier, sans copie de plus.
	 */
	public static function chemin(string $type, int $instant, string $empreinte = '', string $source = ''): string
	{
		$nom = preg_match('/^[0-9a-f]{64}$/', $empreinte) ? substr($empreinte, 0, 32) : bin2hex(random_bytes(16));

		// Un GIF animé qui se nettoie (gif_anime()) reste un GIF : reencoder() le reconnaît à l'extension.
		$extension = $type === 'image/gif' && $source !== '' && is_file($source) && self::gif_anime((string) file_get_contents($source)) !== NULL ? 'gif' : (self::TYPES[$type] ?? 'png');

		return self::DOSSIER.'/'.date('Y/m', $instant).'/'.$nom.'.'.$extension;
	}

	/**
	 * Les images de l'éditeur qu'un texte cite (`upload/editeur/…/<nom>.<ext>`, sans doublon), quelle que soit la
	 * forme de l'adresse : absolue, depuis la racine, relative (`../../upload/…`), dans du Markdown, échappée en JSON
	 * (`upload\/editeur\/…`) ou dans une adresse encodée (`upload%2Fediteur%2F…`).
	 *
	 * @return list<string>
	 */
	public static function chemins_cites(string $texte): array
	{
		$texte = (string) preg_replace('#\\\\+/#', '/', str_ireplace('%2F', '/', $texte));

		preg_match_all('#'.preg_quote(self::DOSSIER, '#').'/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+\.[A-Za-z0-9]{1,5}#', $texte, $cites);

		return array_values(array_unique($cites[0]));
	}

	/** Au-delà, un GIF animé n'en garde que sa première image : le navigateur décode toutes les autres. */
	public const IMAGES_GIF_MAX = 1000;

	/**
	 * Un GIF ANIMÉ, réécrit bloc par bloc sans être décodé — GD n'en lit que la première image, et l'animation se
	 * perdait (m08, 2026-10-10) : seuls passent l'en-tête, l'écran logique et sa palette, les contrôles d'image, les
	 * images (descripteur, palette, données) et la boucle (NETSCAPE2.0 / ANIMEXTS1.0). Commentaires, textes, autres
	 * extensions d'application et tout ce qui suit la fin du fichier sont jetés : rien n'y reste où cacher autre chose
	 * que des pixels compressés, que le navigateur reçoit en `image/gif` et ne peut exécuter.
	 *
	 * Rend les octets du GIF nettoyé, ou NULL : pas un GIF, une seule image (le chemin ordinaire, en PNG), une structure
	 * inattendue, un côté de plus de $cote_max (pas de réduction sans décoder), plus de IMAGES_GIF_MAX images.
	 */
	public static function gif_anime(string $octets, int $cote_max = self::COTE_MAX): ?string
	{
		$n = strlen($octets);

		if ($n < 13 || !in_array(substr($octets, 0, 6), ['GIF87a', 'GIF89a'], TRUE))
		{
			return NULL;
		}

		$largeur = unpack('v', substr($octets, 6, 2))[1];
		$hauteur = unpack('v', substr($octets, 8, 2))[1];
		$drapeaux = ord($octets[10]);

		if ($largeur < 1 || $hauteur < 1 || $largeur > $cote_max || $hauteur > $cote_max)
		{
			return NULL;
		}

		$i      = 13 + ($drapeaux & 0x80 ? 3 * (2 << ($drapeaux & 7)) : 0);
		$sortie = 'GIF89a'.substr($octets, 6, $i - 6);
		$images = 0;

		if ($i > $n)
		{
			return NULL;
		}

		// Les sous-blocs (taille, données) jusqu'au bloc vide : rend leur fin, ou NULL si le fichier s'arrête avant.
		$sous_blocs = static function (int $depart) use ($octets, $n): ?int
		{
			for ($j = $depart; $j < $n; $j += 1 + ord($octets[$j]))
			{
				if (ord($octets[$j]) === 0)
				{
					return $j + 1;
				}
			}

			return NULL;
		};

		while ($i < $n)
		{
			$bloc = ord($octets[$i]);

			if ($bloc === 0x3B)
			{
				return $images >= 2 ? $sortie."\x3B" : NULL;
			}

			if ($bloc === 0x2C)
			{
				if ($i + 10 > $n)
				{
					return NULL;
				}

				// Chaque image, comme l'écran logique, tient dans $cote_max : le navigateur la décode en entier.
				[1 => $l, 2 => $h] = unpack('v2', substr($octets, $i + 5, 4));
				$local = ord($octets[$i + 9]);
				$debut = $i + 10 + ($local & 0x80 ? 3 * (2 << ($local & 7)) : 0);

				if ($l > $cote_max || $h > $cote_max || $debut + 1 > $n || ($fin = $sous_blocs($debut + 1)) === NULL || ++$images > self::IMAGES_GIF_MAX)
				{
					return NULL;
				}

				$sortie .= substr($octets, $i, $fin - $i);
				$i = $fin;

				continue;
			}

			if ($bloc === 0x21 && $i + 2 < $n)
			{
				$etiquette = ord($octets[$i + 1]);

				if (($fin = $sous_blocs($i + 2)) === NULL)
				{
					return NULL;
				}

				// Gardés : le contrôle d'une image (délai, transparence) et la boucle de l'animation.
				$application = $etiquette === 0xFF ? substr($octets, $i + 3, 11) : '';

				if ($etiquette === 0xF9 || in_array($application, ['NETSCAPE2.0', 'ANIMEXTS1.0'], TRUE))
				{
					$sortie .= substr($octets, $i, $fin - $i);
				}

				$i = $fin;

				continue;
			}

			return NULL;
		}

		// Pas de fin de fichier : le GIF est tronqué.
		return NULL;
	}

	/** Le nom d'origine, gardé pour le registre des fichiers (`nf_file`) : lettres, chiffres, `._ -`, 100 caractères. */
	public static function nom(string $origine, string $type): string
	{
		$nom = trim((string) preg_replace('/[^\p{L}\p{N}._ -]+/u', '', basename(str_replace('\\', '/', $origine))), ' .');

		return $nom !== '' ? mb_substr($nom, 0, 100) : 'image.'.(self::TYPES[$type] ?? 'png');
	}

	/**
	 * Les réglages TinyMCE de l'envoi des images, à placer en TÊTE de l'objet passé à `tinymce.init({…})`.
	 * Rend du JavaScript : une suite de propriétés, terminée par une virgule.
	 *
	 * - `images_upload_handler` confie le fichier à `NFEditeur.televerser()` (js/editeur-images.js) ;
	 * - `automatic_uploads`, `paste_data_images` : une image collée ou glissée part aussitôt ;
	 * - `images_reuse_filename: false` : le site choisit le nom, jamais le navigateur ;
	 * - `convert_urls: false` : l'adresse rendue (`/upload/editeur/…`) est gardée telle quelle. Par
	 *   défaut, TinyMCE la récrivait RELATIVE à la page d'édition (`../../upload/…`), qui ne mène plus
	 *   nulle part affichée ailleurs — un lien vers le site lui-même subissait le même sort.
	 *
	 * @param array{url: string, jeton: string, refus: string, echec: string} $reglages
	 */
	public static function options_tinymce(array $reglages): string
	{
		$json = json_encode($reglages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$echec = json_encode(['message' => $reglages['echec'], 'remove' => TRUE], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

		return 'images_upload_handler: function (blobInfo, progress) { '.
					'return window.NFEditeur ? window.NFEditeur.televerser(blobInfo, progress, '.$json.') : Promise.reject('.$echec.'); '.
				'}, '.
				'automatic_uploads: true, '.
				'paste_data_images: true, '.
				'images_reuse_filename: false, '.
				'images_file_types: "'.self::EXTENSIONS_TINYMCE.'", '.
				'convert_urls: false, ';
	}

	/**
	 * Les mêmes réglages, pour la page en cours : un visiteur, la démonstration ou un membre privé d'envoi
	 * de fichiers par la modération reçoivent un refus immédiat (l'image est retirée de l'éditeur, le motif
	 * s'affiche), un membre l'adresse d'envoi et le jeton de sa session. Charge aussi js/editeur-images.js.
	 */
	public static function tinymce(): string
	{
		NeoFrag()->js('editeur-images');

		$reglages = ['url' => '', 'jeton' => '', 'refus' => '', 'echec' => self::lang('L\'image n\'a pas pu être envoyée. Vérifiez votre connexion, puis réessayez.')];

		if (($refus = self::refus_requete((bool) NeoFrag()->user(), nf_demo(), TRUE)) !== NULL)
		{
			$reglages['refus'] = self::message($refus);
		}
		// Une sanction qui retire l'envoi de fichiers : même refus immédiat, avec ce qu'elle interdit (l'adresse
		// d'envoi le refuserait de toute façon, cf. Controllers\Ajax::editeur_image()).
		else if (($bloque = NeoFrag()->moderation->is_blocked_for((int) NeoFrag()->user->id, 'editor.image_upload')) !== NULL)
		{
			$reglages['refus'] = $bloque['message'];
		}
		else
		{
			$reglages['url']   = url('ajax/user/editeur-image');
			$reglages['jeton'] = nf_jeton_csrf();
		}

		return self::options_tinymce($reglages).self::langue_tinymce((string) NeoFrag()->config->lang->info()->name);
	}

	/** La traduction de l'interface de TinyMCE livrée pour chaque langue du site (js/tinymce/langs, tinymce-i18n). */
	const LANGUES_TINYMCE = ['fr' => 'fr_FR', 'de' => 'de', 'es' => 'es', 'it' => 'it', 'pt' => 'pt_PT'];

	/**
	 * L'interface de l'éditeur dans la langue de la page : elle était en anglais partout, aucune traduction n'étant
	 * livrée (m08, 2026-10-10). TinyMCE lit le fichier dans `langs/` à côté de lui. L'anglais est la sienne.
	 * Rend une propriété de `tinymce.init({…})`, terminée par une virgule, ou rien.
	 */
	public static function langue_tinymce(string $langue): string
	{
		return isset(self::LANGUES_TINYMCE[$langue]) ? 'language: "'.self::LANGUES_TINYMCE[$langue].'", ' : '';
	}
}
