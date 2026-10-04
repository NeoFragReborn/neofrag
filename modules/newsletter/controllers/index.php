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
			$this->_inscrire($post['email']);
			redirect('newsletter');
		}

		$body = '<p>'.$this->lang('Inscris-toi à la newsletter pour recevoir les actualités du site directement par email.').'</p>'.$this->form()->display();

		return $this->panel()->title($this->lang('Newsletter'), 'far fa-envelope')->body($body);
	}

	/**
	 * L'inscription envoyée par le widget « Newsletter ».
	 *
	 * Le widget postait `data[email]` sur la page `newsletter`, dont le formulaire lit ses champs sous
	 * son propre jeton (`Form::is_valid()` → `post($token)`) : l'adresse tapée était ignorée, et le
	 * visiteur arrivait devant un champ vide (relevé le 2026-10-04). Il poste désormais ici, avec le
	 * jeton de session que lui donne le module (`Newsletter::jeton_widget()`), et revient à sa page.
	 */
	public function _subscribe()
	{
		$module = $this->module('newsletter');
		$jeton  = post('_');

		if (strtolower((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'post' || !$module instanceof \NF\Modules\Newsletter\Newsletter
			|| !is_string($jeton) || !hash_equals($module->jeton_widget(), $jeton))
		{
			redirect('newsletter');
		}

		$this->_inscrire(post('email'));
		redirect_back('newsletter');
	}

	/** Inscrit l'adresse et dit au visiteur ce qu'il en est — même parcours pour la page et le widget. */
	private function _inscrire($saisie): void
	{
		/** @var \NF\Modules\Newsletter\Models\Newsletter $modele */
		$modele = $this->model('newsletter');

		// Le frein, par adresse IP et par adresse e-mail (Newsletter::FREIN) : sans lui, n'importe qui
		// pouvait faire envoyer par le site des e-mails de confirmation en masse, à des adresses de
		// son choix. Même mécanisme que le livre d'or.
		$frein = new \NF\NeoFrag\Libraries\Rate_Limit($this);
		$cles  = $modele::cles_du_frein(\NF\NeoFrag\Libraries\Rate_Limit::client_ip(), $modele::adresse($saisie));

		foreach ($cles as $cle)
		{
			$etat = $frein->check($cle);

			if (!$etat['allowed'])
			{
				notify($this->lang('Trop de demandes d\'inscription récentes. Réessaie dans %d minute(s).', (int) ceil($etat['retry_after'] / 60)), 'danger');
				return;
			}
		}

		foreach ($cles as $type => $cle)
		{
			[$essais, $fenetre, $blocage] = $modele::FREIN[$type];
			$frein->hit($cle, $essais, $fenetre, $blocage);
		}

		$resultat = $modele->inscrire($saisie, $this->user() ? (int) $this->user->id : NULL);

		switch ($resultat['statut'])
		{
			case 'invalide':
				notify($this->lang('Cette adresse e-mail n\'est pas valide.'), 'danger');
				break;

			case 'inscrit':
				notify($this->lang('Cet email est déjà inscrit à la newsletter.'));
				break;

			case 'en_attente':
				notify($this->lang('Cet email a déjà demandé l\'inscription. Vérifie ta boîte mail pour le lien de confirmation.'));
				break;

			default:
				// Le lien est ABSOLU : il partait relatif (`/fr/newsletter/confirm/…`), qu'un client de
				// messagerie résout contre son propre domaine — le bouton « Confirmer mon inscription »
				// ne menait nulle part. Même règle que l'inscription des membres (`user.registration`).
				$parti = $this->email	->template('newsletter.confirmation', [
											'confirm_url' => absolute_url('newsletter/confirm/'.$resultat['jeton'])
										])
										->to($resultat['email'])
										->send();

				if ($parti)
				{
					notify($this->lang('Email de confirmation envoyé à %s', $resultat['email']));
				}
				else
				{
					// Sans courriel, l'inscription ne pourrait jamais être confirmée, et l'adresse
					// resterait « déjà demandée » : on la retire, le visiteur pourra réessayer.
					$modele->annuler_inscription($resultat['jeton']);
					notify($this->lang('L\'e-mail de confirmation n\'a pas pu partir. Réessaie un peu plus tard.'), 'danger');
				}
		}
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

		NeoFrag()->db	->where('id', $sub['id'])
						->delete('nf_newsletter_subscribers');

		$body = '<div class="alert alert-success">'
			.'<i class="fas fa-check-circle"></i> '.$this->lang('Tu as bien été désinscrit de la newsletter. <strong>%s</strong> ne recevra plus de newsletters.', htmlspecialchars((string) ($sub['email'])))
			.'</div>';

		return $this->panel()->title($this->lang('Newsletter'), 'far fa-envelope')->body($body);
	}
}
