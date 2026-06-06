<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

function url($url = '')
{
	return NeoFrag()->url($url);
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

function urltolink($url)
{
	return '<a href="'.$url.'">'.parse_url($url, PHP_URL_HOST).'</a>';
}

// URL absolue (scheme + host + chemin résolu par url()). Les crawlers et les endpoints de partage
// social exigent de l'absolu ; url() ne renvoie que du root-relative (/fr/...).
function absolute_url($path = '')
{
	$host = $_SERVER['HTTP_HOST'] ?? '';

	if ($host === '')
	{
		return url($path);
	}

	return (NeoFrag()->url->https ? 'https' : 'http').'://'.$host.url($path);
}

// Boutons de partage modernes : partage natif (Web Share API, géré en JS) + X / Facebook / WhatsApp +
// copier-le-lien. $url doit être ABSOLU (cf. absolute_url()). Progressive enhancement : les liens
// fonctionnent sans JS ; le JS (theme enhance.js) ajoute le partage natif et la copie.
function share_buttons($url, $title = '')
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
