<!DOCTYPE html>
<html lang="<?php echo $this->config->lang->info()->name ?>">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, maximum-scale=1, initial-scale=1, user-scalable=0">
<meta content="IE=edge, chrome=1" http-equiv="X-UA-Compatible">
<script>window.__nfNonce=<?php echo json_encode($GLOBALS['nf_csp_nonce'] ?? '') ?>;</script>
<script>(function(){try{var k=<?php echo $this->url->admin ? "'nf-admin-theme'" : "'nf-dungeon-theme'" ?>;var t=localStorage.getItem(k);if(!t){t=(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light';}document.documentElement.setAttribute('data-theme',t);document.documentElement.setAttribute('data-bs-theme',t==='dark'?'dark':'light');}catch(e){}})();</script>
<?php /* Le fuseau horaire du navigateur, pour afficher les heures dans le sien (cf. nf_fuseau(), helpers/time.php). Une préférence d'affichage, rien d'autre. */ ?>
<script>(function(){try{var z=Intl.DateTimeFormat().resolvedOptions().timeZone;if(z&&document.cookie.indexOf('nf_fuseau='+encodeURIComponent(z))<0){document.cookie='nf_fuseau='+encodeURIComponent(z)+';path=/;max-age=31536000;samesite=lax'+(location.protocol==='https:'?';secure':'');}}catch(e){}})();</script>
<?php if ($this->config->nf_theme_color): ?>
<meta name="theme-color" content="<?php echo $this->config->nf_theme_color ?>">
<?php endif ?>
<?php /* La mesure d'audience, jamais dans l'administration : elle n'y mesurerait rien d'utile, et enverrait à Google les adresses des écrans d'administration. */ ?>
<?php if ($this->config->nf_analytics && empty($this->url->admin)) echo $this->view('theme/analytics') ?>
<?php if ($this->config->nf_humans_txt): ?>
<link rel="author" href="<?php echo url('humans.txt') ?>" type="text/plain">
<?php endif ?>
<link rel="shortcut icon" href="<?php echo $path = favicon_url() ?>" type="<?php echo get_mime_by_extension(extension($path)) ?>">
<link rel="apple-touch-icon" href="<?php echo image('apple-touch-icon.png') ?>">
<?php /* Manifeste d'application : rend le site installable. Servi par le produit, cf. Settings\Controllers\Ajax::manifest(). */ ?>
<link rel="manifest" href="<?php echo $this->url->base ?>manifest.webmanifest">
<?php if (!$this->config->nf_favicon): // favicon par défaut : variante claire quand le navigateur est en mode sombre ?>
<link rel="icon" href="<?php echo image('favicon-dark.png') ?>" media="(prefers-color-scheme: dark)" type="image/png">
<?php endif ?>
<?php echo $this->output->css() ?>
<?php
/**
 * Police choisie par l'administrateur.
 *
 * Posée APRÈS les feuilles du thème : c'est ce qui lui permet de surcharger `--nf-font`, que chaque
 * thème définit avec sa propre police. Les jetons propres aux thèmes (`--gr-font`, `--bc-font`…)
 * pointent tous dessus, si bien qu'une seule déclaration suffit pour les sept.
 *
 * `police_du_site()` ne rend que des valeurs de la liste blanche : ce qui suit part dans une adresse et dans
 * une feuille de style, ce n'est pas un endroit pour de la saisie libre. La police est servie par le site
 * lui-même (tools/polices-locales.php) : aucun visiteur n'est plus envoyé chez Google (2026-10-08). Ses
 * feuilles portent `font-display: swap` : le texte s'affiche avec la police de repli le temps qu'elle arrive.
 */
if ($nf_police = police_du_site()):
	$nf_police_feuille = police_du_site_feuille($nf_police).'.css';
	$nf_police_version = nf_version_asset($nf_police_feuille, 'css');
?>
<link rel="stylesheet" href="<?php echo path($nf_police_feuille, 'css').($nf_police_version !== '' ? '?v='.$nf_police_version : '') ?>">
<style>:root{--nf-font:<?php echo police_du_site_pile($nf_police) ?>;--nf-font-display:<?php echo police_du_site_pile($nf_police) ?>}</style>
<?php endif ?>
<?php
/*
 * Le référencement (2026-10-03) : ce que les moteurs lisent ici, ils le croient. Les calculs
 * vivent dans helpers/seo.php ; `tools/check-seo.php` relit le résultat sur les pages servies.
 *
 *   - toutes les adresses sont COMPLÈTES, et construites sur l'origine du site (config/url.php) —
 *     jamais sur l'en-tête `Host` de la requête, que n'importe qui peut forger ;
 *   - l'accueil est la racine de sa langue (`/fr`), jamais `/fr/index` : les deux répondent ;
 *   - `canonical` désigne la langue réellement SERVIE : un contenu rédigé dans une seule langue, servi
 *     dans les cinq autres avec un bandeau, rendrait sinon six pages identiques ; un contenu qui n'a pas
 *     de langue à lui (un sujet du forum, une page du wiki) la place dans la langue première du site
 *     (nf_seo_sans_langue()) ;
 *   - `hreflang` n'annonce que des adresses qui répondent : quand le module a dit dans quelles langues
 *     son contenu existe (`Model::langue_du_contenu()`), on s'y tient. `x-default` — l'adresse sans
 *     langue, que le site redirige vers celle du visiteur — n'accompagne que les pages présentes partout.
 */
$nf_origin       = site_origin();
$nf_chemin       = nf_chemin_public();
$nf_langues_page = (array) ($this->output->data->get('module', 'langues_du_contenu') ?: []);
$nf_langue_page  = (string) ($this->output->data->get('module', 'langue_servie') ?: $this->output->data->get('module', 'langue_canonique') ?: $this->config->lang->info()->name);
$nf_canonical    = nf_seo_adresse($nf_origin, $this->url->base, $nf_langue_page, $nf_chemin);
$nf_accueil      = $nf_chemin === '';
?>
<?php if (count($this->config->langs) > 1): ?>
<?php foreach ($this->config->langs as $lang): ?>
<?php if (!$nf_langues_page || in_array($lang->info()->name, $nf_langues_page, TRUE)): ?>
<link rel="alternate" hreflang="<?php echo $lang->info()->name ?>" href="<?php echo nf_texte(nf_seo_adresse($nf_origin, $this->url->base, $lang->info()->name, $nf_chemin)) ?>">
<?php endif ?>
<?php endforeach ?>
<?php if (!$nf_langues_page): ?>
<link rel="alternate" hreflang="x-default" href="<?php echo nf_texte(nf_seo_adresse($nf_origin, $this->url->base, '', $nf_chemin)) ?>">
<?php endif ?>
<?php endif ?>
<?php
/*
 * La description : celle de la page, sinon celle du site dans la langue de la page (cf. output.php),
 * réduite à une ligne de texte de 160 caractères au plus — au-delà, les moteurs la coupent.
 *
 * L'image de partage : celle de la page (la couverture d'un billet), sinon celle du site — réglages,
 * thème, logo, favicon, cf. nf_seo_image_partage(). Seule une image de partage mérite la grande carte
 * (`summary_large_image`) : un logo carré y serait recadré.
 */
$nf_seo_desc  = nf_seo_description((string) ($description ?? ''));
$nf_og_type   = (string) ($this->output->data->get('module', 'og_type') ?: 'website');
$nf_og_image  = (string) ($this->output->data->get('module', 'og_image') ?: '');
$nf_og_grande = $nf_og_image !== '';
$nf_fichier   = static fn ($id): string => $id ? (string) NeoFrag()->model2('file', $id)->path() : '';

if ($nf_og_image === '')
{
	['adresse' => $nf_og_image, 'grande' => $nf_og_grande] = nf_seo_image_partage();
}

if ($nf_og_image !== '' && strpos($nf_og_image, '://') === FALSE)
{
	$nf_og_image = rtrim($nf_origin, '/').'/'.ltrim($nf_og_image, '/');
}

/*
 * Les données structurées. Celles de la page (un billet, une fiche de logiciel), et sur l'accueil celles
 * du site : `WebSite`, avec sa recherche si le module est installé, et `Organization`, son logo et ses
 * réseaux (Paramètres → Réseaux sociaux) — ce qui relie le site à ses comptes chez les moteurs.
 */
$nf_jsonld = $this->output->data->get('module', 'jsonld');
$nf_jsonld = is_array($nf_jsonld) ? $nf_jsonld : NULL;

if ($nf_accueil)
{
	$nf_reseaux = [];

	foreach (['facebook', 'twitter', 'instagram', 'threads', 'bluesky', 'mastodon', 'tiktok', 'youtube', 'twitch', 'discord', 'github', 'steam', 'linkedin'] as $nf_reseau)
	{
		if (isset($this->config->{'nf_social_'.$nf_reseau}) && ($nf_adresse = trim(utf8_html_entity_decode((string) $this->config->{'nf_social_'.$nf_reseau}))) !== '')
		{
			$nf_reseaux[] = $nf_adresse;
		}
	}

	$nf_logo   = $nf_fichier($this->config->nf_logo);
	$nf_jsonld = nf_seo_jsonld_fusion(
		nf_seo_jsonld_site(
			$nf_origin,
			nf_texte_brut($this->config->nf_name),
			$nf_canonical,
			$nf_langue_page,
			$nf_logo !== '' ? rtrim($nf_origin, '/').'/'.ltrim($nf_logo, '/') : '',
			$nf_reseaux,
			$this->module('search') ? nf_seo_adresse($nf_origin, $this->url->base, $nf_langue_page, 'search').'?q={search_term_string}' : ''
		),
		$nf_jsonld
	);
}

/*
 * `noindex` : une page qui n'a rien à faire dans un moteur — les résultats d'une recherche, la connexion,
 * l'inscription — le déclare (`module.robots`). Elle reste suivie : ses liens mènent à du vrai contenu.
 * L'administration ne s'indexe jamais, et ne se suit pas.
 */
$nf_robots = $this->url->admin ? 'noindex, nofollow' : (string) ($this->output->data->get('module', 'robots') ?: '');
?>
<?php if ($nf_seo_desc !== ''): ?>
<meta name="description" content="<?php echo nf_texte($nf_seo_desc) ?>">
<?php endif ?>
<?php if ($nf_robots !== ''): ?>
<meta name="robots" content="<?php echo nf_texte($nf_robots) ?>">
<?php endif ?>
<link rel="canonical" href="<?php echo nf_texte($nf_canonical) ?>">
<?php if (($nf_code = nf_seo_code_verification(nf_seo_reglage('google'))) !== ''): ?>
<meta name="google-site-verification" content="<?php echo $nf_code ?>">
<?php endif ?>
<?php if (($nf_code = nf_seo_code_verification(nf_seo_reglage('bing'))) !== ''): ?>
<meta name="msvalidate.01" content="<?php echo $nf_code ?>">
<?php endif ?>
<?php if ($nf_jsonld): ?>
<script type="application/ld+json"><?php echo json_encode($nf_jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif ?>
<meta property="og:type" content="<?php echo nf_texte($nf_og_type) ?>">
<meta property="og:site_name" content="<?php echo nf_texte($this->config->nf_name) ?>">
<meta property="og:title" content="<?php echo nf_texte($title) ?>">
<?php if ($nf_seo_desc !== ''): ?>
<meta property="og:description" content="<?php echo nf_texte($nf_seo_desc) ?>">
<?php endif ?>
<meta property="og:url" content="<?php echo nf_texte($nf_canonical) ?>">
<meta property="og:locale" content="<?php echo nf_texte(nf_seo_locale($this->config->lang->locale())) ?>">
<?php foreach ($this->config->langs as $lang): ?>
<?php if ($lang->info()->name !== $this->config->lang->info()->name && (!$nf_langues_page || in_array($lang->info()->name, $nf_langues_page, TRUE))): ?>
<meta property="og:locale:alternate" content="<?php echo nf_texte(nf_seo_locale($lang->locale())) ?>">
<?php endif ?>
<?php endforeach ?>
<?php if ($nf_og_image !== ''): ?>
<meta property="og:image" content="<?php echo nf_texte($nf_og_image) ?>">
<meta property="og:image:alt" content="<?php echo nf_texte($this->config->nf_name) ?>">
<?php endif ?>
<meta name="twitter:card" content="<?php echo $nf_og_grande ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?php echo nf_texte($title) ?>">
<?php if ($nf_seo_desc !== ''): ?>
<meta name="twitter:description" content="<?php echo nf_texte($nf_seo_desc) ?>">
<?php endif ?>
<?php if ($nf_og_image !== ''): ?>
<meta name="twitter:image" content="<?php echo nf_texte($nf_og_image) ?>">
<?php endif ?>
<?php
/*
 * Le titre, encodé sans réencoder ce qui l'est déjà. Les titres du site arrivent encodés (« r&eacute;agit »,
 * écrit par un formulaire) ou non (écrit par l'API) : réencodés, les premiers s'affichaient
 * « &amp;eacute; » dans les aperçus de partage — Discord compris ; laissés tels quels, les seconds
 * auraient pu fermer la balise <title> (2026-10-01).
 */
?>
<title><?php echo nf_texte($title) ?></title>
</head>
<body>
<?php
/*
 * LES BANDEAUX DU HAUT DE PAGE — la démonstration, la maintenance, l'aperçu des droits — portent `data-nf-bandeau` :
 * fixés en haut, ils s'empilent dans l'ordre du document, et NF.bandeaux() réserve leur hauteur totale en haut de la
 * page et la publie dans `--nf-haut`. L'en-tête d'un thème qui reste collé en haut quand on fait défiler porte
 * `data-nf-entete`, et sa hauteur est publiée dans `--nf-entete` (zéro quand, à cette largeur, il ne colle pas).
 * Tout ce qui colle s'y cale : un en-tête ou un rail à `top: var(--nf-haut, 0px)`, une colonne à
 * `top: calc(var(--nf-haut, 0px) + var(--nf-entete, 0px) + 16px)`, et les ancres s'arrêtent sous les deux.
 *
 * Ils passaient dessous : sur la démonstration, la moitié de l'en-tête de quatre thèmes disparaissait sous son bandeau
 * dès qu'on faisait défiler, le bas du rail de Forge et de la barre de l'administration sortait de l'écran, et le menu
 * de l'espace membre glissait sous l'en-tête de trois thèmes (mesuré le 2026-10-08). Chaque bandeau se rangeait à sa
 * façon — l'un collé, l'autre fixé, le troisième posé par un script — et seul Forge en tenait compte, pour deux sur
 * trois.
 */
?>
<style>
:root { --nf-haut: 0px; --nf-entete: 0px; }
html { scroll-padding-top: calc(var(--nf-haut, 0px) + var(--nf-entete, 0px) + 16px); }
[data-nf-bandeau] { position: fixed; top: 0; left: 0; right: 0; z-index: 1035; }
.nf-colle-trop-haut { position: static !important; }
</style>
<?php if (nf_demo()): ?>
	<?php /* Le texte sombre sur le vert : en blanc, il n'avait que 2,4:1 de contraste. */ ?>
	<style>#nf-demo-bar { padding: 6px 12px; background: #1abc9c; color: #0b2e26; text-align: center; font: 600 13px/1.5 system-ui, "Segoe UI", sans-serif; box-shadow: 0 2px 6px rgba(0, 0, 0, .25); }</style>
	<div id="nf-demo-bar" data-nf-bandeau><?php echo $this->lang('Démo %s — réinitialisée régulièrement · connexion : %s', '<strong>NeoFrag Reborn</strong>', '<strong>demo / demo</strong>') ?></div>
<?php endif ?>
<?php if ($this->config->nf_maintenance && !$this->url->admin && isset($this->user) && $this->access->effective_admin() && $this->output->module()->name != 'live_editor'): ?>
	<style>#nf-maint-banner { min-height: 52px; display: flex; align-items: center; }</style>
	<div id="nf-maint-banner" class="bg-danger py-2 w-100" data-nf-bandeau>
		<div class="container">
			<div class="row align-items-center">
				<div class="col-12 col-lg-6 text-white"><?php echo icon('fas fa-power-off').' '.$this->lang('Site en opération de maintenance') ?></div>
				<div class="col-12 col-lg-6 text-end"><a href="<?php echo url('admin/settings/maintenance') ?>" class="btn btn-outline-light"><?php echo $this->lang('Ouvrir le site') ?></a></div>
			</div>
		</div>
	</div>
<?php endif ?>
<?php
// R1.9 — Bandeau preview persistent : indique que l'admin voit le site avec les permissions d'un autre rôle/user.
if (isset($this->user) && $this->user->admin && method_exists($this->access, 'get_preview_target') && ($preview = $this->access->get_preview_target())):
?>
	<style>
	.nf-preview-banner { background: #ffc107; color: #212529; padding: 8px 0; border-bottom: 2px solid #d39e00; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
	.nf-preview-banner a.btn { font-weight: 500; }
	</style>
	<div class="nf-preview-banner" data-nf-bandeau>
		<div class="container-fluid">
			<div class="row align-items-center">
				<div class="col">
					<i class="fas fa-eye"></i>
					<strong><?php echo $this->lang('Mode preview actif') ?></strong> ·
					<?php echo $this->lang('Tu vois le site comme %s : <strong>%s</strong>', $preview['type'] === 'role' ? $this->lang('rôle') : $this->lang('user'), nf_texte($preview['label'])) ?>
					<small class="ms-2 text-muted">
						<?php echo $this->lang('(actif depuis %s, expire dans %d min)', timetostr('H:i', $preview['started_at']), max(0, ceil((1800 - (time() - $preview['started_at'])) / 60))) ?>
					</small>
				</div>
				<div class="col-auto">
					<a href="<?php echo url('admin/access/preview/exit') ?>" class="btn btn-sm btn-dark">
						<i class="fas fa-times"></i> <?php echo $this->lang('Quitter le preview') ?>
					</a>
				</div>
			</div>
		</div>
	</div>
<?php endif ?>
<?php echo $body ?>
<?php echo $debug_bar ?>
<script type="text/javascript">
// Primitives DOM minimales post-jQuery (NF.*). Centralise les seuls points où la parité avec jQuery est
// subtile : (1) X-Requested-With sur l'AJAX same-origin (détection serveur, url.php) ; (2) ré-exécution des
// <script> insérés en AJAX avec le nonce CSP (les réponses JSON ne sont pas noncées par le filtre PHP) ;
// (3) lecture data-* compatible jQuery (.data() : coercion JSON/nombre/booléen). Ce n'est PAS un mini-jQuery :
// aucun sélecteur ni chaînage, juste ces primitives.
window.NF = (function(){
	var nonce = window.__nfNonce || '';

	function ready(fn){
		if (document.readyState !== 'loading'){ fn(); }
		else { document.addEventListener('DOMContentLoaded', fn); }
	}

	function data(el, name){
		var raw = el.getAttribute('data-' + name);
		if (raw === null){ return undefined; }
		if (raw === 'true'){ return true; }
		if (raw === 'false'){ return false; }
		if (raw === 'null'){ return null; }
		if (raw !== '' && String(+raw) === raw){ return +raw; }
		var c = raw.charAt(0);
		if (c === '{' || c === '['){ try { return JSON.parse(raw); } catch (e){} }
		return raw;
	}

	function ajax(opts){
		var url     = opts.url;
		var method  = (opts.method || 'GET').toUpperCase();
		var headers = {};
		if (opts.headers){ for (var k in opts.headers){ headers[k] = opts.headers[k]; } }

		var sameOrigin = !/^https?:\/\//i.test(url) || url.indexOf(window.location.origin) === 0;
		if (sameOrigin && !('X-Requested-With' in headers)){
			headers['X-Requested-With'] = 'XMLHttpRequest';
		}

		var init = { method: method, headers: headers, credentials: 'same-origin' };
		if (opts.signal){ init.signal = opts.signal; }
		if (typeof opts.body !== 'undefined'){
			// String -> form-urlencoded (fetch enverrait text/plain par défaut ; le serveur lit $_POST).
			if (typeof opts.body === 'string' && !('Content-Type' in headers)){
				headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
			}
			init.body = opts.body;
		} else if (opts.data && method !== 'GET'){
			// Sérialisation type jQuery $.param : une valeur tableau -> clé[].
			var sp = new URLSearchParams();
			Object.keys(opts.data).forEach(function(k){
				var v = opts.data[k];
				if (Array.isArray(v)){
					var arrKey = /\[\]$/.test(k) ? k : k + '[]'; // ne pas doubler des [] déjà présents dans le name
					v.forEach(function(item){ sp.append(arrKey, item); });
				}
				else if (v !== null && v !== undefined){ sp.append(k, v); }
			});
			init.body = sp;
		}

		return fetch(url, init).catch(function(e){
			// Le serveur injoignable (réseau coupé, site arrêté) : fetch rejette sans statut. Une
			// annulation voulue (AbortController) n'est pas une erreur à signaler.
			if (e && e.name === 'AbortError'){ throw e; }
			var erreur = new Error('Network — ' + url);
			erreur.status = 0;
			erreur.nfAjax = true;
			throw erreur;
		}).then(function(response){
			// Un statut d'erreur doit REJETER. jQuery ne déclenchait pas .done() sur un 404 ; fetch,
			// lui, ne résout pas seulement : il livre le corps de la page d'erreur. Sans ce garde, un
			// appelant en dataType 'text' insère la page « 404 Not Found » dans le DOM comme si de
			// rien n'était (constaté dans le Live Editor). Les flux qui signalent une erreur
			// applicative répondent en 200 avec un JSON (ex. le file-manager et son `sudo`), ils ne
			// sont donc pas concernés.
			if (!response.ok){
				var error = new Error('HTTP ' + response.status + ' — ' + url);
				error.status = response.status;
				// Lu prudemment : une réponse sans en-têtes (un double de test, un polyfill) ne doit pas
				// faire perdre le statut à l'erreur.
				error.reference = (response.headers && typeof response.headers.get === 'function' && response.headers.get('X-NF-Reference')) || '';
				error.nfAjax = true;
				throw error;
			}
			return opts.dataType === 'text' ? response.text() : response.json();
		});
	}

	function post(url, data){
		return ajax({ url: url, method: 'POST', data: data });
	}

	// Le nonce qui vaut pour le document d'accueil du script. Le Live Editor manipule le DOM d'une
	// IFRAME : c'est une seconde réponse HTTP, donc un second nonce, et poser celui de la page parente
	// sur un script de l'iframe le ferait refuser. Repli sur le nonce de cette page quand le document
	// n'a pas de fenêtre propre — le cas d'un fragment de <template>, dont le contenu sera de toute
	// façon adopté par cette page-ci.
	function nonceFor(doc){
		try {
			var win = doc && doc.defaultView;
			if (win && win !== window && win.__nfNonce){ return win.__nfNonce; }
		}
		catch (e){} // document d'une iframe d'une autre origine : inaccessible, on garde le nôtre
		return nonce;
	}

	function recreateScript(old){
		// Le script doit NAÎTRE dans le document qui l'accueille : un <script> créé par le document
		// parent puis inséré dans l'iframe n'y est pas exécuté comme un script de l'iframe.
		var doc = old.ownerDocument || document;
		var n   = nonceFor(doc);
		var s   = doc.createElement('script');
		for (var i = 0; i < old.attributes.length; i++){
			s.setAttribute(old.attributes[i].name, old.attributes[i].value);
		}
		// Jamais de nonce pour un script d'un autre site : il ne passe que si son origine est dans la politique
		// (Analytics, le captcha) — la même règle que le filtre d'index.php pour les pages.
		var src = old.getAttribute('src') || '';
		var tiers = false;
		try { tiers = /^(?:https?:)?\/\//i.test(src) && new URL(src, location.href).origin !== location.origin; } catch (e){ tiers = true; }
		if (n && !tiers){ s.setAttribute('nonce', n); }
		s.textContent = old.textContent;
		old.parentNode.replaceChild(s, old);
	}

	// innerHTML n'exécute jamais les <script> : on les recrée (avec nonce) pour rétablir l'exécution.
	function runScripts(root){
		if (root.tagName === 'SCRIPT'){ recreateScript(root); return; }
		root.querySelectorAll('script').forEach(recreateScript);
	}

	function setHtml(el, html){
		el.innerHTML = html;
		runScripts(el);
	}

	/**
	 * insertAdjacentHTML avec exécution des <script> AJOUTÉS, et eux seuls.
	 *
	 * insertAdjacentHTML n'exécute jamais les scripts : un widget qui s'initialise en JS inline reste
	 * inerte jusqu'au rechargement de la page. Mais relancer runScripts() sur tout le conteneur
	 * rejouerait aussi les scripts déjà en place — d'où le repérage des bornes avant insertion.
	 */
	function insertHtml(target, position, html){
		var parent = target.parentNode;
		var debut, fin;

		if (position === 'beforeend'){        debut = target.lastChild;      fin = null; }
		else if (position === 'afterbegin'){  debut = null;                  fin = target.firstChild; }
		else if (position === 'beforebegin'){ debut = target.previousSibling; fin = target; }
		else if (position === 'afterend'){    debut = target;                fin = target.nextSibling; }
		else { target.insertAdjacentHTML(position, html); return; }

		var conteneur = (position === 'beforeend' || position === 'afterbegin') ? target : parent;

		target.insertAdjacentHTML(position, html);

		var node = debut ? debut.nextSibling : conteneur.firstChild;
		while (node && node !== fin){
			if (node.nodeType === 1){ runScripts(node); }
			node = node.nextSibling;
		}
	}

	/** Équivalent de `el.outerHTML = html`, mais les <script> du remplaçant s'exécutent. */
	function replaceHtml(el, html){
		insertHtml(el, 'beforebegin', html);
		el.parentNode.removeChild(el);
	}

	function loadScript(src){
		return new Promise(function(resolve, reject){
			var s = document.createElement('script');
			if (nonce){ s.setAttribute('nonce', nonce); }
			s.src = src;
			s.onload = function(){ resolve(); };
			s.onerror = function(){ reject(new Error('script: ' + src)); };
			document.head.appendChild(s);
		});
	}

	/*
	 * Les bandeaux du haut de page et l'en-tête collé du thème (cf. le haut de <body>) : les bandeaux s'empilent, leur
	 * hauteur est réservée en haut de la page, et les deux hauteurs sont publiées. Une colonne collée (`data-nf-colle`)
	 * plus haute que la place qu'il lui reste ne colle plus : collée, son bas restait hors de l'écran jusqu'au bout de
	 * la page — le sommaire du wiki, le menu de l'espace membre (mesuré le 2026-10-08).
	 */
	function bandeaux(){
		var racine = document.documentElement, haut = 0, entete = 0;
		Array.prototype.forEach.call(document.querySelectorAll('[data-nf-bandeau]'), function(b){
			b.style.top = haut + 'px';
			haut += b.offsetHeight;
		});
		Array.prototype.forEach.call(document.querySelectorAll('[data-nf-entete]'), function(e){
			var position = getComputedStyle(e).position;
			if (position === 'sticky' || position === 'fixed'){ entete = Math.max(entete, e.offsetHeight); }
		});
		racine.style.setProperty('--nf-haut', haut + 'px');
		racine.style.setProperty('--nf-entete', entete + 'px');
		document.body.style.paddingTop = haut ? haut + 'px' : '';
		Array.prototype.forEach.call(document.querySelectorAll('[data-nf-colle]'), function(c){
			c.classList.remove('nf-colle-trop-haut');
			var haute = c.offsetHeight > window.innerHeight - (parseFloat(getComputedStyle(c).top) || 0) - 16;
			c.classList.toggle('nf-colle-trop-haut', haute);
		});
	}

	return {
		ready: ready, data: data, ajax: ajax, post: post,
		setHtml: setHtml, insertHtml: insertHtml, replaceHtml: replaceHtml,
		runScripts: runScripts, loadScript: loadScript, bandeaux: bandeaux
	};
})();

// Calés tout de suite — la page est déjà là —, puis à chaque changement de taille : un bandeau passe sur deux lignes
// au téléphone, un en-tête se replie quand on fait défiler, une colonne s'allonge quand un widget se charge.
NF.bandeaux();
window.addEventListener('resize', NF.bandeaux);
NF.ready(function(){
	NF.bandeaux();
	if (window.ResizeObserver){
		var observateur = new ResizeObserver(function(){ NF.bandeaux(); });
		Array.prototype.forEach.call(document.querySelectorAll('[data-nf-bandeau], [data-nf-entete], [data-nf-colle]'), function(e){ observateur.observe(e); });
	}
});

/*
 * Une action qui échoue le DIT. NF.ajax rejette sur un statut d'erreur, mais la plupart des appelants
 * ne traitent pas l'échec : une fenêtre qui ne s'ouvrait pas, un bouton resté grisé, sans un mot
 * (relevé le 2026-10-02). Un rejet de NF.ajax que personne n'a rattrapé devient ici un message, avec
 * la référence de l'erreur quand le serveur en donne une — celle que porte le journal. Un appelant
 * qui traite lui-même l'échec (`.catch`) n'est pas concerné : son rejet n'arrive jamais ici.
 */
window.addEventListener('unhandledrejection', function(ev){
	var e = ev.reason;

	if (!e || !e.nfAjax || typeof notify !== 'function'){ return; }

	ev.preventDefault();

	var message = e.status === 0
		? <?php echo json_encode((string) $this->lang('Le site ne répond pas : vérifiez votre connexion, puis réessayez.'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>
		: (e.status === 403 || e.status === 401
			? <?php echo json_encode((string) $this->lang('Action refusée : vous n’avez pas les droits nécessaires, ou votre session a expiré.'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>
			: <?php echo json_encode((string) $this->lang('L’action n’a pas abouti (erreur %d). Réessayez ; si cela se reproduit, prévenez l’administrateur du site.'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>.replace('%d', e.status));

	if (e.reference && /^[0-9A-F]{8}$/.test(e.reference)){
		message += ' ' + <?php echo json_encode((string) $this->lang('Référence : %s'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>.replace('%s', e.reference);
	}

	notify(message, 'danger');
});
</script>
<?php echo $this->output->js() ?>
<script type="text/javascript">
// Le nonçage des <script> insérés en AJAX est désormais géré par NF.setHtml/NF.runScripts/NF.loadScript
// (réponses JSON non noncées par le filtre PHP). Plus de hook jQuery nécessaire.
NF.ready(function(){
	document.body.dispatchEvent(new CustomEvent('nf.load', { bubbles: true }));

	// Popover étend Tooltip : BS5 refuse deux instances sur le même hôte, donc on délègue
	// depuis deux éléments distincts (html / body). Le `selector` couvre tout le document.
	new bootstrap.Popover(document.documentElement, {
		selector: '[data-bs-toggle="popover"]',
		container: 'body',
		trigger: 'hover'
	});

	new bootstrap.Tooltip(document.body, {
		selector: '[data-bs-toggle="tooltip"]'
	});

	// Comportements UI délégués. Remplacent les gestionnaires inline on*="" que le CSP strict
	// (script-src sans 'unsafe-inline') bloque ; la délégation sur `document` couvre en prime le
	// contenu injecté en AJAX (modales), ce que les handlers inline ne faisaient jamais.
	document.addEventListener('change', function(e){
		var el = e.target;
		if (!el || !el.matches) { return; }
		// [data-nf-submit-on-change] : soumet le formulaire au changement (filtres).
		if (el.matches('[data-nf-submit-on-change]') && el.form) { el.form.submit(); return; }
		// [data-nf-nav-select] : navigue vers base + '/' + data-url de l'option choisie (pagination).
		if (el.matches('[data-nf-nav-select]')) {
			var opt = el.options[el.selectedIndex];
			if (opt) { window.location = (el.getAttribute('data-nf-nav-base') || '') + '/' + (opt.getAttribute('data-url') || ''); }
			return;
		}
		// [data-nf-file-name] : reflète le nom du fichier choisi dans l'élément cible (par id).
		if (el.matches('[data-nf-file-name]')) {
			var tgt = document.getElementById(el.getAttribute('data-nf-file-name'));
			if (tgt) { tgt.textContent = (el.files && el.files[0]) ? el.files[0].name : ''; }
			return;
		}
	});
	document.addEventListener('click', function(e){
		// [data-nf-select-on-click] : sélectionne le contenu d'un champ (copier-coller).
		var sel = e.target.closest ? e.target.closest('[data-nf-select-on-click]') : null;
		if (sel && typeof sel.select === 'function') { sel.select(); }
	});

	<?php echo $this->output->js_load() ?>
});
</script>
<?php
/* Le consentement du visiteur aux services tiers : la fenêtre « Gérer mes cookies », le bandeau quand le site a
   quelque chose à demander (jamais dans l'administration), et le script des avis posés à la place des contenus
   tiers. Cf. theme/consentement.tpl.php et helpers/consentement.php. */
echo $this->view('theme/consentement');
?>
</body>
</html>
