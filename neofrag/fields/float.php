<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Fields;

class Float_
{
	public function init($field): void
	{
		$field->default(0);
	}

	public function value($value): float
	{
		return (float)$value;
	}

	public function raw($value): float
	{
		return (float)$value;
	}
}
