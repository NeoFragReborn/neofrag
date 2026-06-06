<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Teamspeak\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;
use PlanetTeamSpeak\TeamSpeak3Framework\Viewer\Html as TsHtmlViewer;

class Index extends Controller_Widget
{
	const CACHE_TTL = 30;

	public function index($settings = [])
	{
		$mode       = ($settings['mode'] ?? 'simple') === 'tree' ? 'tree' : 'simple';
		$host       = trim($settings['host'] ?? '');
		$voice_port = (int)($settings['voice_port'] ?? 9987);
		$label      = trim($settings['label'] ?? '');

		if (!$host)
		{
			return $this->panel()
						->heading($this->lang('TeamSpeak'), 'fas fa-microphone')
						->body('<div class="alert alert-warning m-2">'.$this->lang('Configurez l\'adresse du serveur TS3 dans le panneau d\'administration.').'</div>', FALSE);
		}

		$ts_url = 'ts3server://'.$host.($voice_port !== 9987 ? '?port='.$voice_port : '');

		// SIMPLE mode = just a card with name + join button
		if ($mode === 'simple')
		{
			return $this->_render_simple($label ?: $host, $host, $voice_port, $ts_url, NULL);
		}

		// TREE mode = ServerQuery + viewer
		$query_port = (int)($settings['query_port'] ?? 10011);
		$query_user = trim($settings['query_user'] ?? '');
		$query_pass = trim($settings['query_pass'] ?? '');

		$tree_data = $this->_fetch_tree($host, $voice_port, $query_port, $query_user, $query_pass);

		if (!$tree_data || !empty($tree_data['error']))
		{
			$err = $tree_data['error'] ?? $this->lang('Connexion impossible.');
			return $this->_render_simple($label ?: $host, $host, $voice_port, $ts_url, $err);
		}

		$this->css('teamspeak');

		return $this->panel()
					->heading($label ?: ($tree_data['server_name'] ?: $host), 'fas fa-microphone')
					->body($this->view('tree', [
						'host'         => $host,
						'voice_port'   => $voice_port,
						'ts_url'       => $ts_url,
						'server_name'  => $tree_data['server_name'],
						'clients_online' => $tree_data['clients_online'],
						'clients_max'  => $tree_data['clients_max'],
						'tree_html'    => $tree_data['tree_html']
					]), FALSE);
	}

	private function _render_simple($name, $host, $port, $ts_url, $error = NULL)
	{
		$this->css('teamspeak');
		return $this->panel()
					->heading($name, 'fas fa-microphone')
					->body($this->view('simple', [
						'name'   => $name,
						'host'   => $host,
						'port'   => $port,
						'ts_url' => $ts_url,
						'error'  => $error
					]), FALSE);
	}

	private function _fetch_tree($host, $voice_port, $query_port, $query_user, $query_pass)
	{
		$cache_dir  = 'cache/widget_teamspeak';
		$cache_file = $cache_dir.'/'.md5($host.'|'.$voice_port.'|'.$query_port).'.json';

		if (is_file($cache_file) && (time() - filemtime($cache_file)) < self::CACHE_TTL)
		{
			$cached = @json_decode(file_get_contents($cache_file), TRUE);
			if (is_array($cached)) return $cached;
		}

		try
		{
			$uri = sprintf(
				'serverquery://%s:%s@%s:%d/?server_port=%d&blocking=0&timeout=4&use_offline_as_virtual=0&nickname=%s',
				rawurlencode($query_user),
				rawurlencode($query_pass),
				$host,
				$query_port,
				$voice_port,
				rawurlencode('NeoFrag-Viewer-'.bin2hex(random_bytes(2)))
			);
			$ts3 = TeamSpeak3::factory($uri);

			$server_info = $ts3->getInfo();

			// Build viewer HTML
			$viewer = new TsHtmlViewer('/widgets/teamspeak/img/', '.png', 'normal');
			$tree_html = $ts3->getViewer($viewer);

			$result = [
				'server_name'    => (string)$server_info['virtualserver_name'],
				'clients_online' => (int)$server_info['virtualserver_clientsonline'] - (int)$server_info['virtualserver_queryclientsonline'],
				'clients_max'    => (int)$server_info['virtualserver_maxclients'],
				'tree_html'      => $tree_html,
				'error'          => NULL
			];
		}
		catch (\Throwable $e)
		{
			$result = ['error' => $e->getMessage()];
		}

		if (!is_dir($cache_dir)) @mkdir($cache_dir, 0775, TRUE);
		@file_put_contents($cache_file, json_encode($result));

		return $result;
	}
}
