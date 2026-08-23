<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;

class No_Translate extends Library
{
	protected $_value;

	public function __invoke($value): static
	{
		$this->_value = $value;
		return $this;
	}

	public function __toString(): string
	{
		return (string)$this->_value;
	}
}
