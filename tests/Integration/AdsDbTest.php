<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Tests d'intégration de la régie pub : détection du perk « no_ads » (boutique) et
 * fenêtre de diffusion (dates) — la logique qui décide d'afficher ou non une pub.
 */
final class AdsDbTest extends IntegrationTestCase
{
	public function test_has_perk_detects_no_ads_purchase(): void
	{
		$buyer = $this->createUser('buyer');
		$other = $this->createUser('other');

		// Item perk 'no_ads' acheté par buyer.
		$this->exec("INSERT INTO nf_shop_items (title, price, type, payload) VALUES ('Sans-Pub', 500, 'perk', 'no_ads')");
		$item = (int) self::$pdo->insert_id;
		$this->exec("INSERT INTO nf_shop_purchases (user_id, item_id, price_paid) VALUES (?, ?, 500)", [$buyer, $item]);

		// Requête exacte de shop->has_perk().
		$sql = "SELECT COUNT(*) FROM nf_shop_purchases p JOIN nf_shop_items i ON i.id = p.item_id WHERE p.user_id = ? AND i.type = 'perk' AND i.payload = 'no_ads'";

		$this->assertSame(1, (int) $this->scalar($sql, [$buyer]), 'buyer possède le perk no_ads');
		$this->assertSame(0, (int) $this->scalar($sql, [$other]), 'other ne possède pas le perk');
	}

	public function test_ad_serving_window(): void
	{
		// Trois annonces sur le même emplacement : active+en-cours, programmée future, expirée.
		$now    = time();
		$future = date('Y-m-d H:i:s', $now + 7 * 86400);
		$past   = date('Y-m-d H:i:s', $now - 7 * 86400);

		$this->exec("INSERT INTO nf_ads (title, placement, format, active, starts_at, ends_at) VALUES ('Now', 'itest_slot', 'html', 1, NULL, NULL)");
		$this->exec("INSERT INTO nf_ads (title, placement, format, active, starts_at, ends_at) VALUES ('Future', 'itest_slot', 'html', 1, ?, NULL)", [$future]);
		$this->exec("INSERT INTO nf_ads (title, placement, format, active, starts_at, ends_at) VALUES ('Expired', 'itest_slot', 'html', 1, NULL, ?)", [$past]);
		$this->exec("INSERT INTO nf_ads (title, placement, format, active, starts_at, ends_at) VALUES ('Inactive', 'itest_slot', 'html', 0, NULL, NULL)");

		// Sélection active + fenêtre de dates (la logique de render(), filtrage côté requête ici).
		$servable = (int) $this->scalar(
			"SELECT COUNT(*) FROM nf_ads WHERE placement = 'itest_slot' AND active = 1
			 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW())"
		);

		$this->assertSame(1, $servable, 'Seule l\'annonce active et dans sa fenêtre est diffusable.');
	}
}
