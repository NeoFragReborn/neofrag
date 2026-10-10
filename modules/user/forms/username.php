<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$this	->rule($this->form_text('username')
					->title('Identifiant')
					->required()
					->check(function($data){
						if ($data['username'] && !$this->db()->from('nf_user')->where('username', $data['username'])->where('deleted', FALSE)->where_if($this->_values, 'id <>', $this->_values->id)->empty())
						{
							return $this->lang('Identifiant déjà pris');
						}

						// Un membre qui CHANGE son identifiant (Mon compte) : un bannissement du profil le fige, la restriction
						// « Liens externes » refuse un identifiant qui porte un lien (2026-10-09). Le reste du compte (mot de
						// passe, adresse) reste modifiable. Le membre lui-même seulement : l'administration, qui édite un membre
						// avec ce même formulaire (Models\User\Update), n'est pas concernée.
						$decode = fn ($texte) => html_entity_decode((string) $texte, ENT_QUOTES | ENT_HTML5, 'UTF-8');

						if ($this->_values && (int) $this->_values->id && !NeoFrag()->url->admin
							&& (int) $this->_values->id === (int) NeoFrag()->user->id
							&& $decode($data['username']) !== $decode($this->_values->username))
						{
							$uid = (int) $this->_values->id;

							if (NeoFrag()->moderation->is_blocked_for($uid, 'user.profile_edit'))
							{
								return $this->lang('Une sanction de modération t’empêche de changer ton identifiant');
							}

							if ($refus = NeoFrag()->moderation->lien_refuse($uid, $decode($data['username'])))
							{
								return $refus['message'];
							}
						}
					})
		);
