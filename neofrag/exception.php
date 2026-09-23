<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag;

#[\AllowDynamicProperties]
class Exception extends \Exception
{
	protected $_callback;

	public function __construct($callback)
	{
		$this->_callback = $callback;
	}

	public function __toString()
	{
		return (string)$this();
	}

	public function __invoke()
	{
		return call_user_func($this->_callback);
	}
}
