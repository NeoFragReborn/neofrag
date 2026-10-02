<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

/*
 * Les fuseaux horaires (2026-10-02).
 *
 * Le produit ENREGISTRE ses dates dans le fuseau qu'a PHP au démarrage — l'heure universelle sur le
 * serveur de production — et n'en change jamais en cours de route : la base aligne sa session sur lui
 * (drivers/mysqli.php), et une date écrite dans un autre fuseau serait relue décalée. C'est ce que
 * faisait le fuseau du profil membre jusqu'ici : il changeait celui de PHP, si bien que ses dates
 * s'enregistraient dans son fuseau tandis que les autres restaient lues à l'heure universelle.
 *
 * Il les AFFICHE dans le fuseau de celui qui regarde : celui que le membre a choisi dans son profil,
 * sinon celui de son navigateur (cookie `nf_fuseau`, posé par views/theme/main.tpl.php), sinon celui
 * du site (Paramètres → Préférences générales). La conversion se fait à l'affichage (timetostr(), la
 * bibliothèque Date) et à la saisie (nf_heure_saisie(), pour les champs date et heure des formulaires).
 *
 * Une date SEULE (anniversaire, échéance) ou une heure SEULE (grille d'une webradio) n'est pas un
 * instant : elle ne se convertit pas, sinon le 5 octobre deviendrait le 4 à New York.
 */

/** Le fuseau dans lequel le produit enregistre ses dates : celui de PHP au démarrage. */
function nf_fuseau_stockage(): \DateTimeZone
{
	static $fuseau = NULL;

	return $fuseau ??= new \DateTimeZone(date_default_timezone_get());
}

/** Le fuseau nommé `$nom` (« Europe/Paris »), ou NULL s'il n'existe pas. */
function nf_fuseau_ouvrir($nom): ?\DateTimeZone
{
	static $ouverts = [];

	if (!is_string($nom) || $nom === '')
	{
		return NULL;
	}

	if (!array_key_exists($nom, $ouverts))
	{
		$ouverts[$nom] = in_array($nom, timezone_identifiers_list(), TRUE) ? new \DateTimeZone($nom) : NULL;
	}

	return $ouverts[$nom];
}

/** Le fuseau du site, réglé dans Paramètres → Préférences générales ; à défaut, celui de l'enregistrement. */
function nf_fuseau_site(): \DateTimeZone
{
	try
	{
		$config = NeoFrag()->config;
		$nom    = isset($config->nf_timezone) ? (string) $config->nf_timezone : '';
	}
	catch (\Throwable $e)
	{
		$nom = '';
	}

	return nf_fuseau_ouvrir($nom) ?? nf_fuseau_stockage();
}

/** Le fuseau choisi par le membre connecté (posé par le module user) ; NULL s'il s'en remet à son navigateur. */
function nf_fuseau_membre($nom = NULL, bool $regler = FALSE): ?\DateTimeZone
{
	static $fuseau = NULL;

	if ($regler)
	{
		$fuseau = nf_fuseau_ouvrir($nom);
	}

	return $fuseau;
}

/** Le fuseau dans lequel afficher les dates à celui qui regarde. */
function nf_fuseau(): \DateTimeZone
{
	return nf_fuseau_membre()
		?? nf_fuseau_ouvrir($_COOKIE['nf_fuseau'] ?? NULL)
		?? nf_fuseau_site();
}

/**
 * Une date et heure SAISIE (« 2026-10-05 18:00:00 », lue dans le fuseau de celui qui la saisit) →
 * la même, dans le fuseau d'enregistrement. Ce qui n'a pas cette forme est rendu tel quel.
 */
function nf_heure_saisie($valeur)
{
	if (!is_string($valeur) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $valeur) || !($moment = date_create($valeur, nf_fuseau())))
	{
		return $valeur;
	}

	return $moment->setTimezone(nf_fuseau_stockage())->format('Y-m-d H:i:s');
}

/** L'inverse de nf_heure_saisie() : une date et heure enregistrée → la même, dans le fuseau de celui qui regarde. */
function nf_heure_affichee($valeur)
{
	if (!is_string($valeur) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $valeur) || !($moment = date_create($valeur, nf_fuseau_stockage())))
	{
		return $valeur;
	}

	return $moment->setTimezone(nf_fuseau())->format('Y-m-d H:i:s');
}

/**
 * Les fuseaux pour une liste déroulante, groupés par région : [région => [nom => « Paris (UTC+02:00) »]].
 * Les villes sont nommées dans la langue du site (ICU), les régions par les traductions du produit.
 */
function nf_fuseaux_liste(): array
{
	$regions = [
		'Europe'     => NeoFrag()->lang('Europe'),
		'America'    => NeoFrag()->lang('Amérique'),
		'Africa'     => NeoFrag()->lang('Afrique'),
		'Asia'       => NeoFrag()->lang('Asie'),
		'Australia'  => NeoFrag()->lang('Australie'),
		'Pacific'    => NeoFrag()->lang('Pacifique'),
		'Indian'     => NeoFrag()->lang('Océan Indien'),
		'Atlantic'   => NeoFrag()->lang('Atlantique'),
		'Arctic'     => NeoFrag()->lang('Arctique'),
		'Antarctica' => NeoFrag()->lang('Antarctique'),
		'UTC'        => NeoFrag()->lang('Temps universel')
	];

	static $cache = [];

	$config = NeoFrag()->config;
	$langue = isset($config->lang) && is_object($config->lang) ? (string) $config->lang->info()->name : 'fr';
	$locale = ['fr' => 'fr_FR', 'en' => 'en_GB', 'de' => 'de_DE', 'es' => 'es_ES', 'it' => 'it_IT', 'pt' => 'pt_PT'][$langue] ?? $langue;
	$liste  = [];

	if (isset($cache[$locale]))
	{
		return $cache[$locale];
	}

	foreach (timezone_identifiers_list() as $nom)
	{
		$region = strstr($nom, '/', TRUE) ?: $nom;

		if (!isset($regions[$region]))
		{
			continue;
		}

		$fuseau  = new \DateTimeZone($nom);
		$decalage = $fuseau->getOffset(new \DateTime('now', $fuseau));
		$ville   = $nom === 'UTC' ? 'UTC' : str_replace('_', ' ', substr(strrchr($nom, '/'), 1));

		// ICU peut ignorer un fuseau que PHP connaît déjà (America/Coyhaique, créé en 2025, sur un
		// serveur dont ICU est plus ancien) : son constructeur lève alors une exception. Le nom de la
		// ville se lit dans l'identifiant.
		try
		{
			if ($nom !== 'UTC' && class_exists('IntlDateFormatter') && ($traduite = (new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $nom, NULL, 'VVV'))->format(0)))
			{
				$ville = $traduite;
			}
		}
		catch (\Throwable $e)
		{
		}

		$liste[$region][$nom] = [$decalage, sprintf('%s (UTC%s%02d:%02d)', $ville, $decalage < 0 ? '−' : '+', intdiv(abs($decalage), 3600), intdiv(abs($decalage) % 3600, 60))];
	}

	$groupes = [];

	foreach ($regions as $region => $titre)
	{
		if (empty($liste[$region]))
		{
			continue;
		}

		uasort($liste[$region], static fn ($a, $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

		// (string) : lang() rend un objet traduisible, qui ne peut pas servir de clé.
		$groupes[(string) $titre] = array_map(static fn ($a) => $a[1], $liste[$region]);
	}

	return $cache[$locale] = $groupes;
}

/** L'instant `$timestamp` (maintenant par défaut), au format de la base, dans le fuseau d'enregistrement. */
function now($timestamp = NULL): string
{
	return (new \DateTime('@'.(int) ($timestamp ?? time())))->setTimezone(nf_fuseau_stockage())->format('Y-m-d H:i:s');
}

function strtoseconds($string)
{
	return strtotime($string, 0);
}

function timetostr($format, $timestamp = NULL): string
{
	// Le fichier est en strict_types : on caste explicitement car les appelants passent souvent
	// des objets stringables (Lang pour $format, Date pour $timestamp) — sinon strtotime()/date()
	// lèvent une TypeError. (string)/(int) déclenchent leur __toString comme avant le strict_types.
	$format = (string)$format;

	if ($timestamp === NULL)
	{
		$timestamp = time();
	}

	// Une date seule ou une heure seule s'écrit telle qu'elle a été enregistrée ; un instant s'écrit
	// dans le fuseau de celui qui regarde (cf. nf_fuseau() plus haut).
	$murale = FALSE;

	if (is_a($timestamp, 'NF\NeoFrag\Libraries\Date'))
	{
		// Un objet Date a un __toString LOCALISÉ (« Le 1 mai 2026 à 09:00 ») que strtotime() ne sait
		// pas parser → 0 → 01/01/1970. On lit son timestamp Unix directement (comme time_span()).
		$murale    = $timestamp->murale();
		$timestamp = $timestamp->timestamp();
	}
	else if (!is_numeric($timestamp))
	{
		$murale    = (bool) preg_match('/^(\d{4}-\d{2}-\d{2}|\d{2}:\d{2}(:\d{2})?)$/', (string) $timestamp);
		$timestamp = strtotime((string)$timestamp);
	}

	$timestamp = (int)$timestamp;
	$fuseau    = $murale ? nf_fuseau_stockage() : nf_fuseau();
	$moment    = (new \DateTimeImmutable('@'.$timestamp))->setTimezone($fuseau);

	if (is_windows())
	{
		$format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
	}

	$config = NeoFrag()->config;
	$langue = isset($config->lang) && is_object($config->lang) ? (string) $config->lang->info()->name : 'fr';

	// Les NOMS de jours et de mois viennent d'ICU (extension intl, exigée par le produit), dans la
	// langue du site : `date()` les écrit toujours en anglais, et la traduction qui suivait ne
	// connaissait que les formes COMPLÈTES — « 30 aug 2026 », « 16 sep 2026 » sur un site français
	// (signalé le 2026-09-23). ICU connaît aussi les abréviations (« 16 sept. 2026 »),
	// et la casse de chaque langue : tout mettre en minuscules écrivait « märz » en allemand.
	if (class_exists('IntlDateFormatter'))
	{
		static $formateurs = [];

		$locale = ['fr' => 'fr_FR', 'en' => 'en_GB', 'de' => 'de_DE', 'es' => 'es_ES', 'it' => 'it_IT', 'pt' => 'pt_PT'][$langue] ?? $langue;
		$noms   = ['D' => 'EEE', 'l' => 'EEEE', 'M' => 'MMM', 'F' => 'MMMM'];
		$output = '';

		for ($i = 0, $n = strlen($format); $i < $n; $i++)
		{
			$c = $format[$i];

			// Un caractère échappé (`\à`, `\l\e`) s'écrit tel quel, comme le fait date().
			if ($c === '\\' && $i + 1 < $n)
			{
				$output .= $format[++$i];
				continue;
			}

			if (isset($noms[$c]))
			{
				// Un fuseau qu'ICU ne connaît pas (plus récent que lui) : son décalage à cet instant,
				// que tout ICU comprend — le jour et le mois sont les mêmes.
				$cle = $locale.$c.$fuseau->getName();

				try
				{
					$formateurs[$cle] ??= new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $fuseau->getName(), \IntlDateFormatter::GREGORIAN, $noms[$c]);
					$output .= (string) $formateurs[$cle]->format($timestamp);
				}
				catch (\Throwable $e)
				{
					$output .= (string) (new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, 'GMT'.$moment->format('P'), \IntlDateFormatter::GREGORIAN, $noms[$c]))->format($timestamp);
				}

				continue;
			}

			$output .= $moment->format($c);
		}

		$output = preg_replace('/ +/', ' ', $output) ?? $output;

		return utf8_string(mb_strtoupper(mb_substr($output, 0, 1)).mb_substr($output, 1));
	}

	$output = $moment->format($format);

	// Sans intl : la traduction des noms complets que porte l'addon de langue.
	$lang_addon = $config->lang ?? NULL;
	if (is_object($lang_addon) && method_exists($lang_addon, 'localize_date_output'))
	{
		$output = $lang_addon->localize_date_output($output);
	}

	return utf8_string(ucfirst(preg_replace('/ +/', ' ', strtolower($output))));
}

function time_span($timestamp): string
{
	if (!is_a($timestamp, 'NF\NeoFrag\Libraries\Date') && !is_numeric($timestamp))
	{
		$timestamp = strtotime($timestamp);
	}

	return (string)NeoFrag()->date($timestamp);
}
