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
	$h       = static fn (string $texte): string => htmlspecialchars($texte, ENT_QUOTES);

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
