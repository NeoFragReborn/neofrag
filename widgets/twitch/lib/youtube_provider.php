<?php
/**
 * https://neofr.ag
 * Provider YouTube — YouTube Data API v3 (clé API + quota). search.list(eventType=live) pour la
 * détection du direct, channels.list pour le nom/avatar, videos.list pour les spectateurs concurrents.
 * ⚠ Nécessite une clé Google (quota strict) → non testable sans clé ; logique couverte par tests (faux $http).
 */

namespace NF\Widgets\Twitch\Lib;

class Youtube_Provider implements Live_Provider
{
	public static function key(): string { return 'youtube'; }
	public static function label(): string { return 'YouTube'; }
	public static function credentials(): array { return ['api_key']; }

	public function fetch(string $channel, array $creds, callable $http): ?array
	{
		$key = (string)($creds['api_key'] ?? '');

		if ($key === '')
		{
			return NULL;
		}

		$chan = $http('GET', 'https://www.googleapis.com/youtube/v3/channels?part=snippet&id='.urlencode($channel).'&key='.urlencode($key));
		$snip = $chan['items'][0]['snippet'] ?? NULL;

		if (!$snip)
		{
			return NULL; // chaîne inconnue / clé invalide
		}

		$search    = $http('GET', 'https://www.googleapis.com/youtube/v3/search?part=snippet&channelId='.urlencode($channel).'&eventType=live&type=video&maxResults=1&key='.urlencode($key));
		$live_item = $search['items'][0] ?? NULL;
		$is_live   = $live_item !== NULL;
		$video_id  = (string)($live_item['id']['videoId'] ?? '');

		$viewers = NULL;
		if ($is_live && $video_id !== '')
		{
			$vid = $http('GET', 'https://www.googleapis.com/youtube/v3/videos?part=liveStreamingDetails&id='.urlencode($video_id).'&key='.urlencode($key));
			$cv  = $vid['items'][0]['liveStreamingDetails']['concurrentViewers'] ?? NULL;
			$viewers = $cv !== NULL ? (int)$cv : NULL;
		}

		return [
			'provider'     => 'youtube',
			'channel'      => $channel,
			'display_name' => (string)($snip['title'] ?? $channel),
			'avatar'       => (string)($snip['thumbnails']['default']['url'] ?? ''),
			'is_live'      => $is_live,
			'title'        => $is_live ? (string)($live_item['snippet']['title'] ?? '') : '',
			'game'         => '',
			'viewers'      => $viewers,
			'thumbnail'    => $is_live ? (string)($live_item['snippet']['thumbnails']['medium']['url'] ?? '') : '',
			'channel_url'  => 'https://www.youtube.com/channel/'.rawurlencode($channel),
			'embed_url'    => $is_live && $video_id !== '' ? 'https://www.youtube.com/embed/'.rawurlencode($video_id) : 'https://www.youtube.com/channel/'.rawurlencode($channel).'/live',
		];
	}
}
