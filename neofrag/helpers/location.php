<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

function url($url = ''): string
{
	return NeoFrag()->url($url);
}

/**
 * Une adresse sans danger dans un `href` : vide, relative, web (http, https), courriel (mailto),
 * téléphone (tel) ou position (geo).
 * Un schéma comme « javascript: » ou « data: » exécute du code au clic : saisi dans un diaporama ou
 * l'annuaire de liens, il frappait chaque visiteur — et sur la démonstration, servie sous le même
 * domaine que le site officiel, avec les droits de celui-ci (audit du 2026-10-02). Les blancs et
 * caractères de contrôle qu'un navigateur ignore dans un schéma (« java\tscript: ») sont retirés
 * avant de juger.
 */
function nf_url_sure(string $url): bool
{
	$nette = (string) preg_replace('/[\x00-\x20\x7f]+/', '', $url);

	if (preg_match('#^([a-z][a-z0-9+.\-]*):#i', $nette, $schema))
	{
		return in_array(strtolower($schema[1]), ['http', 'https', 'mailto', 'tel', 'geo'], TRUE);
	}

	return TRUE;
}

function redirect($location = '')
{
	return NeoFrag()->url->redirect(url($location));
}

function redirect_back($default = '')
{
	return redirect(NeoFrag()->url->back() ?: $default);
}

function refresh()
{
	return NeoFrag()->url->refresh();
}

/**
 * Une adresse en lien, son hôte pour texte. Elle vient souvent du navigateur (la page d'où arrive une session, montrée
 * dans l'administration) : jamais un schéma `javascript:`, et toujours échappée — elle était recopiée telle quelle
 * (audit du 2026-10-09). Une adresse qui n'est pas sûre reste du texte.
 */
function urltolink($url): string
{
	$url  = html_entity_decode((string) $url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$hote = (string) parse_url($url, PHP_URL_HOST);

	return $hote !== '' && nf_url_sure($url)
		? '<a href="'.nf_texte($url).'" rel="noopener noreferrer nofollow">'.nf_texte($hote).'</a>'
		: nf_texte($url);
}

/**
 * Un envoi — tout sauf GET, HEAD et OPTIONS — parti d'un AUTRE site, d'après ce que dit le navigateur (audit de sécurité
 * du 2026-10-09). Les formulaires portent un jeton, mais pas chaque point d'envoi en AJAX, et le cookie de session part
 * avec la requête : une page piégée faisait agir le membre connecté à son insu.
 *
 * Le navigateur le dit par `Sec-Fetch-Site` (tous les navigateurs récents), à défaut par `Origin`. Un sous-domaine voisin
 * compte comme un autre site, sauf le même nom avec ou sans « www. ». Un client qui n'envoie ni l'un ni l'autre — le bot,
 * un service de paiement, un outil en ligne de commande — n'est pas un navigateur : il n'est pas concerné.
 *
 * @param array<string, mixed>|null $serveur          `$_SERVER` par défaut
 * @param string                    $origine_du_site  l'origine canonique (site_origin()), acceptée aussi telle quelle
 */
function nf_envoi_d_un_autre_site(?array $serveur = NULL, string $origine_du_site = ''): bool
{
	$serveur ??= $_SERVER;

	if (in_array(strtoupper((string) ($serveur['REQUEST_METHOD'] ?? 'GET')), ['GET', 'HEAD', 'OPTIONS'], TRUE))
	{
		return FALSE;
	}

	$sans_www = static fn (string $hote): string => (string) preg_replace('#^www\.#', '', strtolower($hote));
	$origine  = is_string($serveur['HTTP_ORIGIN'] ?? NULL) ? (string) $serveur['HTTP_ORIGIN'] : '';

	// L'origine d'un envoi comparée au site : son hôte (et son port), au « www. » près.
	$meme_site = static function () use ($origine, $origine_du_site, $serveur, $sans_www): bool {
		if ($origine === '' || $origine === 'null')
		{
			return FALSE;
		}

		if ($origine_du_site !== '' && strcasecmp(rtrim($origine, '/'), $origine_du_site) === 0)
		{
			return TRUE;
		}

		$partie = parse_url($origine);
		$hote   = strtolower((string) ($partie['host'] ?? '')).(isset($partie['port']) ? ':'.$partie['port'] : '');

		return $hote !== '' && $sans_www($hote) === $sans_www((string) ($serveur['HTTP_HOST'] ?? ''));
	};

	switch (strtolower((string) ($serveur['HTTP_SEC_FETCH_SITE'] ?? '')))
	{
		case 'same-origin':
		case 'none':
			return FALSE;

		case 'cross-site':
			return TRUE;

		case 'same-site':
			return !$meme_site();
	}

	return $origine !== '' && !$meme_site();
}

/**
 * Mène le visiteur vers l'adresse qu'un administrateur a enregistrée — une publicité, un lien de l'annuaire, un
 * téléchargement, un partenaire, un forum-lien. Une adresse au schéma douteux (`javascript:`…) n'est jamais suivie.
 *
 * Sur une DÉMONSTRATION, où tout visiteur est administrateur, une adresse extérieure s'affiche sur une page qui dit où
 * elle mène, au lieu d'y aller (audit de sécurité du 2026-10-09) : le domaine du projet redirigeait sinon vers le site
 * de n'importe qui — une aubaine pour tromper (hameçonnage).
 */
function nf_quitter_le_site(string $adresse, string $sinon = ''): never
{
	if ($adresse === '' || !nf_url_sure($adresse))
	{
		header('Location: '.url($sinon), TRUE, 302);
		exit;
	}

	$hote = strtolower((string) parse_url($adresse, PHP_URL_HOST));

	if (nf_demo() && $hote !== '' && $hote !== strtolower((string) parse_url(site_origin(), PHP_URL_HOST)))
	{
		$lien  = nf_texte($adresse);
		$titre = nf_texte(NeoFrag()->lang('Tu quittes la démonstration'));

		header('Content-Type: text/html; charset=UTF-8');
		header('X-Robots-Tag: noindex, nofollow');
		echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.$titre.'</title><style>body{font-family:-apple-system,sans-serif;max-width:600px;margin:80px auto;padding:24px;color:#333;}a{word-break:break-all;}</style></head><body>'
			.'<h1>'.$titre.'</h1>'
			.'<p>'.NeoFrag()->lang('Ce lien mène hors de la démonstration, vers une adresse que n’importe lequel de ses visiteurs a pu enregistrer :').'</p>'
			.'<p><a href="'.$lien.'" rel="noopener noreferrer nofollow">'.$lien.'</a></p>'
			.'<p><a href="'.url($sinon).'">'.NeoFrag()->lang('Revenir à la démonstration').'</a></p>'
			.'</body></html>';
		exit;
	}

	header('Location: '.$adresse, TRUE, 302);
	exit;
}

// Origine canonique du site (scheme://host, sans slash final). Figée dans config/url.php
// à l'installation : les URLs absolues sensibles (liens d'e-mails de reset/validation,
// callbacks OAuth, retours de paiement) ne doivent JAMAIS dériver du Host de la requête,
// forgeable (password-reset poisoning). Fallback sur la requête si non configurée (dev).
function site_origin(): string
{
	static $origin;

	if ($origin === NULL)
	{
		$url = [];

		if (check_file('config/url.php'))
		{
			include 'config/url.php';
		}

		$origin = rtrim((string)($url['site'] ?? ''), '/');

		if ($origin === '' && !empty($_SERVER['HTTP_HOST']))
		{
			$origin = (NeoFrag()->url->https ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'];
		}
	}

	return $origin;
}

// URL absolue (scheme + host + chemin résolu par url()). Les crawlers et les endpoints de partage
// social exigent de l'absolu ; url() ne renvoie que du root-relative (/fr/...).
function absolute_url($path = ''): string
{
	return ($origin = site_origin()) !== '' ? $origin.url($path) : url($path);
}

// Boutons de partage modernes : partage natif (Web Share API, géré en JS) + X / Facebook / WhatsApp +
// copier-le-lien. $url doit être ABSOLU (cf. absolute_url()). Progressive enhancement : les liens
// fonctionnent sans JS ; le JS (theme enhance.js) ajoute le partage natif et la copie.
function share_buttons($url, $title = ''): string
{
	$url   = (string)$url;
	$title = (string)$title;
	$u     = rawurlencode($url);
	$t     = rawurlencode($title);

	$attr = function($v){
		return nf_texte($v);
	};

	$link = function($href, $ico, $label) use ($attr){
		return '<a class="btn btn-light btn-sm" href="'.$attr($href).'" target="_blank" rel="noopener noreferrer" aria-label="'.$attr($label).'" title="'.$attr($label).'">'.icon($ico).'</a>';
	};

	return '<div class="nf-share btn-group btn-group-sm" role="group" data-share-url="'.$attr($url).'" data-share-title="'.$attr($title).'">'
		.'<button type="button" class="btn btn-light btn-sm nf-share-native" aria-label="Partager" title="Partager" hidden>'.icon('fas fa-share-nodes').'</button>'
		.$link('https://twitter.com/intent/tweet?url='.$u.'&text='.$t, 'fab fa-x-twitter', 'X')
		.$link('https://www.facebook.com/sharer/sharer.php?u='.$u, 'fab fa-facebook-f text-primary', 'Facebook')
		.$link('https://api.whatsapp.com/send?text='.$t.'%20'.$u, 'fab fa-whatsapp text-success', 'WhatsApp')
		.'<button type="button" class="btn btn-light btn-sm nf-share-copy" aria-label="Copier le lien" title="Copier le lien">'.icon('fas fa-link').'</button>'
		.'</div>';
}
