<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Gameserver\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;
use xPaw\SourceQuery\SourceQuery;
use xPaw\SourceQuery\Exception\SourceQueryException;

class Index extends Controller_Widget
{
	const CACHE_TTL = 60;

	public function index($settings = [])
	{
		$engine = $settings['engine'] ?? 'mc-java';
		$host   = trim($settings['host'] ?? '');
		$port   = (int)($settings['port'] ?? 0);
		$label  = trim($settings['label'] ?? '');

		if (!$host)
		{
			return $this->panel()
						->heading($this->lang('Serveur de jeu'), 'fas fa-gamepad')
						->body('<div class="alert alert-warning m-2">'.$this->lang('Configurez l\'adresse du serveur dans le panneau d\'administration.').'</div>', FALSE);
		}

		// Default ports per engine
		if (!$port)
		{
			$port = ['mc-java' => 25565, 'mc-bedrock' => 19132, 'source' => 27015, 'goldsource' => 27015][$engine] ?? 27015;
		}

		$data = $this->_query($engine, $host, $port);

		$this->css('gameserver');

		if (!$data || empty($data['online']))
		{
			return $this->panel()
						->heading($label ?: $this->lang('Serveur de jeu'), $this->_engine_icon($engine))
						->body($this->view('index', [
							'engine'  => $engine,
							'host'    => $host,
							'port'    => $port,
							'online'  => FALSE,
							'data'    => $data ?: [],
							'label'   => $label
						]), FALSE);
		}

		return $this->panel()
					->heading($label ?: ($data['name'] ?? $host.':'.$port), $this->_engine_icon($engine))
					->body($this->view('index', [
						'engine'  => $engine,
						'host'    => $host,
						'port'    => $port,
						'online'  => TRUE,
						'data'    => $data,
						'label'   => $label
					]), FALSE);
	}

	private function _engine_icon($engine)
	{
		return [
			'mc-java'    => 'fas fa-cube',
			'mc-bedrock' => 'fas fa-cube',
			'source'     => 'fab fa-steam',
			'goldsource' => 'fab fa-steam'
		][$engine] ?? 'fas fa-gamepad';
	}

	private function _query($engine, $host, $port)
	{
		$cache_dir  = 'cache/widget_gameserver';
		$cache_file = $cache_dir.'/'.md5($engine.'|'.$host.'|'.$port).'.json';

		// Use cache if fresh
		if (is_file($cache_file) && (time() - filemtime($cache_file)) < self::CACHE_TTL)
		{
			$json = file_get_contents($cache_file);
			$cached = @json_decode($json, TRUE);
			if (is_array($cached)) return $cached;
		}

		// Live query
		try
		{
			if ($engine === 'mc-java')
			{
				$data = $this->_query_mc('java', $host, $port);
			}
			else if ($engine === 'mc-bedrock')
			{
				$data = $this->_query_mc('bedrock', $host, $port);
			}
			else // source / goldsource
			{
				$data = $this->_query_a2s($host, $port, $engine === 'goldsource' ? SourceQuery::GOLDSOURCE : SourceQuery::SOURCE);
			}
		}
		catch (\Throwable $e)
		{
			// On error, return stale cache if available
			if (is_file($cache_file))
			{
				$json = file_get_contents($cache_file);
				$cached = @json_decode($json, TRUE);
				if (is_array($cached)) return $cached;
			}
			$data = ['online' => FALSE, 'error' => $e->getMessage()];
		}

		if (!is_dir($cache_dir)) @mkdir($cache_dir, 0775, TRUE);
		@file_put_contents($cache_file, json_encode($data));

		return $data;
	}

	private function _query_mc($variant, $host, $port)
	{
		$endpoint = 'https://api.mcsrvstat.us/'.($variant === 'bedrock' ? 'bedrock/3' : '3').'/'.urlencode($host).':'.(int)$port;

		$json = @$this->network($endpoint, ['timeout' => 6])->type('text')->get();
		if (!$json) return ['online' => FALSE];

		$raw = @json_decode($json, TRUE);
		if (!is_array($raw)) return ['online' => FALSE];

		if (empty($raw['online']))
		{
			return ['online' => FALSE, 'host' => $host, 'port' => $port];
		}

		return [
			'online'      => TRUE,
			'name'        => is_array($raw['motd']['clean'] ?? NULL) ? trim(implode(' ', $raw['motd']['clean'])) : ($raw['hostname'] ?? $host),
			// Le MOTD HTML vient d'une API tierce (l'opérateur du serveur de jeu le contrôle, pas
			// l'admin du site) : assaini par allow-list (HTMLPurifier garde les <span style=color>
			// des couleurs Minecraft mais retire script/onerror…) avant rendu brut dans la vue.
			'motd_html'   => is_array($raw['motd']['html'] ?? NULL) ? sanitize_html(implode('<br>', $raw['motd']['html'])) : '',
			'players'     => (int)($raw['players']['online'] ?? 0),
			'players_max' => (int)($raw['players']['max'] ?? 0),
			'players_list'=> array_slice($raw['players']['list'] ?? [], 0, 16),
			'version'     => $raw['version'] ?? '',
			'protocol'    => $raw['protocol']['name'] ?? '',
			'icon'        => $raw['icon'] ?? '',
			'host'        => $host,
			'port'        => $port
		];
	}

	private function _query_a2s($host, $port, $engine)
	{
		$query = new SourceQuery();
		try
		{
			$query->Connect($host, $port, 3, $engine);
			$info = $query->GetInfo();
			$players = [];
			try { $players = $query->GetPlayers(); } catch (\Throwable $e) { /* some servers block player query */ }
		}
		finally
		{
			$query->Disconnect();
		}

		if (empty($info))
		{
			return ['online' => FALSE];
		}

		// Top 10 players by score
		usort($players, function($a, $b) { return ($b['Frags'] ?? 0) - ($a['Frags'] ?? 0); });
		$players_list = array_map(function($p){
			return ['name' => $p['Name'] ?? '?', 'score' => $p['Frags'] ?? 0, 'time' => isset($p['Time']) ? (int)$p['Time'] : 0];
		}, array_slice($players, 0, 10));

		return [
			'online'       => TRUE,
			'name'         => $info['HostName'] ?? '',
			'game'         => $info['ModDesc'] ?? ($info['Game'] ?? ''),
			'map'          => $info['Map'] ?? '',
			'players'      => (int)($info['Players'] ?? 0),
			'players_max'  => (int)($info['MaxPlayers'] ?? 0),
			'players_list' => $players_list,
			'vac'          => !empty($info['Secure']),
			'host'         => $host,
			'port'         => $port
		];
	}
}
