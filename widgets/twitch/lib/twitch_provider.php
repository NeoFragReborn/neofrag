<?php
/**
 * https://neofr.ag
 * Provider Twitch — token app (client_credentials) + Helix users/streams, normalisé.
 */

namespace NF\Widgets\Twitch\Lib;

class Twitch_Provider implements Live_Provider
{
	public static function key(): string { return 'twitch'; }
	public static function label(): string { return 'Twitch'; }
	public static function credentials(): array { return ['client_id', 'client_secret']; }

	public function fetch(string $channel, array $creds, callable $http): ?array
	{
		$client_id     = (string)($creds['client_id'] ?? '');
		$client_secret = (string)($creds['client_secret'] ?? '');

		if ($client_id === '' || $client_secret === '')
		{
			return NULL;
		}

		$token = $http('POST', 'https://id.twitch.tv/oauth2/token', ['Content-Type: application/x-www-form-urlencoded'], [
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'grant_type'    => 'client_credentials',
		]);

		if (empty($token['access_token']))
		{
			return NULL;
		}

		$headers = ['Client-Id: '.$client_id, 'Authorization: Bearer '.$token['access_token']];

		$user   = $http('GET', 'https://api.twitch.tv/helix/users?login='.urlencode($channel), $headers);
		$stream = $http('GET', 'https://api.twitch.tv/helix/streams?user_login='.urlencode($channel), $headers);

		$u = $user['data'][0] ?? NULL;
		$s = $stream['data'][0] ?? NULL;

		if (!$u)
		{
			return NULL;
		}

		$is_live = !empty($s['type']) && $s['type'] === 'live';

		return [
			'provider'     => 'twitch',
			'channel'      => $channel,
			'display_name' => (string)($u['display_name'] ?? $channel),
			'avatar'       => (string)($u['profile_image_url'] ?? ''),
			'is_live'      => $is_live,
			'title'        => $is_live ? (string)($s['title'] ?? '') : '',
			'game'         => $is_live ? (string)($s['game_name'] ?? '') : '',
			'viewers'      => $is_live && isset($s['viewer_count']) ? (int)$s['viewer_count'] : NULL,
			'thumbnail'    => $is_live && !empty($s['thumbnail_url']) ? str_replace(['{width}', '{height}'], ['440', '248'], (string)$s['thumbnail_url']) : '',
			'channel_url'  => 'https://www.twitch.tv/'.rawurlencode($channel),
			'embed_url'    => 'https://player.twitch.tv/?channel='.rawurlencode($channel),
		];
	}
}
