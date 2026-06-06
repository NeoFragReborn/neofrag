<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Discord\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	const CACHE_TTL = 120; // 2 minutes

	public function index($settings = [])
	{
		$server_id = trim($settings['server_id'] ?? '');
		$mode      = ($settings['mode']  ?? 'native') === 'iframe' ? 'iframe' : 'native';
		$theme     = ($settings['theme'] ?? 'dark') === 'light' ? 'light' : 'dark';
		$height    = (int)($settings['height'] ?? 400);
		$invite    = trim($settings['invite'] ?? '');

		if (!$server_id)
		{
			return $this->panel()
						->heading($this->lang('Discord'), 'fab fa-discord')
						->body('<div class="alert alert-warning m-2">'.$this->lang('Veuillez configurer l\'ID du serveur Discord dans le panneau d\'administration.').'</div>', FALSE);
		}

		// IFRAME mode = simple official widget (always works if widget enabled on the server)
		if ($mode === 'iframe')
		{
			return $this->panel()
						->heading($this->lang('Discord'), 'fab fa-discord')
						->body('<iframe src="https://discord.com/widget?id='.urlencode($server_id).'&theme='.$theme.'" width="100%" height="'.$height.'" allowtransparency="true" frameborder="0" sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts" style="display:block;border:0"></iframe>', FALSE);
		}

		// NATIVE mode = custom render using widget.json
		$data = $this->_fetch_widget($server_id);

		if (!$data)
		{
			return $this->panel()
						->heading($this->lang('Discord'), 'fab fa-discord')
						->body('<div class="alert alert-danger m-2"><i class="fas fa-exclamation-triangle"></i> '.$this->lang('Impossible de charger les informations du serveur Discord. Vérifiez que le widget est <strong>activé dans les paramètres du serveur Discord</strong> (Paramètres → Widget → Activer le widget du serveur).').'</div>', FALSE);
		}

		$this->css('discord');

		// Filter for voice channels (channels with `position` are voice channels in widget.json)
		$voice_channels = $data['channels'] ?? [];
		usort($voice_channels, function($a, $b) {
			return ($a['position'] ?? 0) - ($b['position'] ?? 0);
		});

		// Online members (sample, max ~10 to keep widget compact)
		$members = $data['members'] ?? [];
		shuffle($members);
		$visible_members = array_slice($members, 0, 12);

		// Invite link: prefer admin-configured, fallback to widget's instant_invite
		$instant_invite = $invite !== '' ? $invite : ($data['instant_invite'] ?? '');

		return $this->panel()
					->heading($this->lang('Discord'), 'fab fa-discord')
					->body($this->view('native', [
						'name'             => $data['name'] ?? '',
						'presence_count'   => count($members),
						'voice_channels'   => $voice_channels,
						'visible_members'  => $visible_members,
						'total_members'    => count($members),
						'instant_invite'   => $instant_invite
					]), FALSE);
	}

	private function _fetch_widget($server_id)
	{
		$endpoint   = 'https://discord.com/api/guilds/'.urlencode($server_id).'/widget.json';
		$cache_dir  = 'cache/widget_discord';
		$cache_file = $cache_dir.'/'.md5($endpoint).'.json';

		if (is_file($cache_file) && (time() - filemtime($cache_file)) < self::CACHE_TTL)
		{
			$json = file_get_contents($cache_file);
		}
		else
		{
			$json = @$this->network($endpoint, ['timeout' => 5])
						   ->header('Accept: application/json')
						   ->get();

			if (!$json)
			{
				if (is_file($cache_file)) $json = file_get_contents($cache_file);
				else return NULL;
			}
			else
			{
				if (!is_dir($cache_dir)) @mkdir($cache_dir, 0775, TRUE);
				@file_put_contents($cache_file, $json);
			}
		}

		$data = @json_decode($json, TRUE);
		if (!is_array($data) || !isset($data['name']))
		{
			return NULL;
		}

		return $data;
	}
}
