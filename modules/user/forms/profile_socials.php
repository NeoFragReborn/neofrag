<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$this	->rule($this->form_url('website')
					->title($this->lang('Site web'))
		)
		->rule($this->form_text('linkedin')
					->title('Linkedin')
					->info('linkedin.com/in/<b>[xxxxx-xxx-xxx]</b>')
					->addon('fab fa-linkedin-in')
		)
		->rule($this->form_text('github')
					->title('GitHub')
					->info($this->lang('GitHub account name'))
					->addon('fab fa-github')
		)
		->rule($this->form_text('instagram')
					->title('Instagram')
					->info($this->lang('Instagram account name'))
					->addon('fab fa-instagram')
		)
		->rule($this->form_text('twitch')
					->title('Twitch')
					->info($this->lang('Twitch account name'))
					->addon('fab fa-twitch')
		)
		->success(function($profile){
			$profile->commit();
			notify($this->lang('Liens modifiés'));
			refresh();
		});
