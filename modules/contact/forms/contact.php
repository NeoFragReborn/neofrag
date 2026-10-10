<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$this->rule($this->form_text('subject')
				->title($this->lang('Votre objet'))
				->required())
	->rule_if(!$this->user->email, $this->form_email('email')
										->title($this->lang('Votre adresse email'))
										->required())
	->rule($this->form_textarea('message')
				->title($this->lang('Votre message'))
				->required())
	->captcha()
	->submit($this->lang('Envoyer'))
	->success(function($data, $form){
		// R2.0 — Rate limit anti-spam : 3 contacts par IP / heure
		$rateLimit = new \NF\NeoFrag\Libraries\Rate_Limit($this);
		$rl_key    = 'contact:ip:'.\NF\NeoFrag\Libraries\Rate_Limit::bloc_ip();
		$rl_check  = $rateLimit->check($rl_key);
		if (!$rl_check['allowed'])
		{
			$form->error($this->lang('Trop de messages récents. Réessaye dans %d minute(s).', ceil($rl_check['retry_after'] / 60)));
			return;
		}
		$rateLimit->hit($rl_key, 3, 3600, 3600);

		$sent = $this	->anti_flood()
						->email
						// L'expéditeur reste l'adresse du site (défaut de la bibliothèque) : écrire « De : visiteur@gmail.com »
						// depuis notre serveur, c'est usurper son domaine — SPF/DMARC le rejettent. Le visiteur va en Reply-To.
						->reply_to($this->user->email ?: $data['email'], $this->user() ? $this->user->username : '')
						->to($this->config->nf_contact)
						->subject($data['subject'])
						->message(function() use ($data){
							return [
								'content' => nl2br(strtolink($data['message'])).($this->user() ? '<br /><br />'.$this->user->view('profile') : '')
							];
						})
						->send();

		if ($sent)
		{
			notify($this->lang('Message envoyé'));
			$this->modal->dispose();
		}
		else
		{
			$form->error($this->lang('Une erreur s\'est produite lors de l\'envoi du message'));
		}
	});
