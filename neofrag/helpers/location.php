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

function urltolink($url): string
{
	return '<a href="'.$url.'">'.parse_url($url, PHP_URL_HOST).'</a>';
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
		return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
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
