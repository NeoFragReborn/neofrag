<?php
declare(strict_types=1);
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
	 * Traduit un message montré à l'utilisateur, dans le domaine du module talks. Hors du CMS — les
	 * tests unitaires chargent cette classe seule, sans NeoFrag() —, rend le texte source mis en forme.
	 * Le nom `lang` n'est pas un hasard : check-langs et check-textes-en-dur reconnaissent ses appels.
	 */
	private static function lang(string $texte, ...$args): string
	{
		return function_exists('NeoFrag') ? (string) NeoFrag()->module('talks')->lang($texte, ...$args) : vsprintf($texte, $args);
	}

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
				// Pas de `finfo_close()` : déprécié depuis PHP 8.5, où l'objet est libéré seul.
				$mime = finfo_file($finfo, $filepath);
				return $mime ?: FALSE;
			}
		}

		// Fallback magic bytes manuel
		$fh = @fopen($filepath, 'rb');
		if (!$fh) return FALSE;
		// `fread()` rend FALSE en cas d'échec de lecture, et tout ce qui suit découpe cette valeur.
		// Sous `strict_types`, `substr(false, …)` lèverait une TypeError — sur un chemin qui ne
		// s'emprunte que si `finfo` est absent de l'installation, donc jamais chez nous, et
		// précisément pour cela jamais exercé.
		$head = (string) fread($fh, 16);
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
	 * Returns TRUE ou un message d'erreur (string), traduit : il est montré à l'expéditeur.
	 */
	public static function validate_file($filepath, array $allowed_mimes, $max_size_bytes = 5242880)
	{
		if (!is_readable($filepath))
		{
			return self::lang('Fichier illisible');
		}

		$size = filesize($filepath);
		if ($size === FALSE || $size <= 0)
		{
			return self::lang('Fichier vide');
		}
		if ($size > $max_size_bytes)
		{
			return self::lang('Fichier trop volumineux');
		}

		$real_mime = self::detect_real_mime($filepath);
		if (!$real_mime)
		{
			return self::lang('Impossible de détecter le type de fichier');
		}

		if (!in_array($real_mime, $allowed_mimes, TRUE))
		{
			return self::lang('Type de fichier non autorisé : %s', $real_mime);
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
					return self::lang('Fichier suspect (contenu exécutable détecté)');
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

					$safe_attrs .= ' '.$name.'="'.htmlspecialchars((string) ($value), ENT_QUOTES, 'UTF-8').'"';
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
	 * Un texte de l'éditeur riche (HTML) mis en texte de messagerie : un message de la messagerie est du
	 * texte, que render_message() échappe, relie et met à la ligne. Le message de bienvenue, écrit dans
	 * l'éditeur de *Paramètres → Inscription*, y arrivait en HTML : ses balises s'affichaient telles
	 * quelles (2026-10-04). Les titres et paragraphes deviennent des blocs séparés d'une ligne vide, les
	 * listes des puces « • » ou des numéros, un lien son texte suivi de son adresse ; les entités sont
	 * décodées. Rien d'autre ne passe : le résultat est du texte, render_message() l'échappera.
	 */
	public static function texte_depuis_html(string $html): string
	{
		if (trim($html) === '')
		{
			return '';
		}

		$document = new \DOMDocument();
		$ancien   = libxml_use_internal_errors(TRUE);
		$document->loadHTML('<?xml encoding="utf-8"?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();
		libxml_use_internal_errors($ancien);

		$blocs = ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'blockquote', 'pre', 'table', 'tr', 'hr'];

		$parcourir = function (\DOMNode $noeud) use (&$parcourir, $blocs): string {
			$texte = '';

			foreach ($noeud->childNodes as $enfant)
			{
				if ($enfant instanceof \DOMText)
				{
					$texte .= preg_replace('/\s+/u', ' ', $enfant->nodeValue ?? '');
					continue;
				}

				if (!$enfant instanceof \DOMElement)
				{
					continue;
				}

				$balise = strtolower($enfant->tagName);

				if (in_array($balise, ['script', 'style'], TRUE))
				{
					continue;
				}

				if ($balise === 'br')
				{
					$texte .= "\n";
				}
				else if ($balise === 'li')
				{
					$liste  = $enfant->parentNode instanceof \DOMElement ? strtolower($enfant->parentNode->tagName) : 'ul';
					$rang   = 1;

					for ($frere = $enfant->previousSibling; $frere; $frere = $frere->previousSibling)
					{
						$rang += $frere instanceof \DOMElement && strtolower($frere->tagName) === 'li' ? 1 : 0;
					}

					$texte .= "\n".($liste === 'ol' ? $rang.'. ' : '• ').trim($parcourir($enfant));
				}
				else if ($balise === 'a')
				{
					$libelle = trim($parcourir($enfant));
					$adresse = trim($enfant->getAttribute('href'));
					$texte  .= $adresse !== '' && $adresse !== $libelle && preg_match('#^(https?://|mailto:)#i', $adresse) ? ($libelle !== '' ? $libelle.' ('.$adresse.')' : $adresse) : $libelle;
				}
				else if (in_array($balise, $blocs, TRUE))
				{
					$texte .= "\n\n".trim($parcourir($enfant))."\n\n";
				}
				else
				{
					$texte .= $parcourir($enfant);
				}
			}

			return $texte;
		};

		$texte = html_entity_decode($parcourir($document->documentElement ?? $document), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$texte = preg_replace('/[ \t]+\n/u', "\n", $texte);
		$texte = preg_replace('/\n[ \t]+/u', "\n", $texte);
		$texte = preg_replace('/\n{3,}/u', "\n\n", $texte);

		return trim((string) $texte);
	}

	/**
	 * Rendu sécurisé d'un message : échappe HTML, auto-link URLs sûres,
	 * GIFs whitelistés rendus en <img>, alerte sur shorteners.
	 */
	public static function render_message($text)
	{
		$text = (string)$text;

		// Step 1: escape HTML strict
		$escaped = htmlspecialchars((string) ($text), ENT_QUOTES, 'UTF-8');

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
					return '<img src="'.htmlspecialchars((string) ($url), ENT_QUOTES, 'UTF-8').'" alt="GIF" style="max-width:200px;max-height:150px;border-radius:6px;display:inline-block;" loading="lazy" />';
				}

				$attrs = 'href="'.htmlspecialchars((string) ($url), ENT_QUOTES, 'UTF-8').'" target="_blank" rel="noopener nofollow"';

				if (self::is_url_shortener($url))
				{
					return '<a '.$attrs.' title="'.htmlspecialchars(self::lang('Lien raccourci — prudence'), ENT_QUOTES, 'UTF-8').'" style="border-bottom: 1px dotted #d57700;"><i class="fas fa-exclamation-triangle text-warning"></i> '.htmlspecialchars((string) ($url), ENT_QUOTES, 'UTF-8').'</a>';
				}

				return '<a '.$attrs.'>'.htmlspecialchars((string) ($url), ENT_QUOTES, 'UTF-8').'</a>';
			},
			$escaped
		);

		// Step 3: nl2br pour les sauts de ligne
		return nl2br($escaped, FALSE);
	}
}
