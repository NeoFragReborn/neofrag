<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Donations\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		$this	->subtitle($this->lang('Campagnes'))
				->icon('fas fa-hand-holding-heart')
				->css('donations');


		$campaigns = $this->model()->get_campaigns();
		foreach ($campaigns as &$c) {
			$totals = $this->model()->get_total($c['id']);
			$c['raised'] = $totals['total'];
			$c['count']  = $totals['count'];
			$c['percentage'] = $c['goal_amount'] > 0 ? min(100, round($c['raised'] / $c['goal_amount'] * 100, 1)) : 0;
		}
		unset($c);

		// Enveloppe partagee : meme carte, meme en-tete, meme etat vide que les 34 autres ecrans
		// d'administration. La vue ne rend plus que le tableau.
		$corps = $this->view('admin/index', [
			'campaigns' => $campaigns,
			'csrf'      => $this->csrf_token(),
			'vide'      => $this->admin_empty('fas fa-bullseye', $this->lang('Aucune campagne.'), $this->lang('Créez-en une pour commencer.')),
		]);

		return $this->admin_card('fas fa-bullseye', $this->lang('Campagnes de dons'), $corps,
			$campaigns ? count($campaigns).' '.$this->lang(count($campaigns) > 1 ? 'campagnes' : 'campagne') : '',
			$this->admin_create('admin/donations/new', $this->lang('Nouvelle campagne')))
			.$this->_services($campaigns);
	}

	/**
	 * Les services reliés (m16, 2026-10-16) : un don fait sur Ko-fi, HelloAsso, TipeeeStream ou par le bouton PayPal
	 * s'enregistre de lui-même dans la campagne choisie. Les clés se rangent chiffrées et ne se relisent pas : un champ
	 * vide garde celle qui est enregistrée.
	 */
	private function _services(array $campagnes): string
	{
		$choix  = [0 => $this->lang('Aucune — le service n’est pas relié')];

		foreach ($campagnes as $c)
		{
			$choix[(int) $c['id']] = $c['title'] ?: $c['name'];
		}

		$garde  = fn (string $reglage): string => $this->config->{$reglage} ? ' '.$this->lang('Une clé est enregistrée, chiffrée. Laissez vide pour la garder.') : '';
		// Les libellés arrivent traduits (`lang()` rend un objet qui s'écrit en texte) : les fermetures ne les typent pas.
		$secret = fn ($libelle, string $reglage, $aide): array => ['label' => $libelle, 'type' => 'password', 'value' => '', 'description' => $aide.$garde($reglage)];
		$coche  = fn (string $reglage, $libelle): array => ['type' => 'checkbox', 'checked' => ['on' => (bool) $this->config->{$reglage}], 'values' => ['on' => $libelle]];

		$form = $this->form()
			->add_rules([
				'kofi_token'          => $secret($this->lang('Ko-fi — jeton de vérification'), 'nf_donations_kofi_token', $this->lang('Sur Ko-fi : Plus → API → Webhooks. Adresse du webhook : %s ; le jeton est affiché sous « Advanced ».', '<code>'.absolute_url('donations/webhook/kofi').'</code>')),
				'kofi_campaign'       => ['label' => $this->lang('Ko-fi — campagne'), 'type' => 'select', 'values' => $choix, 'value' => (int) $this->config->nf_donations_kofi_campaign],
				'helloasso_client_id' => ['label' => $this->lang('HelloAsso — identifiant du client'), 'value' => (string) $this->config->nf_donations_helloasso_client_id, 'description' => $this->lang('Dans l’espace de l’association : Mon compte → Intégrations et API. Adresse de notification à y donner : %s.', '<code>'.absolute_url('donations/webhook/helloasso').'</code>')],
				'helloasso_secret'    => $secret($this->lang('HelloAsso — secret du client'), 'nf_donations_helloasso_secret', ''),
				'helloasso_sandbox'   => $coche('nf_donations_helloasso_sandbox', $this->lang('HelloAsso — bac à sable (essais)')),
				'helloasso_campaign'  => ['label' => $this->lang('HelloAsso — campagne des dons'), 'type' => 'select', 'values' => $choix, 'value' => (int) $this->config->nf_donations_helloasso_campaign],
				'helloasso_days'      => ['label' => $this->lang('HelloAsso — durée d’une adhésion (jours)'), 'type' => 'number', 'value' => (int) $this->config->nf_donations_helloasso_days, 'description' => $this->lang('Une adhésion payée sur HelloAsso rend adhérent sur le site le membre qui a la même adresse (module Paiements). 0 : pas d’adhésion.')],
				'tipeeestream_key'    => $secret($this->lang('TipeeeStream — clé d’API'), 'nf_donations_tipeeestream_key', $this->lang('Sur TipeeeStream : Paramètres → API. Les dons sont relevés par la tâche planifiée, à partir du jour où la clé est enregistrée.')),
				'tipeeestream_campaign' => ['label' => $this->lang('TipeeeStream — campagne'), 'type' => 'select', 'values' => $choix, 'value' => (int) $this->config->nf_donations_tipeeestream_campaign],
				'paypal_ipn'          => $coche('nf_donations_paypal_ipn', $this->lang('PayPal — enregistrer les dons d’eux-mêmes (notification instantanée)')),
				'paypal_sandbox'      => $coche('nf_donations_paypal_sandbox', $this->lang('PayPal — bac à sable (essais)')),
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		if ($form->is_valid($post))
		{
			foreach (['kofi_token' => 'nf_donations_kofi_token', 'helloasso_secret' => 'nf_donations_helloasso_secret', 'tipeeestream_key' => 'nf_donations_tipeeestream_key'] as $champ => $reglage)
			{
				if (trim((string) ($post[$champ] ?? '')) !== '')
				{
					// Relier TipeeeStream ne réimporte pas son historique : les dons comptent à partir d'aujourd'hui.
					if ($reglage === 'nf_donations_tipeeestream_key' && !$this->config->nf_donations_tipeeestream_key)
					{
						$this->config('nf_donations_tipeeestream_since', time(), 'int');
					}

					$this->config($reglage, $this->crypt->encrypt_secret(trim((string) $post[$champ])));
				}
			}

			foreach (['kofi_campaign', 'helloasso_campaign', 'tipeeestream_campaign', 'helloasso_days'] as $champ)
			{
				$this->config('nf_donations_'.$champ, max(0, (int) ($post[$champ] ?? 0)), 'int');
			}

			$this	->config('nf_donations_helloasso_client_id', mb_substr(trim((string) ($post['helloasso_client_id'] ?? '')), 0, 100))
					->config('nf_donations_helloasso_sandbox', in_array('on', (array) ($post['helloasso_sandbox'] ?? []), TRUE), 'bool')
					->config('nf_donations_paypal_ipn', in_array('on', (array) ($post['paypal_ipn'] ?? []), TRUE), 'bool')
					->config('nf_donations_paypal_sandbox', in_array('on', (array) ($post['paypal_sandbox'] ?? []), TRUE), 'bool');

			notify($this->lang('Services reliés enregistrés'));
			redirect('admin/donations');
		}

		return $this->admin_card('fas fa-plug', $this->lang('Services reliés'), '<p class="text-muted">'.$this->lang('Un don fait sur l’un de ces services s’enregistre de lui-même dans la campagne choisie, une seule fois. Les donateurs n’y apparaissent pas publiquement, sauf ceux de Ko-fi et de TipeeeStream qui l’ont voulu ; le total, lui, compte tous les dons.').'</p>'.$form->display());
	}

	public function _new()
	{
		return $this->_edit_form(NULL);
	}

	public function _edit($id)
	{
		return $this->_edit_form((int)$id);
	}

	private function _edit_form($id)
	{
		$campaign = $id ? $this->model()->get_campaign($id) : NULL;
		if ($id && !$campaign) { $this->error(404); return; }

		$langue = (string) $this->config->lang->info()->name;

		if ($campaign)
		{
			// Le texte TEL QU'IL EST ÉCRIT dans la langue de l'administration (vide si rien n'y est encore écrit).
			$campaign = array_merge($campaign, $this->model()->texte_dans((int)$campaign['id'], $langue));
		}

		$this	->subtitle($id ? $this->lang('Modifier la campagne') : $this->lang('Nouvelle campagne'))
				->icon('fas fa-bullseye')
				->css('donations');
		$this->breadcrumb($this->lang('Campagnes'), 'admin/donations');

		$form = $this->form()
			->add_rules([
				'name' => [
					'label' => $this->lang('Identifiant URL (slug)'),
					'value' => $campaign['name'] ?? '',
					'rules' => 'required',
					'description' => $this->lang('Visible dans l\'URL : /donations/<strong>identifiant</strong>. Lettres, chiffres, tirets uniquement.')
				],
				'title' => [
					'label' => $this->lang('Titre'),
					'value' => $campaign['title'] ?? '',
					'rules' => 'required',
					'description' => $this->lang('Écrit pour %s. Change la langue de l\'administration pour traduire cette campagne ; les autres langues gardent leur texte.', $this->config->lang->info()->title)
				],
				'description' => [
					'label' => $this->lang('Description'),
					'value' => $campaign['description'] ?? '',
					'type'  => 'textarea',
					'description' => $this->lang('Markdown ou HTML autorisé.')
				],
				'goal_amount' => [
					'label' => $this->lang('Objectif (montant)'),
					'value' => $campaign['goal_amount'] ?? '',
					'type'  => 'number',
					'rules' => 'required',
					'size'  => 'col-3'
				],
				'currency' => [
					'label' => $this->lang('Devise'),
					'value' => $campaign['currency'] ?? 'EUR',
					'type'  => 'select',
					'values'=> ['EUR' => 'EUR (€)', 'USD' => 'USD ($)', 'GBP' => 'GBP (£)', 'CAD' => 'CAD ($)', 'CHF' => 'CHF'],
					'size'  => 'col-3'
				],
				'paypal_email' => [
					'label' => $this->lang('Email PayPal'),
					'value' => $campaign['paypal_email'] ?? '',
					'type'  => 'email',
					'description' => $this->lang('L\'adresse PayPal qui recevra les dons. Si vide et bouton hosted absent, le bouton est désactivé.')
				],
				'paypal_button_id' => [
					'label' => $this->lang('Bouton PayPal hébergé (Hosted Button ID)'),
					'value' => $campaign['paypal_button_id'] ?? '',
					'description' => $this->lang('Optionnel. Si vous avez créé un bouton "Faire un don" sur PayPal Business, son ID est dans l\'URL : <code>hosted_button_id=<strong>XXXXXX</strong></code>. Prend la priorité sur l\'email.')
				],
				'deadline' => [
					'label' => $this->lang('Échéance (optionnel)'),
					'value' => $campaign['deadline'] ?? '',
					'type'  => 'date',
					'size'  => 'col-4'
				],
				'status' => [
					'label' => $this->lang('Statut'),
					'value' => $campaign['status'] ?? 'active',
					'type'  => 'select',
					'values'=> ['active' => $this->lang('Active'), 'paused' => $this->lang('En pause'), 'archived' => $this->lang('Archivée')],
					'size'  => 'col-3'
				]
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		if ($form->is_valid($post))
		{
			$post['name'] = preg_replace('/[^a-z0-9-]/', '-', strtolower($post['name']));
			$post['name'] = trim(preg_replace('/-+/', '-', $post['name']), '-');
			if ($post['name'] === '') $post['name'] = 'campagne-'.time();

			if (!$post['deadline']) $post['deadline'] = NULL;

			$saved_id = $this->model()->save_campaign($post, $id, $langue);
			notify($this->lang($id ? 'Campagne modifiée' : 'Campagne créée'));
			redirect('admin/donations');
		}

		return $this->admin_card('fas fa-pen',
			$id ? $this->lang('Modifier la campagne') : $this->lang('Nouvelle campagne'),
			$form->display());
	}

	public function _delete($id)
	{
		$this->check_csrf('admin/donations');

		$campaign = $this->model()->get_campaign((int)$id);
		if (!$campaign) { $this->error(404); return; }

		$this->model()->delete_campaign((int)$id);
		notify($this->lang('Campagne supprimée'));
		redirect('admin/donations');
	}

	public function _donations($id)
	{
		$campaign = $this->model()->get_campaign((int)$id);
		if (!$campaign) { $this->error(404); return; }

		$this	->subtitle($this->lang('Dons — %s', $campaign['title']))
				->icon('fas fa-list')
				->css('donations');
		$this->breadcrumb($this->lang('Campagnes'), 'admin/donations');
		$this->breadcrumb($campaign['title']);


		$donations = $this->model()->get_donations((int)$id, FALSE, FALSE);
		$totals    = $this->model()->get_total((int)$id);

		$corps = $this->view('admin/donations', [
			'campaign'  => $campaign,
			'donations' => $donations,
			'csrf'      => $this->csrf_token(),
			'totals'    => $totals,
			'vide'      => $this->admin_empty('fas fa-list', $this->lang('Aucun don pour le moment.')),
		]);

		return $this->admin_card('fas fa-list', $campaign['title'], $corps,
			(int) $totals['count'].' '.$this->lang($totals['count'] > 1 ? 'dons' : 'don')
			.' · '.number_format($totals['total'], 2, ',', ' ').' '.nf_texte($campaign['currency']),
			$this->admin_create('admin/donations/'.$id.'/donation/add', $this->lang('Ajouter un don')));
	}

	public function _donation_add($id)
	{
		return $this->_donation_form((int)$id, NULL);
	}

	public function _donation_edit($id)
	{
		$donation = $this->model()->get_donation((int)$id);
		if (!$donation) { $this->error(404); return; }
		return $this->_donation_form((int)$donation['campaign_id'], (int)$id);
	}

	private function _donation_form($campaign_id, $donation_id)
	{
		$campaign = $this->model()->get_campaign($campaign_id);
		if (!$campaign) { $this->error(404); return; }

		$donation = $donation_id ? $this->model()->get_donation($donation_id) : NULL;

		$this	->subtitle($donation_id ? $this->lang('Modifier le don') : $this->lang('Ajouter un don'))
				->icon('fas fa-edit');
		$this->breadcrumb($this->lang('Campagnes'), 'admin/donations');
		$this->breadcrumb($campaign['title'], 'admin/donations/'.$campaign['id'].'/donations');

		$form = $this->form()
			->add_rules([
				'donor_name' => [
					'label' => $this->lang('Nom du donateur'),
					'value' => $donation['donor_name'] ?? '',
					'rules' => 'required'
				],
				'amount' => [
					'label' => $this->lang('Montant'),
					'value' => $donation['amount'] ?? '',
					'type'  => 'number',
					'rules' => 'required',
					'size'  => 'col-3'
				],
				'currency' => [
					'label' => $this->lang('Devise'),
					'value' => $donation['currency'] ?? $campaign['currency'],
					'type'  => 'select',
					'values'=> ['EUR' => 'EUR', 'USD' => 'USD', 'GBP' => 'GBP', 'CAD' => 'CAD', 'CHF' => 'CHF'],
					'size'  => 'col-3'
				],
				'message' => [
					'label' => $this->lang('Message'),
					'value' => $donation['message'] ?? '',
					'type'  => 'textarea'
				],
				'is_anonymous' => [
					'type'    => 'checkbox',
					'checked' => ['on' => !empty($donation['is_anonymous'])],
					'values'  => ['on' => $this->lang('Don anonyme (le nom n\'est pas affiché publiquement)')]
				],
				'is_public' => [
					'type'    => 'checkbox',
					'checked' => ['on' => $donation === NULL ? TRUE : !empty($donation['is_public'])],
					'values'  => ['on' => $this->lang('Afficher dans la liste publique')]
				],
				'status' => [
					'label' => $this->lang('Statut'),
					'value' => $donation['status'] ?? 'completed',
					'type'  => 'select',
					'values'=> ['pending' => $this->lang('En attente'), 'completed' => $this->lang('Validé'), 'refunded' => $this->lang('Remboursé')]
				]
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		if ($form->is_valid($post))
		{
			$data = [
				'campaign_id'  => $campaign_id,
				'donor_name'   => $post['donor_name'],
				'amount'       => (float)$post['amount'],
				'currency'     => $post['currency'],
				'message'      => $post['message'],
				'is_anonymous' => in_array('on', (array)$post['is_anonymous']) ? 1 : 0,
				'is_public'    => in_array('on', (array)$post['is_public']) ? 1 : 0,
				'status'       => $post['status'],
				'source'       => 'manual'
			];
			$this->model()->save_donation($data, $donation_id);
			notify($this->lang($donation_id ? 'Don modifié' : 'Don ajouté'));
			redirect('admin/donations/'.$campaign_id.'/donations');
		}

		return $this->admin_back('admin/donations/'.$campaign_id.'/donations', $this->lang('Donations')).$this->admin_card('fas fa-edit', $donation_id ? $this->lang('Modifier le don') : $this->lang('Ajouter un don'), $form->display());
	}

	public function _donation_delete($id)
	{
		$this->check_csrf('admin/donations');

		$donation = $this->model()->get_donation((int)$id);
		if (!$donation) { $this->error(404); return; }
		$cid = $donation['campaign_id'];
		$this->model()->delete_donation((int)$id);
		notify($this->lang('Don supprimé'));
		redirect('admin/donations/'.$cid.'/donations');
	}
}
