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
						// Avec la limite d'essais de la confirmation (User::confirmation_refusee()).
						if ($data['password'] && ($module = NeoFrag()->module('user')) instanceof \NF\Modules\User\User)
						{
							return $module->confirmation_refusee($this->_values, (string) $data['password']);
						}
					})
					->required()
		);
