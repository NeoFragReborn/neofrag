<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\NeoFrag\Libraries;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Le client HTTP (PSR-18) qu'attend la bibliothèque OAuth depuis sa version 3. La connexion par Discord, GitHub ou
 * Google lui passait encore le client de la version 2, qui n'existe plus : elle tombait en erreur dès qu'un service était
 * configuré (épreuve des comptes, 2026-10-09).
 *
 * Il ne sert qu'à parler aux services de connexion — échanger un code contre un jeton, lire un profil — : HTTPS
 * seulement, aucune redirection suivie, des délais courts, une réponse de 2 Mo au plus.
 */
class Client_Http implements ClientInterface
{
	private const MAX_OCTETS = 2097152;

	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		$adresse = (string) $request->getUri();

		if (strtolower($request->getUri()->getScheme()) !== 'https')
		{
			throw self::_erreur('adresse refusée, HTTPS seulement : '.$adresse);
		}

		$entetes = [];

		foreach ($request->getHeaders() as $nom => $valeurs)
		{
			$entetes[] = $nom.': '.implode(', ', $valeurs);
		}

		$recus = [];
		$corps = '';
		$trop  = FALSE;
		$ch    = curl_init($adresse);

		$options = [
			CURLOPT_CUSTOMREQUEST  => $request->getMethod(),
			CURLOPT_HTTPHEADER     => $entetes,
			CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
			CURLOPT_FOLLOWLOCATION => FALSE,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT        => 15,
			CURLOPT_USERAGENT      => 'NeoFrag',
			// Une ligne d'état ouvre un nouveau bloc d'en-têtes (après un « 100 Continue ») : on ne garde que le dernier.
			CURLOPT_HEADERFUNCTION => static function ($ch, string $ligne) use (&$recus): int {
				if (preg_match('#^HTTP/\S+\s+\d{3}#', $ligne))
				{
					$recus = [];
				}
				else if (str_contains($ligne, ':'))
				{
					[$nom, $valeur] = explode(':', $ligne, 2);
					$recus[trim($nom)][] = trim($valeur);
				}

				return strlen($ligne);
			},
			CURLOPT_WRITEFUNCTION  => static function ($ch, string $morceau) use (&$corps, &$trop): int {
				if (strlen($corps) + strlen($morceau) > self::MAX_OCTETS)
				{
					$trop = TRUE;
					return 0;
				}

				$corps .= $morceau;

				return strlen($morceau);
			},
		];

		if (($envoi = (string) $request->getBody()) !== '')
		{
			$options[CURLOPT_POSTFIELDS] = $envoi;
		}

		curl_setopt_array($ch, $options);

		// Pas de `curl_close()` : déprécié depuis PHP 8.5, sans effet depuis PHP 8.0 (cf. helpers/remote.php).
		if (curl_exec($ch) === FALSE || $trop)
		{
			throw self::_erreur($trop ? 'réponse de plus de 2 Mo : '.$adresse : curl_error($ch).' : '.$adresse);
		}

		$fabrique = new Psr17Factory();
		$reponse  = $fabrique->createResponse((int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE));

		foreach ($recus as $nom => $valeurs)
		{
			$reponse = $reponse->withHeader($nom, $valeurs);
		}

		return $reponse->withBody($fabrique->createStream($corps));
	}

	/** L'échec d'une requête, sous la forme que PSR-18 demande. */
	private static function _erreur(string $message): ClientExceptionInterface
	{
		return new class('Client_Http : '.$message) extends \RuntimeException implements ClientExceptionInterface {};
	}
}
