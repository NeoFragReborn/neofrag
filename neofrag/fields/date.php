<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Fields;

class Date extends DateTime
{
	public function raw($value): string
	{
		return substr(parent::raw($value), 0, 10);
	}
}
