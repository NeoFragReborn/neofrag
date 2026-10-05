<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$this	->rule($this->form_password('password')
					->title('Mot de passe')
					->info('Au moins 10 caractères.')
					->required()
					// Un NOUVEAU mot de passe se mesure (ligne 0.33) : « a » passait, à l'inscription comme à la
					// réinitialisation. La règle vit dans User::mot_de_passe_refuse().
					->check(function($data){
						$refus = \NF\Modules\User\User::mot_de_passe_refuse((string) $data['password'], (string) ($data['username'] ?? ''));

						if ($refus === 'court')
						{
							return $this->lang('Au moins %d caractères.', \NF\Modules\User\User::MOT_DE_PASSE_MIN);
						}

						if ($refus === 'facile')
						{
							return $this->lang('Ce mot de passe est trop facile à deviner : choisis-en un autre.');
						}
					})
		)
		->rule($this->form_password('password_confirm')
					->title('Confirmation')
					->required()
					->check(function($data){
						if ($data['password'] && $data['password'] !== $data['password_confirm'])
						{
							return $this->lang('Les mots de passe ne correspondent pas');
						}
					})
		);
