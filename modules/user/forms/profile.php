<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$this	->rule($this->form_text('first_name')
					->title($this->lang('Prénom'))
					->size('col-6')
		)
		->rule($this->form_text('last_name')
					->title($this->lang('Nom'))
					->size('col-6')
		)
		->rule($this->form_date('date_of_birth')
					->title($this->lang('Date de naissance'))
					->check(function($post, $data){
						if (!is_empty($data['date_of_birth']) && $this->date($data['date_of_birth'])->diff() > 0)
						{
							return $this->lang('Date de naissance invalide');
						}
					})
					->size('col-6')
		)
		->rule($this->form_radio('sex')
					->title($this->lang('Sexe'))
					->data([
						'female' => $this->lang('Femme'),
						'male'   => $this->lang('Homme')
					])
					->size('col-6')
		)
		->rule($this->form_select('country')
					->title($this->lang('Pays'))
					->data(get_countries())
					->size('col-6')
		)
		->rule($this->form_select('timezone')
					->title($this->lang('Fuseau horaire'))
					->data(['' => $this->lang('Fuseau par défaut du site')] + array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
					->size('col-6')
		)
		->rule($this->form_text('location')
					->title($this->lang('Localisation'))
					->size('col-6')
		)
		->rule($this->form_text('quote')
					->title($this->lang('Citation'))
		)
		->rule($this->form_textarea('signature')
					->title($this->lang('Signature'))
					->rows(5)
					->editor()
		)
		->success(function($profile){
			$profile->commit();
			notify($this->lang('Profil modifié'));
			refresh();
		});
