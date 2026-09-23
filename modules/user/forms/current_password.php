<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$this	->rule($this->form_password('password')
					->title('Mot de passe actuel')
					->value('')
					->check(function($data){
						if ($data['password'] && !$this->_values->password($data['password']))
						{
							return $this->lang('Mot de passe incorrect');
						}
					})
					->required()
		);
