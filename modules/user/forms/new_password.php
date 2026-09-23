<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$this	->rule($this->form_password('password_new')
					->title('Nouveau mot de passe')
					->size('col-12 col-sm-6')
		)
		->rule($this->form_password('password_confirm')
					->title('Confirmation')
					->size('col-12 col-sm-6')
					->check(function($data){
						if ($data['password_new'] && $data['password_new'] !== $data['password_confirm'])
						{
							return $this->lang('Les mots de passe ne correspondent pas');
						}
					})
		);
