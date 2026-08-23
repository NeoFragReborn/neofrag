<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Steam\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	const CACHE_TTL = 300; // 5 minutes
	const NEG_CACHE_TTL = 120; // après un échec, ne pas re-tenter la requête bloquante pendant 2 min

	public function index($settings = [])
	{
		$group        = trim($settings['group'] ?? '');
		$show_avatar  = ($settings['show_avatar']  ?? '1') === '1';
		$show_summary = ($settings['show_summary'] ?? '0') === '1';
		$display      = ($settings['display']      ?? 'compact') === 'full' ? 'full' : 'compact';

		if (!$group)
		{
			return $this->panel()
						->heading($this->lang('Groupe Steam'), 'fab fa-steam')
						->body('<div class="alert alert-warning m-2">'.$this->lang('Veuillez configurer l\'ID du groupe Steam dans le panneau d\'administration du widget.').'</div>', FALSE);
		}

		$data = $this->_fetch_group($group);

		if (!$data)
		{
			return $this->panel()
						->heading($this->lang('Groupe Steam'), 'fab fa-steam')
						->body('<div class="alert alert-danger m-2"><i class="fas fa-exclamation-triangle"></i> '.$this->lang('Impossible de charger les informations du groupe Steam.').'</div>', FALSE);
		}

		$this->css('steam');

		return $this->panel()
					->heading($this->lang('Groupe Steam'), 'fab fa-steam')
					->body($this->view('index', [
						'name'           => (string)$data['name'],
						'url'            => (string)$data['url'],
						'avatar'         => (string)$data['avatar'],
						'members'        => (int)$data['members'],
						'members_ingame' => (int)$data['members_ingame'],
						'members_online' => (int)$data['members_online'],
						'show_avatar'    => $show_avatar,
						'display'        => $display
					]), FALSE)
					->footer_if($show_summary && !empty($data['summary']),
						'<div class="widget-steam-summary"><strong>'.$this->lang('À propos de %s', '<a href="https://steamcommunity.com/groups/'.htmlspecialchars($data['url']).'" target="_blank" rel="noopener">'.htmlspecialchars($data['name']).'</a>').'</strong>'
						.'<div class="widget-steam-summary-text">'.strip_tags((string)$data['summary'], '<br><a><b><i>').'</div></div>',
						'left'
					);
	}

	private function _fetch_group($group)
	{
		// Identify if group is a numeric SteamID64 (gid) or vanity URL
		$is_numeric = preg_match('/^\d{15,20}$/', $group);
		$endpoint   = $is_numeric
			? 'https://steamcommunity.com/gid/'.urlencode($group).'/memberslistxml/?xml=1'
			: 'https://steamcommunity.com/groups/'.urlencode($group).'/memberslistxml/?xml=1';

		$cache_dir  = 'cache/widget_steam';
		$cache_file = $cache_dir.'/'.md5($endpoint).'.xml';

		// Try cache
		if (is_file($cache_file) && (time() - filemtime($cache_file)) < self::CACHE_TTL)
		{
			$xml = @simplexml_load_string(file_get_contents($cache_file));
		}
		else
		{
			$fail_file = $cache_dir.'/'.md5($endpoint).'.fail';

			// Cache négatif : après un échec récent, ne pas refaire la requête bloquante (5 s) à
			// chaque rendu de page pendant NEG_CACHE_TTL (sert le cache périmé s'il existe).
			if (is_file($fail_file) && (time() - filemtime($fail_file)) < self::NEG_CACHE_TTL)
			{
				$xml = is_file($cache_file) ? @simplexml_load_string(file_get_contents($cache_file)) : NULL;
			}
			else
			{
				$raw = @$this->network($endpoint, ['timeout' => 5])->get();

				if (!is_dir($cache_dir)) @mkdir($cache_dir, 0775, TRUE);

				if (!$raw)
				{
					@touch($fail_file);
					$xml = is_file($cache_file) ? @simplexml_load_string(file_get_contents($cache_file)) : NULL;
				}
				else
				{
					$xml = @simplexml_load_string($raw);
					if ($xml && !empty($xml->groupDetails) && (string)$xml->groupDetails->groupName !== '')
					{
						@unlink($fail_file);
						@file_put_contents($cache_file, $raw);
					}
				}
			}
		}

		if (!$xml || empty($xml->groupDetails) || (string)$xml->groupDetails->groupName === '')
		{
			return NULL;
		}

		$d = $xml->groupDetails;
		return [
			'name'           => $d->groupName,
			'url'            => $d->groupURL,
			'avatar'         => $d->avatarFull,
			'members'        => $d->memberCount,
			'members_online' => $d->membersOnline,
			'members_ingame' => $d->membersInGame,
			'summary'        => $d->summary
		];
	}
}
