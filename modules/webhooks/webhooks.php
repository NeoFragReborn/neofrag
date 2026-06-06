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

	protected function _dispatch($url, $body, $secret)
	{
		if (!preg_match('#^https?://#i', $url) || !function_exists('curl_init'))
		{
			return;
		}

		$headers = ['Content-Type: application/json', 'User-Agent: NeoFrag-Webhooks/1.0'];

		if ($secret !== '')
		{
			$headers[] = 'X-NeoFrag-Event: '.preg_replace('/[^a-z0-9._-]/i', '', json_decode($body, TRUE)['event'] ?? '');
			$headers[] = 'X-NeoFrag-Signature: sha256='.hash_hmac('sha256', $body, $secret);
		}

		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_POST           => TRUE,
			CURLOPT_POSTFIELDS     => $body,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_TIMEOUT        => 5,
			CURLOPT_CONNECTTIMEOUT => 3,
			CURLOPT_HTTPHEADER     => $headers
		]);
		curl_exec($ch);
		curl_close($ch);
	}
}
