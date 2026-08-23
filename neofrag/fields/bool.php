<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Fields;

class Bool_
{
	public function init($field): void
	{
		$field->default('0');
	}

	public function value($value): bool
	{
		return (bool)$value;
	}

	public function raw($value): string
	{
		return (string)(int)$value;
	}
}
