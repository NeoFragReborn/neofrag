<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Widgets\Twitch\Lib\Twitch_Provider;
use NF\Widgets\Twitch\Lib\Youtube_Provider;

/**
 * Tests unitaires PURS des providers de statut live (Twitch, YouTube). On injecte un faux $http qui
 * renvoie des réponses API canoniques → on couvre fetch + normalisation **sans réseau ni clé réelle**
 * (c'est précisément le but de l'abstraction Live_Provider).
 */
final class LiveProviderTest extends TestCase
{
	/** Faux transport : matche l'URL par sous-chaîne → réponse canée. */
	private function http(array $responses): callable
	{
		return function(string $method, string $url, array $headers = [], $body = null) use ($responses) {
			foreach ($responses as $needle => $resp) {
				if (strpos($url, $needle) !== false) { return $resp; }
			}
			return null;
		};
	}

	public function test_twitch_live(): void
	{
		$http = $this->http([
			'id.twitch.tv/oauth2/token' => ['access_token' => 'tok', 'expires_in' => 3600],
			'helix/users'               => ['data' => [['display_name' => 'Ninja', 'profile_image_url' => 'http://av']]],
			'helix/streams'             => ['data' => [['type' => 'live', 'title' => 'Stream!', 'game_name' => 'Fortnite', 'viewer_count' => 1234, 'thumbnail_url' => 'http://t/{width}x{height}.jpg']]],
		]);

		$s = (new Twitch_Provider())->fetch('ninja', ['client_id' => 'a', 'client_secret' => 'b'], $http);

		$this->assertSame('twitch', $s['provider']);
		$this->assertTrue($s['is_live']);
		$this->assertSame('Ninja', $s['display_name']);
		$this->assertSame('http://av', $s['avatar']);
		$this->assertSame('Fortnite', $s['game']);
		$this->assertSame(1234, $s['viewers']);
		$this->assertSame('http://t/440x248.jpg', $s['thumbnail']);
		$this->assertStringContainsString('twitch.tv/ninja', $s['channel_url']);
	}

	public function test_twitch_offline(): void
	{
		$http = $this->http([
			'id.twitch.tv/oauth2/token' => ['access_token' => 'tok'],
			'helix/users'               => ['data' => [['display_name' => 'Ninja']]],
			'helix/streams'             => ['data' => []],
		]);

		$s = (new Twitch_Provider())->fetch('ninja', ['client_id' => 'a', 'client_secret' => 'b'], $http);

		$this->assertFalse($s['is_live']);
		$this->assertNull($s['viewers']);
		$this->assertSame('', $s['game']);
	}

	public function test_twitch_without_credentials_is_null(): void
	{
		$called = false;
		$http = function() use (&$called) { $called = true; return null; };

		$this->assertNull((new Twitch_Provider())->fetch('ninja', [], $http));
		$this->assertFalse($called, 'aucune requête sans identifiants');
	}

	public function test_youtube_live(): void
	{
		$http = $this->http([
			'youtube/v3/channels' => ['items' => [['snippet' => ['title' => 'MyChan', 'thumbnails' => ['default' => ['url' => 'http://yt-av']]]]]],
			'youtube/v3/search'   => ['items' => [['id' => ['videoId' => 'VID'], 'snippet' => ['title' => 'Live now', 'thumbnails' => ['medium' => ['url' => 'http://yt-thumb']]]]]],
			'youtube/v3/videos'   => ['items' => [['liveStreamingDetails' => ['concurrentViewers' => '567']]]],
		]);

		$s = (new Youtube_Provider())->fetch('UC123', ['api_key' => 'k'], $http);

		$this->assertSame('youtube', $s['provider']);
		$this->assertTrue($s['is_live']);
		$this->assertSame('MyChan', $s['display_name']);
		$this->assertSame('http://yt-av', $s['avatar']);
		$this->assertSame('Live now', $s['title']);
		$this->assertSame(567, $s['viewers']);
		$this->assertSame('http://yt-thumb', $s['thumbnail']);
		$this->assertStringContainsString('embed/VID', $s['embed_url']);
		$this->assertStringContainsString('youtube.com/channel/UC123', $s['channel_url']);
	}

	public function test_youtube_offline(): void
	{
		$http = $this->http([
			'youtube/v3/channels' => ['items' => [['snippet' => ['title' => 'MyChan']]]],
			'youtube/v3/search'   => ['items' => []],
		]);

		$s = (new Youtube_Provider())->fetch('UC123', ['api_key' => 'k'], $http);

		$this->assertFalse($s['is_live']);
		$this->assertSame('MyChan', $s['display_name']);
	}

	public function test_youtube_without_key_is_null(): void
	{
		$this->assertNull((new Youtube_Provider())->fetch('UC123', [], function() { return null; }));
	}
}
