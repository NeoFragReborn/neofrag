<?php
declare(strict_types=1);
/**
 * NeoFrag Reborn — le consentement du visiteur aux services tiers (2026-10-08).
 *
 * Pourquoi ce fichier
 * -------------------
 * Le bandeau d'avant proposait « Tout accepter » ou « Refuser », mais ne réglait que Google Analytics :
 * les lecteurs YouTube ou Twitch, le widget Discord, les captchas de Google, de hCaptcha ou de Cloudflare
 * se chargeaient quoi que le visiteur ait répondu. Or l'article 82 de la loi
 * Informatique et Libertés (l'article 5.3 de la directive 2002/58/CE, « ePrivacy ») exige un
 * consentement préalable avant toute lecture ou écriture sur le terminal qui n'est pas strictement
 * nécessaire au service demandé ; et chacun de ces services dépose ses cookies ou lit les siens.
 *
 * Le principe
 * -----------
 *   - un service tiers ne se charge que si le visiteur l'a accepté. Tant qu'il ne l'a pas fait, le
 *     contenu est remplacé par un AVIS qui dit qui recevra quoi, avec deux boutons : « Afficher » (cette
 *     fois) et « Toujours autoriser » (le service). C'est le consentement au moment où il a un sens ;
 *   - le bandeau ne s'ouvre que pour les services qui agissent sur toutes les pages sans que le visiteur
 *     ne demande rien : la mesure d'audience, et le captcha d'un tiers. Un site qui n'en a pas ne montre
 *     pas de bandeau — il n'aurait rien à demander ; un bandeau sans objet serait un décor ;
 *   - « Gérer mes cookies », dans le pied de chaque page, rouvre à tout moment le détail : les cookies du
 *     site lui-même, et chaque service, à cocher ou décocher. Retirer son accord est aussi simple que de
 *     le donner (RGPD, article 7.3).
 *
 * Le cookie
 * ---------
 * `nf_consent` vaut `<empreinte>.<jeton>.<services>` — par exemple `3fa2c1.9f3a0c1d2e4b5a6c.youtube-discord` :
 *   - l'empreinte résume les services du bandeau proposés par le site : si l'administrateur en ajoute un
 *     (il configure Analytics, par exemple), elle change, et le bandeau repose la question ;
 *   - le jeton, tiré au hasard par le navigateur, relie le choix à sa preuve, enregistrée sans adresse
 *     IP dans `nf_cookie_consent` (RGPD, article 7.1 : pouvoir démontrer le consentement) ;
 *   - les services acceptés ; un service absent est refusé.
 * Six mois de vie, la durée que retient la CNIL pour garder un choix avant de reposer la question.
 * L'ancienne valeur `essentials` (« refuser ») vaut encore refus de tout : les outils du produit la posent.
 *
 * Les fonctions de ce fichier sont pures : la vue du bandeau, la politique de sécurité (index.php) et le
 * filtre des pages s'en servent, les tests aussi.
 */

/** Six mois (180 jours), en secondes. */
const NF_CONSENTEMENT_DUREE = 15552000;

/**
 * Le cookie des choix : son nom et son chemin. Un site servi à la racine l'appelle `nf_consent` ; un site
 * servi depuis un sous-dossier (`/club/`) prend `nf_consent_club`, à son seul chemin : deux sites du même
 * domaine ne lisent pas les choix l'un de l'autre — comme le choix du thème (nf_theme_cookie()).
 *
 * @return array{nom: string, chemin: string}
 */
function nf_consentement_cookie(string $base): array
{
	$dossier = trim($base, '/');
	$suffixe = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', $dossier), '_'));

	return [
		'nom'    => $suffixe === '' ? 'nf_consent' : 'nf_consent_'.$suffixe,
		'chemin' => $dossier === '' ? '/' : '/'.$dossier.'/',
	];
}

/** La valeur du cookie des choix dans la requête en cours, NULL sans cookie. */
function nf_consentement_valeur(): ?string
{
	$nom = nf_consentement_cookie((string) NeoFrag()->url->base)['nom'];

	return isset($_COOKIE[$nom]) && is_string($_COOKIE[$nom]) ? $_COOKIE[$nom] : NULL;
}

/**
 * Les services tiers que le produit sait afficher.
 *
 * Les noms sont des noms propres : ils ne se traduisent pas. Ce que fait chaque catégorie, en revanche,
 * se dit dans la langue du visiteur : c'est la vue du bandeau qui l'écrit.
 *
 *   - categorie : mesure, securite, videos, musique, communaute ;
 *   - editeur   : qui reçoit les données, et sa politique de confidentialité ;
 *   - hotes     : les hôtes d'une <iframe> qui relève de ce service (le filtre des pages) ; un hôte suivi d'un
 *     chemin (`www.google.com/maps`) ne vaut que pour les adresses qui commencent ainsi ;
 *   - cadres    : les origines que la politique de sécurité laisse s'afficher en cadre.
 *
 * @return array<string, array{nom: string, categorie: string, editeur: string, politique: string, hotes: list<string>, cadres: list<string>}>
 */
function nf_consentement_services(): array
{
	return [
		'analytics' => [
			'nom'       => 'Google Analytics',
			'categorie' => 'mesure',
			'editeur'   => 'Google Ireland Limited',
			'politique' => 'https://policies.google.com/privacy',
			'hotes'     => [],
			'cadres'    => [],
		],
		'recaptcha' => [
			'nom'       => 'Google reCAPTCHA',
			'categorie' => 'securite',
			'editeur'   => 'Google Ireland Limited',
			'politique' => 'https://policies.google.com/privacy',
			'hotes'     => [],
			'cadres'    => [],
		],
		'hcaptcha' => [
			'nom'       => 'hCaptcha',
			'categorie' => 'securite',
			'editeur'   => 'Intuition Machines, Inc.',
			'politique' => 'https://www.hcaptcha.com/privacy',
			'hotes'     => [],
			'cadres'    => [],
		],
		'turnstile' => [
			'nom'       => 'Cloudflare Turnstile',
			'categorie' => 'securite',
			'editeur'   => 'Cloudflare, Inc.',
			'politique' => 'https://www.cloudflare.com/privacypolicy/',
			'hotes'     => [],
			'cadres'    => [],
		],
		'youtube' => [
			'nom'       => 'YouTube',
			'categorie' => 'videos',
			'editeur'   => 'Google Ireland Limited',
			'politique' => 'https://policies.google.com/privacy',
			'hotes'     => ['youtube.com', 'www.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'],
			'cadres'    => ['https://www.youtube.com', 'https://www.youtube-nocookie.com'],
		],
		'twitch' => [
			'nom'       => 'Twitch',
			'categorie' => 'videos',
			'editeur'   => 'Twitch Interactive, Inc.',
			'politique' => 'https://www.twitch.tv/p/legal/privacy-notice/',
			'hotes'     => ['player.twitch.tv', 'clips.twitch.tv'],
			'cadres'    => ['https://player.twitch.tv', 'https://clips.twitch.tv'],
		],
		'vimeo' => [
			'nom'       => 'Vimeo',
			'categorie' => 'videos',
			'editeur'   => 'Vimeo.com, Inc.',
			'politique' => 'https://vimeo.com/privacy',
			'hotes'     => ['player.vimeo.com', 'vimeo.com', 'www.vimeo.com'],
			'cadres'    => ['https://player.vimeo.com'],
		],
		'dailymotion' => [
			'nom'       => 'Dailymotion',
			'categorie' => 'videos',
			'editeur'   => 'Dailymotion SA',
			'politique' => 'https://legal.dailymotion.com/fr/politique-de-confidentialite/',
			'hotes'     => ['www.dailymotion.com', 'dailymotion.com', 'geo.dailymotion.com'],
			'cadres'    => ['https://www.dailymotion.com', 'https://geo.dailymotion.com'],
		],
		'spotify' => [
			'nom'       => 'Spotify',
			'categorie' => 'musique',
			'editeur'   => 'Spotify AB',
			'politique' => 'https://www.spotify.com/legal/privacy-policy/',
			'hotes'     => ['open.spotify.com'],
			'cadres'    => ['https://open.spotify.com'],
		],
		'soundcloud' => [
			'nom'       => 'SoundCloud',
			'categorie' => 'musique',
			'editeur'   => 'SoundCloud Global Limited & Co. KG',
			'politique' => 'https://soundcloud.com/pages/privacy',
			'hotes'     => ['w.soundcloud.com'],
			'cadres'    => ['https://w.soundcloud.com'],
		],
		'discord' => [
			'nom'       => 'Discord',
			'categorie' => 'communaute',
			'editeur'   => 'Discord Inc.',
			'politique' => 'https://discord.com/privacy',
			'hotes'     => ['discord.com', 'discordapp.com'],
			'cadres'    => ['https://discord.com'],
		],
	];
}

/**
 * Le choix du visiteur, lu dans la valeur du cookie ; NULL s'il n'y en a pas, ou s'il n'est pas lisible.
 * L'ancienne valeur `essentials` vaut refus de tout, quelle que soit l'empreinte du site.
 *
 * @return array{empreinte: string, jeton: string, services: list<string>}|null
 */
function nf_consentement_lire(?string $valeur): ?array
{
	$valeur = (string) $valeur;

	if ($valeur === 'essentials')
	{
		return ['empreinte' => '*', 'jeton' => '', 'services' => []];
	}

	if (!preg_match('/^([0-9a-f]{6})\.([0-9a-f]{16})\.([a-z]+(?:-[a-z]+)*)?$/', $valeur, $m))
	{
		return NULL;
	}

	$connus   = nf_consentement_services();
	$services = array_values(array_unique(array_filter(explode('-', $m[3] ?? ''), fn(string $s): bool => isset($connus[$s]))));

	return ['empreinte' => $m[1], 'jeton' => $m[2], 'services' => $services];
}

/**
 * L'empreinte des services que le bandeau propose : six chiffres hexadécimaux, les mêmes tant que la
 * liste ne change pas. Une liste vide a aussi la sienne.
 *
 * @param list<string> $services
 */
function nf_consentement_empreinte(array $services): string
{
	sort($services);

	return substr(hash('sha256', implode('-', $services)), 0, 6);
}

/**
 * Le visiteur a-t-il accepté ce service ? $valeur : celle du cookie, lue dans la requête par défaut.
 */
function nf_consentement_accepte(string $service, ?string $valeur = NULL): bool
{
	$choix = nf_consentement_lire(func_num_args() > 1 ? $valeur : nf_consentement_valeur());

	return $choix !== NULL && in_array($service, $choix['services'], TRUE);
}

/**
 * Les services que CE site peut afficher, en deux groupes :
 *   - bandeau  : ceux qui agiraient sur toutes les pages sans que le visiteur ne demande rien — la mesure
 *     d'audience, si un identifiant est réglé ; le captcha, s'il est confié à un tiers. Ce sont eux qui
 *     ouvrent le bandeau ;
 *   - contenus : ceux qui ne se chargent que là où une page en contient, derrière leur avis — les lecteurs
 *     que l'éditeur et la messagerie savent intégrer (cf. Talks\Security::is_trusted_embed_host()), et le
 *     widget Discord en cadre s'il est posé. La carte des lieux n'en est pas : ses tuiles passent par le site.
 *
 * @return array{bandeau: list<string>, contenus: list<string>}
 */
function nf_consentement_du_site(): array
{
	static $memo = NULL;

	if ($memo !== NULL)
	{
		return $memo;
	}

	$nf      = NeoFrag();
	$config  = $nf->config;
	$bandeau = [];

	if ((string) $config->nf_analytics !== '')
	{
		$bandeau[] = 'analytics';
	}

	$captcha = \NF\NeoFrag\Libraries\Captcha::cle_active((string) $config->nf_captcha_provider, (string) $config->nf_captcha_public_key, (string) $config->nf_captcha_private_key);

	if (in_array($captcha, ['recaptcha', 'hcaptcha', 'turnstile'], TRUE))
	{
		$bandeau[] = $captcha;
	}

	$contenus = ['youtube', 'twitch', 'vimeo', 'dailymotion', 'spotify', 'soundcloud'];

	try
	{
		foreach ($nf->db->select('settings')->from('nf_widgets')->where('widget', 'discord')->get() as $reglages)
		{
			if ((json_decode((string) $reglages, TRUE)['mode'] ?? '') === 'iframe')
			{
				$contenus[] = 'discord';
				break;
			}
		}
	}
	catch (\Throwable $e) {}

	return $memo = ['bandeau' => $bandeau, 'contenus' => $contenus];
}

/**
 * L'avis posé à la place d'un cadre tiers : qui le sert, ce qu'il recevra, et deux boutons — l'afficher
 * cette fois, ou toujours autoriser ce service. Le cadre attend dans un <template> : rien n'en est chargé.
 *
 * Sans service connu ($service vide), le modèle que js/consentement.js remplit lui-même pour un contenu
 * qu'un script charge (le lecteur du widget Twitch, la carte des lieux) : `{nom}` et `{editeur}` à la place
 * des noms.
 *
 * @param callable(string, mixed...): string $lang la traduction (NeoFrag()->lang)
 */
function nf_consentement_avis(string $service, string $cadre, callable $lang): string
{
	$info = nf_consentement_services()[$service] ?? ['nom' => '{nom}', 'editeur' => '{editeur}'];
	$nom  = nf_texte($info['nom']);
	$qui  = nf_texte($info['editeur']);

	return '<div class="nf-tiers" data-nf-tiers="'.$service.'">'
		.'<template>'.$cadre.'</template>'
		.'<p class="nf-tiers__titre"><i class="fas fa-shield-halved" aria-hidden="true"></i> '.$lang('Contenu de %s', $nom).'</p>'
		.'<p>'.$lang('L’afficher envoie votre adresse IP à %s, qui peut déposer des cookies sur votre appareil.', $qui)
		.' <a href="#nf-consentement" data-nf-consentement-ouvrir>'.$lang('Gérer mes cookies').'</a></p>'
		.'<p class="nf-tiers__boutons">'
		.'<button type="button" class="nf-consentement__bouton" data-nf-tiers-afficher>'.$lang('Afficher').'</button> '
		.'<button type="button" class="nf-consentement__bouton" data-nf-tiers-toujours>'.$lang('Toujours autoriser %s', $nom).'</button>'
		.'</p></div>';
}

/**
 * Le service dont relève une adresse de cadre (<iframe src>), NULL si aucun : un cadre du site lui-même,
 * ou d'un hôte que le produit ne connaît pas.
 */
function nf_consentement_service_de(string $adresse): ?string
{
	if (!preg_match('#^(?:https?:)?//#i', $adresse))
	{
		return NULL;
	}

	$parties = parse_url(str_starts_with($adresse, '//') ? 'https:'.$adresse : $adresse) ?: [];
	$hote    = strtolower((string) ($parties['host'] ?? ''));
	$chemin  = $hote.($parties['path'] ?? '/');

	foreach (nf_consentement_services() as $cle => $service)
	{
		foreach ($service['hotes'] as $motif)
		{
			if (str_contains($motif, '/') ? str_starts_with($chemin, $motif) : $hote === $motif)
			{
				return $cle;
			}
		}
	}

	return NULL;
}

/**
 * Toutes les origines de lecteurs que la politique de sécurité laisse s'afficher en cadre. Elles y sont
 * toujours : c'est l'avis, dans la page, qui retient le cadre tant que le visiteur n'a rien accepté — et
 * « Afficher », qui vaut pour cette fois seulement, doit pouvoir le montrer sans recharger la page.
 */
function nf_consentement_cadres(): string
{
	$cadres = [];

	foreach (nf_consentement_services() as $service)
	{
		array_push($cadres, ...$service['cadres']);
	}

	return implode(' ', array_unique($cadres));
}

/**
 * Le filtre des pages : chaque <iframe> d'un service que le visiteur n'a pas accepté est remplacée par
 * son avis. Le cadre n'est pas perdu : l'avis le garde dans un <template>, inerte (le navigateur ne charge
 * rien de ce qu'il contient), et « Afficher » l'en sort.
 *
 * Une page passe ici en entier, une seule fois (index.php) : les articles, le forum, la messagerie, les
 * widgets — tout ce qui affiche un cadre tiers, sans qu'aucun module n'ait à y penser. Les <iframe> déjà
 * gardées dans un <template>, et le contenu des <script> et des <textarea>, ne sont pas touchés.
 *
 * @param callable(string): bool           $accepte le visiteur a-t-il accepté ce service ?
 * @param callable(string, string): string $avis    l'avis d'un service, le cadre d'origine en second
 */
function nf_consentement_filtrer(string $html, callable $accepte, callable $avis): string
{
	if (stripos($html, '<iframe') === FALSE)
	{
		return $html;
	}

	// Les zones à ne pas toucher, mises de côté le temps du filtre.
	$reserves = [];
	$html     = (string) preg_replace_callback('#<(script|textarea|template)\b[^>]*>.*?</\1\s*>#is', function (array $m) use (&$reserves): string {
		$reserves[] = $m[0];

		return "\x1Anf-reserve-".(count($reserves) - 1)."\x1A";
	}, $html);

	$html = (string) preg_replace_callback('#<iframe\b[^>]*>.*?</iframe\s*>#is', function (array $m) use ($accepte, $avis): string {
		if (!preg_match('#\bsrc\s*=\s*(["\'])(.*?)\1#is', $m[0], $src))
		{
			return $m[0];
		}

		$service = nf_consentement_service_de(html_entity_decode($src[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

		return $service === NULL || $accepte($service) ? $m[0] : $avis($service, $m[0]);
	}, $html);

	return (string) preg_replace_callback("#\x1Anf-reserve-(\d+)\x1A#", fn(array $m): string => $reserves[(int) $m[1]], $html);
}

/**
 * Une page du site, ou un fragment, avant de partir vers le navigateur : chaque cadre d'un service que le
 * visiteur n'a pas accepté devient son avis (nf_consentement_filtrer()), et chaque image d'un autre site
 * passe par le relais (helpers/relais.php). Appelé une fois par réponse, par le filtre d'index.php.
 */
function nf_tiers_page(string $html): string
{
	$nf = NeoFrag();

	if (stripos($html, '<iframe') !== FALSE)
	{
		$html = nf_consentement_filtrer($html, 'nf_consentement_accepte', function (string $service, string $cadre) use ($nf): string {
			return nf_consentement_avis($service, $cadre, fn(string $texte, ...$valeurs): string => (string) $nf->lang($texte, ...$valeurs));
		});
	}

	if (stripos($html, '<img') !== FALSE || stripos($html, 'url(') !== FALSE)
	{
		static $relais = NULL;

		$relais ??= [
			'hote'    => (string) parse_url(site_origin(), PHP_URL_HOST),
			'cle'     => $nf->crypt->derive('relais'),
			'base'    => (string) $nf->url->base,
			'adresse' => url('ajax/user/relais').'/',
		];

		$html = nf_relais_filtrer($html, function (string $adresse) use ($relais): ?string {
			$distante = nf_relais_distante($adresse, $relais['hote']);

			return $distante === NULL ? NULL : nf_relais_adresse($distante, NEOFRAG_CMS, $relais['cle'], $relais['base'], $relais['adresse']);
		});
	}

	return $html;
}

/**
 * Une réponse JSON : les mêmes filtres que nf_tiers_page(), sur chacun des textes qu'elle porte (le HTML
 * d'une fenêtre, les messages d'une discussion rechargés en direct). Les objets restent des objets, les
 * listes des listes ; une réponse où rien ne change est rendue telle quelle, à l'octet près.
 */
function nf_tiers_json(string $json): string
{
	if (stripos($json, '<iframe') === FALSE && stripos($json, '<img') === FALSE && stripos($json, 'url(') === FALSE
		&& stripos($json, '<iframe') === FALSE && stripos($json, '<img') === FALSE)
	{
		return $json;
	}

	try
	{
		$donnees = json_decode($json, FALSE, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
	}
	catch (\JsonException $e)
	{
		return $json;
	}

	$change  = FALSE;
	$filtrer = function ($valeur) use (&$filtrer, &$change) {
		if (is_string($valeur))
		{
			if (stripos($valeur, '<iframe') === FALSE && stripos($valeur, '<img') === FALSE && stripos($valeur, 'url(') === FALSE)
			{
				return $valeur;
			}

			$filtre  = nf_tiers_page($valeur);
			$change  = $change || $filtre !== $valeur;

			return $filtre;
		}

		if (is_array($valeur))
		{
			return array_map($filtrer, $valeur);
		}

		if (is_object($valeur))
		{
			foreach (get_object_vars($valeur) as $nom => $sous)
			{
				$valeur->$nom = $filtrer($sous);
			}
		}

		return $valeur;
	};

	$donnees = $filtrer($donnees);

	return $change ? (string) json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) : $json;
}
