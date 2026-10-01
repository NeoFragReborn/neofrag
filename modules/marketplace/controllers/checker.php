<?php
declare(strict_types=1);
namespace NF\Modules\Marketplace\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;
use NF\NeoFrag\Installer;

class Checker extends Module_Checker
{
	const CACHE_TTL = 3600; // 1 h : la page marketplace ne refait pas un fetch distant à chaque visite.

	public function index()
	{
		require_once NEOFRAG_CMS . '/neofrag/installer.php';

		// Origine fixe validée contre l'allow-list (défaut neofrag-reborn.xyz) : un override
		// nf_marketplace_url ne peut pointer que vers un hôte autorisé en HTTPS (anti-SSRF).
		$cfg = $this->config->nf_marketplace_url;
		$url = Installer::sanitize_marketplace_url(is_string($cfg) ? $cfg : NULL);

		$cache_file = NEOFRAG_CMS . '/cache/marketplace-catalog.json';
		$catalog    = NULL;

		// 1) Cache frais (< TTL).
		if (is_file($cache_file) && (time() - (int) @filemtime($cache_file)) < self::CACHE_TTL)
		{
			$catalog = @json_decode((string) @file_get_contents($cache_file), TRUE);
		}

		// 2) Sinon, fetch distant + mise en cache.
		if (!is_array($catalog))
		{
			if (is_array($fetched = Installer::fetch_catalog($url)))
			{
				@file_put_contents($cache_file, json_encode($fetched));
				$catalog = $fetched;
			}
			elseif (is_file($cache_file)) // distant KO → cache périmé en secours
			{
				$catalog = @json_decode((string) @file_get_contents($cache_file), TRUE);
			}
		}

		$remote = is_array($catalog);

		// 3) Dernier repli : catalogue local (showcase) embarqué.
		if (!$remote)
		{
			$catalog = @json_decode(@file_get_contents(NEOFRAG_CMS . '/marketplace/catalog.json'), TRUE) ?: [];
		}

		// Le titre et la description dans la langue du site : le catalogue porte leurs traductions.
		$addons = array_map(static function($a){
			if (is_array($a))
			{
				$a['title']       = Installer::texte_catalogue($a, 'title');
				$a['description'] = Installer::texte_catalogue($a, 'description');
			}

			return $a;
		}, is_array($catalog['addons'] ?? NULL) ? $catalog['addons'] : []);

		// [addons, base_url distant (vide si repli local → liens locaux)]
		return [$addons, $remote ? rtrim($url, '/') : ''];
	}
}
