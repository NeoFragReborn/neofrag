<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Donations\Models;

use NF\NeoFrag\Loadables\Model;

class Donations extends Model
{
	/* ---------- CAMPAIGNS ---------- */

	// Le titre et la description d'une campagne se traduisent (m32, 2026-10-15) : les lectures servent la meilleure
	// traduction (la langue demandée, sinon le français, sinon la première écrite), l'écran d'édition la langue où
	// l'on travaille (`texte_dans`).
	public function get_campaigns($status = NULL)
	{
		$this->db->from('nf_donations_campaigns')->join_lang('nf_donations_campaigns_lang dl', 'campaign_id', 'nf_donations_campaigns.id', NULL, 'LEFT')->order_by('status ASC', 'created_at DESC');
		if ($status) $this->db->where('status', $status);
		return $this->db->get();
	}

	public function get_campaign($id_or_name)
	{
		$row = $this->db->from('nf_donations_campaigns')->join_lang('nf_donations_campaigns_lang dl', 'campaign_id', 'nf_donations_campaigns.id', NULL, 'LEFT');
		if (is_numeric($id_or_name))
		{
			$row->where('id', (int)$id_or_name);
		}
		else
		{
			$row->where('name', $id_or_name);
		}
		return $row->row();
	}

	public function get_active_campaigns()
	{
		return $this->db->from('nf_donations_campaigns')->join_lang('nf_donations_campaigns_lang dl', 'campaign_id', 'nf_donations_campaigns.id', NULL, 'LEFT')->where('status', 'active')->order_by('created_at DESC')->get();
	}

	/*
	 * Le titre et la description TELS QU'ILS SONT ÉCRITS dans une langue — vides si rien n'y est encore écrit : on
	 * traduit une campagne en changeant la langue de l'administration, sans repli.
	 */
	public function texte_dans(int $id, string $langue): array
	{
		$ecrit = $this->db	->select('title', 'description')
							->from('nf_donations_campaigns_lang')
							->where('campaign_id', $id)
							->where('lang', $langue)
							->row();

		return [
			'title'       => is_array($ecrit) ? (string) ($ecrit['title'] ?? '') : '',
			'description' => is_array($ecrit) ? (string) ($ecrit['description'] ?? '') : ''
		];
	}

	/*
	 * Le titre et la description vont dans la langue donnée ; le reste (le nom technique, l'objectif, les dates…)
	 * est commun à toutes les langues.
	 */
	public function save_campaign($data, $id = NULL, string $langue = 'fr')
	{
		$texte = array_intersect_key($data, ['title' => '', 'description' => '']);
		$data  = array_diff_key($data, $texte);

		if ($id)
		{
			$this->db->where('id', (int)$id)->update('nf_donations_campaigns', $data);
		}
		else
		{
			$id = (int)$this->db->insert('nf_donations_campaigns', $data);
		}

		if ($texte)
		{
			// `ecrire_lang` CRÉE la ligne quand elle manque (leçon de m30).
			$this->db->ecrire_lang('nf_donations_campaigns_lang', ['campaign_id' => (int)$id, 'lang' => $langue], [
				'title'       => (string) ($texte['title'] ?? ''),
				'description' => (string) ($texte['description'] ?? '')
			]);
		}

		return (int)$id;
	}

	public function delete_campaign($id)
	{
		$this->db->where('id', (int)$id)->delete('nf_donations_campaigns');
	}

	/* ---------- DONATIONS ---------- */

	public function get_donations($campaign_id, $only_public = FALSE, $only_completed = TRUE)
	{
		$q = $this->db	->from('nf_donations')
						->where('campaign_id', (int)$campaign_id)
						->order_by('created_at DESC');
		if ($only_public) $q->where('is_public', TRUE);
		if ($only_completed) $q->where('status', 'completed');
		return $q->get();
	}

	public function get_donation($id)
	{
		return $this->db->from('nf_donations')->where('id', (int)$id)->row();
	}

	public function get_total($campaign_id)
	{
		$row = $this->db	->select('SUM(amount) AS total', 'COUNT(*) AS count')
							->from('nf_donations')
							->where('campaign_id', (int)$campaign_id)
							->where('status', 'completed')
							->row();
		return [
			'total' => (float)($row['total'] ?? 0),
			'count' => (int)($row['count'] ?? 0)
		];
	}

	public function get_top_donors($campaign_id, $limit = 5)
	{
		return $this->db	->select('donor_name', 'SUM(amount) AS total', 'COUNT(*) AS count', 'MAX(created_at) AS last_at')
							->from('nf_donations')
							->where('campaign_id', (int)$campaign_id)
							->where('status', 'completed')
							->where('is_public', TRUE)
							->where('is_anonymous', FALSE)
							->group_by('donor_name')
							->order_by('total DESC')
							->limit($limit)
							->get();
	}

	public function save_donation($data, $id = NULL)
	{
		if ($id)
		{
			$this->db->where('id', (int)$id)->update('nf_donations', $data);
			return (int)$id;
		}
		return (int)$this->db->insert('nf_donations', $data);
	}

	public function delete_donation($id)
	{
		$this->db->where('id', (int)$id)->delete('nf_donations');
	}

	/* ------------------------------------------------- Les services reliés (m16, 2026-10-16) */

	/** Les adresses des services. Une épreuve les remplace par l'environnement (NF_DONS_API_*) : un site jamais. */
	public function adresse_service(string $service): string
	{
		$epreuve = getenv('NF_DONS_API_'.strtoupper($service));

		if (is_string($epreuve) && $epreuve !== '')
		{
			return rtrim($epreuve, '/');
		}

		return match ($service)
		{
			'helloasso'    => $this->config->nf_donations_helloasso_sandbox ? 'https://api.helloasso-sandbox.com' : 'https://api.helloasso.com',
			'paypal'       => $this->config->nf_donations_paypal_sandbox ? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr' : 'https://ipnpb.paypal.com/cgi-bin/webscr',
			'tipeeestream' => 'https://api.tipeeestream.com',
			default        => '',
		};
	}

	/** Un réglage secret, déchiffré ; vide s'il n'est pas posé. */
	public function secret(string $reglage): string
	{
		$valeur = (string) $this->config->{$reglage};

		return $valeur === '' ? '' : (string) $this->crypt->decrypt_secret($valeur);
	}

	/** La campagne qu'un service alimente, si elle existe encore. */
	public function campagne_du_service(string $service): int
	{
		$id = (int) $this->config->{'nf_donations_'.$service.'_campaign'};

		return $id && $this->get_campaign($id) ? $id : 0;
	}

	/**
	 * Enregistre un don venu d'un service, UNE fois : la clé unique (source, external_id) arrête une notification
	 * reçue deux fois, même en même temps. Le donateur est relié à son compte si son adresse est celle d'un membre.
	 * Rend VRAI si le don est nouveau.
	 */
	public function enregistrer_externe(string $source, int $campagne, array $don): bool
	{
		if (!in_array($source, \NF\Modules\Donations\Lib\Fournisseurs::SERVICES, TRUE) || !$campagne || ($don['amount'] ?? 0) <= 0 || ($don['external_id'] ?? '') === '')
		{
			return FALSE;
		}

		if (!$this->db->from('nf_donations')->where('source', $source)->where('external_id', $don['external_id'])->empty())
		{
			return FALSE;
		}

		$membre = $don['email'] !== '' ? $this->db->select('id')->from('nf_user')->where('email', $don['email'])->where('deleted', '0')->row() : NULL;

		try
		{
			$this->db->insert('nf_donations', [
				'campaign_id'   => $campagne,
				'user_id'       => $membre ? (int) $membre : NULL,
				'donor_name'    => $don['donor_name'],
				'amount'        => $don['amount'],
				'currency'      => $don['currency'],
				'message'       => $don['message'] !== '' ? $don['message'] : NULL,
				'is_anonymous'  => 0,
				'is_public'     => $don['is_public'] ? 1 : 0,
				'source'        => $source,
				'external_id'   => $don['external_id'],
				'paypal_txn_id' => $source === 'paypal' ? $don['external_id'] : NULL,
				'status'        => 'completed',
			]);
		}
		catch (\Throwable $e)
		{
			return FALSE; // inséré entre-temps : la notification arrivait deux fois
		}

		return TRUE;
	}

	/**
	 * HelloAsso : la commande relue par l'API, avec les identifiants de l'association (client credentials, OAuth2).
	 * NULL si le service ne répond pas — la notification sera refaite ; FALSE si la commande n'existe pas pour ce
	 * compte : elle ne vient pas de l'association.
	 *
	 * @return array<string, mixed>|false|null
	 */
	public function helloasso_commande(string $numero): array|false|null
	{
		$base  = $this->adresse_service('helloasso');
		$jeton = $this->_http('POST', $base.'/oauth2/token', [], http_build_query([
			'grant_type'    => 'client_credentials',
			'client_id'     => (string) $this->config->nf_donations_helloasso_client_id,
			'client_secret' => $this->secret('nf_donations_helloasso_secret'),
		]), 'application/x-www-form-urlencoded');

		if ($jeton === NULL || $jeton['code'] !== 200 || !is_string($acces = $jeton['json']['access_token'] ?? NULL))
		{
			return NULL;
		}

		$commande = $this->_http('GET', $base.'/v5/orders/'.rawurlencode($numero), ['Authorization: Bearer '.$acces]);

		if ($commande === NULL || $commande['code'] >= 500)
		{
			return NULL;
		}

		return $commande['code'] === 200 && is_array($commande['json']) ? $commande['json'] : FALSE;
	}

	/** PayPal : la notification renvoyée telle quelle à PayPal, qui répond VERIFIED ou INVALID. NULL s'il ne répond pas. */
	public function paypal_confirme(string $brut): ?bool
	{
		$reponse = $this->_http('POST', $this->adresse_service('paypal'), ['User-Agent: NeoFrag-Reborn-IPN'], 'cmd=_notify-validate&'.$brut, 'application/x-www-form-urlencoded');

		if ($reponse === NULL || $reponse['code'] !== 200)
		{
			return NULL;
		}

		return trim($reponse['brut']) === 'VERIFIED';
	}

	/**
	 * TipeeeStream : relève les dons reçus depuis que le service est relié (la tâche planifiée). Rend le nombre de
	 * dons nouveaux, ou NULL si le service n'a pas répondu.
	 */
	public function relever_tipeeestream(): ?int
	{
		$cle      = $this->secret('nf_donations_tipeeestream_key');
		$campagne = $this->campagne_du_service('tipeeestream');

		if ($cle === '' || !$campagne)
		{
			return 0;
		}

		$reponse = $this->_http('GET', $this->adresse_service('tipeeestream').'/v1.0/events.json?'.http_build_query(['apiKey' => $cle, 'type' => ['donation'], 'limit' => 50]));

		if ($reponse === NULL || $reponse['code'] !== 200 || !is_array($reponse['json']))
		{
			return NULL;
		}

		$nouveaux = 0;

		foreach (\NF\Modules\Donations\Lib\Fournisseurs::tipeeestream($reponse['json'], (int) $this->config->nf_donations_tipeeestream_since) as $don)
		{
			$nouveaux += (int) $this->enregistrer_externe('tipeeestream', $campagne, $don);
		}

		return $nouveaux;
	}

	/** @return array{code: int, brut: string, json: mixed}|null NULL si le service est injoignable */
	private function _http(string $methode, string $adresse, array $entetes = [], ?string $corps = NULL, string $type = 'application/json'): ?array
	{
		if (!preg_match('#^https?://#', $adresse))
		{
			return NULL;
		}

		$c = curl_init($adresse);

		if ($corps !== NULL)
		{
			$entetes[] = 'Content-Type: '.$type;
			curl_setopt($c, CURLOPT_POSTFIELDS, $corps);
		}

		curl_setopt_array($c, [
			CURLOPT_CUSTOMREQUEST  => $methode,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HTTPHEADER     => array_merge(['Accept: application/json'], $entetes),
			CURLOPT_TIMEOUT        => 15,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS | CURLPROTO_HTTP,
		]);

		$brut = curl_exec($c);

		if (!is_string($brut))
		{
			return NULL;
		}

		return ['code' => (int) curl_getinfo($c, CURLINFO_RESPONSE_CODE), 'brut' => $brut, 'json' => json_decode($brut, TRUE)];
	}
}
