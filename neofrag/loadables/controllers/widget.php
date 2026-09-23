<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Loadables\Controllers;

use NF\NeoFrag\Loadables\Controller;

abstract class Widget extends Controller
{
	abstract public function index($config = []);

	public function __construct($caller)
	{
		parent::__construct($this->widget = $caller);
	}

	public function title($title)
	{
		$this->widget->data->append('title', $title);
		return $this;
	}
}
