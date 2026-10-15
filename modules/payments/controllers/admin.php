<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Paiements — admin : réglages Stripe (clés) + catalogue des packs.
 */

namespace NF\Modules\Payments\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		$this->subtitle($this->lang('Stripe'))->icon('fas fa-credit-card');

		$form = $this->form()
			->add_rules([
				'enabled' => [
					'type'    => 'checkbox',
					'checked' => ['on' => $this->config->pay_stripe_enabled],
					'values'  => ['on' => $this->lang('Activer les paiements Stripe')]
				],
				'public' => ['label' => $this->lang('Clé publique (publishable)'), 'value' => $this->config->pay_stripe_public],
				// Les deux secrets ne se relisent plus (audit du 2026-10-09) : ils s'affichaient en clair dans la page, à qui
				// regardait l'écran ou le code de la page. Enregistrés chiffrés, comme le mot de passe SMTP ; vide = gardé.
				'secret' => [
					'label'       => $this->lang('Clé secrète'),
					'type'        => 'password',
					'value'       => '',
					'description' => $this->config->pay_stripe_secret ? $this->lang('Une clé est enregistrée, chiffrée. Laissez vide pour la garder.') : '',
				],
				'webhook_secret' => [
					'label'       => $this->lang('Secret de signature du webhook'),
					'type'        => 'password',
					'value'       => '',
					'description' => $this->lang('Dans Stripe, crée un webhook sur <code>%s</code> pour l\'événement <code>checkout.session.completed</code>, et colle ici son secret de signature.', absolute_url('payments/webhook'))
						.($this->config->pay_stripe_webhook_secret ? ' '.$this->lang('Une clé est enregistrée, chiffrée. Laissez vide pour la garder.') : ''),
				],
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		if ($form->is_valid($post))
		{
			$this	->config('pay_stripe_enabled',        in_array('on', (array)$post['enabled']), 'bool')
					->config('pay_stripe_public',         $post['public']);

			// Un secret ne se réécrit que s'il est saisi (le formulaire rend NULL pour un champ vide).
			foreach (['secret' => 'pay_stripe_secret', 'webhook_secret' => 'pay_stripe_webhook_secret'] as $champ => $reglage)
			{
				if (trim((string) ($post[$champ] ?? '')) !== '')
				{
					$this->config($reglage, $this->crypt->encrypt_secret(trim((string) $post[$champ])));
				}
			}

			notify($this->lang('Réglages Stripe enregistrés'));
			redirect('admin/payments');
		}

		$packs = $this->db->select('*')->from('nf_payment_packs')->join_lang('nf_payment_packs_lang pl', 'pack_id', 'nf_payment_packs.id', NULL, 'LEFT')->order_by('position', 'id')->get(FALSE);

		$rows = '';
		foreach ($packs as $p)
		{
			$rows .= '<tr>'
				.'<td>'.nf_texte($p['label']).'</td>'
				.'<td>'.match ($p['kind']) { 'vip' => $this->lang('VIP (%d j)', (int)$p['units']), 'adhesion' => $this->lang('Adhésion (%d j)', (int)$p['units']), default => $this->lang('%d points', (int)$p['units']) }.'</td>'
				.'<td class="text-end">'.number_format((int)$p['price_cents'] / 100, 2).' '.strtoupper($p['currency']).'</td>'
				.'<td class="text-center">'.(!empty($p['active']) ? '<span class="badge text-bg-success">'.$this->lang('Actif').'</span>' : '<span class="badge text-bg-secondary">'.$this->lang('Inactif').'</span>').'</td>'
				.'<td class="text-end">'
					.'<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/payments/packs/edit/'.(int)$p['id']).'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/payments/packs/delete/'.(int)$p['id']).'" data-confirm="'.nf_texte($this->lang('Supprimer ce pack ?')).'"><i class="far fa-trash-alt"></i></a>'
				.'</td>'
				.'</tr>';
		}

		$packs_body = $packs
			? '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>'.$this->lang('Pack').'</th><th>'.$this->lang('Contenu').'</th><th class="text-end">'.$this->lang('Prix').'</th><th class="text-center">'.$this->lang('Statut').'</th><th></th></tr></thead><tbody>'.$rows.'</tbody></table></div>'
			: $this->admin_empty('fas fa-box', $this->lang('Aucun pack.'));

		// Sans Gamification, rien ne crédite des points ni du VIP : ces lots-là ne se vendent pas (Payments::vente_possible),
		// et l'administrateur doit savoir pourquoi la page publique ne les montre pas. Les cotisations, elles, se vendent.
		$pay    = $this->module('payments');
		$pay    = $pay instanceof \NF\Modules\Payments\Payments ? $pay : NULL;
		$alerte = $pay !== NULL && !$pay->gamification()
			? '<div class="alert alert-warning">'.icon('fas fa-exclamation-triangle').' '.$this->lang('Les points et le VIP vendus ici sont crédités par le module Gamification, absent ou désactivé : ces lots restent fermés tant qu\'il n\'est pas installé et activé. Les cotisations, elles, se vendent sans lui.').'</div>'
			: '';

		return $alerte
			.$this->admin_card('fas fa-credit-card', $this->lang('Réglages Stripe'), $form->display())
			.$this->admin_card('fas fa-box', $this->lang('Packs'), $packs_body, '', $this->admin_create('admin/payments/packs/new', $this->lang('Nouveau pack')))
			.($pay !== NULL ? $this->_adherents($pay) : '');
	}

	/**
	 * Les adhérents : les adhésions en cours, et une cotisation payée autrement qu'en ligne (espèces, chèque,
	 * virement) à enregistrer — elle prolonge l'adhésion comme un paiement en ligne.
	 */
	private function _adherents(\NF\Modules\Payments\Payments $pay): string
	{
		$jeton = $this->csrf_token();
		$liste = $pay->adherents();

		$corps = $liste
			? '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>'.$this->lang('Membre').'</th><th>'.$this->lang('Jusqu\'au').'</th><th>'.$this->lang('Origine').'</th><th></th></tr></thead><tbody>'
				.implode('', array_map(fn (array $a): string => '<tr>'
					.'<td>'.nf_texte($a['username']).'</td>'
					.'<td>'.nf_date($a['expires_at']).'</td>'
					.'<td><span class="badge text-bg-secondary">'.nf_texte($a['source'] === 'stripe' ? 'Stripe' : ($a['source'] === 'admin' ? $this->lang('Enregistrée à la main') : $a['source'])).'</span></td>'
					.'<td class="text-end"><a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/payments/adhesions/end/'.(int)$a['user_id']).'" data-confirm="'.nf_texte($this->lang('Mettre fin à cette adhésion maintenant ?')).'" title="'.nf_texte($this->lang('Mettre fin à l\'adhésion')).'"><i class="fas fa-user-slash"></i></a></td>'
					.'</tr>', $liste))
				.'</tbody></table></div>'
			: $this->admin_empty('fas fa-id-card', $this->lang('Aucun adhérent pour le moment.'), $this->lang('Un lot « Adhésion » vendu en ligne, ou une cotisation enregistrée ci-dessous, rend un membre adhérent pour une durée. Le rôle « Adhérent » lui ouvre ce que tu lui ouvres.'));

		$formulaire = '<form method="post" action="'.url('admin/payments/adhesions/add').'" class="row g-2 align-items-end mt-2">'
			.'<input type="hidden" name="_" value="'.$jeton.'">'
			.'<div class="col-12 col-md-5"><label class="form-label">'.$this->lang('Pseudo du membre').'</label><input type="text" name="username" class="form-control form-control-sm" required maxlength="100"></div>'
			.'<div class="col-6 col-md-3"><label class="form-label">'.$this->lang('Durée (jours)').'</label><input type="number" name="days" class="form-control form-control-sm" min="1" max="3660" value="365" required></div>'
			.'<div class="col-6 col-md-4"><button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-plus"></i> '.$this->lang('Enregistrer une cotisation').'</button></div>'
			.'</form>';

		return $this->admin_card('fas fa-id-card', $this->lang('Adhérents'), $corps.$formulaire, $this->lang('%d adhérent|%d adhérents', count($liste), count($liste)));
	}

	public function _adhesion_add()
	{
		$this->exiger_post();
		$this->check_csrf('admin/payments');

		$pay  = $this->module('payments');
		$jours = (int) ($_POST['days'] ?? 0);
		$membre = $this->db->select('id')->from('nf_user')->where('username', trim(nf_texte_brut($_POST['username'] ?? '')))->where('deleted', '0')->row();

		if ($pay instanceof \NF\Modules\Payments\Payments && $membre && $jours >= 1 && $jours <= 3660)
		{
			$pay->accorder_adhesion((int) $membre, $jours, 'admin');
			notify($this->lang('Cotisation enregistrée.'));
		}
		else
		{
			notify($membre ? $this->lang('Une durée entre 1 et 3 660 jours.') : $this->lang('Aucun membre ne porte ce pseudo.'), 'danger');
		}

		redirect('admin/payments');
	}

	public function _adhesion_end($id)
	{
		$this->check_csrf('admin/payments');

		if (($pay = $this->module('payments')) instanceof \NF\Modules\Payments\Payments)
		{
			$pay->mettre_fin_adhesion((int) $id);
			notify($this->lang('Adhésion terminée.'));
		}

		redirect('admin/payments');
	}

	public function _pack_new()
	{
		return $this->_pack_form(NULL);
	}

	public function _pack_edit($id)
	{
		return $this->_pack_form((int)$id);
	}

	private function _pack_form($id)
	{
		$pack = $id ? $this->db->select('*')->from('nf_payment_packs')->where('id', $id)->row(FALSE) : NULL;

		if ($id && !$pack)
		{
			$this->error(404);
			return;
		}

		$langue = (string) $this->config->lang->info()->name;

		if ($pack)
		{
			// Le libellé TEL QU'IL EST ÉCRIT dans la langue de l'administration, vide si rien n'y est encore écrit
			// (m32, 2026-10-15) : on traduit un pack en changeant de langue.
			$libelle       = $this->db->select('label')->from('nf_payment_packs_lang')->where('pack_id', $id)->where('lang', $langue)->row();
			$pack['label'] = is_string($libelle) ? $libelle : '';
		}

		$this->subtitle($id ? $this->lang('Modifier le pack') : $this->lang('Nouveau pack'))->icon('fas fa-box');
		$this->breadcrumb($this->lang('Paiements'), 'admin/payments');

		$form = $this->form()
			->add_rules([
				'label'       => ['label' => $this->lang('Libellé'),  'value' => $pack['label'] ?? '',        'rules' => 'required',
				                  'description' => $this->lang('Écrit pour %s. Change la langue de l\'administration pour traduire ce pack ; les autres langues gardent leur texte.', $this->config->lang->info()->title)],
				'kind'        => ['label' => $this->lang('Type'),     'value' => $pack['kind'] ?? 'points',   'type'  => 'select', 'values' => ['points' => $this->lang('Points'), 'vip' => $this->lang('VIP (jours)'), 'adhesion' => $this->lang('Adhésion (jours)')]],
				'units'       => ['label' => $this->lang('Quantité (points, ou jours de VIP ou d\'adhésion)'), 'value' => $pack['units'] ?? 0, 'type' => 'number', 'rules' => 'required', 'size' => 'col-3'],
				'price_cents' => ['label' => $this->lang('Prix (centimes)'), 'value' => $pack['price_cents'] ?? 0, 'type' => 'number', 'rules' => 'required', 'size' => 'col-3', 'description' => $this->lang('Ex. 499 = 4,99')],
				'currency'    => ['label' => $this->lang('Devise'),   'value' => $pack['currency'] ?? 'eur',  'type'  => 'select', 'values' => ['eur' => 'EUR', 'usd' => 'USD', 'gbp' => 'GBP', 'cad' => 'CAD', 'chf' => 'CHF'], 'size' => 'col-3'],
				'position'    => ['label' => $this->lang('Position'), 'value' => $pack['position'] ?? 0,      'type'  => 'number', 'size' => 'col-3'],
				'active'      => ['type' => 'checkbox', 'checked' => ['on' => $pack === NULL ? TRUE : !empty($pack['active'])], 'values' => ['on' => $this->lang('Actif')]],
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		if ($form->is_valid($post))
		{
			$data = [
				'kind'        => in_array($post['kind'], ['vip', 'adhesion'], TRUE) ? $post['kind'] : 'points',
				'units'       => max(0, (int)$post['units']),
				'price_cents' => max(0, (int)$post['price_cents']),
				'currency'    => strtolower($post['currency']),
				'position'    => (int)$post['position'],
				'active'      => in_array('on', (array)$post['active']) ? 1 : 0,
			];

			if ($id)
			{
				$this->db->where('id', $id)->update('nf_payment_packs', $data);
			}
			else
			{
				$id = (int) $this->db->insert('nf_payment_packs', $data);
			}

			// `ecrire_lang` CRÉE la ligne quand elle manque (leçon de m30).
			$this->db->ecrire_lang('nf_payment_packs_lang', ['pack_id' => $id, 'lang' => $langue], ['label' => $post['label']]);

			notify($this->lang($id ? 'Pack modifié' : 'Pack créé'));
			redirect('admin/payments');
		}

		return $this->admin_card('fas fa-box', $id ? $this->lang('Modifier le pack') : $this->lang('Nouveau pack'), $form->display());
	}

	public function _pack_delete($id)
	{
		$this->check_csrf('admin/payments');

		$this->db->where('id', (int)$id)->delete('nf_payment_packs');
		notify($this->lang('Pack supprimé'));
		redirect('admin/payments');
	}
}
