<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Twitch\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	const CACHE_TTL_STREAM = 60;
	const CACHE_TTL_USER   = 3600;

	public function index($settings = [])
	{
		$username      = strtolower(trim($settings['username']      ?? ''));
		$client_id     = trim($settings['client_id']     ?? '');
		$client_secret = trim($settings['client_secret'] ?? '');
		$open_mode     = ($settings['open_mode']         ?? 'popup') === 'newtab' ? 'newtab' : 'popup';
		$show_offline  = ($settings['show_offline']      ?? '1') === '1';

		if (!$username)
		{
			return $this->panel()
						->heading($this->lang('Twitch'), 'fab fa-twitch')
						->body('<div class="alert alert-warning m-2">'.$this->lang('Configurez le pseudo Twitch dans le panneau d\'administration.').'</div>', FALSE);
		}

		$user = $stream = NULL;

		// API access requires creds. Without them, we fallback to public-only display.
		if ($client_id && $client_secret)
		{
			$user   = $this->_fetch_user($username, $client_id, $client_secret);
			$stream = $this->_fetch_stream($username, $client_id, $client_secret);
		}

		$this->css('twitch');

		$is_live = !empty($stream['type']) && $stream['type'] === 'live';

		// Build embed URLs
		$embed_url   = 'https://player.twitch.tv/?channel='.urlencode($username).'&parent='.urlencode($_SERVER['HTTP_HOST'] ?? 'localhost');
		$channel_url = 'https://www.twitch.tv/'.urlencode($username);

		// If offline + show_offline=0 + we know it's offline (creds OK), hide widget
		if (!$is_live && !$show_offline && $user)
		{
			return '';
		}

		return $this->panel()
					->heading($this->lang('Twitch'), 'fab fa-twitch')
					->body($this->view('index', [
						'username'    => $username,
						'user'        => $user,
						'stream'      => $stream,
						'is_live'     => $is_live,
						'embed_url'   => $embed_url,
						'channel_url' => $channel_url,
						'open_mode'   => $open_mode,
						'has_creds'   => $client_id && $client_secret
					]), FALSE);
	}

	private function _get_token($client_id, $client_secret)
	{
		$cache_dir  = 'cache/widget_twitch';
		$token_file = $cache_dir.'/token_'.md5($client_id).'.json';

		if (is_file($token_file))
		{
			$cached = @json_decode(file_get_contents($token_file), TRUE);
			if (is_array($cached) && !empty($cached['access_token']) && ($cached['expires_at'] ?? 0) > time() + 60)
			{
				return $cached['access_token'];
			}
		}

		$resp = @$this->network('https://id.twitch.tv/oauth2/token', ['timeout' => 5])
					   ->header('Content-Type: application/x-www-form-urlencoded')
					   ->post([
						   'client_id'     => $client_id,
						   'client_secret' => $client_secret,
						   'grant_type'    => 'client_credentials'
					   ]);

		if (!$resp) return NULL;
		$data = @json_decode($resp, TRUE);
		if (empty($data['access_token'])) return NULL;

		if (!is_dir($cache_dir)) @mkdir($cache_dir, 0775, TRUE);
		@file_put_contents($token_file, json_encode([
			'access_token' => $data['access_token'],
			'expires_at'   => time() + (int)($data['expires_in'] ?? 3600)
		]));

		return $data['access_token'];
	}

	private function _api_get($url, $client_id, $client_secret, $cache_ttl)
	{
		$cache_dir  = 'cache/widget_twitch';
		$cache_file = $cache_dir.'/'.md5($url).'.json';

		if (is_file($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl)
		{
			return @json_decode(file_get_contents($cache_file), TRUE);
		}

		$token = $this->_get_token($client_id, $client_secret);
		if (!$token) return NULL;

		$resp = @$this->network($url, ['timeout' => 5])
					   ->header('Client-Id: '.$client_id)
					   ->header('Authorization: Bearer '.$token)
					   ->get();

		if (!$resp) return NULL;
		$data = @json_decode($resp, TRUE);
		if (!is_array($data)) return NULL;

		if (!is_dir($cache_dir)) @mkdir($cache_dir, 0775, TRUE);
		@file_put_contents($cache_file, json_encode($data));

		return $data;
	}

	private function _fetch_user($username, $client_id, $client_secret)
	{
		$data = $this->_api_get('https://api.twitch.tv/helix/users?login='.urlencode($username), $client_id, $client_secret, self::CACHE_TTL_USER);
		return $data['data'][0] ?? NULL;
	}

	private function _fetch_stream($username, $client_id, $client_secret)
	{
		$data = $this->_api_get('https://api.twitch.tv/helix/streams?user_login='.urlencode($username), $client_id, $client_secret, self::CACHE_TTL_STREAM);
		return $data['data'][0] ?? NULL;
	}
}
