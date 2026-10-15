<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Modules\Donations\Lib\Fournisseurs;

/**
 * Les dons reliés à des services extérieurs (m16) : ce que Ko-fi, HelloAsso, TipeeeStream et PayPal envoient, lu et
 * vérifié. Les exemples suivent les documentations des services. Tests PURS : la confirmation par le réseau
 * (commande relue chez HelloAsso, notification confirmée par PayPal) s'éprouve sur l'atelier.
 */
final class DonsReliesTest extends TestCase
{
	private const KOFI = [
		'verification_token' => 'jeton-du-site',
		'message_id' => '3a1fac0c-f960-4506-a60e-824979a74e74',
		'timestamp' => '2026-10-16T08:21:39Z',
		'type' => 'Donation',
		'is_public' => TRUE,
		'from_name' => 'Ko-fi Team',
		'message' => 'Good luck with the integration!',
		'amount' => '3.00',
		'url' => 'https://ko-fi.com/Home/CoffeeShop?txid=00000000-1111-2222-3333-444444444444',
		'email' => 'Jo.Example@Example.com',
		'currency' => 'USD',
		'is_subscription_payment' => FALSE,
		'is_first_subscription_payment' => FALSE,
		'kofi_transaction_id' => '00000000-1111-2222-3333-444444444444',
	];

	public function test_un_don_ko_fi_avec_le_bon_jeton(): void
	{
		$don = Fournisseurs::kofi(['data' => json_encode(self::KOFI)], 'jeton-du-site');

		self::assertIsArray($don);
		self::assertSame('00000000-1111-2222-3333-444444444444', $don['external_id']);
		self::assertSame('Ko-fi Team', $don['donor_name']);
		self::assertSame('jo.example@example.com', $don['email']);
		self::assertSame(3.0, $don['amount']);
		self::assertSame('USD', $don['currency']);
		self::assertTrue($don['is_public']);
	}

	public function test_ko_fi_refuse_un_mauvais_jeton_ou_un_service_non_relie(): void
	{
		self::assertFalse(Fournisseurs::kofi(['data' => json_encode(self::KOFI)], 'autre-jeton'));
		self::assertFalse(Fournisseurs::kofi(['data' => json_encode(self::KOFI)], ''));
		self::assertFalse(Fournisseurs::kofi(['data' => 'pas du json'], 'jeton-du-site'));
	}

	public function test_ko_fi_n_enregistre_que_les_dons_et_les_abonnements(): void
	{
		self::assertNull(Fournisseurs::kofi(['data' => json_encode(['type' => 'Shop Order'] + self::KOFI)], 'jeton-du-site'));
		self::assertIsArray(Fournisseurs::kofi(['data' => json_encode(['type' => 'Subscription'] + self::KOFI)], 'jeton-du-site'));
	}

	public function test_un_don_ko_fi_prive_le_reste(): void
	{
		$don = Fournisseurs::kofi(['data' => json_encode(['is_public' => FALSE] + self::KOFI)], 'jeton-du-site');

		self::assertIsArray($don);
		self::assertFalse($don['is_public']);
	}

	private const HELLOASSO = [
		'id' => 12578,
		'formType' => 'Donation',
		'amount' => ['total' => 2500, 'vat' => 0],
		'payer' => ['email' => 'marie@example.org', 'firstName' => 'Marie', 'lastName' => 'Curie'],
		'payments' => [['id' => 1, 'amount' => 2500, 'state' => 'Authorized']],
	];

	public function test_la_notification_helloasso_ne_donne_qu_un_numero_de_commande(): void
	{
		self::assertSame('12578', Fournisseurs::helloasso_numero(['eventType' => 'Order', 'data' => ['id' => 12578]]));
		self::assertNull(Fournisseurs::helloasso_numero(['eventType' => 'Payment', 'data' => ['id' => 12578]]));
		self::assertNull(Fournisseurs::helloasso_numero(['eventType' => 'Order', 'data' => ['id' => '12578 OR 1=1']]));
	}

	public function test_une_commande_helloasso_de_don_payee(): void
	{
		$don = Fournisseurs::helloasso(self::HELLOASSO);

		self::assertIsArray($don);
		self::assertSame('don', $don['type']);
		self::assertSame('12578', $don['external_id']);
		self::assertSame('Marie Curie', $don['donor_name']);
		self::assertSame(25.0, $don['amount']);
		self::assertFalse($don['is_public'], 'un payeur HelloAsso n’a pas consenti à paraître sur le site');
	}

	public function test_une_adhesion_helloasso(): void
	{
		$don = Fournisseurs::helloasso(['formType' => 'Membership'] + self::HELLOASSO);

		self::assertIsArray($don);
		self::assertSame('adhesion', $don['type']);
	}

	public function test_helloasso_ignore_ce_qui_n_est_ni_paye_ni_un_don(): void
	{
		self::assertNull(Fournisseurs::helloasso(['payments' => [['amount' => 2500, 'state' => 'Refused']]] + self::HELLOASSO));
		self::assertNull(Fournisseurs::helloasso(['formType' => 'Event'] + self::HELLOASSO));
		self::assertNull(Fournisseurs::helloasso(['payments' => []] + self::HELLOASSO));
	}

	public function test_tipeeestream_ne_releve_que_les_dons_recus_depuis_la_liaison(): void
	{
		$reponse = ['datas' => ['items' => [
			['id' => 501, 'type' => 'donation', 'created_at' => '2026-10-16T10:00:00+02:00', 'parameters' => ['amount' => 5, 'currency' => 'EUR', 'username' => 'Viewer', 'message' => 'GG']],
			['id' => 502, 'type' => 'follow', 'created_at' => '2026-10-16T10:01:00+02:00', 'parameters' => ['username' => 'Autre']],
			['id' => 400, 'type' => 'donation', 'created_at' => '2026-10-01T10:00:00+02:00', 'parameters' => ['amount' => 9, 'username' => 'Ancien']],
			['id' => 503, 'type' => 'donation', 'created_at' => '2026-10-16T11:00:00+02:00', 'parameters' => ['amount' => '2,50', 'currency' => 'eur', 'username' => '']],
		]]];

		$dons = Fournisseurs::tipeeestream($reponse, (int) strtotime('2026-10-15T00:00:00+02:00'));

		self::assertSame(['501', '503'], array_column($dons, 'external_id'));
		self::assertSame(2.5, $dons[1]['amount']);
		self::assertSame('EUR', $dons[1]['currency']);
		self::assertSame('Anonyme', $dons[1]['donor_name']);
	}

	public function test_paypal_un_paiement_termine_et_sa_campagne(): void
	{
		$don = Fournisseurs::paypal([
			'payment_status' => 'Completed', 'txn_id' => '61E67681CH3238416', 'custom' => '7', 'receiver_email' => 'Tresorier@Asso.example',
			'first_name' => 'Ada', 'last_name' => 'Lovelace', 'payer_email' => 'ada@example.org', 'mc_gross' => '10.00', 'mc_currency' => 'EUR',
		]);

		self::assertIsArray($don);
		self::assertSame(7, $don['campagne']);
		self::assertSame('tresorier@asso.example', $don['destinataire']);
		self::assertSame('61E67681CH3238416', $don['external_id']);
		self::assertSame(10.0, $don['amount']);
	}

	public function test_paypal_ignore_un_paiement_en_attente(): void
	{
		self::assertNull(Fournisseurs::paypal(['payment_status' => 'Pending', 'txn_id' => 'X']));
		self::assertNull(Fournisseurs::paypal(['payment_status' => 'Completed']));
	}
}
