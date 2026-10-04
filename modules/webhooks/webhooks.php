<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Webhooks — émission de webhooks sortants signés (HMAC) vers des URLs externes,
 * déclenchés par les événements du site. Appeler depuis un module :
 *   \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['webhooks'])?->trigger('news.published', [...]);
 */

namespace NF\Modules\Webhooks;

use NF\NeoFrag\Addons\Module;
use NF\Modules\Webhooks\Lib\Discord;

require_once __DIR__.'/lib/discord.php';

class Webhooks extends Module
{
	/*
	 * Les événements disponibles : des clés techniques, sans libellé. Une constante ne peut pas
	 * appeler lang() — le formulaire d'administration affichait donc les libellés français qu'elle
	 * portait, sur un site anglais. Pour AFFICHER un événement : event_labels(), ci-dessous.
	 */
	// `stream.live` : une chaîne suivie par le widget « Statut live » passe en direct (widgets/twitch,
	// contrôleur `cron`). Ajouté le 2026-09-23 avec le format Discord.
	const EVENTS = ['news.published', 'article.published', 'user.registered', 'comment.created', 'forum.topic', 'stream.live'];

	protected function __info()
	{
		return [
			'title'       => $this->lang('Webhooks'),
			'description' => $this->lang('Notifie des services externes (Discord, Zapier…) par webhook signé à chaque événement.'),
			'icon'        => 'fas fa-bolt',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			// install/seed.sql l'enregistre dans le coeur : la declaration doit dire ce qui est
			// REELLEMENT installe. Ses appelants le traitent pourtant deja comme optionnel
			// (`if ($wh = $this->module('webhooks'))`), donc son passage hors coeur sera possible
			// quand le seed sera regenere pour le paquet allege. Cf. TODO §1d.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
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
						'title'  => $this->lang('Webhooks'),
						'icon'   => 'fas fa-bolt',
						'access' => [
							'manage' => ['title' => $this->lang('Gérer les webhooks'), 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}

	/** Les libellés TRADUITS des événements (mêmes clés et mêmes textes que EVENTS). */
	public function event_labels()
	{
		return [
			'news.published'    => $this->lang('Actualité publiée'),
			'article.published' => $this->lang('Article publié'),
			'user.registered'   => $this->lang('Nouveau membre'),
			'comment.created'   => $this->lang('Nouveau commentaire'),
			'forum.topic'       => $this->lang('Nouveau sujet forum'),
			'stream.live'       => $this->lang('Chaîne en direct'),
		];
	}

	/**
	 * Le message d'un événement, pour Discord : des textes traduits dans la langue du site, et
	 * l'adresse de ce dont il parle. Le formatage — limites, adresse absolue, couleur — est à Discord::corps().
	 *
	 * @return array{titre: string, description?: string, url?: string, image?: string}
	 */
	public function discord_message(string $event, array $data): array
	{
		switch ($event)
		{
			case 'news.published':
				return ['titre' => $this->lang('Nouvelle actualité : %s', (string) ($data['title'] ?? '')), 'url' => (string) ($data['url'] ?? '')];

			case 'article.published':
				return ['titre' => $this->lang('Nouvel article : %s', (string) ($data['title'] ?? '')), 'url' => (string) ($data['url'] ?? '')];

			case 'user.registered':
				return [
					'titre'       => $this->lang('Bienvenue à %s !', (string) ($data['username'] ?? '')),
					'description' => $this->lang('Un nouveau membre vient de rejoindre la communauté.'),
					'url'         => url('user/'.(int) ($data['user_id'] ?? 0).'/'.url_title((string) ($data['username'] ?? ''))),
				];

			case 'comment.created':
				// Le commentaire ne dit que le module et l'identifiant : c'est le module commenté qui sait
				// le titre et l'adresse de son contenu (sa méthode `comments()`, celle qu'emploie la modération).
				$cible  = NULL;
				$module = (string) ($data['module'] ?? '');

				if ($module !== '' && ($commente = $this->module($module)) && method_exists($commente, 'comments'))
				{
					$cible = $commente->comments((int) ($data['module_id'] ?? 0));
				}

				return [
					'titre'       => $this->lang('Nouveau commentaire de %s', (string) ($data['username'] ?? '')),
					'description' => is_array($cible) ? (string) ($cible['title'] ?? '') : '',
					'url'         => is_array($cible) && !empty($cible['url']) ? url((string) $cible['url']) : '',
				];

			case 'forum.topic':
				return ['titre' => $this->lang('Nouveau sujet : %s', (string) ($data['title'] ?? '')), 'url' => (string) ($data['url'] ?? '')];

			case 'stream.live':
				return [
					'titre'       => $this->lang('%s est en direct', (string) ($data['display_name'] ?? $data['channel'] ?? '')),
					'description' => trim((string) ($data['title'] ?? '').(!empty($data['game']) ? ' — '.$data['game'] : '')),
					'url'         => (string) ($data['url'] ?? ''),
					'image'       => (string) ($data['thumbnail'] ?? ''),
				];

			case 'webhook.test':
				return ['titre' => $this->lang('Test du webhook'), 'description' => $this->lang('Si ce message apparaît, le site sait écrire dans ce salon.')];
		}

		return ['titre' => $event];
	}

	/** Le corps pour cette adresse : le format de Discord si c'est un salon Discord, le format générique sinon. */
	private function _corps(string $url, string $event, array $data, string $generique): string
	{
		if (!Discord::est_adresse($url))
		{
			return $generique;
		}

		return (string) json_encode(
			Discord::corps($event, $this->discord_message($event, $data), (string) $this->config->nf_name, site_origin()),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);
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
				$this->_dispatch($hook['url'], $this->_corps((string) $hook['url'], $event, $payload, (string) $body), (string)$hook['secret']);
			}
		}
	}

	/** Émet un payload de test vers UN webhook et renvoie l'issue (bouton admin « Tester »). */
	public function test(array $hook)
	{
		$data = ['message' => 'NeoFrag webhook test', 'webhook' => $hook['title'] ?? ''];
		$body = json_encode([
			'event'     => 'webhook.test',
			'data'      => $data,
			'timestamp' => time()
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return $this->_dispatch($hook['url'], $this->_corps((string) $hook['url'], 'webhook.test', $data, (string) $body), (string)($hook['secret'] ?? ''));
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

		if (!$ok || $error !== '')
		{
			return ['ok' => FALSE, 'status' => $status ?: NULL, 'error' => $error ?: 'request_failed'];
		}

		return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'error' => NULL];
	}

	/**
	 * Résout $host en IP et la renvoie UNIQUEMENT si elle est publique, NULL sinon.
	 *
	 * L'implémentation vit désormais dans `neofrag/helpers/remote.php`, d'où elle est partagée avec
	 * les autres addons qui sortent sur le réseau (le lecteur de flux RSS, par exemple). Elle est
	 * restée ici en relais pour ne rien changer à ce qui appelle cette méthode.
	 */
	protected function _resolve_public_ip($host)
	{
		return nf_resolve_public_ip((string) $host);
	}
}
