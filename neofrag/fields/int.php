<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Fields;

class Int_
{
	public function init($field): void
	{
		$field->default(0);
	}

	public function value($value): int
	{
		return (int)$value;
	}

	public function raw($value): int
	{
		return (int)$value;
	}
}
