<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Tests d'intégration de la logique money-critique (points / karma / VIP / boutique)
 * contre la vraie base : schéma, contraintes FK, et le SQL exact utilisé par les
 * modules. C'est le « harness DB » qui gate la monétisation argent réel.
 */
final class GamificationDbTest extends IntegrationTestCase
{
	/* --------------------------------------------------------------- Points */

	public function test_points_balance_math(): void
	{
		$uid = $this->createUser();

		// Crédit 50 (gain) puis dépense 20 — mime add_points().
		$this->exec("INSERT INTO nf_user_points (user_id, total, earned, spent) VALUES (?, 50, 50, 0)", [$uid]);
		$this->exec("UPDATE nf_user_points SET total = GREATEST(0, total - 20), spent = spent + 20 WHERE user_id = ?", [$uid]);

		$row = $this->exec("SELECT total, earned, spent FROM nf_user_points WHERE user_id = ?", [$uid])->get_result()->fetch_assoc();

		$this->assertSame(30, (int) $row['total']);
		$this->assertSame(50, (int) $row['earned']);
		$this->assertSame(20, (int) $row['spent']);
	}

	public function test_points_daily_cap_window_counts_today_only(): void
	{
		$uid = $this->createUser();

		$today     = date('Y-m-d H:i:s');
		$yesterday = date('Y-m-d H:i:s', time() - 2 * 86400);
		$start     = date('Y-m-d').' 00:00:00';

		$this->exec("INSERT INTO nf_points_log (user_id, amount, type, created_at) VALUES (?, 30, 'comment', ?)", [$uid, $today]);
		$this->exec("INSERT INTO nf_points_log (user_id, amount, type, created_at) VALUES (?, 30, 'comment', ?)", [$uid, $yesterday]);
		// Une dépense (négative) ne doit pas compter dans le plafond de GAIN.
		$this->exec("INSERT INTO nf_points_log (user_id, amount, type, created_at) VALUES (?, -10, 'comment', ?)", [$uid, $today]);

		// Requête exacte de earn() : SUM des gains du jour pour ce type.
		$sum = (int) $this->scalar(
			"SELECT COALESCE(SUM(amount), 0) FROM nf_points_log WHERE user_id = ? AND type = 'comment' AND amount > 0 AND created_at >= ?",
			[$uid, $start]
		);

		$this->assertSame(30, $sum, 'Seuls les gains positifs du jour comptent dans le plafond.');
	}

	/* ----------------------------------------------------------------- VIP */

	public function test_vip_expiry_comparison(): void
	{
		$uid = $this->createUser();

		$future = date('Y-m-d H:i:s', time() + 86400);
		$this->exec("INSERT INTO nf_vip (user_id, expires_at, source) VALUES (?, ?, 'test')", [$uid, $future]);

		$active = (int) $this->scalar("SELECT COUNT(*) FROM nf_vip WHERE user_id = ? AND expires_at > NOW()", [$uid]);
		$this->assertSame(1, $active, 'VIP futur = actif');

		$past = date('Y-m-d H:i:s', time() - 86400);
		$this->exec("UPDATE nf_vip SET expires_at = ? WHERE user_id = ?", [$past, $uid]);

		$active = (int) $this->scalar("SELECT COUNT(*) FROM nf_vip WHERE user_id = ? AND expires_at > NOW()", [$uid]);
		$this->assertSame(0, $active, 'VIP passé = expiré');
	}

	/* --------------------------------------------------------------- Boutique */

	public function test_shop_ownership(): void
	{
		$buyer  = $this->createUser('buyer');
		$other  = $this->createUser('other');

		$this->exec("INSERT INTO nf_shop_items (title, price, type, payload) VALUES ('Test Item', 100, 'perk', 'k')");
		$item = (int) self::$pdo->insert_id;

		$this->exec("INSERT INTO nf_shop_purchases (user_id, item_id, price_paid) VALUES (?, ?, 100)", [$buyer, $item]);

		$owned_by_buyer = (int) $this->scalar("SELECT COUNT(*) FROM nf_shop_purchases WHERE user_id = ? AND item_id = ?", [$buyer, $item]);
		$owned_by_other = (int) $this->scalar("SELECT COUNT(*) FROM nf_shop_purchases WHERE user_id = ? AND item_id = ?", [$other, $item]);

		$this->assertSame(1, $owned_by_buyer);
		$this->assertSame(0, $owned_by_other);
	}

	/* ------------------------------------------------------- Intégrité (FK) */

	public function test_user_delete_cascades_gamification_rows(): void
	{
		$uid = $this->createUser();

		$this->exec("INSERT INTO nf_karma (user_id, score) VALUES (?, 10)", [$uid]);
		$this->exec("INSERT INTO nf_user_points (user_id, total, earned) VALUES (?, 5, 5)", [$uid]);
		$this->exec("INSERT INTO nf_points_log (user_id, amount, type) VALUES (?, 5, 'comment')", [$uid]);
		$this->exec("INSERT INTO nf_vip (user_id, expires_at) VALUES (?, ?)", [$uid, date('Y-m-d H:i:s', time() + 86400)]);

		$this->exec("INSERT INTO nf_shop_items (title, price, type, payload) VALUES ('Cascade Item', 1, 'perk', 'k')");
		$item = (int) self::$pdo->insert_id;
		$this->exec("INSERT INTO nf_shop_purchases (user_id, item_id, price_paid) VALUES (?, ?, 1)", [$uid, $item]);

		// Sanity : les lignes existent.
		$this->assertSame(1, (int) $this->scalar("SELECT COUNT(*) FROM nf_karma WHERE user_id = ?", [$uid]));

		$this->exec("DELETE FROM nf_user WHERE id = ?", [$uid]);

		$this->assertSame(0, (int) $this->scalar("SELECT COUNT(*) FROM nf_karma WHERE user_id = ?", [$uid]), 'nf_karma cascade');
		$this->assertSame(0, (int) $this->scalar("SELECT COUNT(*) FROM nf_user_points WHERE user_id = ?", [$uid]), 'nf_user_points cascade');
		$this->assertSame(0, (int) $this->scalar("SELECT COUNT(*) FROM nf_points_log WHERE user_id = ?", [$uid]), 'nf_points_log cascade');
		$this->assertSame(0, (int) $this->scalar("SELECT COUNT(*) FROM nf_vip WHERE user_id = ?", [$uid]), 'nf_vip cascade');
		$this->assertSame(0, (int) $this->scalar("SELECT COUNT(*) FROM nf_shop_purchases WHERE user_id = ?", [$uid]), 'nf_shop_purchases cascade');
	}

	public function test_reaction_unique_constraint(): void
	{
		$uid = $this->createUser();

		$this->exec("INSERT INTO nf_reactions (user_id, content_type, content_id) VALUES (?, 'comment', 12345)", [$uid]);

		// La clé unique (user_id, content_type, content_id) interdit le doublon.
		$dup = self::$pdo->prepare("INSERT INTO nf_reactions (user_id, content_type, content_id) VALUES (?, 'comment', 12345)");
		$dup->bind_param('i', $uid);
		$ok = @$dup->execute();

		$this->assertFalse($ok, 'Le doublon de réaction doit être rejeté par la clé unique.');
		$this->assertSame(1, (int) $this->scalar("SELECT COUNT(*) FROM nf_reactions WHERE user_id = ? AND content_type='comment' AND content_id=12345", [$uid]));
	}
}
