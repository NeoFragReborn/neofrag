<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries\Buttons;

use NF\NeoFrag\Libraries\Button;

class Dropdown extends Button
{
	protected $_dropdown = [];

	public function __invoke()
	{
		parent::__invoke();

		$this->_container = function($content){
			$dropdown = '';

			if ($this->_dropdown)
			{
				$dropdown = $this	->html()
									->attr('class', 'dropdown-menu')
									->content(array_map(function($a){
										return $this->html()
													->attr('class', 'dropdown-item')
													->content($a);
									}, $this->_dropdown));
			}

			return $this->html()
						->attr('class', 'btn-group')
						->content($content.$dropdown);
		};

		$this->_template[] = function(&$content, &$attrs, &$tag){
			$attrs['type'] = 'button';
			$attrs['class'] .= ' dropdown-toggle';
			$tag = 'button';
		};

		// `bs-toggle` : voir Modal::dismiss(). Bootstrap 5 ignore `data-toggle` en silence.
		return $this->data('bs-toggle', 'dropdown');
	}

	public function dropdown($dropdown)
	{
		$this->_dropdown = $dropdown;
		return $this;
	}
}
