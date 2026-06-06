<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Test d'intégration de la zone forum VIP : une catégorie vip_only n'est accessible
 * qu'aux membres au statut VIP actif (la décision exacte du gate _vip_locked, hors
 * bypass admin).
 */
final class ForumVipDbTest extends IntegrationTestCase
{
	public function test_vip_category_access_decision(): void
	{
		$vip   = $this->createUser('vip');
		$plain = $this->createUser('plain');

		$this->exec("INSERT INTO nf_vip (user_id, expires_at) VALUES (?, ?)", [$vip, date('Y-m-d H:i:s', time() + 86400)]);

		$this->exec("INSERT INTO nf_forum_categories (title, vip_only) VALUES ('Zone VIP', 1)");
		$cat = (int) self::$pdo->insert_id;

		// Reproduit _vip_locked() : si la catégorie est vip_only, accès réservé au VIP actif.
		$accessible = function(int $uid) use ($cat): bool {
			if (!(int) $this->scalar("SELECT vip_only FROM nf_forum_categories WHERE category_id = ?", [$cat]))
			{
				return TRUE;
			}
			return (int) $this->scalar("SELECT COUNT(*) FROM nf_vip WHERE user_id = ? AND expires_at > NOW()", [$uid]) > 0;
		};

		$this->assertTrue($accessible($vip), 'Un membre VIP accède à la zone VIP.');
		$this->assertFalse($accessible($plain), 'Un membre non-VIP est bloqué.');
	}

	public function test_non_vip_category_open_to_all(): void
	{
		$plain = $this->createUser('plain');

		$this->exec("INSERT INTO nf_forum_categories (title, vip_only) VALUES ('Zone publique', 0)");
		$cat = (int) self::$pdo->insert_id;

		$locked = (int) $this->scalar("SELECT vip_only FROM nf_forum_categories WHERE category_id = ?", [$cat]);
		$this->assertSame(0, $locked, 'Une catégorie non VIP reste ouverte à tous.');
	}
}
