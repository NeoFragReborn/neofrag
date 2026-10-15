<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Les services de dons reliés au site (m16, 2026-10-16) : ce que chacun envoie, lu et vérifié SANS réseau.
 * Les appels (jeton HelloAsso, confirmation PayPal, relevé TipeeeStream) vivent dans le modèle ; ici, seulement
 * des fonctions pures, que tests/Unit/DonsReliesTest.php éprouve sur les exemples des documentations.
 *
 * Chaque lecture rend un don NORMALISÉ — `external_id` (l'identifiant chez le service : un paiement ne s'enregistre
 * qu'une fois, même si la notification arrive deux fois), `donor_name`, `email`, `amount`, `currency`, `message`,
 * `is_public` — ou NULL pour ce qui n'est pas un don à enregistrer.
 */

namespace NF\Modules\Donations\Lib;

class Fournisseurs
{
	/** Les services, et ce que chacun permet. */
	const SERVICES = ['kofi', 'helloasso', 'tipeeestream', 'paypal'];

	/**
	 * Ko-fi : un formulaire dont le champ `data` porte du JSON, et son `verification_token` — le jeton de la page
	 * « API » de Ko-fi, que seul le site connaît. Les dons et les paiements d'abonnement s'enregistrent ; une commande
	 * de boutique ou une commande d'art ne sont pas des dons.
	 *
	 * @param array<string, mixed> $post
	 * @return array<string, mixed>|false|null FALSE : le jeton n'est pas le bon (ou le service n'est pas relié)
	 */
	public static function kofi(array $post, string $jeton): array|false|null
	{
		$data = json_decode((string) ($post['data'] ?? ''), TRUE);

		if (!is_array($data) || $jeton === '' || !hash_equals($jeton, (string) ($data['verification_token'] ?? '')))
		{
			return FALSE;
		}

		if (!in_array($data['type'] ?? '', ['Donation', 'Subscription'], TRUE) || ($id = (string) ($data['kofi_transaction_id'] ?? '')) === '')
		{
			return NULL;
		}

		return self::don($id, (string) ($data['from_name'] ?? ''), (string) ($data['email'] ?? ''), $data['amount'] ?? 0, (string) ($data['currency'] ?? 'EUR'), (string) ($data['message'] ?? ''), !empty($data['is_public']));
	}

	/**
	 * HelloAsso : la notification ne dit que le numéro de la commande — elle n'est pas signée. Ce qui fait foi, c'est
	 * la commande relue par l'API avec les identifiants de l'association (Modele::helloasso_commande()). Ceci lit
	 * cette commande relue : un don, ou une adhésion, payés.
	 *
	 * @param array<string, mixed> $commande
	 * @return array<string, mixed>|null avec `type` : `don` ou `adhesion`
	 */
	public static function helloasso(array $commande): ?array
	{
		$id    = (string) ($commande['id'] ?? '');
		$type  = ['Donation' => 'don', 'Membership' => 'adhesion'][$commande['formType'] ?? ''] ?? NULL;
		$payes = array_filter((array) ($commande['payments'] ?? []), static fn ($p): bool => is_array($p) && in_array($p['state'] ?? '', ['Authorized', 'Registered'], TRUE));

		if ($id === '' || $type === NULL || !$payes)
		{
			return NULL;
		}

		$payeur = (array) ($commande['payer'] ?? []);
		$nom    = trim((string) ($payeur['firstName'] ?? '').' '.(string) ($payeur['lastName'] ?? ''));
		$centimes = (int) ($commande['amount']['total'] ?? array_sum(array_map(static fn (array $p): int => (int) ($p['amount'] ?? 0), $payes)));

		return ['type' => $type] + self::don($id, $nom, (string) ($payeur['email'] ?? ''), $centimes / 100, 'EUR', '', FALSE);
	}

	/** Le numéro de commande qu'une notification HelloAsso annonce, s'il s'agit d'une commande. */
	public static function helloasso_numero(array $notification): ?string
	{
		$id = $notification['data']['id'] ?? NULL;

		return ($notification['eventType'] ?? '') === 'Order' && (is_int($id) || (is_string($id) && ctype_digit($id))) ? (string) $id : NULL;
	}

	/**
	 * TipeeeStream : le relevé des événements par l'API, avec la clé du compte. Seuls les dons, et seulement ceux
	 * reçus depuis `$depuis` : relier le service ne réimporte pas tout l'historique.
	 *
	 * @param array<string, mixed> $reponse
	 * @return list<array<string, mixed>>
	 */
	public static function tipeeestream(array $reponse, int $depuis): array
	{
		$dons = [];

		foreach ((array) ($reponse['datas']['items'] ?? $reponse['items'] ?? []) as $e)
		{
			if (!is_array($e) || ($e['type'] ?? '') !== 'donation' || !isset($e['id']))
			{
				continue;
			}

			$quand = strtotime((string) ($e['created_at'] ?? ''));

			if ($quand === FALSE || $quand < $depuis)
			{
				continue;
			}

			$p      = (array) ($e['parameters'] ?? []);
			$dons[] = self::don((string) $e['id'], (string) ($p['username'] ?? ''), '', $p['amount'] ?? 0, (string) ($p['currency'] ?? 'EUR'), (string) ($p['message'] ?? ''), TRUE);
		}

		return array_values(array_filter($dons, static fn (array $d): bool => $d['amount'] > 0));
	}

	/**
	 * PayPal (IPN) : une fois la notification CONFIRMÉE par PayPal (Modele::paypal_confirme()), un paiement terminé
	 * vers l'adresse de la campagne. `custom` porte le numéro de la campagne, que le bouton du site y pose.
	 *
	 * @param array<string, mixed> $post
	 * @return array<string, mixed>|null avec `campagne` et `destinataire`
	 */
	public static function paypal(array $post): ?array
	{
		if (($post['payment_status'] ?? '') !== 'Completed' || ($id = (string) ($post['txn_id'] ?? '')) === '')
		{
			return NULL;
		}

		$nom = trim((string) ($post['first_name'] ?? '').' '.(string) ($post['last_name'] ?? ''));

		return ['campagne' => ctype_digit((string) ($post['custom'] ?? '')) ? (int) $post['custom'] : 0, 'destinataire' => mb_strtolower(trim((string) ($post['receiver_email'] ?? $post['business'] ?? '')))]
			+ self::don($id, $nom, (string) ($post['payer_email'] ?? ''), $post['mc_gross'] ?? 0, (string) ($post['mc_currency'] ?? 'EUR'), (string) ($post['memo'] ?? ''), FALSE);
	}

	/** @return array<string, mixed> */
	private static function don(string $id, string $nom, string $email, mixed $montant, string $devise, string $message, bool $public): array
	{
		return [
			'external_id' => mb_substr($id, 0, 100),
			'donor_name'  => mb_substr(trim($nom) !== '' ? trim($nom) : 'Anonyme', 0, 100),
			'email'       => filter_var(trim($email), FILTER_VALIDATE_EMAIL) ? mb_strtolower(trim($email)) : '',
			'amount'      => round(max(0, (float) str_replace(',', '.', (string) $montant)), 2),
			'currency'    => preg_match('/^[A-Za-z]{3}$/', $devise) ? strtoupper($devise) : 'EUR',
			'message'     => mb_substr(trim($message), 0, 2000),
			'is_public'   => $public,
		];
	}
}
