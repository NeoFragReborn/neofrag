<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;

class Js_Load extends Library
{
	protected $_script;

	public function __invoke($script): static
	{
		$this->_script = $script;

		$this->output->data->append('js_load', $this);

		return $this;
	}

	public function __toString(): string
	{
		return (string)$this->_script;
	}
}
