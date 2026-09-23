<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Addons;

use NF\NeoFrag\Loadables\Addon;

abstract class Widget extends Addon
{
	static public function __class($name)
	{
		return 'Widgets\\'.$name.'\\'.$name;
	}

	// Widgets adossés à un module optionnel (homonyme, tables préfixées nf_<nom>). Si le module
	// n'est pas installé, le bloc se masque au lieu de fataliser (ex. module news désinstallé mais
	// widget news resté dans une disposition). Même réflexe que le widget slider, centralisé ici.
	static protected $_module_widgets = ['articles', 'awards', 'calendar', 'donations', 'downloads', 'events', 'forum', 'gallery', 'guestbook', 'links', 'news', 'newsletter', 'partners', 'surveys', 'teams'];

	public function output($type = 'index', $settings = [])
	{
		if (is_array($type))
		{
			$settings = $type;
			$type     = 'index';
		}

		if (in_array($name = $this->info()->name, self::$_module_widgets, TRUE) && !$this->_module_installed($name))
		{
			return;
		}

		if (($controller = $this->controller('index')) && $controller->has_method($type))
		{
			return call_user_func_array([$controller, $type], [$this->_completer($type, $settings)]);
		}
	}

	/**
	 * Les réglages tels que le contrôleur les attend.
	 *
	 * Un widget peut arriver SANS réglages : posé par l'`install()` d'un thème, restauré d'une
	 * disposition ancienne, livré par un jeu de données partiel. Son checker sait rendre des réglages
	 * complets (`check-widget-reglages` l'exige), mais il ne sert qu'à l'ENREGISTREMENT du formulaire :
	 * ce qui s'affichait était le tableau stocké, vide, et douze couples widget/type écrivaient au
	 * journal à chaque page — la navigation plantait même (2026-09-22).
	 *
	 * Les clés ABSENTES reçoivent donc la valeur que le checker du même type rend pour des réglages
	 * vides — exactement ce que le formulaire aurait enregistré par défaut. Les clés PRÉSENTES ne sont
	 * jamais touchées : un checker n'est pas idempotent (celui de `header` code le titre en entités, et
	 * le rappeler sur un titre déjà codé le coderait deux fois). Calculé une fois par requête.
	 */
	private function _completer($type, $settings): array
	{
		static $defauts = [];

		$settings = is_array($settings) ? $settings : [];
		$cle      = $this->info()->name.'/'.$type;

		if (!array_key_exists($cle, $defauts))
		{
			$defauts[$cle] = [];

			try
			{
				if (($checker = @$this->controller('checker')) && $checker->has_method($type)
					&& is_array($valeurs = call_user_func_array([$checker, $type], [[]])))
				{
					$defauts[$cle] = $valeurs;
				}
			}
			catch (\Throwable $e)
			{
				// Un checker qui ne sait pas partir de rien laisse les réglages tels quels : le
				// contrôleur garde alors sa propre défense, que `check-widget-contract` mesure.
			}
		}

		return $settings + $defauts[$cle];
	}

	private function _module_installed($name)
	{
		static $tables;

		if ($tables === NULL)
		{
			$tables = $this->db->tables();
		}

		foreach ($tables as $table)
		{
			if ($table === 'nf_'.$name || strpos($table, 'nf_'.$name.'_') === 0)
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	public function get_admin($type, $settings = [])
	{
		if (($controller = @$this->controller('admin')) && $controller->has_method($type))
		{
			if (!is_array($output = call_user_func_array([$controller, $type], [$settings])))
			{
				$output = [$output];
			}

			return $output;
		}

		return [];
	}

	public function get_settings($type, $settings = [])
	{
		if (($controller = @$this->controller('checker')) && $controller->has_method($type))
		{
			return \NF\NeoFrag\Fields\Json::encode(call_user_func_array([$controller, $type], [$settings]));
		}
	}
}
