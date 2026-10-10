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

	public function output($type = 'index', $settings = [])
	{
		if (is_array($type))
		{
			$settings = $type;
			$type     = 'index';
		}

		// Sans le module qu'il montre, le widget se tait : ses tables manquent, ou ses liens mènent à une page fermée.
		if ($this->modules_manquants())
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

	/**
	 * Les modules que ce widget déclare dans `requires` et qui manquent ici : pas installés, ou désactivés.
	 *
	 * Un widget qui montre ce que fait un module — ses actualités, son forum, sa galerie — le déclare : sans lui, il
	 * ne s'affiche pas, et l'éditeur en direct ne le propose pas. Jusqu'au 2026-10-09, une liste de quinze noms écrite
	 * ici tenait ce rôle et devinait le module à ses tables : elle oubliait Recrutement, et ne voyait pas un module
	 * DÉSACTIVÉ, dont le widget restait affiché avec des liens vers des pages fermées. La déclaration fait foi.
	 *
	 * @return list<string>
	 */
	public function modules_manquants(): array
	{
		$manquants = [];

		foreach ((array) ($this->info()->requires ?? []) as $nom)
		{
			if (!($module = $this->module((string) $nom)) || !$module->is_enabled())
			{
				$manquants[] = (string) $nom;
			}
		}

		return $manquants;
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
