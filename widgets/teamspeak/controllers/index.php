<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Teamspeak\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class Index extends Controller_Widget
{
	const CACHE_TTL     = 30;
	const NEG_CACHE_TTL = 60; // après un échec, ne pas refaire la connexion bloquante à chaque rendu

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
						'tree'         => $tree_data['tree']
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
		$fail_file  = $cache_dir.'/'.md5($host.'|'.$voice_port.'|'.$query_port).'.fail';

		$cached = function() use ($cache_file) {
			$d = is_file($cache_file) ? @json_decode(file_get_contents($cache_file), TRUE) : NULL;
			return (is_array($d) && empty($d['error']) && isset($d['server_name'])) ? $d : NULL;
		};

		if (is_file($cache_file) && (time() - filemtime($cache_file)) < self::CACHE_TTL)
		{
			if ($d = $cached()) return $d;
		}

		// Cache négatif : après un échec récent, servir le dernier arbre valide plutôt que de rouvrir
		// une connexion bloquante à chaque rendu (et ne jamais écraser le bon cache avec une erreur).
		if (is_file($fail_file) && (time() - filemtime($fail_file)) < self::NEG_CACHE_TTL)
		{
			if ($d = $cached()) return $d;
			return ['error' => $this->lang('Connexion impossible.')];
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

			// On construit l'arbre nous-mêmes : le viewer HTML du framework appelle getClass(null) sur une
			// signature getClass(string) → TypeError sous PHP 8, et impose un pack d'icônes TS3 (copyright).
			$clients_by_cid = [];
			foreach ($ts3->clientList() as $cl)
			{
				if ((int)$cl['client_type'] !== 0) continue; // ignorer les clients ServerQuery
				$clients_by_cid[(int)$cl['cid']][] = [
					'name'    => (string)$cl['client_nickname'],
					'away'    => (int)$cl['client_away'] === 1,
					'mic_off' => (int)$cl['client_input_muted'] === 1,
					'snd_off' => (int)$cl['client_output_muted'] === 1,
				];
			}

			$by_pid = [];
			foreach ($ts3->channelList() as $ch)
			{
				$cid = (int)$ch['cid'];
				$by_pid[(int)$ch['pid']][] = [
					'cid'       => $cid,
					'order'     => (int)$ch['channel_order'],
					'is_spacer' => (bool)$ch->isSpacer(),
					'name'      => (string)$ch['channel_name'],
					'clients'   => $clients_by_cid[$cid] ?? [],
				];
			}

			$result = [
				'server_name'    => (string)$server_info['virtualserver_name'],
				'clients_online' => (int)$server_info['virtualserver_clientsonline'] - (int)$server_info['virtualserver_queryclientsonline'],
				'clients_max'    => (int)$server_info['virtualserver_maxclients'],
				'tree'           => $this->_build_tree($by_pid, 0),
				'error'          => NULL
			];
		}
		catch (\Throwable $e)
		{
			// Le message brut du framework peut exposer host:port — on le garde côté serveur.
			error_log('[widget teamspeak] '.$e->getMessage());

			if (!is_dir($cache_dir)) @mkdir($cache_dir, 0775, TRUE);
			@touch($fail_file);

			if ($d = $cached()) return $d; // dernier arbre valide plutôt qu'un blanc
			return ['error' => $this->lang('Connexion impossible.')];
		}

		if (!is_dir($cache_dir)) @mkdir($cache_dir, 0775, TRUE);
		@unlink($fail_file);
		@file_put_contents($cache_file, json_encode($result));

		return $result;
	}

	/** Reconstruit l'arbre depuis le parent $pid en respectant l'ordre TS3 (liste chaînée :
	 *  channel_order = cid du canal précédent au même niveau, 0 = premier). */
	private function _build_tree($by_pid, $pid)
	{
		if (empty($by_pid[$pid]))
		{
			return [];
		}

		$siblings = $by_pid[$pid];
		$by_order = [];
		foreach ($siblings as $c)
		{
			$by_order[$c['order']] = $c;
		}

		$ordered = [];
		$prev    = 0; // order 0 = premier du niveau
		$guard   = 0;
		while (isset($by_order[$prev]) && $guard++ <= count($siblings))
		{
			$node             = $by_order[$prev];
			$node['children'] = $this->_build_tree($by_pid, $node['cid']);
			$ordered[]        = $node;
			$prev             = $node['cid'];
		}

		// Filet : si la liste chaînée est incohérente, compléter par ordre brut pour ne rien perdre.
		if (count($ordered) < count($siblings))
		{
			$seen = array_column($ordered, 'cid');
			usort($siblings, function($a, $b){ return $a['order'] <=> $b['order']; });
			foreach ($siblings as $c)
			{
				if (!in_array($c['cid'], $seen, TRUE))
				{
					$c['children'] = $this->_build_tree($by_pid, $c['cid']);
					$ordered[]     = $c;
				}
			}
		}

		return $ordered;
	}
}
