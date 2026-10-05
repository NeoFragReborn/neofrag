<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

/*
 * Le choix de thème du VISITEUR : le menu du pied de page, et le cookie qui le retient.
 *
 * Signalé le 2026-09-23 : un visiteur qui choisissait Forge sur la démonstration voyait
 * AUSSI le site vitrine en Forge. Le cookie était posé pour tout le domaine (`path=/`) sous un seul
 * nom, alors que la démonstration est servie depuis un sous-dossier du site vitrine (`/demo/`) ; et
 * les deux sites avaient par hasard la même « époque » de thème, si bien que le site vitrine
 * honorait le choix fait sur la démonstration.
 *
 * Deux protections, indépendantes l'une de l'autre :
 *   - le cookie est PROPRE AU SITE : il porte le chemin du site, comme le cookie de session
 *     (cf. core/session.php), et un nom qui en dérive quand le site vit dans un sous-dossier ;
 *   - l'administrateur peut FERMER le choix (Préférences générales → Choix du thème) : le site
 *     vitrine n'a qu'une apparence, et un cookie venu d'ailleurs n'y change rien.
 *
 * Les cinq thèmes qui proposent le menu en recopiaient chacun le HTML et le script : ils vivent ici
 * et dans `js/theme-visiteur.js`.
 */

/**
 * Le cookie du choix : son nom, celui de son « époque », et son chemin.
 *
 * Un site servi à la racine garde le nom historique `nf_theme` : les choix déjà faits restent
 * valables. Un site servi depuis `/demo/` prend `nf_theme_demo`.
 *
 * @return array{nom: string, epoque: string, chemin: string}
 */
function nf_theme_cookie(?string $base = NULL): array
{
	$dossier = trim($base ?? (string) NeoFrag()->url->base, '/');
	$chemin  = $dossier === '' ? '/' : '/'.$dossier.'/';
	$suffixe = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', $dossier), '_'));
	$nom     = $suffixe === '' ? 'nf_theme' : 'nf_theme_'.$suffixe;

	return ['nom' => $nom, 'epoque' => $nom.'_epoch', 'chemin' => $chemin];
}

/** L'administrateur laisse-t-il les visiteurs choisir le thème ? Oui, tant qu'il n'a pas fermé le choix. */
function nf_theme_choix_permis(): bool
{
	$config = NeoFrag()->config;

	return !isset($config->nf_theme_visiteur) || (bool) $config->nf_theme_visiteur;
}

/** Retire le cookie du choix de CE site : choix périmé, ou arrivé alors que le choix est fermé. */
function nf_theme_cookie_retirer(): void
{
	if (headers_sent())
	{
		return;
	}

	$cookie = nf_theme_cookie();

	foreach ([$cookie['nom'], $cookie['epoque']] as $nom)
	{
		setcookie($nom, '', ['expires' => time() - 3600, 'path' => $cookie['chemin'], 'samesite' => 'Lax']);
	}
}

/**
 * Le thème que le visiteur a choisi, s'il doit être honoré ; une chaîne vide sinon.
 *
 * Honoré seulement si le choix est permis, et s'il a été posé depuis le dernier changement du thème
 * par défaut (comparaison d'« époque », cf. Theme::enable()) : sinon le visiteur suit le nouveau
 * défaut, et le cookie est retiré. Jamais le thème de l'administration. Que le thème soit installé,
 * c'est à l'appelant de le vérifier en le chargeant.
 */
function nf_theme_du_visiteur(): string
{
	$cookie = nf_theme_cookie();
	$choix  = $_COOKIE[$cookie['nom']] ?? '';

	if (!is_string($choix) || $choix === '')
	{
		return '';
	}

	$epoque = isset($_COOKIE[$cookie['epoque']]) && is_string($_COOKIE[$cookie['epoque']]) ? (int) $_COOKIE[$cookie['epoque']] : -1;

	if (!nf_theme_choix_permis() || $epoque !== (int) NeoFrag()->config->nf_theme_epoch)
	{
		nf_theme_cookie_retirer();
		return '';
	}

	$choix = (string) preg_replace('/[^a-z0-9_]/i', '', $choix);

	return strtolower($choix) === 'admin' ? '' : $choix;
}

/**
 * Le menu « thème » du pied de page, ou rien : choix fermé par l'administrateur, ou un seul thème
 * public installé. Les thèmes l'affichent par `<?php echo nf_selecteur_theme() ?>`.
 */
function nf_selecteur_theme(): string
{
	if (!nf_theme_choix_permis())
	{
		return '';
	}

	$themes = array_map('strval', NeoFrag()->db	->select('a.name')
												->from('nf_addon a')
												->join('nf_addon_type t', 't.id = a.type_id')
												->where('t.name', 'theme')
												->where('a.name !=', 'admin')
												->order_by('a.name')
												->get());

	if (count($themes) < 2)
	{
		return '';
	}

	$affiche = NeoFrag()->output->theme();
	$courant = $affiche ? (string) $affiche->info()->name : (string) NeoFrag()->config->nf_default_theme;
	$cookie  = nf_theme_cookie();
	$h       = static fn (string $texte): string => nf_texte($texte);

	NeoFrag()->js('theme-visiteur');

	$entrees = '';

	foreach ($themes as $theme)
	{
		$entrees .= '<button type="button" class="dropdown-item'.($theme === $courant ? ' active' : '').'" data-theme-pick="'.$h($theme).'">'.icon('fas fa-palette').' '.$h(ucfirst($theme)).'</button>';
	}

	return '<div class="nf-theme-switch dropup" data-nf-cookie="'.$h($cookie['nom']).'" data-nf-chemin="'.$h($cookie['chemin']).'" data-nf-epoque="'.(int) NeoFrag()->config->nf_theme_epoch.'">'
		.'<button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="'.$h((string) NeoFrag()->lang('Thème du site : %s', ucfirst($courant))).'">'.icon('fas fa-palette').' '.$h(ucfirst($courant)).'</button>'
		.'<div class="dropdown-menu dropdown-menu-end">'.$entrees.'</div>'
		.'</div>';
}

/**
 * Les rubriques de l'administration, dans leur ordre. La barre latérale du thème d'administration les
 * affiche, et le menu « Navigation » de l'éditeur en direct en fait ses sous-menus : une seule liste.
 *
 * Neuf rubriques courtes, choisies le 2026-10-02 : les six précédentes mêlaient le
 * calendrier aux médias, la newsletter au contenu, le Bugtracker à la communauté, et treize modules
 * finissaient dans « Autres modules ». Celle-ci ne reçoit plus que les modules qu'elle ne nomme pas
 * (une extension de la place de marché). Des pages publiques sans administration (la liste des
 * membres, la recherche…) y sont nommées pour l'éditeur en direct.
 *
 * @return array<string, array{title: string, icon: string, modules: list<string>}>
 */
function nf_rubriques_admin(): array
{
	$nf = NeoFrag();

	return [
		'contenu'      => ['title' => (string) $nf->lang('Contenu'),        'icon' => 'fas fa-bullhorn',     'modules' => ['pages', 'articles', 'news', 'slider', 'quotes', 'recipes', 'menu', 'marketplace', 'search']],
		'communaute'   => ['title' => (string) $nf->lang('Communauté'),     'icon' => 'fas fa-users',        'modules' => ['forum', 'talks', 'comments', 'guestbook', 'emojis', 'members', 'user', 'notifications']],
		'animation'    => ['title' => (string) $nf->lang('Animation'),      'icon' => 'fas fa-calendar-alt', 'modules' => ['calendar', 'surveys', 'gamification', 'classifieds']],
		'gaming'       => ['title' => (string) $nf->lang('Gaming'),         'icon' => 'fas fa-gamepad',      'modules' => ['events', 'teams', 'games', 'recruits', 'awards', 'partners']],
		'savoir'       => ['title' => (string) $nf->lang('Savoir'),         'icon' => 'fas fa-book',         'modules' => ['wiki', 'faq', 'glossary', 'downloads', 'links', 'places']],
		'medias'       => ['title' => (string) $nf->lang('Médias'),         'icon' => 'fas fa-photo-video',  'modules' => ['media', 'gallery', 'files', 'webradio']],
		'diffusion'    => ['title' => (string) $nf->lang('Diffusion'),      'icon' => 'fas fa-paper-plane',  'modules' => ['newsletter', 'emails', 'feeds', 'discord', 'webhooks', 'api']],
		'support'      => ['title' => (string) $nf->lang('Support'),        'icon' => 'fas fa-life-ring',    'modules' => ['contact', 'bugtracker', 'moderation', 'sandbox']],
		'monetisation' => ['title' => (string) $nf->lang('Monétisation'),   'icon' => 'fas fa-coins',        'modules' => ['shop', 'donations', 'payments', 'ads']],
		'autres'       => ['title' => (string) $nf->lang('Autres modules'), 'icon' => 'fas fa-ellipsis-h',   'modules' => []],
	];
}
