<?php
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

		$this->add_action($this->button($this->lang('Nouvelle campagne'), 'fas fa-plus', 'primary')->url('admin/donations/new'));

		$campaigns = $this->model()->get_campaigns();
		foreach ($campaigns as &$c) {
			$totals = $this->model()->get_total($c['id']);
			$c['raised'] = $totals['total'];
			$c['count']  = $totals['count'];
			$c['percentage'] = $c['goal_amount'] > 0 ? min(100, round($c['raised'] / $c['goal_amount'] * 100, 1)) : 0;
		}
		unset($c);

		return $this->view('admin/index', ['campaigns' => $campaigns]);
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
					'rules' => 'required'
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

			$saved_id = $this->model()->save_campaign($post, $id);
			notify($this->lang($id ? 'Campagne modifiée' : 'Campagne créée'));
			redirect('admin/donations');
		}

		return $this->view('admin/edit', [
			'form' => $form,
			'id'   => $id,
			'campaign' => $campaign
		]);
	}

	public function _delete($id)
	{
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

		$this->add_action($this->button($this->lang('Ajouter un don'), 'fas fa-plus', 'primary')->url('admin/donations/'.$id.'/donation/add'));

		$donations = $this->model()->get_donations((int)$id, FALSE, FALSE);
		$totals    = $this->model()->get_total((int)$id);

		return $this->view('admin/donations', [
			'campaign'  => $campaign,
			'donations' => $donations,
			'totals'    => $totals
		]);
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
		$donation = $this->model()->get_donation((int)$id);
		if (!$donation) { $this->error(404); return; }
		$cid = $donation['campaign_id'];
		$this->model()->delete_donation((int)$id);
		notify($this->lang('Don supprimé'));
		redirect('admin/donations/'.$cid.'/donations');
	}
}
