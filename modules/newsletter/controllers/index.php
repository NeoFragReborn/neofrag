<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Newsletter\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index()
	{
		$this	->title($this->lang('Newsletter'))
				->icon('far fa-envelope')
				->breadcrumb()
				->form()
				->add_rules([
					'email' => [
						'label' => $this->lang('Adresse email'),
						'type'  => 'email',
						'rules' => 'required'
					]
				])
				->add_submit($this->lang('S\'inscrire'), 'fas fa-envelope-open-text');

		if ($this->form()->is_valid($post))
		{
			$email = strtolower(trim($post['email']));

			// Check si déjà inscrit
			$existing = NeoFrag()->db	->select('id', 'confirmed')
										->from('nf_newsletter_subscribers')
										->where('email', $email)
										->row();

			if (!empty($existing))
			{
				if ($existing['confirmed'])
				{
					notify($this->lang('Cet email est déjà inscrit à la newsletter.'));
				}
				else
				{
					notify($this->lang('Cet email a déjà demandé l\'inscription. Vérifie ta boîte mail pour le lien de confirmation.'));
				}
				redirect('newsletter');
			}

			$token = bin2hex(random_bytes(32));

			NeoFrag()->db->insert('nf_newsletter_subscribers', [
				'email'   => $email,
				'token'   => $token,
				'user_id' => $this->user() ? $this->user->id : NULL
			]);

			$confirm_url = url('newsletter/confirm/'.$token);
			$unsub_url   = url('newsletter/unsubscribe/'.$token);

			$this	->email
					->template('newsletter.confirmation', [
						'confirm_url' => $confirm_url
					])
					->to($email)
					->send();

			notify($this->lang('Email de confirmation envoyé à %s', $email));
			redirect('newsletter');
		}

		$body = '<p>'.$this->lang('Inscris-toi à la newsletter pour recevoir les actualités du site directement par email.').'</p>'.$this->form()->display();

		return $this->panel()->title($this->lang('Newsletter'), 'far fa-envelope')->body($body);
	}

	public function _confirm($sub)
	{
		$this->title($this->lang('Confirmation newsletter'))->icon('far fa-envelope')->breadcrumb();

		if ($sub['confirmed'])
		{
			$body = '<div class="alert alert-info">'.$this->lang('Email déjà confirmé. Tu es bien inscrit à la newsletter.').'</div>';
		}
		else
		{
			NeoFrag()->db	->where('id', $sub['id'])
							->update('nf_newsletter_subscribers', [
								'confirmed' => 1,
								'confirmed_at' => NeoFrag()->date()->sql()
							]);

			$body = '<div class="alert alert-success">'
				.'<i class="fas fa-check-circle"></i> '.$this->lang('Inscription confirmée ! Tu recevras les prochaines newsletters à <strong>%s</strong>.', htmlspecialchars((string) ($sub['email'])))
				.'</div>';
		}

		return $this->panel()->title($this->lang('Newsletter'), 'far fa-envelope')->body($body);
	}

	public function _track($token)
	{
		/** @var \NF\Modules\Newsletter\Models\Newsletter $model */
		$model = $this->model('newsletter');
		$model->record_open($token);

		if (!headers_sent())
		{
			header('Content-Type: image/gif');
			header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
			header('Pragma: no-cache');
		}

		// GIF transparent 1×1 — l'email client ne voit jamais d'image cassée.
		exit(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
	}

	public function _unsubscribe($sub)
	{
		$this->title($this->lang('Désinscription'))->icon('far fa-envelope')->breadcrumb();

		NeoFrag()->db	->from('nf_newsletter_subscribers')
						->where('id', $sub['id'])
						->delete();

		$body = '<div class="alert alert-success">'
			.'<i class="fas fa-check-circle"></i> '.$this->lang('Tu as bien été désinscrit de la newsletter. <strong>%s</strong> ne recevra plus de newsletters.', htmlspecialchars((string) ($sub['email'])))
			.'</div>';

		return $this->panel()->title($this->lang('Newsletter'), 'far fa-envelope')->body($body);
	}
}
