<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Paiements — page publique (packs), retours success/cancel, et webhook Stripe.
 */

namespace NF\Modules\Payments\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index()
	{
		$this->js('payments');

		$pay = $this->module('payments');
		$pay = $pay instanceof \NF\Modules\Payments\Payments ? $pay : NULL;

		// Un lot ne se montre que s'il peut se vendre : les points et le VIP demandent la Gamification, pas la cotisation.
		$packs = array_values(array_filter(
			(array) $this->db->select('*')->from('nf_payment_packs')->join_lang('nf_payment_packs_lang pl', 'pack_id', 'nf_payment_packs.id', NULL, 'LEFT')->where('active', 1)->order_by('position', 'id')->get(FALSE),
			fn (array $p): bool => $pay !== NULL && (in_array($p['kind'], \NF\Modules\Payments\Payments::SANS_GAMIFICATION, TRUE) || $pay->gamification() !== NULL)
		));

		return $this->view('index', [
			'packs'    => $packs,
			'enabled'  => $pay !== NULL && $pay->vente_possible(),
			'logged'   => (bool)$this->user(),
			// L'adhésion du membre, s'il en a une en cours : son échéance.
			'adhesion' => $pay !== NULL && $this->user() ? $pay->adhesion_expire((int) $this->user->id) : NULL,
			// Le jeton des actions qui modifient, comme l'achat de la boutique (cf. Ajax::_checkout).
			// csrf: vérifié par modules/payments/controllers/ajax.php
			'jeton'   => $this->user() ? (string) $this->csrf_token() : ''
		]);
	}

	public function _success()
	{
		notify($this->lang('Paiement reçu ! Ton compte sera crédité sous peu.'));
		redirect('payments');
	}

	public function _cancel()
	{
		notify($this->lang('Paiement annulé.'), 'info');
		redirect('payments');
	}

	/** Webhook Stripe : vérifie la signature puis crédite (idempotent). Pas d'auth (appel serveur Stripe). */
	public function _webhook()
	{
		$pay     = $this->module('payments');
		$payload = file_get_contents('php://input');
		$sig     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

		if (!$pay instanceof \NF\Modules\Payments\Payments || !$pay->verify_signature($payload, $sig, $this->crypt->decrypt_secret($this->config->pay_stripe_webhook_secret)))
		{
			http_response_code(400);
			echo 'invalid signature';
			exit;
		}

		$event = json_decode($payload, TRUE);

		if (($event['type'] ?? '') === 'checkout.session.completed')
		{
			$session  = $event['data']['object'] ?? [];
			$metadata = $session['metadata'] ?? [];

			// Gamification absente ou éteinte : rien pour créditer des points ou du VIP. Une réponse d'erreur, et
			// Stripe représente l'événement plus tard ; répondre « ok » l'aurait clos sans crédit. Une cotisation,
			// elle, n'en a pas besoin.
			if (!$pay->gamification() && !in_array($metadata['kind'] ?? '', \NF\Modules\Payments\Payments::SANS_GAMIFICATION, TRUE))
			{
				error_log('[payments] paiement '.($event['id'] ?? '?').' reçu sans module Gamification actif : non crédité, Stripe le représentera');
				http_response_code(503);
				echo 'gamification unavailable';
				exit;
			}

			$pay->fulfill(
				$event['id']       ?? '',
				$session['id']     ?? '',
				(int)($metadata['user_id'] ?? 0),
				$metadata['kind']  ?? '',
				(int)($metadata['units'] ?? 0)
			);
		}

		http_response_code(200);
		echo 'ok';
		exit;
	}
}
