<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Fields;

class Serialized
{
	public function init($field): void
	{
		$field->default('');
	}

	// Classes autorisées à la désérialisation d'un champ serialized. DateTime/DateTimeZone DOIVENT y
	// figurer : Date encapsule un \DateTime (cf. Date::__sleep). Sans eux, un Date stocké en session
	// (ex. anti_flood) se désérialise avec un _datetime incomplet (__PHP_Incomplete_Class) → fatal dès
	// qu'une méthode est appelée dessus. Ces classes natives ne sont pas des gadgets POP : sûres à autoriser.
	const ALLOWED_CLASSES = [
		\NF\NeoFrag\Libraries\Array_::class,
		\NF\NeoFrag\Libraries\Date::class,
		\DateTime::class,
		\DateTimeZone::class,
	];

	/** Désérialisation bornée (anti-POP) d'un champ serialized. Statique + sans dépendance framework → testable. */
	public static function unserialize_safe($value)
	{
		return unserialize($value, ['allowed_classes' => self::ALLOWED_CLASSES]);
	}

	public function value($value)
	{
		if (is_a($value, 'NF\NeoFrag\Libraries\Array_'))
		{
			return $value;
		}

		return $value ? NeoFrag()->array(self::unserialize_safe($value)) : NeoFrag()->array;
	}

	public function raw($value): string
	{
		$convert = function(&$value) use (&$convert){
			if ((is_string($value) || is_object($value)) && method_exists($value, '__toArray'))
			{
				$value = $value->__toArray();

				array_walk($value, $convert);
			}
			else if (is_a($value, 'NF\NeoFrag\Libraries\Date'))
			{
				$value = $value->sql();
			}
		};

		$convert($value);

		return $value ? serialize($value) : '';
	}
}
