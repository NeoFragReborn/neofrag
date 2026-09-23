<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Fields;

#[\AllowDynamicProperties]
class Primary
{
	public function init($field): void
	{
		if (!$field->is_text() && !$field->is_depends())
		{
			$field->int();
		}
	}
}
