<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Widget « Statut live » multi-chaînes / multi-plateformes (Twitch, YouTube). Les chaînes sont déclarées
 * en `provider:chaîne` ; chaque provider (lib/) normalise son statut, le contrôleur orchestre le cache HTTP.
 */

namespace NF\Widgets\Twitch\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;
use NF\Widgets\Twitch\Lib\Twitch_Provider;
use NF\Widgets\Twitch\Lib\Youtube_Provider;

class Index extends Controller_Widget
{
	const CACHE_TTL     = 60;  // statut live (GET)
	const NEG_CACHE_TTL = 120; // après un échec, pas de re-tentative bloquante
	const MAX_CHANNELS  = 12;

	public function index($settings = [])
	{
		$channels = $this->_parse_channels($settings);

		if (!$channels)
		{
			return $this->panel()
						->heading($this->lang('Live'), 'fas fa-broadcast-tower')
						->body('<div class="alert alert-warning m-2">'.$this->lang('Configurez au moins une chaîne dans le panneau d\'administration.').'</div>', FALSE);
		}

		$creds = [
			'client_id'     => trim($settings['client_id']     ?? ''),
			'client_secret' => trim($settings['client_secret'] ?? ''),
			'api_key'       => trim($settings['api_key']       ?? ''),
		];

		$show_offline = ($settings['show_offline'] ?? '1') === '1';
		$open_mode    = ($settings['open_mode']    ?? 'popup') === 'newtab' ? 'newtab' : 'popup';

		$providers = ['twitch' => new Twitch_Provider(), 'youtube' => new Youtube_Provider()];
		$http      = $this->_make_http();

		$results = [];
		foreach ($channels as $c)
		{
			if (!isset($providers[$c['provider']]))
			{
				continue;
			}

			$status = $providers[$c['provider']]->fetch($c['channel'], $creds, $http);
			$results[] = $status ?: $this->_unknown_status($c['provider'], $c['channel']);
		}

		// Direct d'abord.
		usort($results, function($a, $b){ return (int)($b['is_live'] ?? FALSE) <=> (int)($a['is_live'] ?? FALSE); });

		// Masquer les hors-ligne CONFIRMÉS si l'option le demande (on garde les « inconnus » = statut indispo).
		if (!$show_offline)
		{
			$results = array_values(array_filter($results, function($r){ return !empty($r['is_live']) || !empty($r['unknown']); }));
		}

		if (!$results)
		{
			return '';
		}

		$this->css('twitch');

		return $this->panel()
					->heading($this->lang('Live'), 'fas fa-broadcast-tower')
					->body($this->view('index', ['channels' => $results, 'open_mode' => $open_mode]), FALSE);
	}

	/** « provider:chaîne » par ligne → liste normalisée ; rétro-compat de l'ancien réglage `username` (Twitch). */
	public function _parse_channels($settings)
	{
		$out = [];

		foreach (preg_split('/[\r\n]+/', (string)($settings['channels'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $line)
		{
			$line = trim($line);

			if (strpos($line, ':') !== FALSE)
			{
				list($p, $ch) = explode(':', $line, 2);
			}
			else
			{
				$p = 'twitch'; $ch = $line;
			}

			$p  = strtolower(trim($p));
			$ch = trim($ch);

			if ($ch !== '' && in_array($p, ['twitch', 'youtube'], TRUE))
			{
				$out[] = ['provider' => $p, 'channel' => $ch];
			}
		}

		if (!$out && !empty($settings['username']))
		{
			$out[] = ['provider' => 'twitch', 'channel' => strtolower(trim($settings['username']))];
		}

		return array_slice($out, 0, self::MAX_CHANNELS);
	}

	// Entrée minimale quand le fetch échoue (creds absents / API KO) : la chaîne reste affichée en « statut indispo ».
	private function _unknown_status($provider, $channel)
	{
		$urls = [
			'twitch'  => 'https://www.twitch.tv/'.rawurlencode($channel),
			'youtube' => 'https://www.youtube.com/channel/'.rawurlencode($channel),
		];

		return [
			'provider'     => $provider,
			'channel'      => $channel,
			'display_name' => $channel,
			'avatar'       => '',
			'is_live'      => FALSE,
			'unknown'      => TRUE,
			'title'        => '',
			'game'         => '',
			'viewers'      => NULL,
			'thumbnail'    => '',
			'channel_url'  => $urls[$provider] ?? '#',
			'embed_url'    => '',
		];
	}

	/** Transport HTTP caché partagé par les providers : GET caché (CACHE_TTL), POST (token) caché selon expires_in,
	 *  cache négatif après échec. Signature : fn(method, url, headers[], body?): ?array. */
	public function _make_http(): callable
	{
		$cache_dir = 'cache/widget_twitch';

		return function(string $method, string $url, array $headers = [], $body = NULL) use ($cache_dir)
		{
			$is_get     = strtoupper($method) === 'GET';
			$key        = md5($method.'|'.$url.'|'.json_encode($body));
			$cache_file = $cache_dir.'/'.$key.'.json';
			$fail_file  = $cache_dir.'/'.$key.'.fail';

			if (is_file($cache_file))
			{
				$cached = @json_decode(file_get_contents($cache_file), TRUE);
				if (is_array($cached) && ($cached['_exp'] ?? 0) > time())
				{
					return $cached['_data'];
				}
			}

			if (is_file($fail_file) && (time() - filemtime($fail_file)) < self::NEG_CACHE_TTL)
			{
				return NULL;
			}

			$mark_fail = function() use ($cache_dir, $fail_file) {
				if (!is_dir($cache_dir)) { @mkdir($cache_dir, 0775, TRUE); }
				@touch($fail_file);
			};

			$req = $this->network($url, ['timeout' => 5])->type('text');
			foreach ($headers as $h)
			{
				$req->header($h);
			}

			$resp = $is_get ? @$req->get() : @$req->post($body);

			if (!$resp || !is_array($data = @json_decode($resp, TRUE)))
			{
				$mark_fail();
				return NULL;
			}

			$ttl = (!$is_get && isset($data['expires_in'])) ? max(60, (int)$data['expires_in'] - 60) : self::CACHE_TTL;

			if (!is_dir($cache_dir)) { @mkdir($cache_dir, 0775, TRUE); }
			@unlink($fail_file);
			@file_put_contents($cache_file, json_encode(['_exp' => time() + $ttl, '_data' => $data]));

			return $data;
		};
	}
}
