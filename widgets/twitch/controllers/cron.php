<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Twitch\Controllers;

use NF\NeoFrag\Loadables\Controller;
use NF\Widgets\Twitch\Lib\Twitch_Provider;
use NF\Widgets\Twitch\Lib\Youtube_Provider;

/**
 * Annonce le PASSAGE en direct des chaînes suivies : l'événement de webhook `stream.live`.
 *
 * Le widget savait déjà, à l'affichage, si une chaîne était en ligne ; personne n'était prévenu.
 * Ce contrôleur, appelé par le carrefour `cron` de `/monitoring/cron` toutes les cinq minutes,
 * compare l'état de chaque chaîne à celui du passage précédent et annonce une chaîne qui vient de
 * s'allumer — une seule fois par direct (2026-09-23).
 *
 * Deux règles tiennent l'annonce honnête :
 *   - le tout PREMIER passage n'annonce rien : il apprend l'état courant. Sans cela, activer le
 *     webhook annoncerait comme « nouveaux » tous les directs déjà en cours ;
 *   - une chaîne au statut INCONNU (identifiants absents, API muette) ne change pas d'état : une
 *     panne de l'API ne doit ni éteindre ni rallumer une chaîne.
 */
class Cron extends Controller
{
	public const ETAT = 'cache/widget_twitch/en-direct.json';

	public function cron(): string
	{
		// Le module Webhooks est facultatif : sans lui, l'état est tenu à jour et rien n'est annoncé.
		$webhooks = $this->module('webhooks');
		$webhooks = $webhooks instanceof \NF\Modules\Webhooks\Webhooks ? $webhooks : NULL;
		$index    = NeoFrag()->widget('twitch')->controller('index');

		if (!$index)
		{
			return 'no widget controller';
		}

		$providers = ['twitch' => new Twitch_Provider(), 'youtube' => new Youtube_Provider()];
		$http      = $index->_make_http();
		$avant     = @json_decode((string) @file_get_contents(self::ETAT), TRUE);
		$premier   = !is_array($avant);
		$avant     = is_array($avant) ? $avant : [];
		$apres     = $avant;
		$annonces  = 0;
		$vues      = [];

		foreach ((array) $this->db->select('settings')->from('nf_widgets')->where('widget', 'twitch')->get() as $ligne)
		{
			$settings = @json_decode((string) $ligne, TRUE);

			if (!is_array($settings))
			{
				continue;
			}

			$creds = [
				'client_id'     => trim((string) ($settings['client_id']     ?? '')),
				'client_secret' => trim((string) ($settings['client_secret'] ?? '')),
				'api_key'       => trim((string) ($settings['api_key']       ?? '')),
			];

			foreach ($index->_parse_channels($settings) as $chaine)
			{
				$cle = $chaine['provider'].':'.strtolower($chaine['channel']);

				if (isset($vues[$cle]) || !isset($providers[$chaine['provider']]))
				{
					continue;
				}

				$vues[$cle] = TRUE;
				$statut     = $providers[$chaine['provider']]->fetch($chaine['channel'], $creds, $http);

				if ($statut === NULL || !empty($statut['unknown']))
				{
					continue; // statut inconnu : l'état ne bouge pas
				}

				$en_direct   = !empty($statut['is_live']);
				$apres[$cle] = $en_direct;

				if ($en_direct && !$premier && empty($avant[$cle]) && $webhooks)
				{
					$webhooks->trigger('stream.live', [
						'provider'     => $chaine['provider'],
						'channel'      => $chaine['channel'],
						'display_name' => (string) ($statut['display_name'] ?? $chaine['channel']),
						'title'        => (string) ($statut['title'] ?? ''),
						'game'         => (string) ($statut['game'] ?? ''),
						'url'          => (string) ($statut['channel_url'] ?? ''),
						'thumbnail'    => (string) ($statut['thumbnail'] ?? ''),
					]);

					$annonces++;
				}
			}
		}

		if (!is_dir(dirname(self::ETAT)))
		{
			@mkdir(dirname(self::ETAT), 0775, TRUE);
		}

		@file_put_contents(self::ETAT, json_encode($apres));

		return $annonces.' live announced, '.count($vues).' channel(s)'.($premier ? ', first pass' : '');
	}
}
