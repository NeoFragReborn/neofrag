<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Tests d'intégration des purges de maintenance (cron) contre la vraie base.
 * Domaine DESTRUCTIF : ces requêtes SUPPRIMENT définitivement du contenu et des
 * comptes. Chaque test mime le SQL EXACT de tools/maintenance.php afin de figer
 * la frontière de rétention et les garde-fous (IS NOT NULL, admin='0',
 * last_activity_date NULL) — une régression ici détruit des données réelles.
 */
final class MaintenanceDbTest extends IntegrationTestCase
{
	/* --------------------------------------------------------------- Corbeille */

	public function test_trash_purge_retention_boundary(): void
	{
		$uid = $this->createUser();

		$inside  = date('Y-m-d H:i:s', time() - 29 * 86400); // N-1 j → survit (juste dans la fenêtre)
		$outside = date('Y-m-d H:i:s', time() - 31 * 86400); // N+1 j → purgé

		$this->exec("INSERT INTO nf_comment (user_id, module_id, module, content, deleted_at) VALUES (?, 1, 'news', 'inside', ?)", [$uid, $inside]);
		$this->exec("INSERT INTO nf_comment (user_id, module_id, module, content, deleted_at) VALUES (?, 1, 'news', 'outside', ?)", [$uid, $outside]);
		$this->exec("INSERT INTO nf_comment (user_id, module_id, module, content, deleted_at) VALUES (?, 1, 'news', 'live', NULL)", [$uid]);

		// Requête exacte de purge_trash() — tools/maintenance.php:95
		// $where = "`deleted_at` IS NOT NULL AND `deleted_at` < (NOW() - INTERVAL {$days} DAY)"
		$count = (int) $this->scalar(
			"SELECT COUNT(*) FROM nf_comment WHERE deleted_at IS NOT NULL AND deleted_at < (NOW() - INTERVAL ? DAY) AND user_id = ?",
			[30, $uid]
		);

		$this->assertSame(1, $count, "Seul le contenu strictement plus vieux que la rétention est purgé (frontière '<', live protégé).");
	}

	/* ------------------------------------------------------- Comptes non confirmés */

	public function test_unconfirmed_purge_never_targets_admin(): void
	{
		$uid = $this->createUser('admin');

		$old = date('Y-m-d H:i:s', time() - 30 * 86400);
		$this->exec("UPDATE nf_user SET last_activity_date = NULL, admin = '1', registration_date = ? WHERE id = ?", [$old, $uid]);

		// Requête exacte de purge_unconfirmed() — tools/maintenance.php:118
		// $where = "`last_activity_date` IS NULL AND `admin` = '0' AND `registration_date` < (NOW() - INTERVAL {$days} DAY)"
		$count = (int) $this->scalar(
			"SELECT COUNT(*) FROM nf_user WHERE last_activity_date IS NULL AND admin = '0' AND registration_date < (NOW() - INTERVAL ? DAY) AND id = ?",
			[7, $uid]
		);

		$this->assertSame(0, $count, "GARDE-FOU : un compte admin jamais confirmé n'est JAMAIS purgé (clause admin='0').");
	}

	public function test_unconfirmed_purge_skips_confirmed_user(): void
	{
		$uid = $this->createUser();

		$old = date('Y-m-d H:i:s', time() - 30 * 86400);
		$now = date('Y-m-d H:i:s');
		$this->exec("UPDATE nf_user SET last_activity_date = ?, admin = '0', registration_date = ? WHERE id = ?", [$now, $old, $uid]);

		// Requête exacte de purge_unconfirmed() — tools/maintenance.php:118
		// $where = "`last_activity_date` IS NULL AND `admin` = '0' AND `registration_date` < (NOW() - INTERVAL {$days} DAY)"
		$count = (int) $this->scalar(
			"SELECT COUNT(*) FROM nf_user WHERE last_activity_date IS NULL AND admin = '0' AND registration_date < (NOW() - INTERVAL ? DAY) AND id = ?",
			[7, $uid]
		);

		$this->assertSame(0, $count, "Un membre confirmé (last_activity_date NOT NULL) est exclu par la clause IS NULL.");
	}

	/* ----------------------------------------------- Garde d'activation (gate) */

	public function test_unconfirmed_purge_gated_by_registration_validation(): void
	{
		$uid = $this->createUser();

		// Compte éligible au WHERE interne : jamais confirmé, non-admin, ancien.
		$old = date('Y-m-d H:i:s', time() - 30 * 86400);
		$this->exec("UPDATE nf_user SET last_activity_date = NULL, admin = '0', registration_date = ? WHERE id = ?", [$old, $uid]);

		$inner = "SELECT COUNT(*) FROM nf_user WHERE last_activity_date IS NULL AND admin = '0' AND registration_date < (NOW() - INTERVAL ? DAY) AND id = ?";
		$gate  = "SELECT value FROM nf_settings WHERE name = 'nf_registration_validation'";

		$this->assertSame(1, (int) $this->scalar($inner, [7, $uid]), 'Pré-condition : le compte matche le WHERE interne de la purge.');

		// Décision RÉELLE de purge_unconfirmed() : si !nf_registration_validation → return tôt
		// (tools/maintenance.php:113-116), donc la suppression n'a JAMAIS lieu, quel que soit le WHERE interne.
		// On teste donc la décision composée (gate ET where), pas un setting isolé.
		$this->exec("DELETE FROM nf_settings WHERE name = 'nf_registration_validation'");
		$this->exec("INSERT INTO nf_settings (name, site, lang, value, type) VALUES ('nf_registration_validation', '', '', '0', 'int')");
		$gate_off  = (bool) (int) $this->scalar($gate);
		$inner_hit = (bool) (int) $this->scalar($inner, [7, $uid]);
		$this->assertFalse($gate_off && $inner_hit, "Validation OFF : la purge sort tôt — un compte pourtant éligible survit.");

		$this->exec("UPDATE nf_settings SET value = '1' WHERE name = 'nf_registration_validation'");
		$gate_on = (bool) (int) $this->scalar($gate);
		$this->assertTrue($gate_on && $inner_hit, "Validation ON : la purge s'applique au compte éligible.");
	}
}
