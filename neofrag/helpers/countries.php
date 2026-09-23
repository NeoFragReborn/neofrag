<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

/**
 * Les pays, code => nom, dans la langue du site.
 *
 * La table ci-dessous est écrite en français, la langue source. Dans une autre langue, le nom vient
 * de la base internationale d'ICU (extension `intl`, exigée par le produit) : 245 pays traduits dans
 * les six langues sans une ligne de traduction à tenir. Le site anglais proposait jusqu'ici
 * « Allemagne » et « Émirats arabes unis » dans le formulaire de profil (2026-09-23).
 *
 * Quelques codes ne sont pas ceux d'ICU : les nations du Royaume-Uni, la Catalogne, l'Union
 * européenne, et deux codes RETIRÉS de la norme — `an` (Antilles néerlandaises) et `cs`
 * (Tchécoslovaquie), qu'ICU rend aujourd'hui « Curaçao » et « Serbie ». Ceux-là passent par lang().
 */
function get_countries(): array
{
	static $par_langue = [];

	$langue = 'fr';

	$config = NeoFrag()->config;

	if (isset($config->lang) && is_object($config->lang))
	{
		$langue = (string) $config->lang->info()->name;
	}

	if (isset($par_langue[$langue]))
	{
		return $par_langue[$langue];
	}

	//https://github.com/mledoze/countries
	$countries =  [
		'ad'            => 'Andorre',
		'ae'            => 'Émirats arabes unis',
		'af'            => 'Afghanistan',
		'ag'            => 'Antigua-et-Barbuda',
		'ai'            => 'Anguilla',
		'al'            => 'Albanie',
		'am'            => 'Arménie',
		'an'            => 'Antilles néerlandaises',
		'ao'            => 'Angola',
		'ar'            => 'Argentine',
		'as'            => 'Samoa américaines',
		'at'            => 'Autriche',
		'au'            => 'Australie',
		'aw'            => 'Aruba',
		'ax'            => 'Ahvenanmaa',
		'az'            => 'Azerbaïdjan',
		'ba'            => 'Bosnie-Herzégovine',
		'bb'            => 'Barbade',
		'bd'            => 'Bangladesh',
		'be'            => 'Belgique',
		'bf'            => 'Burkina Faso',
		'bg'            => 'Bulgarie',
		'bh'            => 'Bahreïn',
		'bi'            => 'Burundi',
		'bj'            => 'Bénin',
		'bm'            => 'Bermudes',
		'bn'            => 'Brunei',
		'bo'            => 'Bolivie',
		'br'            => 'Brésil',
		'bs'            => 'Bahamas',
		'bt'            => 'Bhoutan',
		'bv'            => 'Île Bouvet',
		'bw'            => 'Botswana',
		'by'            => 'Biélorussie',
		'bz'            => 'Belize',
		'ca'            => 'Canada',
		'catalonia'     => 'Catalogne',
		'cc'            => 'Îles Cocos',
		'cd'            => 'Congo (Rép. dém.)',
		'cf'            => 'République centrafricaine',
		'cg'            => 'Congo',
		'ch'            => 'Suisse',
		'ci'            => 'Côte d\'Ivoire',
		'ck'            => 'Îles Cook',
		'cl'            => 'Chili',
		'cm'            => 'Cameroun',
		'cn'            => 'Chine',
		'co'            => 'Colombie',
		'cr'            => 'Costa Rica',
		'cs'            => 'Tchécoslovaquie',
		'cu'            => 'Cuba',
		'cv'            => 'Îles du Cap-Vert',
		'cx'            => 'Île Christmas',
		'cy'            => 'Chypre',
		'cz'            => 'Tchéquie',
		'de'            => 'Allemagne',
		'dj'            => 'Djibouti',
		'dk'            => 'Danemark',
		'dm'            => 'Dominique',
		'do'            => 'République dominicaine',
		'dz'            => 'Algérie',
		'ec'            => 'Équateur',
		'ee'            => 'Estonie',
		'eg'            => 'Égypte',
		'eh'            => 'Sahara Occidental',
		'england'       => 'Angleterre',
		'er'            => 'Érythrée',
		'es'            => 'Espagne',
		'et'            => 'Éthiopie',
		'europeanunion' => 'Union européenne',
		'fi'            => 'Finlande',
		'fj'            => 'Fidji',
		'fk'            => 'Îles Malouines',
		'fm'            => 'Micronésie',
		'fo'            => 'Îles Féroé',
		'fr'            => 'France',
		'ga'            => 'Gabon',
		'gb'            => 'Royaume-Uni',
		'gd'            => 'Grenade',
		'ge'            => 'Géorgie',
		'gf'            => 'Guyane',
		'gh'            => 'Ghana',
		'gi'            => 'Gibraltar',
		'gl'            => 'Groenland',
		'gm'            => 'Gambie',
		'gn'            => 'Guinée',
		'gp'            => 'Guadeloupe',
		'gq'            => 'Guinée équatoriale',
		'gr'            => 'Grèce',
		'gs'            => 'Géorgie du Sud-et-les Îles Sandwich du Sud',
		'gt'            => 'Guatemala',
		'gu'            => 'Guam',
		'gw'            => 'Guinée-Bissau',
		'gy'            => 'Guyana',
		'hk'            => 'Hong Kong',
		'hm'            => 'Îles Heard-et-MacDonald',
		'hn'            => 'Honduras',
		'hr'            => 'Croatie',
		'ht'            => 'Haïti',
		'hu'            => 'Hongrie',
		'id'            => 'Indonésie',
		'ie'            => 'Irlande',
		'il'            => 'Israël',
		'in'            => 'Inde',
		'io'            => 'Territoire britannique de l\'océan Indien',
		'iq'            => 'Irak',
		'ir'            => 'Iran',
		'is'            => 'Islande',
		'it'            => 'Italie',
		'jm'            => 'Jamaïque',
		'jo'            => 'Jordanie',
		'jp'            => 'Japon',
		'ke'            => 'Kenya',
		'kg'            => 'Kirghizistan',
		'kh'            => 'Cambodge',
		'ki'            => 'Kiribati',
		'km'            => 'Comores',
		'kn'            => 'Saint-Christophe-et-Niévès',
		'kp'            => 'Corée du Nord',
		'kr'            => 'Corée du Sud',
		'kw'            => 'Koweït',
		'ky'            => 'Îles Caïmans',
		'kz'            => 'Kazakhstan',
		'la'            => 'Laos',
		'lb'            => 'Liban',
		'lc'            => 'Sainte-Lucie',
		'li'            => 'Liechtenstein',
		'lk'            => 'Sri Lanka',
		'lr'            => 'Liberia',
		'ls'            => 'Lesotho',
		'lt'            => 'Lituanie',
		'lu'            => 'Luxembourg',
		'lv'            => 'Lettonie',
		'ly'            => 'Libye',
		'ma'            => 'Maroc',
		'mc'            => 'Monaco',
		'md'            => 'Moldavie',
		'me'            => 'Monténégro',
		'mg'            => 'Madagascar',
		'mh'            => 'Îles Marshall',
		'mk'            => 'Macédoine',
		'ml'            => 'Mali',
		'mm'            => 'Birmanie',
		'mn'            => 'Mongolie',
		'mo'            => 'Macao',
		'mp'            => 'Îles Mariannes du Nord',
		'mq'            => 'Martinique',
		'mr'            => 'Mauritanie',
		'ms'            => 'Montserrat',
		'mt'            => 'Malte',
		'mu'            => 'Île Maurice',
		'mv'            => 'Maldives',
		'mw'            => 'Malawi',
		'mx'            => 'Mexique',
		'my'            => 'Malaisie',
		'mz'            => 'Mozambique',
		'na'            => 'Namibie',
		'nc'            => 'Nouvelle-Calédonie',
		'ne'            => 'Niger',
		'nf'            => 'Île Norfolk',
		'ng'            => 'Nigéria',
		'ni'            => 'Nicaragua',
		'nl'            => 'Pays-Bas',
		'no'            => 'Norvège',
		'np'            => 'Népal',
		'nr'            => 'Nauru',
		'nu'            => 'Niue',
		'nz'            => 'Nouvelle-Zélande',
		'om'            => 'Oman',
		'pa'            => 'Panama',
		'pe'            => 'Pérou',
		'pf'            => 'Polynésie française',
		'pg'            => 'Papouasie-Nouvelle-Guinée',
		'ph'            => 'Philippines',
		'pk'            => 'Pakistan',
		'pl'            => 'Pologne',
		'pm'            => 'Saint-Pierre-et-Miquelon',
		'pn'            => 'Îles Pitcairn',
		'pr'            => 'Porto Rico',
		'ps'            => 'Palestine',
		'pt'            => 'Portugal',
		'pw'            => 'Palaos (Palau)',
		'py'            => 'Paraguay',
		'qa'            => 'Qatar',
		're'            => 'Réunion',
		'ro'            => 'Roumanie',
		'rs'            => 'Serbie',
		'ru'            => 'Russie',
		'rw'            => 'Rwanda',
		'sa'            => 'Arabie Saoudite',
		'sb'            => 'Îles Salomon',
		'sc'            => 'Seychelles',
		'scotland'      => 'Écosse',
		'sd'            => 'Soudan',
		'se'            => 'Suède',
		'sg'            => 'Singapour',
		'sh'            => 'Sainte-Hélène',
		'si'            => 'Slovénie',
		'sj'            => 'Svalbard et Jan Mayen',
		'sk'            => 'Slovaquie',
		'sl'            => 'Sierra Leone',
		'sm'            => 'Saint-Marin',
		'sn'            => 'Sénégal',
		'so'            => 'Somalie',
		'sr'            => 'Surinam',
		'st'            => 'São Tomé et Príncipe',
		'sv'            => 'Salvador',
		'sy'            => 'Syrie',
		'sz'            => 'Swaziland',
		'tc'            => 'Îles Turques-et-Caïques',
		'td'            => 'Tchad',
		'tf'            => 'Terres australes et antarctiques françaises',
		'tg'            => 'Togo',
		'th'            => 'Thaïlande',
		'tj'            => 'Tadjikistan',
		'tk'            => 'Tokelau',
		'tl'            => 'Timor oriental',
		'tm'            => 'Turkménistan',
		'tn'            => 'Tunisie',
		'to'            => 'Tonga',
		'tr'            => 'Turquie',
		'tt'            => 'Trinité-et-Tobago',
		'tv'            => 'Tuvalu',
		'tw'            => 'Taïwan',
		'tz'            => 'Tanzanie',
		'ua'            => 'Ukraine',
		'ug'            => 'Ouganda',
		'um'            => 'Îles mineures éloignées des États-Unis',
		'us'            => 'États-Unis',
		'uy'            => 'Uruguay',
		'uz'            => 'Ouzbékistan',
		'va'            => 'Cité du Vatican',
		'vc'            => 'Saint-Vincent-et-les-Grenadines',
		've'            => 'Venezuela',
		'vg'            => 'Îles Vierges britanniques',
		'vi'            => 'Îles Vierges des États-Unis',
		'vn'            => 'Viêt Nam',
		'vu'            => 'Vanuatu',
		'wales'         => 'Pays de Galles',
		'wf'            => 'Wallis-et-Futuna',
		'ws'            => 'Samoa',
		'ye'            => 'Yémen',
		'yt'            => 'Mayotte',
		'za'            => 'Afrique du Sud',
		'zm'            => 'Zambie',
		'zw'            => 'Zimbabwe'
	];

	if ($langue !== 'fr')
	{
		// Écrits ici en toutes lettres pour que `check-langs` voie ces textes et exige leurs traductions.
		$hors_icu = [
			'an'            => NeoFrag()->lang('Antilles néerlandaises'),
			'cs'            => NeoFrag()->lang('Tchécoslovaquie'),
			'catalonia'     => NeoFrag()->lang('Catalogne'),
			'england'       => NeoFrag()->lang('Angleterre'),
			'europeanunion' => NeoFrag()->lang('Union européenne'),
			'scotland'      => NeoFrag()->lang('Écosse'),
			'wales'         => NeoFrag()->lang('Pays de Galles'),
		];

		foreach ($countries as $code => $nom)
		{
			if (isset($hors_icu[$code]))
			{
				$countries[$code] = $hors_icu[$code];
			}
			else if (class_exists('Locale') && ($traduit = \Locale::getDisplayRegion('-'.strtoupper($code), $langue)) !== '' && strcasecmp($traduit, $code) !== 0)
			{
				$countries[$code] = $traduit;
			}
		}
	}

	array_natsort($countries);

	return $par_langue[$langue] = $countries;
}

/**
 * Nom d'un pays d'après son code, ou chaîne vide si le code est inconnu.
 *
 * Pourquoi ce helper existe
 * -------------------------
 * `get_countries()[$code]` était écrit tel quel à HUIT endroits — fiches d'événement, matchs à
 * venir, résultats, équipes, profil membre, administration des adversaires. Un code absent de la
 * table y produisait un `Undefined array key`, journalisé à chaque affichage de la page.
 *
 * Deux causes possibles, et les deux se sont présentées : une donnée restaurée d'un ancien export,
 * et un code en MAJUSCULES alors que la table est indexée en minuscules (`fr`, pas `FR`). D'où la
 * normalisation : le même pays doit se retrouver quelle que soit la casse d'où il vient.
 *
 * Constaté le 2026-09-16 dans le journal du site de démonstration : `Undefined array key "CA"`,
 * `"BE"` et `"FR"` à chaque chargement des pages Événements.
 */
function country_name($code): string
{
	$code = strtolower(trim((string) $code));

	if ($code === '')
	{
		return '';
	}

	return (string) (get_countries()[$code] ?? '');
}
