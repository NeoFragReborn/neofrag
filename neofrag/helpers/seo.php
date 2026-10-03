<?php
declare(strict_types=1);

/**
 * Le référencement : ce que les moteurs lisent dans l'en-tête d'une page.
 *
 * un chantier interne, 2026-10-03. Mesuré en production le jour même : un titre « NeoFrag Reborn | NeoFrag
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
		$xml .= "\t<url>\n\t\t<loc>".htmlspecialchars($adresse['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8')."</loc>\n";

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
		$xml .= "\t<sitemap>\n\t\t<loc>".htmlspecialchars($plan, ENT_XML1 | ENT_QUOTES, 'UTF-8')."</loc>\n\t</sitemap>\n";
	}

	return $xml.'</sitemapindex>'."\n";
}
