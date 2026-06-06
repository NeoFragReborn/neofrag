<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Test d'intégration des paiements : idempotence du webhook (un même événement
 * Stripe ne peut être crédité qu'une fois — garanti par la clé unique event_id).
 */
final class PaymentsDbTest extends IntegrationTestCase
{
	public function test_webhook_idempotency_unique_event(): void
	{
		$uid = $this->createUser();

		$this->exec("INSERT INTO nf_payments (event_id, user_id, kind, units) VALUES ('evt_itest_1', ?, 'points', 1000)", [$uid]);

		// Le re-traitement du même événement (clé unique event_id) doit échouer → pas de double crédit.
		$dup = self::$pdo->prepare("INSERT INTO nf_payments (event_id, user_id, kind, units) VALUES ('evt_itest_1', ?, 'points', 1000)");
		$dup->bind_param('i', $uid);
		$ok = @$dup->execute();

		$this->assertFalse($ok, 'Un event_id déjà traité doit être rejeté (idempotence).');
		$this->assertSame(1, (int) $this->scalar("SELECT COUNT(*) FROM nf_payments WHERE event_id = 'evt_itest_1'"));
	}

	public function test_payment_pack_kinds(): void
	{
		$this->exec("INSERT INTO nf_payment_packs (kind, label, units, price_cents) VALUES ('vip', 'VIP itest', 30, 500)");
		$id = (int) self::$pdo->insert_id;

		$row = $this->exec("SELECT kind, units, price_cents FROM nf_payment_packs WHERE id = ?", [$id])->get_result()->fetch_assoc();

		$this->assertSame('vip', $row['kind']);
		$this->assertSame(30, (int) $row['units']);
		$this->assertSame(500, (int) $row['price_cents']);
	}
}
