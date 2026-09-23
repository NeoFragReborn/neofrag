<?php
/**
 * La langue de l'assistant d'installation.
 *
 * L'assistant tourne AVANT le CMS : il n'a ni ses addons de langue ni son `$this->lang()`. Il était
 * donc écrit en français, d'un bout à l'autre, alors que le produit s'installe en six langues — la
 * première chose que voyait quelqu'un qui essayait NeoFrag depuis l'étranger était un formulaire
 * qu'il ne pouvait pas lire (relevé le 2026-09-23).
 *
 * Même principe que le CMS, pour que les mêmes outils s'appliquent : le texte français est la
 * source, sa traduction est rangée dans `install/langs/<code>.php` sous la clé CRC32 du texte, et
 * la fonction s'appelle `lang()` — `check-langs` vérifie donc ses clés et ses pluriels comme ceux
 * d'un module, et `fill-langs` écrit ses traductions.
 *
 *   lang('Base de données')                                  // « Database » en anglais
 *   lang('Connexion impossible : %s', $erreur)               // sprintf
 *   lang('%d étape|%d étapes', $n, $n)                       // pluriel : le compteur DEUX fois
 *
 * La langue : `?lang=xx` (le sélecteur du gabarit), retenue en session ; à défaut celle que le
 * navigateur préfère ; à défaut le français. En ligne de commande : `--lang=xx`. Chargé par le CMS
 * (qui emploie `Installer::fetch_catalog()`), il suit la langue du site.
 */

/** Les langues de l'assistant, dans leur propre langue : c'est ainsi que le sélecteur les montre. */
const NF_INSTALL_LANGUES = [
	'fr' => 'Français',
	'en' => 'English',
	'de' => 'Deutsch',
	'es' => 'Español',
	'it' => 'Italiano',
	'pt' => 'Português',
];

/**
 * La langue courante de l'assistant. `$choix` la fixe (option `--lang` de l'installeur en ligne de
 * commande) ; sans argument, elle se déduit une fois pour toute la requête.
 */
function nf_install_langue(?string $choix = NULL): string
{
	static $langue = NULL;

	if ($choix !== NULL)
	{
		return $langue = isset(NF_INSTALL_LANGUES[$choix]) ? $choix : 'fr';
	}

	if ($langue !== NULL)
	{
		return $langue;
	}

	// Dans le CMS : la langue du site.
	if (function_exists('NeoFrag') && class_exists('NF\NeoFrag\NeoFrag', FALSE))
	{
		$config = NeoFrag()->config;

		if (isset($config->lang) && is_object($config->lang) && isset(NF_INSTALL_LANGUES[$code = (string) $config->lang->info()->name]))
		{
			return $langue = $code;
		}
	}

	if (PHP_SAPI !== 'cli')
	{
		if (isset($_GET['lang']) && is_string($_GET['lang']) && isset(NF_INSTALL_LANGUES[$_GET['lang']]))
		{
			if (session_status() === PHP_SESSION_ACTIVE)
			{
				$_SESSION['nf_install_lang'] = $_GET['lang'];
			}

			return $langue = $_GET['lang'];
		}

		if (isset($_SESSION['nf_install_lang']) && isset(NF_INSTALL_LANGUES[$_SESSION['nf_install_lang']]))
		{
			return $langue = $_SESSION['nf_install_lang'];
		}

		// « de-CH,de;q=0.9,en;q=0.8 » : la première langue connue, par préférence décroissante.
		if (preg_match_all('/([a-z]{2})(?:-[A-Za-z]{2,})?(?:;q=([0-9.]+))?/i', (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''), $trouves, PREG_SET_ORDER))
		{
			$preferees = [];

			foreach ($trouves as $t)
			{
				$code = strtolower($t[1]);
				$preferees[$code] = max($preferees[$code] ?? 0, isset($t[2]) ? (float) $t[2] : 1.0);
			}

			arsort($preferees);

			foreach (array_keys($preferees) as $code)
			{
				if (isset(NF_INSTALL_LANGUES[$code]))
				{
					return $langue = $code;
				}
			}
		}
	}

	return $langue = 'fr';
}

/**
 * Un texte d'ADDON (le titre d'un module, dans l'étape « Profil ») traduit par les fichiers de cet
 * addon. Lus au texte, jamais inclus : un fichier de langue peut employer `$this`, qui n'existe pas
 * ici.
 */
function lang_addon(string $dossier, string $texte): string
{
	static $lus = [];

	if (($code = nf_install_langue()) === 'fr')
	{
		return $texte;
	}

	$fichier = $dossier.'/langs/'.$code.'.php';

	if (!isset($lus[$fichier]))
	{
		$lus[$fichier] = [];

		if (is_file($fichier) && preg_match_all("/'([0-9a-f]{8})'\s*=>\s*'((?:[^'\\\\]|\\\\.)*)'\s*(?=,|\n|\])/", (string) file_get_contents($fichier), $trouves, PREG_SET_ORDER))
		{
			foreach ($trouves as [, $cle, $valeur])
			{
				$lus[$fichier][$cle] ??= str_replace(["\\'", '\\\\'], ["'", '\\'], $valeur);
			}
		}
	}

	return $lus[$fichier][sprintf('%08x', crc32($texte))] ?? $texte;
}

if (!function_exists('lang'))
{
	/**
	 * Le texte dans la langue de l'assistant. Mêmes conventions que `lang()` du CMS : arguments
	 * passés à `sprintf`, pluriel `forme1|forme2` choisi sur le premier argument — qui est alors
	 * RETIRÉ, d'où le compteur passé deux fois quand le texte l'affiche.
	 */
	function lang(string $texte, ...$arguments): string
	{
		static $traductions = [];

		$code = nf_install_langue();

		if ($code !== 'fr')
		{
			if (!isset($traductions[$code]))
			{
				$fichier              = dirname(__DIR__).'/langs/'.$code.'.php';
				$traductions[$code]   = is_file($fichier) ? (array) include $fichier : [];
			}

			$texte = (string) ($traductions[$code][sprintf('%08x', crc32($texte))] ?? $texte);
		}

		if (str_contains($texte, '|') && $arguments && is_numeric($arguments[0]))
		{
			$formes = explode('|', $texte);
			$n      = (int) array_shift($arguments);
			$texte  = $formes[$n <= 1 ? 0 : min($n - 1, count($formes) - 1)];
		}

		return $arguments ? vsprintf($texte, $arguments) : $texte;
	}
}
