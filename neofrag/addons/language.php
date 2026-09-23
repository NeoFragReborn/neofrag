<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Addons;

use NF\NeoFrag\Loadables\Addon;

abstract class Language extends Addon
{
	static public function __class($name)
	{
		return 'Addons\language_'.$name.'\language_'.$name;
	}

	abstract public function locale();
	abstract public function date2sql(&$date);
	abstract public function time2sql(&$time);
	abstract public function datetime2sql(&$datetime);

	public function __invoke($caller, $key, $source, $text)
	{
		$paths = [];

		$callback = function(&$path) use ($key){
			if (check_file($path))
			{
				$locales = include $path;

				if (array_key_exists($key, $locales))
				{
					$path = $locales[$key];
					return TRUE;
				}
			}
		};

		if ($locale = $caller->__path('langs', $this->info()->name.'.php', $paths, $callback))
		{
			return $locale;
		}
		else if (strpbrk((string) $text, '<>') !== FALSE)
		{
			// Une chaîne COMPOSÉE — `->heading($this->lang('Candidature de').' <b>'.$pseudo.'</b>…')` — n'est
			// pas une clé de traduction : c'est du HTML autour de textes déjà traduits. La bibliothèque de
			// libellés la repasse pourtant par lang() ; la chercher dans les fichiers de langue puis
			// journaliser son absence n'apprend rien à personne. Rendue telle quelle.
			return $text;
		}
		else if ($this->deja_traduit($text, $paths))
		{
			// Une chaîne DÉJÀ traduite qui repasse par lang() : `->heading($this->lang('Espace membre'))`,
			// puis la bibliothèque de libellés traduit à son tour ce qu'on lui donne. En français la seconde
			// recherche retombe sur la même clé ; dans les autres langues elle cherchait « Member area » comme
			// CLÉ et journalisait « Unfound lang » à chaque affichage. Si le texte est une VALEUR connue de la
			// langue cible, il est déjà traduit : on le rend tel quel, sans bruit.
			return $text;
		}
		else if (($result = NeoFrag()->collection('i18n')->where('lang_id', $this->__addon->id)->where('model', NULL)->where('name', $key)->row()) && $result())
		{
			return $result->value;
		}
		else if ($this->config->nf_translate_api)
		{
			$source = NeoFrag()->model2('addon')->get('language', $source);

			$locale = $this	->network('https://i18n.neofr.ag')
							->auth($this->config->nf_translate_api)
							->post([
								'source' => $source->info()->name,
								'text'   => $text,
								'target' => $this->info()->name
							]);

			if (!empty($locale->success))
			{
				NeoFrag()	->model2('i18n')
							->set('lang',  $this->__addon->id)
							->set('name',  $key)
							->set('value', $locale->success)
							->create();

				if (($result = NeoFrag()->collection('i18n')->where('lang_id', $lang = $source->__addon->id)->where('model', NULL)->where('name', $key)->row()) && !$result())
				{
					NeoFrag()	->model2('i18n')
								->set('lang',  $lang)
								->set('name',  $key)
								->set('value', $text)
								->create();
				}

				return $locale->success;
			}
		}

		// Un texte ENREGISTRÉ par un formulaire arrive codé en entités : le checker du widget de
		// navigation stocke « Actualit&eacute;s ». Sa clé n'est pas celle d'« Actualités », et le libellé
		// restait en français dans les cinq autres langues, avec une alerte au journal à chaque page.
		// On retente UNE fois avec le texte décodé — jamais s'il devient du HTML : un texte qu'un
		// formulaire a codé ne doit pas ressortir décodé.
		if (str_contains((string) $text, '&') && ($decode = utf8_html_entity_decode($text)) !== (string) $text && strpbrk($decode, '<>') === FALSE)
		{
			return $this($caller, hash('crc32b', $decode), $source, $decode);
		}

		// Un texte que le produit ne connaît pas dans sa langue SOURCE n'est pas une chaîne à
		// traduire : c'est du CONTENU — le nom d'un type d'événement, d'une catégorie, d'un lien de menu
		// saisi par l'administrateur, que la bibliothèque de libellés fait passer par lang() comme
		// n'importe quel titre. Il n'a de traduction dans aucune langue ; l'alerte ne signalait donc rien
		// de réparable, et elle s'écrivait à chaque page affichée hors du français. Elle reste pour ce
		// qu'elle sait dire : une chaîne du produit à laquelle il manque sa traduction.
		// (`$source` devient un objet dans la branche du service de traduction, plus haut.)
		if (!$this->source_connue($key, is_string($source) ? $source : ''))
		{
			return NULL;
		}

		trigger_error('Unfound lang: '.$key.' in paths ['.implode(';', $paths).']', E_USER_WARNING);
	}

	/**
	 * Vrai si la clé figure dans un fichier de la langue SOURCE — là où `check-langs --fix` écrit
	 * chaque chaîne du produit. Tous les fichiers sont regardés, lus une fois par requête, et
	 * seulement sur le chemin de l'échec.
	 */
	private function source_connue(string $key, string $source): bool
	{
		static $cles = [];

		$source = $source !== '' ? $source : 'fr';

		if (!isset($cles[$source]))
		{
			$cles[$source] = [];

			$racine   = dirname(__DIR__, 2);
			$fichiers = array_merge(
				glob($racine.'/neofrag/langs/'.$source.'.php') ?: [],
				glob($racine.'/{modules,widgets,themes,addons}/*/langs/'.$source.'.php', GLOB_BRACE) ?: [],
				glob($racine.'/overrides/{neofrag,modules/*,widgets/*,themes/*,addons/*}/langs/'.$source.'.php', GLOB_BRACE) ?: []
			);

			foreach ($fichiers as $fichier)
			{
				if (is_array($locales = include $fichier))
				{
					$cles[$source] += array_fill_keys(array_map('strval', array_keys($locales)), TRUE);
				}
			}
		}

		return isset($cles[$source][$key]);
	}

	/**
	 * Vrai si $text figure parmi les VALEURS d'un fichier de la langue cible — c'est-à-dire s'il est
	 * lui-même le résultat d'une traduction précédente. Tous les fichiers de la langue sont regardés,
	 * pas seulement ceux de l'addon appelant : le widget « Espace membre » délègue au module `user`, et
	 * c'est dans les fichiers du WIDGET que « Member area » est une valeur. Lus une fois par requête.
	 */
	private function deja_traduit($text, array $paths): bool
	{
		static $valeurs = [];

		$langue = $this->info()->name;

		if (!isset($valeurs[$langue]))
		{
			$valeurs[$langue] = [];

			$racine   = dirname(__DIR__, 2);
			$fichiers = array_merge(
				glob($racine.'/neofrag/langs/'.$langue.'.php') ?: [],
				glob($racine.'/{modules,widgets,themes,addons}/*/langs/'.$langue.'.php', GLOB_BRACE) ?: [],
				glob($racine.'/overrides/{neofrag,modules/*,widgets/*,themes/*,addons/*}/langs/'.$langue.'.php', GLOB_BRACE) ?: []
			);

			foreach ($fichiers as $fichier)
			{
				if (is_array($locales = include $fichier))
				{
					foreach ($locales as $valeur)
					{
						if (is_string($valeur) && $valeur !== '')
						{
							$valeurs[$langue][$valeur] = TRUE;
						}
					}
				}
			}
		}

		return isset($valeurs[$langue][(string) $text]);
	}
}
