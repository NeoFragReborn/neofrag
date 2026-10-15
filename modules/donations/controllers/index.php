<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Donations\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index()
	{
		$this	->title($this->lang('Dons'))
				->icon('fas fa-hand-holding-heart')
				->css('donations');

		$campaigns = $this->model()->get_active_campaigns();

		// Si exactement 1 campagne, on redirige vers son URL canonique
		// (et non un appel direct à _campaign qui exigerait segments[1]).
		if (count($campaigns) === 1 && !empty($campaigns[0]['name']))
		{
			redirect('donations/'.url_title($campaigns[0]['name']));
		}

		// Aucune campagne : une page vide, que les moteurs n'indexent pas (et que le plan du site tait).
		if (!$campaigns)
		{
			$this->output->data->set('module', 'robots', 'noindex, follow');
		}

		return $this->view('index', [
			'campaigns' => array_map([$this, '_enrich'], $campaigns)
		]);
	}

	public function _campaign()
	{
		// Route returns the campaign columns as args; just take the slug from URL segments.
		$name = $this->url->segments[1] ?? '';
		$campaign = $this->model()->get_campaign($name);

		if (!$campaign)
		{
			$this->error(404);
			return;
		}

		// Une campagne n'a pas de langue à elle : sa canonique est dans la langue première du site.
		nf_seo_sans_langue();

		$this	->title($campaign['title'])
				->icon('fas fa-hand-holding-heart')
				->css('donations');
		$this->breadcrumb($this->lang('Dons'), 'donations');
		$this->breadcrumb($campaign['title']);

		$campaign  = $this->_enrich($campaign);
		$donations = $this->model()->get_donations($campaign['id'], TRUE);

		return $this->view('campaign', [
			'c'         => $campaign,
			'donations' => $donations,
			'donate_url'=> $this->_paypal_url($campaign)
		]);
	}

	private function _enrich($c)
	{
		$totals = $this->model()->get_total($c['id']);
		$c['raised']     = $totals['total'];
		$c['count']      = $totals['count'];
		$c['percentage'] = $c['goal_amount'] > 0 ? min(100, round($c['raised'] / $c['goal_amount'] * 100, 1)) : 0;
		return $c;
	}

	/**
	 * Les notifications des services de dons (m16, 2026-10-16) : …/donations/webhook/{kofi|helloasso|paypal}. Sans
	 * session : le service appelle de lui-même. Chacune se VÉRIFIE avant qu'un don ne s'enregistre — le jeton de Ko-fi,
	 * la commande relue par l'API d'HelloAsso, la notification confirmée par PayPal —, et un don ne s'enregistre
	 * qu'une fois. Une réponse 503 dit au service de réessayer plus tard (le nôtre n'a pas pu vérifier) ; une 403, que
	 * la notification n'est pas la nôtre.
	 */
	public function _webhook($service)
	{
		$modele = $this->model();
		$code   = $modele instanceof \NF\Modules\Donations\Models\Donations ? match ((string) $service)
		{
			'kofi'      => $this->_kofi($modele),
			'helloasso' => $this->_helloasso($modele),
			'paypal'    => $this->_paypal($modele),
			default     => 404,
		} : 404;

		http_response_code($code);
		header('Content-Type: text/plain; charset=utf-8');
		echo $code === 200 ? 'ok' : 'refused';
		exit;
	}

	private function _kofi(\NF\Modules\Donations\Models\Donations $modele): int
	{
		$don = \NF\Modules\Donations\Lib\Fournisseurs::kofi($_POST, $this->config->nf_donations_kofi_token ? $modele->secret('nf_donations_kofi_token') : '');

		if ($don === FALSE)
		{
			return 403;
		}

		if ($don !== NULL && ($campagne = $modele->campagne_du_service('kofi')))
		{
			$modele->enregistrer_externe('kofi', $campagne, $don);
		}

		return 200;
	}

	/**
	 * HelloAsso : un don va à la campagne choisie ; une adhésion rend le payeur adhérent (module Paiements) si son
	 * adresse est celle d'un membre — une seule fois par commande, comme un paiement en ligne.
	 *
	 * couplage(payments): facultatif — `module('payments')` rend NULL sans le module, et l'adhésion n'est pas accordée.
	 */
	private function _helloasso(\NF\Modules\Donations\Models\Donations $modele): int
	{
		if ((string) $this->config->nf_donations_helloasso_client_id === '' || (string) $this->config->nf_donations_helloasso_secret === '')
		{
			return 404;
		}

		$notification = json_decode((string) file_get_contents('php://input'), TRUE);
		$numero       = is_array($notification) ? \NF\Modules\Donations\Lib\Fournisseurs::helloasso_numero($notification) : NULL;

		if ($numero === NULL)
		{
			return 200; // un autre événement (un formulaire publié, un paiement seul) : rien à enregistrer
		}

		$commande = $modele->helloasso_commande($numero);

		if ($commande === NULL)
		{
			return 503;
		}

		if ($commande === FALSE)
		{
			return 403;
		}

		$don = \NF\Modules\Donations\Lib\Fournisseurs::helloasso($commande);

		if ($don === NULL)
		{
			return 200;
		}

		if ($don['type'] === 'don' && ($campagne = $modele->campagne_du_service('helloasso')))
		{
			$modele->enregistrer_externe('helloasso', $campagne, $don);
		}
		else if ($don['type'] === 'adhesion' && ($jours = (int) $this->config->nf_donations_helloasso_days) > 0 && $don['email'] !== ''
			&& ($paiements = $this->module('payments')) instanceof \NF\Modules\Payments\Payments
			&& ($membre = $this->db->select('id')->from('nf_user')->where('email', $don['email'])->where('deleted', '0')->row()))
		{
			$paiements->fulfill('helloasso:'.$don['external_id'], '', (int) $membre, 'adhesion', $jours);
		}

		return 200;
	}

	private function _paypal(\NF\Modules\Donations\Models\Donations $modele): int
	{
		if (!$this->config->nf_donations_paypal_ipn)
		{
			return 404;
		}

		$brut = (string) file_get_contents('php://input');
		parse_str($brut, $champs);

		$confirme = $modele->paypal_confirme($brut);

		if ($confirme === NULL)
		{
			return 503;
		}

		if (!$confirme)
		{
			return 403;
		}

		$don = \NF\Modules\Donations\Lib\Fournisseurs::paypal($champs);

		if ($don === NULL)
		{
			return 200;
		}

		// La campagne que le bouton a nommée, si l'argent est bien allé à SON adresse ; sinon celle dont c'est l'adresse.
		$adresse_de = fn (array $c): string => mb_strtolower(trim((string) ($c['paypal_email'] ?: $this->config->nf_donations_paypal_email)));
		$campagne   = $don['campagne'] ? $modele->get_campaign($don['campagne']) : NULL;

		if (!$campagne || $adresse_de($campagne) !== $don['destinataire'])
		{
			$campagne = NULL;

			foreach ($modele->get_campaigns('active') as $c)
			{
				if ($adresse_de($c) === $don['destinataire'])
				{
					$campagne = $c;
					break;
				}
			}
		}

		if ($campagne && $don['destinataire'] !== '')
		{
			$modele->enregistrer_externe('paypal', (int) $campagne['id'], $don);
		}

		return 200;
	}

	private function _paypal_url($campaign)
	{
		// Hosted button takes priority if set
		if (!empty($campaign['paypal_button_id']))
		{
			return 'https://www.paypal.com/donate?hosted_button_id='.urlencode($campaign['paypal_button_id']);
		}
		$email = $campaign['paypal_email'] ?: $this->config->nf_donations_paypal_email;
		if (!$email) return NULL;
		// L'enregistrement automatique (m16) : PayPal prévient le site, en nommant la campagne (`custom`).
		$automatique = $this->config->nf_donations_paypal_ipn ? ['custom' => (string) (int) $campaign['id'], 'notify_url' => absolute_url('donations/webhook/paypal')] : [];

		return 'https://www.paypal.com/donate?'.http_build_query([
			'business'      => $email,
			'item_name'     => $campaign['title'].' — '.$this->config->nf_name,
			'currency_code' => $campaign['currency'] ?: 'EUR',
			'no_recurring'  => '0',
			'no_shipping'   => '1',
			'cmd'           => '_donations'
		] + $automatique);
	}
}
