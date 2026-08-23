<?php
declare(strict_types=1);

namespace NF\Modules\Pages\Lib;

/**
 * Palier 0 page-builder — parsing/validation PURE des paramètres d'un bloc `[block:clé p=v …]`
 * (aucune dépendance au service locator → unit-testable directement). Le modèle pages garde le
 * registre et le rendu ; il délègue ici l'extraction + la coercition des paramètres contre les
 * `fields` déclarés par le bloc. Seuls les champs DÉCLARÉS passent (params inconnus ignorés) et
 * sont coercés par type → la frontière reste sûre quel que soit le contenu de la page.
 */
final class Block_Settings
{
	/**
	 * @param  array  $def    définition du bloc (peut porter 'fields' => [nom => spec])
	 * @param  string $params ce qui suit la clé dans le shortcode (ex. " id=3 count=5")
	 * @return array  settings validés (nom → valeur), defaults appliqués
	 */
	public static function parse(array $def, string $params): array
	{
		$raw      = self::tokenize($params);
		$settings = [];

		foreach ($def['fields'] ?? [] as $name => $spec)
		{
			$settings[$name] = self::coerce((array) $spec, $raw[strtolower((string) $name)] ?? null);
		}

		return $settings;
	}

	/** `id=3 count=5` ou `a="x y"` → ['id'=>'3','count'=>'5',…]. Valeurs nues ou quotées. */
	public static function tokenize(string $params): array
	{
		$raw = [];

		if (preg_match_all('/([a-z0-9_]+)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s\]]+)/i', $params, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $m)
			{
				$raw[strtolower($m[1])] = trim($m[2], '"\'');
			}
		}

		return $raw;
	}

	/**
	 * Valide un tableau de valeurs (ex. settings d'instance / composer) contre les `fields`.
	 * La présence se mesure par la clé (pas la « vacuité ») → un `false`/`0` natif passe tel
	 * quel au lieu de retomber sur le défaut. Seuls les champs déclarés sortent.
	 */
	public static function from_array(array $def, array $values): array
	{
		$settings = [];

		foreach ($def['fields'] ?? [] as $name => $spec)
		{
			$spec = (array) $spec;

			$settings[$name] = array_key_exists($name, $values)
				? self::coerce_value($spec, $values[$name])
				: ($spec['default'] ?? null);
		}

		return $settings;
	}

	/** Coerce une valeur de shortcode (chaîne) : vide/absente → défaut, sinon coercition par type. */
	public static function coerce(array $spec, $value)
	{
		if ($value === null || $value === '')
		{
			return $spec['default'] ?? null;
		}

		return self::coerce_value($spec, $value);
	}

	/** Coercition par type (int|bool|string + min/max/max_length). Accepte les types natifs JSON. */
	public static function coerce_value(array $spec, $value)
	{
		switch ($spec['type'] ?? 'string')
		{
			case 'int':
				$value = (int) $value;
				if (isset($spec['min']))
				{
					$value = max((int) $spec['min'], $value);
				}
				if (isset($spec['max']))
				{
					$value = min((int) $spec['max'], $value);
				}
				return $value;

			case 'bool':
				return is_bool($value) ? $value : in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);

			default:
				$value = (string) $value;
				if (isset($spec['max_length']))
				{
					$value = mb_substr($value, 0, (int) $spec['max_length']);
				}
				return $value;
		}
	}
}
