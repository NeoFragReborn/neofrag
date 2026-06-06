<?php
/**
 * https://neofr.ag
 *
 * Helpers sécurité pour le module talks (Phase T3 + T9).
 * Réutilisable aussi dans le module MP (Phase 7).
 */

namespace NF\Modules\Talks;

class Security
{
	/**
	 * Magic bytes check : lit les premiers octets du fichier pour détecter
	 * son vrai type, peu importe l'extension ou le MIME header HTTP.
	 *
	 * Returns le MIME détecté ou FALSE si lecture échoue.
	 */
	public static function detect_real_mime($filepath)
	{
		if (!is_readable($filepath))
		{
			return FALSE;
		}

		if (function_exists('finfo_open'))
		{
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			if ($finfo)
			{
				$mime = finfo_file($finfo, $filepath);
				finfo_close($finfo);
				return $mime ?: FALSE;
			}
		}

		// Fallback magic bytes manuel
		$fh = @fopen($filepath, 'rb');
		if (!$fh) return FALSE;
		$head = fread($fh, 16);
		fclose($fh);

		// Quelques signatures connues
		if (substr($head, 0, 3) === "\xFF\xD8\xFF")           return 'image/jpeg';
		if (substr($head, 0, 8) === "\x89PNG\r\n\x1A\n")      return 'image/png';
		if (substr($head, 0, 6) === "GIF87a" || substr($head, 0, 6) === "GIF89a") return 'image/gif';
		if (substr($head, 0, 4) === "RIFF" && substr($head, 8, 4) === "WEBP") return 'image/webp';
		if (substr($head, 0, 4) === "%PDF")                   return 'application/pdf';
		if (substr($head, 0, 2) === "PK")                     return 'application/zip';

		return FALSE;
	}

	/**
	 * Check si un fichier uploadé est sécurisé pour acceptation.
	 * Vérifie : MIME whitelist + magic bytes match.
	 *
	 * Returns TRUE ou un message d'erreur (string).
	 */
	public static function validate_file($filepath, array $allowed_mimes, $max_size_bytes = 5242880)
	{
		if (!is_readable($filepath))
		{
			return 'Fichier illisible';
		}

		$size = filesize($filepath);
		if ($size === FALSE || $size <= 0)
		{
			return 'Fichier vide';
		}
		if ($size > $max_size_bytes)
		{
			return 'Fichier trop volumineux';
		}

		$real_mime = self::detect_real_mime($filepath);
		if (!$real_mime)
		{
			return 'Impossible de détecter le type de fichier';
		}

		if (!in_array($real_mime, $allowed_mimes, TRUE))
		{
			return 'Type de fichier non autorisé : '.$real_mime;
		}

		// Détection des polyglotes/exécutables potentiels
		$content = @file_get_contents($filepath, FALSE, NULL, 0, 4096);
		if ($content !== FALSE)
		{
			// Scripts inline dans une image (JPG avec PHP embedded etc.)
			$dangerous_patterns = [
				'/<\?php/i',                  // PHP open tag
				'/<\?=/',                     // PHP short echo
				'/<script\b/i',               // <script>
				'/<iframe\b/i',               // <iframe>
				'/javascript:/i',             // javascript: scheme
				'/on(error|click|load|mouseover)\s*=/i', // event handlers
			];
			foreach ($dangerous_patterns as $pattern)
			{
				if (preg_match($pattern, $content))
				{
					return 'Fichier suspect (contenu exécutable détecté)';
				}
			}
		}

		return TRUE;
	}

	/**
	 * Sanitize un message texte pour stockage.
	 * On garde le contenu brut, l'échappement HTML est fait au render.
	 * On strip les caractères de contrôle dangereux.
	 */
	public static function sanitize_message_text($text)
	{
		$text = (string)$text;
		// Strip null bytes et control chars (sauf \n \r \t)
		$text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
		return trim($text);
	}

	/**
	 * Vérifie si une URL est "safe" pour rendu en lien cliquable.
	 * Retourne FALSE pour les schemes dangereux.
	 */
	public static function is_safe_url($url)
	{
		$url = trim((string)$url);
		if ($url === '') return FALSE;

		$lower = strtolower($url);

		// Bloque les schemes dangereux
		$dangerous = ['javascript:', 'data:', 'vbscript:', 'file:', 'about:'];
		foreach ($dangerous as $scheme)
		{
			if (strpos($lower, $scheme) === 0)
			{
				return FALSE;
			}
		}

		// Whitelist : http(s), mailto, ou path relatif
		if (preg_match('#^(https?://|mailto:|/)#i', $url))
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Détecte si un domaine est un URL shortener (suspect potentiel).
	 */
	public static function is_url_shortener($url)
	{
		$shorteners = [
			'bit.ly', 'tinyurl.com', 't.co', 'goo.gl', 'ow.ly', 'is.gd',
			'buff.ly', 'lnkd.in', 'fb.me', 'tr.im', 'tiny.cc', 'rebrand.ly'
		];
		$host = strtolower((string)parse_url($url, PHP_URL_HOST));
		return in_array($host, $shorteners, TRUE);
	}

	/**
	 * Domaines whitelistés pour render GIF inline (Giphy/Tenor).
	 */
	public static function is_trusted_gif_host($url)
	{
		$trusted = ['giphy.com', 'media.giphy.com', 'i.giphy.com', 'tenor.com', 'media.tenor.com', 'media1.tenor.com', 'c.tenor.com'];
		$host = strtolower((string)parse_url($url, PHP_URL_HOST));
		return in_array($host, $trusted, TRUE);
	}

	/**
	 * Rendu permissif réservé à la chatbox staff (audience='staff').
	 * Autorise une whitelist de balises HTML basiques (b, i, u, em, strong, code, pre,
	 * a, br, p, blockquote, ul, ol, li, span, img). Strip les attributs dangereux,
	 * force target=_blank rel=noopener sur les <a>, vérifie src des <img> contre les
	 * schemes dangereux. À n'utiliser que pour les conversations entre admins.
	 */
	public static function render_staff_message($text)
	{
		$text = (string)$text;

		// Whitelist élargie pour le rendu des messages staff (chatbox WYSIWYG admin) :
		// headings, tables, code blocks, todo lists, mark/highlight, media embed
		// (iframe restreint aux trusted hosts).
		$allowed = '<b><strong><i><em><u><s><del><code><pre><br><p><div><blockquote>'
		         . '<ul><ol><li><span><a><img><h1><h2><h3><h4><h5><h6><hr>'
		         . '<table><thead><tbody><tfoot><tr><td><th><caption>'
		         . '<mark><sub><sup><figure><figcaption><iframe><input><label>';
		$text = strip_tags($text, $allowed);

		// Nettoie les attributs et bloque sources non sûres
		$text = preg_replace_callback('#<([a-zA-Z][a-zA-Z0-9]*)([^>]*)>#', function($m){
			$tag   = strtolower($m[1]);
			$attrs = $m[2];
			$safe_attrs = '';

			// Allowed attrs par tag (whitelist stricte)
			$generic_attrs = ['class', 'title', 'dir', 'lang'];
			$tag_attrs = [
				'a'        => ['href', 'rel', 'target', 'name'],
				'img'      => ['src', 'alt', 'width', 'height', 'loading'],
				'iframe'   => ['src', 'width', 'height', 'allowfullscreen', 'frameborder', 'allow', 'referrerpolicy'],
				'table'    => ['border', 'cellpadding', 'cellspacing'],
				'th'       => ['scope', 'colspan', 'rowspan'],
				'td'       => ['colspan', 'rowspan'],
				'input'    => ['type', 'checked', 'disabled'],
				'mark'     => [],
				'pre'      => ['data-language'],
				'code'     => ['class'],
				'ol'       => ['start', 'type', 'reversed'],
				'ul'       => ['type'],
				'figure'   => [],
			];
			$allowed_attrs_for_tag = array_merge($generic_attrs, $tag_attrs[$tag] ?? []);

			if (preg_match_all('#\s+([a-zA-Z\-]+)\s*=\s*("([^"]*)"|\'([^\']*)\')#', $attrs, $am, PREG_SET_ORDER))
			{
				foreach ($am as $a)
				{
					$name  = strtolower($a[1]);
					$value = $a[3] !== '' ? $a[3] : $a[4];

					// Strip toujours : handlers, style inline (peut contenir url(javascript:), formaction
					if (preg_match('#^on#', $name)) continue;
					if ($name === 'style') continue;
					if ($name === 'formaction') continue;

					if (!in_array($name, $allowed_attrs_for_tag, TRUE)) continue;

					// Validation URL pour href/src
					if (in_array($name, ['href', 'src'], TRUE))
					{
						if ($tag === 'iframe' && !self::is_trusted_embed_host($value)) continue;
						if ($tag !== 'iframe' && !self::is_safe_url($value)) continue;
					}

					$safe_attrs .= ' '.$name.'="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'"';
				}
			}

			// Force target=_blank rel=noopener sur les <a> http(s)
			if ($tag === 'a' && stripos($safe_attrs, 'href=') !== FALSE)
			{
				if (stripos($safe_attrs, 'target=') === FALSE) $safe_attrs .= ' target="_blank"';
				if (stripos($safe_attrs, 'rel=') === FALSE)    $safe_attrs .= ' rel="noopener nofollow"';
			}
			// Force lazy loading + style max-width sur les images
			if ($tag === 'img' && stripos($safe_attrs, 'loading=') === FALSE)
			{
				$safe_attrs .= ' loading="lazy"';
			}

			return '<'.$tag.$safe_attrs.'>';
		}, $text);

		// Strip <input> qui ne sont pas des checkboxes (todo list = <input type="checkbox" disabled>)
		$text = preg_replace('#<input(?![^>]*type="checkbox")[^>]*>#i', '', $text);

		return $text;
	}

	/**
	 * Whitelist stricte des hosts autorisés pour les embeds iframe (TinyMCE media plugin).
	 * YouTube, Vimeo, Dailymotion, Twitch, Spotify, SoundCloud — pas plus.
	 */
	public static function is_trusted_embed_host($url)
	{
		$url = trim((string)$url);
		if ($url === '' || stripos($url, 'http') !== 0) return FALSE;

		$host = strtolower((string)parse_url($url, PHP_URL_HOST));
		$trusted = [
			'www.youtube.com', 'youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com',
			'player.vimeo.com', 'vimeo.com', 'www.vimeo.com',
			'www.dailymotion.com', 'dailymotion.com',
			'player.twitch.tv', 'clips.twitch.tv',
			'open.spotify.com',
			'w.soundcloud.com'
		];
		return in_array($host, $trusted, TRUE);
	}

	/**
	 * Rendu sécurisé d'un message : échappe HTML, auto-link URLs sûres,
	 * GIFs whitelistés rendus en <img>, alerte sur shorteners.
	 */
	public static function render_message($text)
	{
		$text = (string)$text;

		// Step 1: escape HTML strict
		$escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

		// Step 2: auto-link URLs avec checks
		$escaped = preg_replace_callback(
			'#(https?://[^\s<>"\']+)#u',
			function($match) {
				$url = $match[1];

				if (!self::is_safe_url($url))
				{
					return $url; // laisse en plain text
				}

				// GIF whitelisté → render <img>
				if (self::is_trusted_gif_host($url) && preg_match('/\.(gif|webp|mp4)(\?|$)/i', $url))
				{
					return '<img src="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'" alt="GIF" style="max-width:200px;max-height:150px;border-radius:6px;display:inline-block;" loading="lazy" />';
				}

				$attrs = 'href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'" target="_blank" rel="noopener nofollow"';

				if (self::is_url_shortener($url))
				{
					return '<a '.$attrs.' title="Lien raccourci — prudence" style="border-bottom: 1px dotted #d57700;"><i class="fas fa-exclamation-triangle text-warning"></i> '.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'</a>';
				}

				return '<a '.$attrs.'>'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'</a>';
			},
			$escaped
		);

		// Step 3: nl2br pour les sauts de ligne
		return nl2br($escaped, FALSE);
	}
}
