<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$this	->rule($this->form_password('password_new')
					->title('Nouveau mot de passe')
					->info('Au moins 10 caractères.')
					->size('col-12 col-sm-6')
					// Un NOUVEAU mot de passe se mesure (ligne 0.33) ; laissé vide, le mot de passe ne change pas.
					// La règle vit dans User::mot_de_passe_refuse().
					->check(function($data){
						if ((string) $data['password_new'] === '')
						{
							return;
						}

						$refus = \NF\Modules\User\User::mot_de_passe_refuse((string) $data['password_new'], (string) ($data['username'] ?? ''));

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
					->size('col-12 col-sm-6')
					->check(function($data){
						if ($data['password_new'] && $data['password_new'] !== $data['password_confirm'])
						{
							return $this->lang('Les mots de passe ne correspondent pas');
						}
					})
		);
