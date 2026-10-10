<?php
declare(strict_types=1);

/**
 * Le référencement : ce que les moteurs lisent dans l'en-tête d'une page.
 *
 * Mesuré en production le 2026-10-03 : un titre « NeoFrag Reborn | NeoFrag
 * Reborn », une description « NeoFrag Reborn », des liens entre langues relatifs et sans `x-default`,
 * l'accueil canonique sur `/fr/index`, des adresses construites sur l'en-tête `Host` de la requête — que
 * n'importe qui peut forger —, et un plan du site aux adresses relatives, que Google ignore. Le gabarit
 * (`neofrag/views/theme/main.tpl.php`) calculait tout cela en ligne ; ces fonctions sont PURES, pour
 * être éprouvées une à une (tests/Unit/HelpersSeoTest.php) et contrôlées sur les pages servies
 * (`tools/check-seo.php`).
 */

/**
 * Le chemin PUBLIC d'une page, sans la langue. L'accueil est la racine de sa langue (`/fr`), jamais
 * `/fr/index` : les deux répondent, et deux adresses pour un même contenu se font concurrence chez les
 * moteurs. Un module qui se visite sous un autre nom que le sien (`articles` → `blog`, cf.
 * `Url::ADRESSE_DE_MODULE`) reprend son adresse publique : les segments sont ceux du ROUTAGE, et toutes
 * les pages du Blog se déclaraient canoniques sur `/fr/articles/…`, une redirection (2026-10-03).
 *
 * @param list<string>          $segments
 * @param array<string, string> $adresses  nom du module => adresse publique
 */
function nf_seo_chemin(array $segments, array $adresses = []): string
{
	$segments = array_values(array_filter(array_map('strval', $segments), static fn (string $s): bool => $s !== ''));

	if ($segments === ['index'])
	{
		return '';
	}

	if (isset($segments[0], $adresses[$segments[0]]))
	{
		$segments[0] = $adresses[$segments[0]];
	}

	return implode('/', $segments);
}

/** Le chemin public de la page servie, sans la langue : celui du canonique et des liens entre langues. */
function nf_chemin_public(): string
{
	return nf_seo_chemin(NeoFrag()->url->segments, \NF\NeoFrag\Core\Url::ADRESSE_DE_MODULE);
}

/**
 * L'adresse complète d'une page dans une langue. Sans langue, c'est l'adresse que le site redirige vers
 * celle du visiteur — ce que les moteurs attendent de `x-default`.
 */
function nf_seo_adresse(string $origine, string $base, string $langue, string $chemin): string
{
	$parties = array_filter([trim($base, '/'), $langue, trim($chemin, '/')], static fn (string $p): bool => $p !== '');

	return rtrim($origine, '/').'/'.implode('/', $parties);
}

/**
 * Une saisie de formulaire telle que la personne l'a tapée. Le formulaire du produit rend chaque valeur en
 * entités HTML (`ç` devient `&ccedil;`, un guillemet `&quot;`) : mesurée telle quelle, une description
 * portugaise de 139 caractères en comptait 167 et le formulaire Référencement refusait TOUT l'envoi —
 * la case IndexNow de la vitrine comprise (2026-10-03). Les textes du référencement se vérifient et
 * s'enregistrent donc en clair ; chaque sortie les échappe (en-tête des pages, bilan, formulaires).
 */
function nf_seo_saisie(mixed $valeur): string
{
	return trim(html_entity_decode(is_scalar($valeur) ? (string) $valeur : '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

/**
 * Un enregistrement TXT commence-t-il par `$prefixe` ? Une propriété « Domaine » de Google Search Console
 * se vérifie par un TXT `google-site-verification=…` posé chez l'hébergeur du domaine, sans rien dans le
 * site : le bilan ne regardait que le réglage, et annonçait « non déclaré » une console vérifiée (2026-10-03).
 *
 * @param list<string> $textes
 */
function nf_seo_txt_annonce(array $textes, string $prefixe): bool
{
	foreach ($textes as $texte)
	{
		if (str_starts_with(trim((string) $texte, " \t\""), $prefixe))
		{
			return TRUE;
		}
	}

	return FALSE;
}

/** Les TXT du domaine du site et, pour un sous-domaine (`www.…`), de son domaine parent. @return list<string> */
function nf_seo_txt_du_domaine(): array
{
	$hote = (string) parse_url(site_origin(), PHP_URL_HOST);

	if ($hote === '' || filter_var($hote, FILTER_VALIDATE_IP) || !function_exists('dns_get_record'))
	{
		return [];
	}

	$parties  = explode('.', $hote);
	$domaines = count($parties) > 2 ? [$hote, implode('.', array_slice($parties, 1))] : [$hote];
	$textes   = [];

	foreach ($domaines as $domaine)
	{
		foreach ((array) @dns_get_record($domaine, DNS_TXT) as $enregistrement)
		{
			if (!empty($enregistrement['txt']))
			{
				$textes[] = (string) $enregistrement['txt'];
			}
		}
	}

	return $textes;
}

/** Deux textes disent-ils la même chose, entités, casse et espaces mis à part ? */
function nf_seo_meme_texte(string $a, string $b): bool
{
	$normaliser = static fn (string $t): string => mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));

	return $normaliser($a) === $normaliser($b);
}

/**
 * Le titre de la page. Une page porte « son titre | le site » ; l'accueil porte « le site — son accroche ».
 * Jamais deux fois le même texte : un titre de page qui vaut le nom du site, ou une accroche qui le
 * répète, s'effacent.
 */
function nf_seo_titre(string $titre_page, string $nom, string $accroche = ''): string
{
	$nom        = trim($nom);
	$titre_page = trim($titre_page);
	$accroche   = trim($accroche);

	if ($titre_page !== '' && !nf_seo_meme_texte($titre_page, $nom))
	{
		return $nom !== '' ? $titre_page.' | '.$nom : $titre_page;
	}

	if ($accroche !== '' && !nf_seo_meme_texte($accroche, $nom))
	{
		return $nom !== '' ? $nom.' — '.$accroche : $accroche;
	}

	return $nom;
}

/**
 * Une description lisible par un moteur : du texte seul, sur une ligne, d'au plus `$max` caractères,
 * coupée entre deux mots. Les moteurs tronquent vers 155 à 160 caractères ; au-delà, la fin se perd.
 */
function nf_seo_description(string $texte, int $max = 160): string
{
	$texte = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ' ', $texte)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$texte = trim((string) preg_replace('/\s+/u', ' ', $texte));

	if (mb_strlen($texte) <= $max)
	{
		return $texte;
	}

	$coupe = mb_substr($texte, 0, $max - 1);

	if (($espace = mb_strrpos($coupe, ' ')) !== FALSE && $espace > $max * 0.6)
	{
		$coupe = mb_substr($coupe, 0, $espace);
	}

	return rtrim($coupe, " ,;:.—–-").'…';
}

/**
 * La locale Open Graph d'une langue (`fr_FR`), lue dans la première locale que déclare son addon.
 *
 * @param list<string>|string $locales
 */
function nf_seo_locale(array|string $locales): string
{
	$premiere = is_array($locales) ? (string) reset($locales) : $locales;

	return (string) preg_replace('/\..*$/', '', $premiere);
}

/**
 * Le code de vérification d'un outil pour webmestres (Google Search Console, Bing). On accepte le code
 * seul ou la balise entière que l'outil fait copier : c'est elle qu'on colle, le plus souvent.
 */
function nf_seo_code_verification(string $saisie): string
{
	$saisie = trim(html_entity_decode($saisie, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

	if (preg_match('/content\s*=\s*["\']([^"\']*)["\']/i', $saisie, $m))
	{
		$saisie = $m[1];
	}

	return preg_match('/^[A-Za-z0-9_\-+=\/.]{4,128}$/', $saisie) ? $saisie : '';
}

/**
 * Les langues que préfère un navigateur, dans l'ordre, d'après son en-tête `Accept-Language`. Une
 * variante régionale vaut aussi pour sa langue : `de-DE` seul désigne l'allemand. Sans ce repli, un
 * navigateur qui n'annonce que `de-DE` tombait sur la langue par défaut du site — et les moteurs
 * suivent la même redirection pour l'adresse `x-default`.
 *
 * @return list<string>  codes en minuscules, du préféré au moins préféré
 */
function nf_langues_acceptees(string $entete): array
{
	$poids = [];

	foreach (explode(',', $entete) as $rang => $morceau)
	{
		if (!preg_match('/^\s*([a-zA-Z]{1,8}(?:-[a-zA-Z0-9]{1,8})*)\s*(?:;\s*q\s*=\s*([0-9.]+))?\s*$/', $morceau, $m))
		{
			continue;
		}

		$code = strtolower($m[1]);
		$q    = isset($m[2]) ? (float) $m[2] : 1.0;

		if ($q <= 0)
		{
			continue;
		}

		// À poids égal, l'ordre de l'en-tête départage ; la langue tirée d'une variante passe juste après elle.
		$poids[$code] = max($poids[$code] ?? 0, $q - $rang / 1000);

		if (str_contains($code, '-'))
		{
			$langue         = strstr($code, '-', TRUE);
			$poids[$langue] = max($poids[$langue] ?? 0, $q - $rang / 1000 - 0.0005);
		}
	}

	arsort($poids);

	return array_map('strval', array_keys($poids));
}

/**
 * Les données structurées de l'accueil : le site (`WebSite`, avec sa recherche quand elle existe) et
 * celui qui le publie (`Organization`, son logo, ses réseaux). Les identifiants (`@id`) ne dépendent
 * pas de la langue : les six accueils décrivent le même site.
 *
 * @param list<string> $reseaux  adresses complètes des comptes du site
 * @return array<string, mixed>
 */
function nf_seo_jsonld_site(string $origine, string $nom, string $adresse_accueil, string $langue, string $logo = '', array $reseaux = [], string $recherche = ''): array
{
	$racine = rtrim($origine, '/').'/';

	$site = [
		'@type'      => 'WebSite',
		'@id'        => $racine.'#site',
		'name'       => $nom,
		'url'        => $adresse_accueil,
		'inLanguage' => $langue,
		'publisher'  => ['@id' => $racine.'#organisation'],
	];

	if ($recherche !== '')
	{
		$site['potentialAction'] = [
			'@type'       => 'SearchAction',
			'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => $recherche],
			'query-input' => 'required name=search_term_string',
		];
	}

	$organisation = [
		'@type' => 'Organization',
		'@id'   => $racine.'#organisation',
		'name'  => $nom,
		'url'   => $racine,
	];

	if ($logo !== '')
	{
		$organisation['logo'] = $logo;
	}

	if ($reseaux = array_values(array_filter($reseaux, static fn ($r): bool => is_string($r) && preg_match('#^https?://#', $r))))
	{
		$organisation['sameAs'] = $reseaux;
	}

	return ['@context' => 'https://schema.org', '@graph' => [$site, $organisation]];
}

/**
 * Plusieurs blocs de données structurées en un seul : un `@graph` qui réunit leurs nœuds. Un bloc est
 * un nœud (avec son `@context`) ou déjà un graphe. Un seul `<script>` par page, lisible d'un coup.
 *
 * @param array<string, mixed>|null ...$blocs
 * @return array<string, mixed>
 */
function nf_seo_jsonld_fusion(?array ...$blocs): array
{
	$noeuds = [];

	foreach ($blocs as $bloc)
	{
		if (!$bloc)
		{
			continue;
		}

		foreach (isset($bloc['@graph']) && is_array($bloc['@graph']) ? $bloc['@graph'] : [$bloc] as $noeud)
		{
			if (is_array($noeud) && $noeud)
			{
				unset($noeud['@context']);
				$noeuds[] = $noeud;
			}
		}
	}

	if (!$noeuds)
	{
		return [];
	}

	return count($noeuds) === 1
		? ['@context' => 'https://schema.org'] + $noeuds[0]
		: ['@context' => 'https://schema.org', '@graph' => $noeuds];
}

/**
 * Un réglage de référencement (Paramètres → Référencement) dans la langue de la page : `accroche`,
 * `description`, `image`, `google`, `bing`. Les textes existent par langue (`nf_seo_description_en`) ;
 * les autres, une fois pour le site (`nf_seo_image`). Un réglage jamais enregistré vaut une chaîne vide :
 * lire une clé absente de la configuration chargerait une bibliothèque du même nom.
 */
function nf_seo_reglage(string $nom, ?string $langue = NULL): string
{
	$config = NeoFrag()->config;
	$cle    = 'nf_seo_'.$nom.(in_array($nom, ['accroche', 'description'], TRUE) ? '_'.($langue ?? $config->lang->info()->name) : '');

	return isset($config->$cle) ? trim((string) $config->$cle) : '';
}

/**
 * L'accroche du titre de l'accueil : celle de la langue, sinon la description du site quand elle est
 * assez courte pour tenir dans un titre. Jusqu'au 2026-10-03, l'accueil portait « description | nom » —
 * « NeoFrag Reborn | NeoFrag Reborn » quand la description répétait le nom.
 */
function nf_seo_accroche(): string
{
	if (($accroche = nf_seo_reglage('accroche')) !== '')
	{
		return $accroche;
	}

	$description = trim(html_entity_decode((string) NeoFrag()->config->nf_description, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

	return mb_strlen($description) <= 60 ? $description : '';
}

/**
 * La date de dernière modification d'une adresse du plan, au format que veut sitemaps.org (`AAAA-MM-JJ`).
 * Un horodatage, ou une date SQL ; une date nulle (`0000-00-00`), future ou illisible ne s'écrit pas :
 * mieux vaut aucune date qu'une date fausse, que les moteurs apprennent à ignorer.
 */
function nf_seo_date(int|string|null $date, ?int $maintenant = NULL): ?string
{
	$horodatage = is_int($date) ? $date : (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}/', $date) && !str_starts_with($date, '0000') ? strtotime($date) : FALSE);

	if (!$horodatage || $horodatage > ($maintenant ?? time()) + 86400)
	{
		return NULL;
	}

	return date('Y-m-d', $horodatage);
}

/**
 * Le plan du site d'une langue (sitemaps.org 0.9) : des adresses COMPLÈTES — le plan d'avant écrivait
 * `/fr/blog`, que la norme refuse —, chacune une fois, avec sa dernière modification quand on la connaît.
 *
 * @param list<array{loc: string, lastmod?: ?string}> $adresses
 */
function nf_seo_plan_xml(array $adresses): string
{
	$xml = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

	foreach ($adresses as $adresse)
	{
		$xml .= "\t<url>\n\t\t<loc>".nf_texte($adresse['loc'])."</loc>\n";

		if (!empty($adresse['lastmod']))
		{
			$xml .= "\t\t<lastmod>".$adresse['lastmod']."</lastmod>\n";
		}

		$xml .= "\t</url>\n";
	}

	return $xml.'</urlset>'."\n";
}

/**
 * L'index des plans : un par langue, à la racine (`/sitemap.xml`), celui qu'annonce `robots.txt`.
 *
 * @param list<string> $plans  adresses complètes
 */
function nf_seo_index_xml(array $plans): string
{
	$xml = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

	foreach ($plans as $plan)
	{
		$xml .= "\t<sitemap>\n\t\t<loc>".nf_texte($plan)."</loc>\n\t</sitemap>\n";
	}

	return $xml.'</sitemapindex>'."\n";
}

/**
 * Le titre et la description qu'un CONTENU donne aux moteurs dans une langue, saisis dans
 * Paramètres → Référencement depuis la carte d'édition du contenu. NULL s'il n'en donne pas : tout
 * reste alors automatique. Un site pas encore migré n'a pas la table : NULL aussi.
 *
 * @return array{title: string, description: string}|null
 */
function nf_seo_meta(string $type, int $id, ?string $langue = NULL): ?array
{
	static $table = NULL;

	$db = NeoFrag()->db;

	if (!($table ??= (bool) $db->table_exists('nf_seo_meta')))
	{
		return NULL;
	}

	$ligne = $db	->select('title', 'description')
					->from('nf_seo_meta')
					->where('content_type', $type)
					->where('content_id', $id)
					->where('lang', $langue ?? NeoFrag()->config->lang->info()->name)
					->row();

	// row() rend un tableau VIDE quand rien ne correspond : is_array() seul le laissait passer.
	return is_array($ligne) && $ligne && (trim((string) $ligne['title']) !== '' || trim((string) $ligne['description']) !== '')
		? ['title' => trim((string) $ligne['title']), 'description' => trim((string) $ligne['description'])]
		: NULL;
}

/**
 * Applique à la page servie le titre et la description qu'un contenu donne aux moteurs, dans la langue
 * réellement servie. Une ligne dans la page publique d'un contenu : `nf_seo_contenu('news', $news_id);`.
 * Le titre devient « titre pour Google | Nom du site » ; la description passe avant celle de la page.
 */
function nf_seo_contenu(string $type, int $id): void
{
	$output = NeoFrag()->output;
	$langue = (string) ($output->data->get('module', 'langue_servie') ?: NeoFrag()->config->lang->info()->name);

	if ($meta = nf_seo_meta($type, $id, $langue))
	{
		if ($meta['title'] !== '')
		{
			$output->data->set('module', 'seo_titre', $meta['title']);
		}

		if ($meta['description'] !== '')
		{
			$output->data->set('module', 'seo_description', $meta['description']);
		}
	}
}

/**
 * Fait `$faire` dans la langue du membre `$user_id` — celle qu'il a choisie, sinon la langue première du site —, pour
 * un courriel ou une notification qu'on lui écrit pendant la page d'un autre (un modérateur qui le sanctionne, un membre
 * qui le mentionne) : ils partaient dans la langue de celui qui agissait (audit du 2026-10-09). Rend ce que rend `$faire`.
 */
function nf_dans_la_langue_du_membre(int $user_id, callable $faire): mixed
{
	$choisie = $user_id ? NeoFrag()->db->select('a.name')->from('nf_user u')->join('nf_addon a', 'a.id = u.language', 'INNER')->where('u.id', $user_id)->row() : NULL;

	foreach (array_unique(array_filter([is_string($choisie) ? $choisie : '', nf_langue_premiere()])) as $langue)
	{
		$fait     = FALSE;
		$resultat = NeoFrag()->config->dans_la_langue($langue, function () use ($faire, &$fait) {
			$fait = TRUE;

			return $faire();
		});

		if ($fait)
		{
			return $resultat;
		}
	}

	return $faire();
}

/**
 * La langue première du site : la première dans l'ordre d'affichage des langues (Admin → Langues), la même
 * sur toutes les pages. `config->langs` met la langue de la PAGE en tête (cf. Config) : lu tel quel, il
 * rendait l'allemand sur une page allemande.
 */
function nf_langue_premiere(): string
{
	$config  = NeoFrag()->config;
	$langues = array_values(array_filter((array) ($config->langs ?: [$config->lang]), 'is_object'));

	usort($langues, static fn ($a, $b): int => strnatcmp((string) $a->settings()->order, (string) $b->settings()->order) ?: strcmp((string) $a->info()->name, (string) $b->info()->name));

	return $langues ? (string) $langues[0]->info()->name : '';
}

/**
 * La page sert un contenu qui n'a pas de langue à lui — un sujet du forum, une page du wiki, un ticket, une
 * annonce : rédigé une fois, il répond sous chaque préfixe de langue, seuls les menus traduits. Six adresses,
 * un même texte, et chacune se disait canonique : Google en retenait une autre, et n'indexait pas les pages
 * (« Page en double : Google n'a pas choisi la même URL canonique que l'utilisateur », Search Console de la
 * vitrine, 2026-10-07 : 59 contenus annoncés six fois, 354 de ses 580 adresses). Sa canonique est désormais
 * dans la langue première du site, la seule qu'annonce `hreflang` ; le plan du site ne la donne que dans cette
 * langue (`sans_langue`). Rien n'est servi en repli : pas de bandeau. Une ligne dans la page publique du contenu.
 */
function nf_seo_sans_langue(): void
{
	if (($langue = nf_langue_premiere()) !== '')
	{
		NeoFrag()->output->data->set('module', 'langue_canonique', $langue);
		NeoFrag()->output->data->set('module', 'langues_du_contenu', [$langue]);
	}
}

/**
 * Le bouton « Référencement » d'une carte d'édition : il mène à la page qui règle le titre et la
 * description de ce contenu, dans toutes les langues du site. Une ligne dans l'administration d'un module.
 */
function nf_seo_bouton(string $type, int $id): string
{
	return '<a class="btn btn-sm btn-light" href="'.url('admin/settings/seo-contenu/'.rawurlencode($type).'/'.$id).'">'
		.icon('fas fa-magnifying-glass-chart').' '.NeoFrag()->lang('Référencement').'</a>';
}

/**
 * Le plan du site de la langue courante : l'accueil, puis ce que chaque module donne par le carrefour
 * `sitemap` (`modules/<module>/controllers/sitemap.php`, méthode `sitemap()`), en adresses complètes,
 * chacune une fois. Un module désactivé ne contribue pas : ses pages répondent 404. Un module qui
 * échoue n'emporte pas le plan : son erreur part au journal, les autres sont servis.
 *
 * Servi par `/sitemap.xml` (Settings\Controllers\Ajax::sitemap()) et compté par le bilan du
 * référencement (Paramètres → Référencement) : les deux voient exactement les mêmes pages.
 *
 * Une entrée marquée `sans_langue` — un contenu qui n'a pas de langue à lui, cf. nf_seo_sans_langue() —
 * ne figure qu'au plan de la langue première du site, celle de sa canonique.
 *
 * @return array{adresses: list<array{loc: string, lastmod: ?string}>, modules: array<string, int>}
 */
function nf_seo_plan(): array
{
	$origine  = site_origin();
	$entrees  = [['adresse' => '', 'module' => '']];
	$modules  = [];
	$courante = is_object($langue = NeoFrag()->config->lang) ? (string) $langue->info()->name : '';
	$premiere = $courante === '' || $courante === nf_langue_premiere();

	foreach (NeoFrag()->model2('addon')->get('module') as $module)
	{
		if (!$module->is_enabled() || !($controleur = @$module->controller('sitemap')) || !method_exists($controleur, 'sitemap'))
		{
			continue;
		}

		$nom = (string) $module->info()->name;

		try
		{
			foreach ((array) $controleur->sitemap() as $entree)
			{
				if ($premiere || empty($entree['sans_langue']))
				{
					$entrees[] = ['module' => $nom] + (array) $entree;
				}
			}
		}
		catch (\Throwable $erreur)
		{
			trigger_error('Plan du site : le module « '.$nom.' » a échoué — '.$erreur->getMessage(), E_USER_WARNING);
		}
	}

	$adresses = [];

	foreach ($entrees as $entree)
	{
		$loc = $origine.url((string) ($entree['adresse'] ?? ''));

		if (!isset($adresses[$loc]))
		{
			$adresses[$loc] = ['loc' => $loc, 'lastmod' => nf_seo_date($entree['date'] ?? NULL)];

			if ($entree['module'] !== '')
			{
				$modules[$entree['module']] = ($modules[$entree['module']] ?? 0) + 1;
			}
		}
	}

	return ['adresses' => array_values($adresses), 'modules' => $modules];
}

/**
 * L'image de partage du site quand la page n'a pas la sienne : celle des réglages
 * (Paramètres → Référencement), sinon celle du THÈME dans la langue de la page — un thème peut livrer
 * `images/partage/<langue>.png`, ou `images/partage.png`, en 1 200 × 630 —, sinon le logo, sinon le
 * favicon téléversé. Le gabarit principal l'emploie, et le bilan la décrit : les deux suivent la même règle.
 *
 * @return array{adresse: string, grande: bool, source: string}  `source` : reglage, theme, logo, favicon ou '' ;
 *         `adresse` relative au site, versionnée quand c'est un fichier du thème
 */
function nf_seo_image_partage(): array
{
	$config  = NeoFrag()->config;
	$fichier = static fn ($id): string => $id ? (string) NeoFrag()->model2('file', $id)->path() : '';

	if (($image = $fichier(nf_seo_reglage('image'))) !== '')
	{
		return ['adresse' => $image, 'grande' => TRUE, 'source' => 'reglage'];
	}

	foreach (['partage/'.$config->lang->info()->name.'.png', 'partage.png'] as $chemin)
	{
		if ($version = asset_version($chemin, 'images'))
		{
			return ['adresse' => image($chemin).'?v='.$version, 'grande' => TRUE, 'source' => 'theme'];
		}
	}

	if (($image = $fichier($config->nf_logo)) !== '')
	{
		return ['adresse' => $image, 'grande' => FALSE, 'source' => 'logo'];
	}

	if (($image = $fichier($config->nf_favicon)) !== '')
	{
		return ['adresse' => $image, 'grande' => FALSE, 'source' => 'favicon'];
	}

	return ['adresse' => '', 'grande' => FALSE, 'source' => ''];
}

/**
 * Le chemin d'une redirection, tel qu'on le compare à la requête : sans origine, sans langue,
 * sans paramètres, sans barre au bord. On accepte ce qu'un administrateur colle — `/fr/ancienne-page`,
 * `https://site/fr/ancienne-page?x=1`, `ancienne-page` — et l'adresse d'un ancien site, extension comprise
 * (`page.php`).
 *
 * @param list<string> $langues  les codes des langues du site, qu'on retire en tête de chemin
 */
function nf_redirection_source(string $saisie, array $langues = []): string
{
	$chemin   = trim(rawurldecode((string) (parse_url(trim($saisie), PHP_URL_PATH) ?? '')), '/');
	$segments = $chemin === '' ? [] : explode('/', $chemin);

	if ($segments && in_array(strtolower($segments[0]), $langues, TRUE))
	{
		array_shift($segments);
	}

	return implode('/', $segments);
}

/**
 * La cible d'une redirection : une adresse complète en http(s), ou un chemin du site, normalisé comme
 * une source. Tout autre schéma (`javascript:`, `data:`, `mailto:`) est refusé : chaîne vide.
 *
 * @param list<string> $langues
 */
function nf_redirection_cible(string $saisie, array $langues = []): string
{
	$saisie = trim($saisie);

	if (preg_match('#^https?://#i', $saisie))
	{
		return filter_var($saisie, FILTER_VALIDATE_URL) ? $saisie : '';
	}

	if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $saisie) || str_starts_with($saisie, '//'))
	{
		return '';
	}

	return nf_redirection_source($saisie, $langues);
}

/** Les codes des langues du site, pour les redirections. @return list<string> */
function nf_langues_du_site(): array
{
	$config = NeoFrag()->config;

	return array_map(static fn ($langue): string => (string) $langue->info()->name, $config->langs ?: [$config->lang]);
}

/**
 * L'adresse vers laquelle rediriger la requête, si une redirection la prévoit ; NULL sinon.
 * Appelée juste avant de répondre 404 (Libraries\Error) : une page qui existe n'est jamais détournée.
 * Seulement pour une page publique demandée en GET ; le compteur dit si l'ancienne adresse sert encore.
 */
function nf_redirection(): ?string
{
	$url = NeoFrag()->url;
	$db  = NeoFrag()->db;

	if ($url->admin || $url->ajax() || $url->cli || strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET' || !$db->table_exists('nf_redirects'))
	{
		return NULL;
	}

	$chemin = substr((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), strlen(rtrim((string) $url->base, '/')));

	if (($source = nf_redirection_source($chemin, nf_langues_du_site())) === '')
	{
		return NULL;
	}

	$ligne = $db->select('id', 'target')->from('nf_redirects')->where('source', $source)->row();

	// row() rend un tableau VIDE quand rien ne correspond : is_array() seul le laissait passer, et chaque page
	// introuvable écrivait deux avertissements au journal (check-journal, 2026-10-03).
	if (!is_array($ligne) || empty($ligne['target']))
	{
		return NULL;
	}

	$db->execute('UPDATE `nf_redirects` SET `hits` = `hits` + 1, `last_hit_at` = CURRENT_TIMESTAMP WHERE `id` = '.(int) $ligne['id']);

	$cible = (string) $ligne['target'];

	return preg_match('#^https?://#i', $cible) ? $cible : site_origin().url($cible);
}

/**
 * Enregistre qu'une ancienne adresse mène désormais à une nouvelle — quand une page change de nom, par
 * exemple. Une redirection qui partait de la NOUVELLE adresse est retirée : on ne tourne pas en rond ; et
 * celles qui menaient à l'ANCIENNE mènent désormais à la nouvelle, sans double saut.
 */
function nf_redirection_ajouter(string $source, string $cible): void
{
	$db      = NeoFrag()->db;
	$langues = nf_langues_du_site();
	$source  = nf_redirection_source($source, $langues);
	$cible   = nf_redirection_cible($cible, $langues);

	if ($source === '' || $cible === '' || $source === $cible || !$db->table_exists('nf_redirects'))
	{
		return;
	}

	$db->where('source', $cible)->delete('nf_redirects');
	$db->where('source', $source)->delete('nf_redirects');
	$db->where('target', $source)->update('nf_redirects', ['target' => $cible]);
	$db->insert('nf_redirects', ['source' => $source, 'target' => $cible]);
}

/*
 * IndexNow — prévenir les moteurs dès qu'une page paraît, change ou disparaît.
 *
 * Un moteur relit un plan du site quand IL le décide : des heures, parfois des jours. IndexNow est le
 * protocole ouvert par lequel un site le PRÉVIENT ; une adresse envoyée à api.indexnow.org parvient à tous
 * ceux qui y participent — Bing, Yandex, Seznam, Naver, Yep, Amazon. Pas Google, qui n'y participe pas :
 * pour lui, le plan du site et la Search Console restent la voie.
 *
 * Rien n'est branché dans les modules. La tâche planifiée compare le plan du site de chaque langue — le
 * calcul même de /sitemap.xml, que tout module alimente par son carrefour `sitemap` — à celui de son
 * passage précédent (table nf_indexnow) : une adresse nouvelle, dont la date a changé, ou disparue, part.
 * Un addon qui annonce ses pages au plan est donc prévenu sans une ligne de plus, et une page renommée
 * envoie son ancienne adresse (qui redirige) avec la nouvelle, comme le protocole le demande.
 *
 * Le moteur vérifie que l'envoi vient du site en lisant la clé à sa racine (`/<clé>.txt`, servie par
 * Settings\Controllers\Ajax::indexnow()).
 */

/** L'adresse commune des moteurs participants. Le réglage caché `nf_seo_indexnow_api` la remplace, pour les essais. */
const NF_INDEXNOW_API = 'https://api.indexnow.org/indexnow';

/**
 * Le robots.txt ferme-t-il le site entier à tous les moteurs ? Vrai pour un « Disallow: / » du groupe
 * `User-agent: *` qu'aucun « Allow » ne rouvre en partie. Un « Disallow: / » réservé à un robot nommé ne
 * ferme pas le site : un simple motif sur le fichier le croyait (bilan du référencement, 2026-10-03).
 */
function nf_seo_robots_ferme(string $robots): bool
{
	$agents  = [];
	$regles  = FALSE;
	$ferme   = FALSE;
	$rouvert = FALSE;

	foreach (preg_split('/\R/', $robots) ?: [] as $ligne)
	{
		if (!preg_match('/^\s*([a-z-]+)\s*:\s*(.*?)\s*$/i', (string) preg_replace('/#.*/', '', $ligne), $champ))
		{
			continue;
		}

		$nom    = strtolower($champ[1]);
		$valeur = $champ[2];

		if ($nom === 'user-agent')
		{
			// Des agents qui se suivent forment un groupe ; une règle le clôt.
			if ($regles)
			{
				$agents = [];
				$regles = FALSE;
			}

			$agents[] = strtolower($valeur);
		}
		else if ($nom === 'disallow' || $nom === 'allow')
		{
			$regles = TRUE;

			if (in_array('*', $agents, TRUE))
			{
				$ferme   = $ferme   || ($nom === 'disallow' && $valeur === '/');
				$rouvert = $rouvert || ($nom === 'allow' && $valeur !== '');
			}
		}
	}

	return $ferme && !$rouvert;
}

/** Une clé telle que le site en crée : 32 caractères hexadécimaux (le protocole en admet de 8 à 128). */
function nf_indexnow_cle_valide(string $cle): bool
{
	return (bool) preg_match('/^[a-f0-9]{32}$/', $cle);
}

/**
 * Ce qui a changé d'un passage à l'autre : les adresses du plan absentes du précédent, celles dont la date
 * a changé, celles qui n'y sont plus. Une date qui apparaît ou s'efface compte comme un changement ; une
 * page sans date ne se signale qu'en paraissant ou en disparaissant.
 *
 * @param array<string, ?string> $connues adresse => date, au passage précédent
 * @param array<string, ?string> $plan    adresse => date, maintenant
 * @return array{nouvelles: list<string>, changees: list<string>, disparues: list<string>}
 */
function nf_indexnow_ecarts(array $connues, array $plan): array
{
	$ecarts = ['nouvelles' => [], 'changees' => [], 'disparues' => []];

	foreach ($plan as $adresse => $date)
	{
		if (!array_key_exists($adresse, $connues))
		{
			$ecarts['nouvelles'][] = (string) $adresse;
		}
		else if ($connues[$adresse] !== $date)
		{
			$ecarts['changees'][] = (string) $adresse;
		}
	}

	foreach (array_keys(array_diff_key($connues, $plan)) as $adresse)
	{
		$ecarts['disparues'][] = (string) $adresse;
	}

	return $ecarts;
}

/**
 * Le corps d'un envoi : l'hôte du site, sa clé, l'adresse où le moteur la vérifie, et les adresses. Seules
 * celles du site partent, 10 000 au plus — le moteur refuserait tout l'envoi pour une seule étrangère (422).
 *
 * @param list<string> $adresses
 * @return array{host: string, key: string, keyLocation: string, urlList: list<string>}
 */
function nf_indexnow_corps(string $origine, string $base, string $cle, array $adresses): array
{
	$origine  = rtrim($origine, '/');
	$racine   = $origine.rtrim('/'.trim($base, '/'), '/').'/';
	$retenues = [];

	foreach ($adresses as $adresse)
	{
		if (str_starts_with((string) $adresse, $racine))
		{
			$retenues[(string) $adresse] = TRUE;
		}
	}

	return [
		'host'        => (string) parse_url($origine, PHP_URL_HOST),
		'key'         => $cle,
		'keyLocation' => $racine.$cle.'.txt',
		'urlList'     => array_slice(array_keys($retenues), 0, 10000),
	];
}

/**
 * Ce que dit la réponse : `recue` (200 ; 202, la clé reste à vérifier), `refusee` (400, 403 clé
 * introuvable, 422 adresses d'un autre site — à corriger, pas à répéter) ou `plus_tard` (429 trop
 * d'envois, une erreur du serveur, pas de réponse).
 */
function nf_indexnow_issue(int $statut): string
{
	if ($statut === 200 || $statut === 202)
	{
		return 'recue';
	}

	return $statut >= 400 && $statut < 500 && $statut !== 429 ? 'refusee' : 'plus_tard';
}

/** La clé du site, ou une chaîne vide. */
function nf_indexnow_cle(): string
{
	$config = NeoFrag()->config;

	return isset($config->nf_seo_indexnow_cle) ? (string) $config->nf_seo_indexnow_cle : '';
}

/** IndexNow est-il en service ? Jamais sur une démonstration, ni sans sa table ou sa clé. */
function nf_indexnow_actif(): bool
{
	return !nf_demo() && !empty(NeoFrag()->config->nf_seo_indexnow) && nf_indexnow_cle_valide(nf_indexnow_cle()) && NeoFrag()->db->table_exists('nf_indexnow');
}

/**
 * Ce que la tâche planifiée a fait en dernier, pour le bilan : `passage` (date), `suivies` (adresses du
 * plan), `envoi`, `statut`, `issue`, `envoyees` (la dernière tentative), `recue`, `recues` (le dernier
 * envoi accepté).
 *
 * @return array<string, int|string>
 */
function nf_indexnow_etat(): array
{
	$config = NeoFrag()->config;
	$etat   = isset($config->nf_seo_indexnow_etat) ? json_decode(utf8_html_entity_decode((string) $config->nf_seo_indexnow_etat), TRUE) : NULL;

	return is_array($etat) ? $etat : [];
}

/**
 * Note des adresses comme vues, par lots : `$a_envoyer` les marque à envoyer — une adresse déjà connue,
 * même disparue entre-temps, est remise à jour.
 *
 * @param array<string, ?string> $adresses adresse => date
 */
function nf_indexnow_noter(array $adresses, bool $a_envoyer): void
{
	$db = NeoFrag()->db;

	foreach (array_chunk($adresses, 500, TRUE) as $lot)
	{
		$valeurs = [];

		foreach ($lot as $adresse => $date)
		{
			$valeurs[] = '(\''.$db->escape_string((string) $adresse).'\', '.($date === NULL ? 'NULL' : '\''.$db->escape_string((string) $date).'\'').', '.(int) $a_envoyer.', 0, CURRENT_TIMESTAMP)';
		}

		$db->execute('INSERT INTO `nf_indexnow` (`url`, `lastmod`, `pending`, `gone`, `changed_at`) VALUES '.implode(', ', $valeurs)
			.' ON DUPLICATE KEY UPDATE `lastmod` = VALUES(`lastmod`), `pending` = VALUES(`pending`), `gone` = 0, `changed_at` = CURRENT_TIMESTAMP');
	}
}

/** Envoie le corps à IndexNow ; rend le code HTTP de la réponse, 0 sans réponse. */
function nf_indexnow_envoyer(array $corps): int
{
	$config = NeoFrag()->config;
	$api    = isset($config->nf_seo_indexnow_api) && preg_match('#^https?://#i', (string) $config->nf_seo_indexnow_api) ? (string) $config->nf_seo_indexnow_api : NF_INDEXNOW_API;

	if (!function_exists('curl_init'))
	{
		return 0;
	}

	$ch = curl_init($api);
	curl_setopt_array($ch, [
		CURLOPT_POST           => TRUE,
		CURLOPT_POSTFIELDS     => json_encode($corps, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
		CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_TIMEOUT        => 15,
		CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_USERAGENT      => 'NeoFrag-Reborn/'.(defined('NEOFRAG_VERSION') ? NEOFRAG_VERSION : '1'),
		CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
		CURLOPT_FOLLOWLOCATION => FALSE,
	]);
	curl_exec($ch);

	return (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
}

/**
 * Le passage de la tâche planifiée (Monitoring\Controllers\Index::cron()) : compare le plan du site de
 * chaque langue au passage précédent, et envoie ce qui a changé. Rend la ligne du compte rendu du cron.
 *
 * Le premier passage — à la mise en service — ne fait que relever le plan : les pages qui existent déjà,
 * les moteurs les connaissent par lui. Après un envoi refusé ou sans réponse, on attend une heure ; les
 * adresses restent à envoyer jusque-là. Un site fermé aux moteurs (maintenance, robots.txt) ne signale rien.
 * La ligne rendue est en anglais, comme les autres du compte rendu : un journal technique, pas un écran.
 */
function nf_indexnow(): string
{
	if (!nf_indexnow_actif())
	{
		return 'indexnow: off';
	}

	$config = NeoFrag()->config;
	$db     = NeoFrag()->db;

	if (!empty($config->nf_maintenance) || nf_seo_robots_ferme((string) $config->nf_robots_txt))
	{
		return 'indexnow: closed to search engines (maintenance or robots.txt), nothing sent';
	}

	$plan = [];

	foreach (nf_langues_du_site() as $code)
	{
		foreach ((array) $config->dans_la_langue($code, static fn () => nf_seo_plan()['adresses']) as $adresse)
		{
			$plan[(string) $adresse['loc']] = $adresse['lastmod'];
		}
	}

	$connues = [];
	$suivies = 0;

	foreach ($db->select('url', 'lastmod', 'gone')->from('nf_indexnow')->get() as $ligne)
	{
		$suivies++;

		// Une adresse disparue, pas encore envoyée, n'est plus « connue » : si elle revient, elle repart.
		if (empty($ligne['gone']))
		{
			$connues[(string) $ligne['url']] = $ligne['lastmod'] !== NULL ? (string) $ligne['lastmod'] : NULL;
		}
	}

	$etat     = ['passage' => date('Y-m-d H:i:s'), 'suivies' => count($plan)] + nf_indexnow_etat();
	$retenir  = static function(array $etat) use ($config){
		$config('nf_seo_indexnow_etat', (string) json_encode($etat));
	};

	if ($suivies === 0)
	{
		nf_indexnow_noter($plan, FALSE);
		$retenir($etat);

		return 'indexnow: '.count($plan).' url(s) recorded, nothing sent on the first pass';
	}

	$ecarts = nf_indexnow_ecarts($connues, $plan);
	nf_indexnow_noter(array_intersect_key($plan, array_flip(array_merge($ecarts['nouvelles'], $ecarts['changees']))), TRUE);

	if ($ecarts['disparues'])
	{
		foreach (array_chunk($ecarts['disparues'], 500) as $lot)
		{
			$db->where('url', $lot)->update('nf_indexnow', ['pending' => 1, 'gone' => 1]);
		}
	}

	$a_envoyer = array_map('strval', (array) $db->select('url')->from('nf_indexnow')->where('pending', 1)->order_by('changed_at ASC')->limit(10000)->get());

	if (!$a_envoyer)
	{
		$retenir($etat);

		return 'indexnow: nothing new ('.count($plan).' urls tracked)';
	}

	if (($etat['issue'] ?? 'recue') !== 'recue' && isset($etat['envoi']) && strtotime((string) $etat['envoi']) > time() - 3600)
	{
		$retenir($etat);

		return 'indexnow: '.count($a_envoyer).' url(s) waiting, retry one hour after the failure';
	}

	$corps  = nf_indexnow_corps(site_origin(), (string) NeoFrag()->url->base, nf_indexnow_cle(), $a_envoyer);
	$statut = $corps['urlList'] ? nf_indexnow_envoyer($corps) : 200;
	$issue  = nf_indexnow_issue($statut);

	$etat = ['envoi' => date('Y-m-d H:i:s'), 'statut' => $statut, 'issue' => $issue, 'envoyees' => count($corps['urlList'])] + $etat;

	if ($issue === 'recue')
	{
		foreach (array_chunk($a_envoyer, 500) as $lot)
		{
			$db->where('url', $lot)->where('gone', 1)->delete('nf_indexnow');
			$db->where('url', $lot)->update('nf_indexnow', ['pending' => 0]);
		}

		$etat = ['recue' => $etat['envoi'], 'recues' => count($corps['urlList'])] + $etat;
	}

	$retenir($etat);

	return 'indexnow: '.count($corps['urlList']).' url(s) sent, response '.$statut.' ('.$issue.')';
}
