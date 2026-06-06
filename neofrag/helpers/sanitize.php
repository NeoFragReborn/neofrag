<?php
/**
 * https://neofr.ag
 *
 * Sanitization HTML côté serveur (anti XSS stocké), basée sur HTMLPurifier.
 *
 * Le contenu riche (éditeur TinyMCE, BBCode converti, signatures…) provient d'utilisateurs non
 * fiables et est rendu en HTML brut dans les vues. `sanitize_html()` applique une allow-list
 * stricte de balises/attributs/CSS alignée sur ce que produit l'éditeur, et n'autorise que les
 * schemes http/https/mailto (bloque javascript:, data:, vbscript:…). HTMLPurifier est idempotent :
 * on peut donc l'appliquer à l'entrée (stockage) ET à la sortie (rendu) sans double-échappement.
 */

function sanitize_html($html)
{
	static $purifier = NULL;

	$html = (string)$html;

	if ($html === '')
	{
		return '';
	}

	if ($purifier === NULL)
	{
		$config = HTMLPurifier_Config::createDefault();

		// N.B. allow-list restreinte aux éléments/attributs du doctype HTMLPurifier par défaut
		// (XHTML 1.0 Transitional). Les balises HTML5 (mark/figure/figcaption) et attributs
		// (loading, allowfullscreen) ne sont pas produits par la toolbar TinyMCE → écartés pour
		// éviter les warnings « not supported » (et la CI tourne avec failOnWarning).
		$config->set('HTML.Allowed',
			'p[style],br,div[class|style],span[class|style],'.
			'b,strong,i,em,u,s,del,ins,sub,sup,small,'.
			'h1[style],h2[style],h3[style],h4[style],h5[style],h6[style],hr,'.
			'ul,ol[start],li,blockquote,'.
			'a[href|title|target|rel],'.
			'img[src|alt|width|height],'.
			'pre,code[class],'.
			'table,thead,tbody,tfoot,tr,'.
			'th[scope|colspan|rowspan|style],td[colspan|rowspan|style],caption,'.
			'iframe[src|width|height|frameborder]'
		);

		// CSS inline restreint à un sous-ensemble sûr (HTMLPurifier valide chaque valeur :
		// pas de url(javascript:), expression(), etc.). Préserve couleurs/alignement de TinyMCE.
		$config->set('CSS.AllowedProperties', [
			'color', 'background-color', 'text-align',
			'font-weight', 'font-style', 'text-decoration',
			'font-size', 'font-family'
		]);

		// Schemes d'URL autorisés (href/src) : tout le reste est supprimé.
		$config->set('URI.AllowedSchemes', ['http' => TRUE, 'https' => TRUE, 'mailto' => TRUE]);

		// Liens : target=_blank + rel noopener/nofollow sur les liens externes.
		$config->set('HTML.TargetBlank', TRUE);
		$config->set('HTML.Nofollow', TRUE);

		// Embeds iframe restreints à une whitelist d'hôtes (vidéo/audio).
		$config->set('HTML.SafeIframe', TRUE);
		$config->set('URI.SafeIframeRegexp',
			'%^https://(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/|'.
			'(www\.)?dailymotion\.com/embed/|player\.twitch\.tv/|clips\.twitch\.tv/embed|'.
			'open\.spotify\.com/embed/|w\.soundcloud\.com/player/|'.
			'www\.google\.com/maps/embed|maps\.google\.com/maps)%'
		);

		// Cache des définitions (perf). Désactive proprement si le dossier n'est pas inscriptible.
		$cache_dir = __DIR__.'/../../cache/htmlpurifier';
		if (!is_dir($cache_dir))
		{
			@mkdir($cache_dir, 0775, TRUE);
		}
		if (is_dir($cache_dir) && is_writable($cache_dir))
		{
			$config->set('Cache.SerializerPath', $cache_dir);
		}
		else
		{
			$config->set('Cache.DefinitionImpl', NULL);
		}

		$purifier = new HTMLPurifier($config);
	}

	return $purifier->purify($html);
}
