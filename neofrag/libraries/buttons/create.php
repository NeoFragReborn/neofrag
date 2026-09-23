<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries\Buttons;

use NF\NeoFrag\Library;

class Create extends Library
{
	public function __invoke($url = '', $title = '', $icon = 'fas fa-plus')
	{
		return $this->button()
					->title($title)
					->url($url)
					->icon($icon)
					->color('primary');
	}
}
