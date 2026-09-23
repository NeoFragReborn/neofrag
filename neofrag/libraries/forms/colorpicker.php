<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries\Forms;

class Colorpicker extends Text
{
	public function __invoke($name)
	{
		parent::__invoke($name);

		$this->_check[] = function($post, &$data){
			if (isset($post[$this->_name]) && $post[$this->_name] !== '' && !is_color($post[$this->_name]))
			{
				$this->_errors[] = $this->lang('Couleur invalide');
			}
		};

		$this->_template[] = function(&$input){
			NeoFrag()->js('colorpicker');

			$input->append_attr('class', 'color');
		};

		return $this->addon('fas fa-eye-dropper', 'right')
					->addon($this->label()->title('<i></i>'))
					->size('col-12 col-sm-3');
	}
}
