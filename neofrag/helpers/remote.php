<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Récupération d'une ressource distante dont l'adresse a été SAISIE dans l'administration.
 *
 * Pourquoi ces fonctions existent au lieu d'un simple `file_get_contents()`
 * -------------------------------------------------------------------------
 * Une adresse saisie en administration est semi-confiante : l'administrateur n'est pas un attaquant,
 * mais l'adresse qu'il colle peut venir de n'importe où, et le serveur qui la récupère parle depuis
 * l'INTÉRIEUR du réseau. Sans garde, `http://169.254.169.254/` rend les identifiants d'instance d'un
 * hébergeur cloud, et `http://127.0.0.1:3306/` sonde les services locaux.
 *
 * Le module `webhooks` s'en protégeait déjà, seul, depuis sa propre classe. Ces fonctions sont
 * l'extraction telle quelle de ce garde, pour que le prochain addon qui sort sur le réseau n'ait pas
 * à le réécrire — et surtout pas à le réécrire en moins bien. `webhooks` les appelle désormais.
 *
 * Ce que le garde fait, et ce qu'il ne fait pas
 * --------------------------------------------
 * Il résout l'hôte, exige que TOUTES les adresses résolues soient publiques, puis ÉPINGLE l'adresse
 * retenue (`CURLOPT_RESOLVE`) : sans cet épinglage, un nom peut répondre une adresse publique à la
 * validation et une adresse interne à la requête (« DNS rebinding »), et le contrôle ne servirait à
 * rien. Les redirections ne sont pas suivies, pour la même raison.
 *
 * Il ne prétend pas rendre sûre une adresse fournie par un visiteur anonyme : ce n'est pas le cas
 * d'usage, et il faudrait alors y ajouter une liste d'hôtes autorisés, comme le fait le marketplace.
 */

/**
 * L'adresse IP d'un hôte, UNIQUEMENT si elle est publique.
 *
 * Rend NULL pour une boucle locale, une plage privée, une adresse de lien local ou réservée, en IPv4
 * comme en IPv6, et pour un hôte qui ne résout pas. Un hôte déjà écrit en IP est validé tel quel.
 */
function nf_resolve_public_ip(string $host): ?string
{
	$ips = [];

	if (filter_var($host, FILTER_VALIDATE_IP))
	{
		$ips[] = $host;
	}
	else
	{
		foreach ((array) @dns_get_record($host, DNS_A | DNS_AAAA) as $record)
		{
			if (!empty($record['ip']))   { $ips[] = $record['ip']; }
			if (!empty($record['ipv6'])) { $ips[] = $record['ipv6']; }
		}

		if (!$ips && ($resolved = gethostbyname($host)) && $resolved !== $host)
		{
			$ips[] = $resolved;
		}
	}

	if (!$ips)
	{
		return NULL;
	}

	// TOUTES les adresses résolues doivent être publiques : il suffirait sinon qu'un nom en réponde
	// une interne à côté d'une publique pour que le garde soit contourné.
	foreach ($ips as $ip)
	{
		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
		{
			return NULL;
		}
	}

	return $ips[0];
}

/**
 * Récupère le corps d'une ressource HTTP(S) publique, ou NULL.
 *
 * Trois bornes, parce qu'un addon ne doit jamais pouvoir faire attendre — ni saturer — le serveur :
 *   - `$timeout` secondes en tout, la connexion ayant sa propre borne plus courte ;
 *   - `$max_octets` : la réponse est interrompue dès qu'elle dépasse, plutôt que de remplir la
 *     mémoire avec un fichier de plusieurs gigaoctets servi sous couvert de flux RSS ;
 *   - les redirections ne sont pas suivies (l'épinglage d'IP ne vaudrait plus pour la suivante).
 *
 * @param string $agent Ce que le serveur distant verra ; nommer l'addon rend nos requêtes lisibles
 *                      dans SON journal, et c'est la moindre des politesses.
 */
function nf_fetch_public_url(string $url, int $timeout = 5, int $max_octets = 1048576, string $agent = 'NeoFrag/1.0'): ?string
{
	if (!preg_match('#^https?://#i', $url) || !function_exists('curl_init'))
	{
		return NULL;
	}

	$parts = parse_url($url);
	$host  = $parts['host'] ?? '';
	$port  = $parts['port'] ?? (strtolower($parts['scheme'] ?? '') === 'https' ? 443 : 80);

	if (!$host || !($ip = nf_resolve_public_ip((string) $host)))
	{
		return NULL;
	}

	$corps = '';
	$trop  = FALSE;

	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER  => FALSE,
		CURLOPT_TIMEOUT         => max(1, $timeout),
		CURLOPT_CONNECTTIMEOUT  => max(1, (int) ceil($timeout / 2)),
		CURLOPT_HTTPHEADER      => ['Accept: application/rss+xml, application/atom+xml, application/xml;q=0.9, */*;q=0.8'],
		CURLOPT_USERAGENT       => $agent,
		CURLOPT_PROTOCOLS       => CURLPROTO_HTTP | CURLPROTO_HTTPS,
		CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
		CURLOPT_FOLLOWLOCATION  => FALSE,
		CURLOPT_RESOLVE         => [$host.':'.$port.':'.$ip],
		CURLOPT_WRITEFUNCTION   => function ($ch, $morceau) use (&$corps, &$trop, $max_octets) {
			$corps .= $morceau;

			if (strlen($corps) > $max_octets)
			{
				$trop = TRUE;
				return 0;   // rendre autre chose que la longueur écrite interrompt le transfert
			}

			return strlen($morceau);
		},
	]);

	$ok     = curl_exec($ch) !== FALSE;
	$erreur = curl_error($ch);
	$statut = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
	// Pas de `curl_close()` : déprécié depuis PHP 8.5, sans effet depuis PHP 8.0 où la poignée est un OBJET.

	if ($trop || (!$ok && $erreur !== '') || $statut < 200 || $statut >= 300)
	{
		return NULL;
	}

	return $corps !== '' ? $corps : NULL;
}
