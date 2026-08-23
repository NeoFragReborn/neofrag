<?php
/**
 * https://neofr.ag
 * Abstraction « provider de statut live » : chaque plateforme (Twitch, YouTube…) implémente fetch()
 * en s'appuyant sur un transport HTTP injecté ($http) → la logique fetch+normalisation est testable
 * sans réseau réel (on injecte un faux $http). Ajouter une plateforme = une classe, sans toucher le widget.
 */

namespace NF\Widgets\Twitch\Lib;

interface Live_Provider
{
	/** Clé technique du provider (ex. 'twitch', 'youtube') — préfixe des chaînes « provider:chaîne ». */
	public static function key(): string;

	/** Libellé humain. */
	public static function label(): string;

	/** Clés d'identifiants attendues dans $creds (pour le formulaire admin), ex. ['client_id','client_secret']. */
	public static function credentials(): array;

	/**
	 * Statut live normalisé d'une chaîne, ou NULL si introuvable/échec.
	 *
	 * @param string   $channel  identifiant de chaîne (login Twitch, channelId YouTube…)
	 * @param array    $creds    identifiants (client_id/secret, api_key…)
	 * @param callable $http     fn(string $method, string $url, array $headers = [], $body = null): ?array
	 *                           — exécute la requête (GET caché, POST pour les tokens) et renvoie le JSON décodé.
	 * @return array{provider:string,channel:string,display_name:string,avatar:string,is_live:bool,title:string,game:string,viewers:?int,thumbnail:string,channel_url:string,embed_url:string}|null
	 */
	public function fetch(string $channel, array $creds, callable $http): ?array;
}
