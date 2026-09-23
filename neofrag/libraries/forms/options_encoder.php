<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries\Forms;

/**
 * Encodage JSON des options d'un <select> (attribut data-options de selectize).
 *
 * Isolé de Select pour border un invariant de sécurité, testable sans bootstrapper
 * le framework : une option dont la VALEUR est un objet stringable (ex. Lang renvoyé
 * par lang()) ne doit jamais passer par (array)$objet — ce cast exposerait les
 * propriétés internes de l'objet, dont __caller (le module appelant). Une fois
 * stringifié, ce module re-rend toute la page courante (Module::__toString), laquelle
 * contient ce même select → récursion infinie (« Maximum call stack size » fatal).
 */
class Options_Encoder
{
	public static function encode($data): string
	{
		if ((is_string($data) || is_object($data)) && method_exists($data, '__toArray'))
		{
			$data = $data->__toArray();
		}

		array_walk($data, function(&$value, $key){
			if (is_object($value))
			{
				$value = method_exists($value, '__toArray') ? $value->__toArray() : [(string)$value];
			}

			$value = array_merge([$key], array_map('utf8_html_entity_decode', (array)$value));
		});

		return utf8_htmlentities(json_encode(array_values($data)));
	}
}
