<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Twitch\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	const MAX_CHANNELS = 12;

	public function index($settings = [])
	{
		// Chaînes : on ne garde que les lignes « provider:chaîne » valides (twitch|youtube), plafonné.
		$clean = [];
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

			// Twitch login = [a-z0-9_] ; channelId YouTube = [A-Za-z0-9_-]. On reste permissif mais borné.
			if (in_array($p, ['twitch', 'youtube'], TRUE) && preg_match('/^[A-Za-z0-9_\-]{2,64}$/', $ch))
			{
				$clean[] = $p.':'.($p === 'twitch' ? strtolower($ch) : $ch);
			}

			if (count($clean) >= self::MAX_CHANNELS)
			{
				break;
			}
		}

		$client_id     = trim($settings['client_id']     ?? '');
		$client_secret = trim($settings['client_secret'] ?? '');
		$api_key       = trim($settings['api_key']       ?? '');

		if ($client_id     !== '' && !preg_match('/^[a-z0-9]{25,50}$/i',  $client_id))     $client_id     = '';
		if ($client_secret !== '' && !preg_match('/^[a-z0-9]{25,80}$/i',  $client_secret)) $client_secret = '';
		if ($api_key       !== '' && !preg_match('/^[A-Za-z0-9_\-]{20,60}$/', $api_key))   $api_key       = '';

		return [
			'channels'      => implode("\n", $clean),
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'api_key'       => $api_key,
			'open_mode'     => in_array($settings['open_mode']    ?? 'popup', ['popup', 'newtab'], TRUE) ? ($settings['open_mode'] ?? 'popup')    : 'popup',
			'show_offline'  => in_array($settings['show_offline'] ?? '1', ['0', '1'], TRUE)              ? ($settings['show_offline'] ?? '1') : '1'
		];
	}
}
