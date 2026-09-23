<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries\Forms;

class Url extends Text
{
	public function __invoke($name)
	{
		parent::__invoke($name);

		$this->_check[] = function($post, &$data){
			if (isset($post[$this->_name]) && $post[$this->_name] !== '' && !is_valid_url($post[$this->_name]))
			{
				$this->_errors[] = $this->lang('Veuillez entrer une adresse url valide');
			}
		};

		return $this->addon('fas fa-globe');
	}
}
