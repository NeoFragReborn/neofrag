<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Discord\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	const CACHE_TTL = 120; // 2 minutes
	const NEG_CACHE_TTL = 120; // après un échec, ne pas re-tenter la requête bloquante pendant 2 min

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
		$reason = '';
		$data   = $this->_fetch_widget($server_id, $reason);

		if (!$data)
		{
			return $this->panel()
						->heading($this->lang('Discord'), 'fab fa-discord')
						->body('<div class="alert alert-danger m-2"><i class="fas fa-exclamation-triangle"></i> '.$this->_reason_message($reason).'</div>', FALSE);
		}

		$this->css('discord');

		// Filter for voice channels (channels with `position` are voice channels in widget.json)
		$voice_channels = $data['channels'] ?? [];
		usort($voice_channels, function($a, $b) {
			return ($a['position'] ?? 0) - ($b['position'] ?? 0);
		});

		// Online members: widget.json caps the `members` array at 100 ; le total réel est dans presence_count.
		$members = $data['members'] ?? [];
		shuffle($members);
		$visible_members = array_slice($members, 0, 12);
		$presence_count  = (int)($data['presence_count'] ?? count($members));

		// Invite link: prefer admin-configured, fallback to widget's instant_invite
		$instant_invite = $invite !== '' ? $invite : ($data['instant_invite'] ?? '');

		return $this->panel()
					->heading($this->lang('Discord'), 'fab fa-discord')
					->body($this->view('native', [
						'name'             => $data['name'] ?? '',
						'icon_url'         => $data['_icon_url'] ?? '',
						'presence_count'   => $presence_count,
						'voice_channels'   => $voice_channels,
						'visible_members'  => $visible_members,
						'total_members'    => $presence_count,
						'instant_invite'   => $instant_invite
					]), FALSE);
	}

	private function _fetch_widget($server_id, &$reason = '')
	{
		$endpoint   = 'https://discord.com/api/guilds/'.urlencode($server_id).'/widget.json';
		$cache_dir  = 'cache/widget_discord';
		$cache_file = $cache_dir.'/'.md5($endpoint).'.json';
		$fail_file  = $cache_dir.'/'.md5($endpoint).'.fail';

		$cached = function() use ($cache_file) {
			$d = is_file($cache_file) ? @json_decode(file_get_contents($cache_file), TRUE) : NULL;
			return (is_array($d) && isset($d['name'])) ? $d : NULL;
		};

		if (is_file($cache_file) && (time() - filemtime($cache_file)) < self::CACHE_TTL)
		{
			if ($d = $cached()) return $d;
		}

		// Cache négatif : après un échec récent, on ne refait pas la requête bloquante (5 s) à
		// chaque rendu de page — on sert le cache périmé s'il existe, sinon on remonte la dernière raison.
		if (is_file($fail_file) && (time() - filemtime($fail_file)) < self::NEG_CACHE_TTL)
		{
			if ($d = $cached()) return $d;
			$reason = trim((string)@file_get_contents($fail_file)) ?: 'network';
			return NULL;
		}

		$reason = '';
		$json   = @$this->network($endpoint, ['timeout' => 5])
						 ->type('text')
						 ->header('Accept: application/json')
						 ->error(function($body, $code) use (&$reason) {
							 $err  = is_string($body) ? json_decode($body, TRUE) : NULL;
							 $dc   = is_array($err) ? (int)($err['code'] ?? 0) : 0;
							 if     ($dc === 50004)                   $reason = 'widget_disabled';
							 elseif ($code === 404 || $dc === 10004)  $reason = 'unknown_guild';
							 else                                     $reason = 'http_'.(int)$code;
						 })
						 ->get();

		if (!is_dir($cache_dir)) @mkdir($cache_dir, 0775, TRUE);

		$data = is_string($json) ? @json_decode($json, TRUE) : NULL;

		if (!is_array($data) || !isset($data['name']))
		{
			if ($reason === '') $reason = 'network'; // aucune réponse exploitable = Discord injoignable
			@file_put_contents($fail_file, $reason);
			if ($d = $cached()) return $d;
			return NULL;
		}

		// widget.json ne contient pas l'icône du serveur : on la récupère via l'invitation publique.
		$data['_icon_url'] = $this->_fetch_icon($server_id, $data['instant_invite'] ?? '');

		@unlink($fail_file);
		@file_put_contents($cache_file, json_encode($data));

		return $data;
	}

	/** Icône du serveur via l'endpoint public /invites/{code} (widget.json ne l'expose pas). */
	private function _fetch_icon($server_id, $instant_invite)
	{
		if (!preg_match('#/([A-Za-z0-9-]+)/?(?:\?|$)#', (string)$instant_invite, $m))
		{
			return '';
		}

		$json = @$this->network('https://discord.com/api/v10/invites/'.rawurlencode($m[1]).'?with_counts=true', ['timeout' => 5])
					  ->type('text')
					  ->header('Accept: application/json')
					  ->get();

		$inv  = is_string($json) ? @json_decode($json, TRUE) : NULL;
		$hash = $inv['guild']['icon'] ?? '';
		$gid  = $inv['guild']['id']   ?? $server_id;

		if (!$hash)
		{
			return '';
		}

		$ext = strpos($hash, 'a_') === 0 ? 'gif' : 'png';
		return 'https://cdn.discordapp.com/icons/'.rawurlencode($gid).'/'.rawurlencode($hash).'.'.$ext;
	}

	private function _reason_message($reason)
	{
		switch ($reason)
		{
			case 'widget_disabled':
				return $this->lang('Le widget de ce serveur Discord n\'est pas activé : <strong>Paramètres du serveur → Widget → Activer le widget du serveur</strong>.');
			case 'unknown_guild':
				return $this->lang('ID de serveur Discord introuvable. Active le <strong>Mode développeur</strong> (Paramètres → Avancés), puis clic droit sur le <strong>serveur</strong> → « Copier l\'identifiant du serveur » (à ne pas confondre avec un ID de salon ou un code d\'invitation).');
			case 'network':
				return $this->lang('Serveur Discord injoignable. Si l\'hébergement bloque les requêtes sortantes, passe le widget en mode <strong>iframe</strong> dans ses réglages.');
			default:
				return $this->lang('Erreur côté Discord (%s). Réessaie dans quelques minutes.', $reason);
		}
	}
}
