<?php
/**
 * https://neofr.ag
 * Module Webhooks — émission de webhooks sortants signés (HMAC) vers des URLs externes,
 * déclenchés par les événements du site. Appeler depuis un module :
 *   \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['webhooks'])?->trigger('news.published', [...]);
 */

namespace NF\Modules\Webhooks;

use NF\NeoFrag\Addons\Module;

class Webhooks extends Module
{
	/** Événements disponibles (clé technique => libellé). */
	const EVENTS = [
		'news.published'    => 'Actualité publiée',
		'article.published' => 'Article publié',
		'user.registered'   => 'Nouveau membre',
		'comment.created'   => 'Nouveau commentaire',
		'forum.topic'       => 'Nouveau sujet forum',
	];

	protected function __info()
	{
		return [
			'title'       => $this->lang('Webhooks'),
			'description' => $this->lang('Notifie des services externes (Discord, Zapier…) par webhook signé à chaque événement.'),
			'icon'        => 'fas fa-bolt',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				'admin{pages}'                  => 'index',
				'admin/add'                     => '_add',
				'admin/edit/{id}/{url_title}'   => '_edit',
				'admin/test/{id}/{url_title}'   => '_test',
				'admin/delete/{id}/{url_title}' => '_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => 'Webhooks',
						'icon'   => 'fas fa-bolt',
						'access' => [
							'manage' => ['title' => $this->lang('Gérer les webhooks'), 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}

	/** Émet un événement vers tous les webhooks abonnés. Fire-and-forget, ne casse jamais l'appelant. */
	public function trigger($event, array $payload = [])
	{
		try
		{
			$hooks = $this->db->select('url', 'secret', 'events')->from('nf_webhooks')->where('enabled', 1)->get();
		}
		catch (\Throwable $e)
		{
			return;
		}

		if (!$hooks)
		{
			return;
		}

		$body = json_encode([
			'event'     => $event,
			'data'      => $payload,
			'timestamp' => time()
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		foreach ($hooks as $hook)
		{
			$events = array_filter(array_map('trim', explode(',', (string)$hook['events'])));

			if (in_array('*', $events, TRUE) || in_array($event, $events, TRUE))
			{
				$this->_dispatch($hook['url'], $body, (string)$hook['secret']);
			}
		}
	}

	/** Émet un payload de test vers UN webhook et renvoie l'issue (bouton admin « Tester »). */
	public function test(array $hook)
	{
		$body = json_encode([
			'event'     => 'webhook.test',
			'data'      => ['message' => 'NeoFrag webhook test', 'webhook' => $hook['title'] ?? ''],
			'timestamp' => time()
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return $this->_dispatch($hook['url'], $body, (string)($hook['secret'] ?? ''));
	}

	// Renvoie ['ok' => bool, 'status' => ?int, 'error' => ?string]. `trigger()` ignore le retour (fire-and-forget) ;
	// `test()` s'en sert pour le retour admin.
	protected function _dispatch($url, $body, $secret)
	{
		if (!preg_match('#^https?://#i', $url) || !function_exists('curl_init'))
		{
			return ['ok' => FALSE, 'status' => NULL, 'error' => 'invalid_url'];
		}

		// Anti-SSRF : un webhook (URL saisie en admin) ne doit pas pouvoir cibler le réseau interne
		// ni les métadonnées cloud. On résout l'hôte vers une IP publique et on ÉPINGLE cette IP
		// (CURLOPT_RESOLVE) pour fermer la fenêtre de DNS rebinding entre la validation et la requête.
		$parts = parse_url($url);
		$host  = $parts['host'] ?? '';
		$port  = $parts['port'] ?? (strtolower($parts['scheme'] ?? '') === 'https' ? 443 : 80);

		if (!$host || !($ip = $this->_resolve_public_ip($host)))
		{
			return ['ok' => FALSE, 'status' => NULL, 'error' => 'ssrf_blocked'];
		}

		$headers = ['Content-Type: application/json', 'User-Agent: NeoFrag-Webhooks/1.0'];

		if ($secret !== '')
		{
			$headers[] = 'X-NeoFrag-Event: '.preg_replace('/[^a-z0-9._-]/i', '', json_decode($body, TRUE)['event'] ?? '');
			$headers[] = 'X-NeoFrag-Signature: sha256='.hash_hmac('sha256', $body, $secret);
		}

		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_POST            => TRUE,
			CURLOPT_POSTFIELDS      => $body,
			CURLOPT_RETURNTRANSFER  => TRUE,
			CURLOPT_TIMEOUT         => 5,
			CURLOPT_CONNECTTIMEOUT  => 3,
			CURLOPT_HTTPHEADER      => $headers,
			CURLOPT_PROTOCOLS       => CURLPROTO_HTTP | CURLPROTO_HTTPS,
			CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
			CURLOPT_FOLLOWLOCATION  => FALSE,
			CURLOPT_RESOLVE         => [$host.':'.$port.':'.$ip]
		]);
		$ok     = curl_exec($ch) !== FALSE;
		$status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		$error  = curl_error($ch);
		curl_close($ch);

		if (!$ok || $error !== '')
		{
			return ['ok' => FALSE, 'status' => $status ?: NULL, 'error' => $error ?: 'request_failed'];
		}

		return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'error' => NULL];
	}

	// Résout $host en IP et la renvoie UNIQUEMENT si elle est publique (rejette loopback/privé/
	// link-local/réservé, IPv4 et IPv6). NULL sinon. Si l'hôte est déjà une IP littérale, on la valide telle quelle.
	protected function _resolve_public_ip($host)
	{
		$ips = [];

		if (filter_var($host, FILTER_VALIDATE_IP))
		{
			$ips[] = $host;
		}
		else
		{
			foreach ((array)@dns_get_record($host, DNS_A | DNS_AAAA) as $record)
			{
				if (!empty($record['ip']))   { $ips[] = $record['ip']; }
				if (!empty($record['ipv6'])) { $ips[] = $record['ipv6']; }
			}

			if (!$ips && ($resolved = gethostbyname($host)) && $resolved !== $host)
			{
				$ips[] = $resolved;
			}
		}

		if (!$ips)
		{
			return NULL;
		}

		// TOUTES les IP résolues doivent être publiques (sinon un attaquant fait résoudre vers une IP interne).
		foreach ($ips as $ip)
		{
			if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
			{
				return NULL;
			}
		}

		return $ips[0];
	}
}
