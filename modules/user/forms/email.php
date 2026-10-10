<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$this	->rule($this->form_email('email')
					->title('Email')
					->required()
					->check(function($data){
						// Répondre « adresse déjà utilisée » dit qu'elle a un compte ici (audit du 2026-10-09) : hors de
						// l'administration, cinq de ces réponses par heure, par réseau (par membre une fois connecté) ; ensuite,
						// plus aucune adresse ne passe pendant une heure — qu'elle soit prise ou non, pour ne rien dire de plus.
						// Une adresse inchangée (le membre enregistre son compte sans y toucher) ne compte pas.
						$actuelle = $this->_values && (int) $this->_values->id ? mb_strtolower(trim((string) $this->_values->email)) : NULL;
						$limite   = NeoFrag()->url->admin || mb_strtolower(trim((string) $data['email'])) === $actuelle ? NULL : new \NF\NeoFrag\Libraries\Rate_Limit($this);
						$cle      = 'adresse_prise:'.(NeoFrag()->user() ? 'uid:'.(int) NeoFrag()->user->id : 'ip:'.\NF\NeoFrag\Libraries\Rate_Limit::bloc_ip());

						if ($limite && $data['email'] && !($etat = $limite->check($cle))['allowed'])
						{
							return $this->lang('Trop de tentatives. Réessayez dans %d minute(s).', (int) ceil($etat['retry_after'] / 60));
						}

						if ($data['email'] && !$this->db()->from('nf_user')->where('email', $data['email'])->where('deleted', FALSE)->where_if($this->_values, 'id <>', $this->_values->id)->empty())
						{
							$limite?->hit($cle, 5, 3600, 3600);

							return $this->lang('Adresse email déjà utilisée');
						}
					})
		);
