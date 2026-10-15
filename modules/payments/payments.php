<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Paiements (Stripe) — recharge de points, packs VIP et cotisations en argent réel.
 *
 * Les COTISATIONS (m16, 2026-10-16) : un lot du type `adhesion` rend « adhérent » pour une durée — le statut VIP à
 * durée, généralisé : même échéance prolongée, même idempotence. Un adhérent reçoit le rôle du site
 * « Adhérent » (que l'administration peut ouvrir à ce qu'elle veut : une section du forum, des pages) et fait partie
 * du groupe « Adhérents » (son badge, son rôle sur Discord). La tâche planifiée le prévient avant l'échéance, et lui
 * retire le rôle à l'échéance. Un administrateur enregistre aussi une cotisation payée autrement (espèces, chèque).
 * Les cotisations ne demandent PAS la Gamification : seuls les points et le VIP en ont besoin.
 *
 * SÉCURITÉ : inerte tant que les clés ne sont pas configurées. Le webhook vérifie
 * la signature Stripe (HMAC-SHA256 horodaté, anti-rejeu) et l'idempotence
 * (event_id unique) avant de créditer. À relire en revue sécu + tester avec de
 * vraies clés (mode test) avant toute mise en production.
 */

namespace NF\Modules\Payments;

use NF\NeoFrag\Addons\Module;

class Payments extends Module
{
	const SIGNATURE_TOLERANCE = 300; // secondes

	protected function __info()
	{
		return [
			'title'       => $this->lang('Paiements'),
			'description' => $this->lang('Packs de points ou de jours VIP (module Gamification) payés via Stripe Checkout, crédités après confirmation signée de Stripe, une seule fois par paiement.'),
			'icon'        => 'fas fa-credit-card',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['association'],
			// Les points et le VIP qu'il vend vivent dans Gamification : sans elle, ces lots-là ne se vendent
			// pas (`vente_possible()`), et un paiement reçu pour eux n'est pas marqué traité. Les cotisations,
			// elles, n'en ont pas besoin : une association n'a pas à installer des points pour encaisser.
			'requires'    => [],
			'version'     => '1.0',
			'admin'       => TRUE,
			'depends'     => ['neofrag' => '1.0.0'],
			'routes'      => [
				''                  => 'index',
				'success'           => '_success',
				'cancel'            => '_cancel',
				'webhook'           => '_webhook',
				'ajax/checkout/{id}'=> '_checkout',
				'admin'             => 'index',
				'admin/packs/new'   => '_pack_new',
				'admin/packs/edit/{id}'   => '_pack_edit',
				'admin/packs/delete/{id}' => '_pack_delete',
				'admin/adhesions/add'     => '_adhesion_add',
				'admin/adhesions/end/{id}' => '_adhesion_end',
			]
		];
	}

	/** Stripe est-il configuré et activé ? (NB : PAS is_enabled — réservé au routage des addons). */
	public function is_configured()
	{
		return (bool)$this->config->pay_stripe_enabled && !empty($this->config->pay_stripe_secret);
	}

	/**
	 * Le module qui crédite : points et VIP vivent dans Gamification. NULL s'il est absent ou éteint.
	 *
	 * `requires` le fait installer avec ce module, mais n'empêche ni un site plus ancien de tourner
	 * sans lui, ni qu'on le désactive ensuite. Avant ce contrôle, `fulfill()` enregistrait le paiement
	 * comme traité puis ne créditait rien : l'argent était encaissé, le membre ne recevait rien.
	 */
	public function gamification()
	{
		$gam = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['gamification']);

		return ($gam && $gam->is_enabled()) ? $gam : NULL;
	}

	/** Les types de lot qui ne demandent que Stripe : la cotisation. Les autres créditent la Gamification. */
	const SANS_GAMIFICATION = ['adhesion'];

	/** Avant l'échéance d'une adhésion, combien de jours plus tôt le membre est prévenu. */
	const RAPPEL_JOURS = 7;

	/**
	 * Peut-on vendre ? Stripe prêt ET de quoi créditer l'achat : la Gamification pour les points et le VIP, rien de
	 * plus pour une cotisation. Sans type : peut-on vendre quelque chose ?
	 */
	public function vente_possible($kind = NULL)
	{
		if (!$this->is_configured())
		{
			return FALSE;
		}

		return in_array($kind, self::SANS_GAMIFICATION, TRUE) || $this->gamification() !== NULL
			|| ($kind === NULL && !$this->db->from('nf_payment_packs')->where('active', 1)->where('kind', self::SANS_GAMIFICATION)->empty());
	}

	/** Packs achetables actifs. */
	public function packs()
	{
		return $this->db->select('*')->from('nf_payment_packs')->join_lang('nf_payment_packs_lang pl', 'pack_id', 'nf_payment_packs.id', NULL, 'LEFT')->where('active', 1)->order_by('position', 'id')->get(FALSE);
	}

	public function pack($id)
	{
		return $this->db->select('*')->from('nf_payment_packs')->join_lang('nf_payment_packs_lang pl', 'pack_id', 'nf_payment_packs.id', NULL, 'LEFT')->where('id', (int)$id)->where('active', 1)->row(FALSE);
	}

	private function abs_url($path)
	{
		// site_origin() : les URLs de retour Stripe ne doivent pas dériver d'un Host forgeable.
		return site_origin().$this->url->base.$path;
	}

	/** Crée une session Stripe Checkout (API directe). @return string|null URL de paiement */
	public function create_checkout_session($pack, $user_id)
	{
		if (!$pack || !$this->vente_possible($pack['kind']))
		{
			return NULL;
		}

		$params = [
			'mode'                => 'payment',
			'success_url'         => $this->abs_url('payments/success'),
			'cancel_url'          => $this->abs_url('payments/cancel'),
			'client_reference_id' => (string)(int)$user_id,
			'metadata'            => [
				'user_id' => (string)(int)$user_id,
				'kind'    => $pack['kind'],
				'units'   => (string)(int)$pack['units'],
			],
			'line_items'          => [[
				'quantity'   => 1,
				'price_data' => [
					'currency'     => $pack['currency'] ?: 'eur',
					'unit_amount'  => (int)$pack['price_cents'],
					'product_data' => ['name' => $pack['label']],
				],
			]],
		];

		$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
		curl_setopt_array($ch, [
			CURLOPT_POST           => TRUE,
			CURLOPT_POSTFIELDS     => http_build_query($params),
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_TIMEOUT        => 20,
			CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$this->crypt->decrypt_secret($this->config->pay_stripe_secret)],
		]);

		$resp = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($code !== 200 || !$resp)
		{
			return NULL;
		}

		$data = json_decode($resp, TRUE);

		return $data['url'] ?? NULL;
	}

	/** Vérifie la signature Stripe d'un webhook (HMAC-SHA256 horodaté + anti-rejeu). */
	public function verify_signature($payload, $header, $secret)
	{
		if (empty($secret) || empty($header))
		{
			return FALSE;
		}

		$t = $v1 = NULL;
		foreach (explode(',', $header) as $kv)
		{
			$p = explode('=', $kv, 2);
			if (count($p) === 2)
			{
				if ($p[0] === 't')  $t  = $p[1];
				if ($p[0] === 'v1') $v1 = $p[1];
			}
		}

		if (!$t || !$v1 || abs(time() - (int)$t) > self::SIGNATURE_TOLERANCE)
		{
			return FALSE;
		}

		return hash_equals(hash_hmac('sha256', $t.'.'.$payload, $secret), $v1);
	}

	/**
	 * Traite un paiement validé (idempotent). @return bool crédité
	 * Insère d'abord la ligne idempotence (clé unique event_id) : si l'événement a
	 * déjà été traité, l'insert échoue et on ne crédite pas deux fois.
	 */
	public function fulfill($event_id, $session_id, $user_id, $kind, $units)
	{
		$user_id = (int)$user_id;
		$units   = (int)$units;

		if (!$event_id || !$user_id || $units <= 0 || !in_array($kind, ['points', 'vip', 'adhesion'], TRUE))
		{
			return FALSE;
		}

		// Rien pour créditer : le paiement n'est PAS marqué traité. Le webhook répond alors une erreur
		// (cf. Index::_webhook), et Stripe représente l'événement plus tard — une fois Gamification
		// rétablie, le crédit part. Marqué traité ici, il était perdu pour de bon.
		$gam = $this->gamification();

		if (!$gam && !in_array($kind, self::SANS_GAMIFICATION, TRUE))
		{
			return FALSE;
		}

		// Idempotence : déjà traité ?
		if (!$this->db->from('nf_payments')->where('event_id', $event_id)->empty())
		{
			return FALSE;
		}

		// La clé unique event_id garantit l'unicité même en cas de course (2 webhooks).
		try
		{
			$this->db->insert('nf_payments', [
				'event_id'   => $event_id,
				'session_id' => (string)$session_id,
				'user_id'    => $user_id,
				'kind'       => $kind,
				'units'      => $units,
				'status'     => 'completed',
			]);
		}
		catch (\Throwable $e)
		{
			return FALSE; // inséré entre-temps → pas de double crédit
		}

		if ($kind === 'adhesion')
		{
			$this->accorder_adhesion($user_id, $units, 'stripe');
		}
		else if ($kind === 'points')
		{
			$gam->add_points($user_id, $units, 'stripe', $this->lang('Recharge Stripe'));
		}
		else
		{
			$gam->grant_vip($user_id, $units, 'stripe');
		}

		return TRUE;
	}

	/* ------------------------------------------------------------ Cotisations */

	/**
	 * Rend un membre adhérent pour N jours, ou prolonge son adhésion : depuis son échéance si elle court encore,
	 * depuis maintenant sinon — en une requête, comme le VIP (deux paiements simultanés ne s'écrasent pas). Le rappel
	 * de la prochaine échéance est réarmé ; le rôle « Adhérent » est donné.
	 */
	public function accorder_adhesion($user_id, $days, $source = '')
	{
		$user_id = (int)$user_id;
		$days    = (int)$days;

		if (!$user_id || $days <= 0)
		{
			return;
		}

		$maintenant = date('Y-m-d H:i:s');
		$echeance   = date('Y-m-d H:i:s', time() + $days * 86400);
		$origine    = $this->db->escape_string(mb_substr((string)$source, 0, 50));

		$this->db->execute('INSERT INTO nf_adhesions (user_id, expires_at, source) VALUES ('.$user_id.', \''.$echeance.'\', \''.$origine.'\')'
			.' ON DUPLICATE KEY UPDATE expires_at = IF(expires_at > \''.$maintenant.'\', expires_at + INTERVAL '.$days.' DAY, \''.$echeance.'\'), source = \''.$origine.'\', reminded_at = NULL');

		$this->db->execute('INSERT IGNORE INTO nf_users_roles (user_id, role_id) VALUES ('.$user_id.', '.$this->role_adherent().')');
		$this->access->invalidate_cache();
		$this->_groupes_changes([$user_id]);
	}

	/** Met fin à une adhésion tout de suite (une erreur de saisie, un remboursement). */
	public function mettre_fin_adhesion($user_id)
	{
		$user_id = (int)$user_id;
		// Lu AVANT la chaîne qui suit : la requête du rôle, lancée au milieu d'un `where()` en cours, en héritait.
		$role    = $this->role_adherent();

		$this->db->where('user_id', $user_id)->update('nf_adhesions', ['expires_at' => date('Y-m-d H:i:s', time() - 1)]);
		$this->db->where('user_id', $user_id)->where('role_id', $role)->delete('nf_users_roles');
		$this->access->invalidate_cache();
		$this->_groupes_changes([$user_id]);
	}

	/** L'échéance de l'adhésion d'un membre si elle court, sinon NULL. */
	public function adhesion_expire($user_id)
	{
		$exp = (int)$user_id ? $this->db->select('expires_at')->from('nf_adhesions')->where('user_id', (int)$user_id)->row() : NULL;

		return ($exp && strtotime((string)$exp) > time()) ? (string)$exp : NULL;
	}

	/** @return list<array<string, mixed>> Les adhésions en cours, de la plus proche de son échéance à la plus lointaine. */
	public function adherents()
	{
		return array_values((array) $this->db	->select('a.user_id', 'a.expires_at', 'a.source', 'u.username')
												->from('nf_adhesions a')
												->join('nf_user u', 'u.id = a.user_id AND u.deleted = "0"', 'INNER')
												->where('a.expires_at >', date('Y-m-d H:i:s'))
												->order_by('a.expires_at')
												->get());
	}

	/**
	 * La tâche planifiée : un rappel à qui arrive à moins de RAPPEL_JOURS jours de son échéance (une fois par
	 * échéance), et le rôle retiré à qui l'a passée.
	 *
	 * couplage(notifications): facultatif — `Module::__load` rend NULL sans le module, et le rappel ne part pas.
	 *
	 * @return array{rappels: int, fins: int}
	 */
	public function echeances()
	{
		$maintenant = date('Y-m-d H:i:s');
		$rappels    = 0;
		$fins       = [];
		$notifications = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications']);

		foreach ((array) $this->db	->select('user_id', 'expires_at')
									->from('nf_adhesions')
									->where('expires_at >', $maintenant)
									->where('expires_at <=', date('Y-m-d H:i:s', time() + self::RAPPEL_JOURS * 86400))
									->where('reminded_at', NULL)
									->get() as $a)
		{
			if ($notifications instanceof \NF\Modules\Notifications\Notifications)
			{
				$notifications->push((int)$a['user_id'], 'payments_adhesion_reminder', (string)$this->lang('Votre adhésion prend fin le %s : pensez à la renouveler.', nf_date($a['expires_at'])), 'payments');
			}

			$this->db->where('user_id', (int)$a['user_id'])->update('nf_adhesions', ['reminded_at' => $maintenant]);
			$rappels++;
		}

		$role = (int)$this->db->select('role_id')->from('nf_roles')->where('name', 'adherent')->row();

		if ($role)
		{
			foreach ((array) $this->db	->select('a.user_id')
										->from('nf_adhesions a')
										->join('nf_users_roles r', 'r.user_id = a.user_id AND r.role_id = '.$role, 'INNER')
										->where('a.expires_at <=', $maintenant)
										->get() as $user_id)
			{
				$this->db->where('user_id', (int)$user_id)->where('role_id', $role)->delete('nf_users_roles');
				$fins[] = (int)$user_id;
			}
		}

		if ($fins)
		{
			$this->access->invalidate_cache();
			$this->_groupes_changes($fins);
		}

		return ['rappels' => $rappels, 'fins' => count($fins)];
	}

	/**
	 * Le rôle du site « Adhérent », créé à la première adhésion. L'administration l'ouvre à ce qu'elle veut — une
	 * section du forum, des pages — comme n'importe quel rôle ; le module le donne et le retire seul.
	 */
	public function role_adherent()
	{
		$id = $this->db->select('role_id')->from('nf_roles')->where('name', 'adherent')->row();

		if ($id)
		{
			return (int)$id;
		}

		$id = (int)$this->db->insert('nf_roles', [
			'name'        => 'adherent',
			'title'       => (string)$this->lang('Adhérent'),
			'description' => (string)$this->lang('Posé par le module Paiements : il suit les cotisations en cours, ne le donnez pas à la main.'),
			'color'       => 'success',
			'icon'        => 'fas fa-id-card',
			'order'       => 150,
		]);

		$this->access->reload();

		return $id;
	}

	/**
	 * Le groupe « Adhérents » (clé `payments-1`) : les adhésions en cours — son badge, et sa correspondance avec un rôle
	 * sur Discord. L'identifiant d'un groupe de module est un NOMBRE : l'écran d'édition des groupes ne reconnaît que
	 * `<module>-<n>` (un identifiant en lettres y répondait 404, relevé par check-liens).
	 */
	public function groups()
	{
		if (!$this->db->table_exists('nf_adhesions'))
		{
			return [];
		}

		return [1 => [
			'name'  => 'adherents',
			'title' => (string)$this->lang('Adhérents'),
			'icon'  => 'fas fa-id-card',
			'color' => 'success',
			'users' => array_map('intval', array_column((array)$this->db()	->select('a.user_id')
																				->from('nf_adhesions a')
																				->join('nf_user u', 'u.id = a.user_id AND u.deleted = "0"', 'INNER')
																				->where('a.expires_at >', date('Y-m-d H:i:s'))
																				->get(FALSE), 'user_id')),
		]];
	}

	/** Les notifications que ce module envoie, pour les préférences de chaque membre. */
	public function types_de_notification(): array
	{
		return [
			['type' => 'payments_adhesion_reminder', 'titre' => (string)$this->lang('Mon adhésion arrive à échéance'), 'email' => FALSE, 'ordre' => 85],
		];
	}

	/** À la désinstallation, le rôle « Adhérent » part avec le module : il n'aurait plus personne pour le tenir. */
	public function uninstall($remove = TRUE)
	{
		if ($remove && !nf_demo())
		{
			$this->db->where('name', 'adherent')->delete('nf_roles');
		}

		return parent::uninstall($remove);
	}

	/** Les groupes de ces membres ont changé : le bot Discord met leurs rôles à jour. */
	private function _groupes_changes(array $user_ids)
	{
		// couplage(api): facultatif — sans le module api, `Module::__load` rend NULL et rien n'est inscrit.
		if (($api = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['api'])) instanceof \NF\Modules\Api\Api)
		{
			foreach ($user_ids as $user_id)
			{
				$api->consigner('user.groups.changed', ['user_id' => (int)$user_id]);
			}
		}
	}
}
