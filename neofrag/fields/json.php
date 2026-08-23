<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Champ JSON léger : stockage de structures (arrays de scalaires/sous-arrays) en JSON
 * plutôt qu'en PHP-serialized. Sert aux settings de widgets et aux futures colonnes
 * structurées (ex. pages_instances.settings). decode() reste rétro-compatible avec le
 * legacy PHP-serialized le temps que les lignes soient ré-enregistrées (jamais d'objet
 * désérialisé : allowed_classes=false). Statique + sans dépendance framework → testable.
 */

namespace NF\NeoFrag\Fields;

class Json
{
	public function init($field): void
	{
		$field->default('');
	}

	/** Valeur stockée → array. Accepte JSON (nouveau) OU PHP-serialized legacy (sans objets). */
	public static function decode($value): array
	{
		if (!is_string($value) || ($value = trim($value)) === '')
		{
			return [];
		}

		if ($value[0] === '{' || $value[0] === '[')
		{
			$decoded = json_decode($value, TRUE);

			return is_array($decoded) ? $decoded : [];
		}

		// Legacy PHP-serialized — disparaît au prochain ré-enregistrement (allowed_classes=false : anti-POP).
		$decoded = @unserialize($value, ['allowed_classes' => FALSE]);

		return is_array($decoded) ? $decoded : [];
	}

	/** array (ou Array_, à toute profondeur) → JSON compact. '' si vide. */
	public static function encode($value): string
	{
		$value = self::normalize($value);

		return ($value === NULL || $value === [] || $value === '') ? '' : (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	/** Convertit récursivement les Array_ du framework en arrays PHP nus (json_encode-able). */
	public static function normalize($value)
	{
		if (is_object($value) && method_exists($value, '__toArray'))
		{
			$value = $value->__toArray();
		}

		if (is_array($value))
		{
			return array_map([self::class, 'normalize'], $value);
		}

		return $value;
	}

	public function value($value)
	{
		return NeoFrag()->array(self::decode($value));
	}

	public function raw($value): string
	{
		return self::encode($value);
	}
}
