<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Api\Models;

use NF\NeoFrag\Loadables\Model;

class Api extends Model
{
	/*
	 * Les clés d'accès. Une clé vaut `nfr_` suivi de 40 caractères hexadécimaux ; seule
	 * son empreinte SHA-256 est gardée, avec ses huit premiers caractères pour la reconnaître dans la
	 * liste. Une fuite de la base ne donne donc aucune clé utilisable, et une clé perdue se révoque
	 * et se recrée : elle ne se « retrouve » pas.
	 */

	public const PREFIXE = 'nfr_';

	/**
	 * Crée une clé et rend sa valeur EN CLAIR — la seule fois où elle existe en clair.
	 *
	 * @param list<string> $droits
	 */
	public function creer(string $nom, array $droits, ?int $auteur): string
	{
		$cle = self::PREFIXE.bin2hex(random_bytes(20));

		$this->db->insert('nf_api_tokens', [
			'name'       => mb_substr($nom, 0, 100),
			'prefix'     => substr($cle, 0, 8),
			'hash'       => hash('sha256', $cle),
			'scopes'     => implode(',', $droits),
			'created_by' => $auteur,
		]);

		return $cle;
	}

	/**
	 * La clé active qui correspond à cette valeur, ou NULL. La recherche se fait sur l'empreinte : la
	 * valeur reçue n'est jamais comparée en clair, et `hash_equals` confirme sans fuite de durée.
	 *
	 * @return array{token_id: int, name: string, scopes: list<string>}|null
	 */
	public function verifier(string $cle): ?array
	{
		if (!str_starts_with($cle, self::PREFIXE) || strlen($cle) !== strlen(self::PREFIXE) + 40)
		{
			return NULL;
		}

		$empreinte = hash('sha256', $cle);
		$jeton     = $this->db	->select('token_id', 'name', 'hash', 'scopes')
								->from('nf_api_tokens')
								->where('hash', $empreinte)
								->where('revoked_at', NULL)
								->row();

		if (!is_array($jeton) || !$jeton || !hash_equals((string) $jeton['hash'], $empreinte))
		{
			return NULL;
		}

		return [
			'token_id' => (int) $jeton['token_id'],
			'name'     => (string) $jeton['name'],
			'scopes'   => array_values(array_filter(explode(',', (string) $jeton['scopes']))),
		];
	}

	/** Note le dernier usage d'une clé : l'administration montre ainsi les clés qui servent encore. */
	public function marquer_usage(int $token_id, string $ip): void
	{
		$this->db->where('token_id', $token_id)->update('nf_api_tokens', ['last_used_at' => date('Y-m-d H:i:s'), 'last_ip' => mb_substr($ip, 0, 45)]);
	}

	public function revoquer(int $token_id): void
	{
		$this->db->where('token_id', $token_id)->where('revoked_at', NULL)->update('nf_api_tokens', ['revoked_at' => date('Y-m-d H:i:s')]);
	}

	/**
	 * Toutes les clés, les actives d'abord.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function liste(): array
	{
		return array_values((array) $this->db	->select('t.token_id', 't.name', 't.prefix', 't.scopes', 't.created_at', 't.last_used_at', 't.last_ip', 't.revoked_at', 'u.username')
												->from('nf_api_tokens t')
												->join('nf_user u', 'u.id = t.created_by', 'LEFT')
												->order_by('t.revoked_at IS NOT NULL', 't.token_id DESC')
												->get());
	}

	/** @return array<string, mixed>|null */
	public function cle(int $token_id): ?array
	{
		$jeton = $this->db->select('token_id', 'name', 'revoked_at')->from('nf_api_tokens')->where('token_id', $token_id)->row();

		return is_array($jeton) && $jeton ? $jeton : NULL;
	}
}
