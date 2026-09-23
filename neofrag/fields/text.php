<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Fields;

#[\AllowDynamicProperties]
class Text
{
	protected $_size;

	public function __construct($size = NULL)
	{
		$this->_size = $size;
	}

	public function init($field): void
	{
		$field->default('');
	}

	public function value($value): string
	{
		return (string)$value;
	}
}
