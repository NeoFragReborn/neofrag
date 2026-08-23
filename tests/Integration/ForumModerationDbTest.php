<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Tests d'intégration de l'escalade automatique des sanctions (modération forum)
 * contre la vraie base : le compteur d'avertissements actifs dans la fenêtre
 * glissante qui pilote le passage avertissement → mute → ban.
 *
 * Mime le SQL EXACT du listener d'escalade (modules/moderation/moderation.php:243-249).
 * Un avertissement révoqué ou hors-fenêtre ne doit jamais peser dans le seuil, sinon
 * un membre pourrait être mute/ban sur des avertissements retirés ou périmés.
 *
 * NB : nf_sanctions.created_at a DEFAULT CURRENT_TIMESTAMP ; on l'INSÈRE explicitement
 * pour contrôler l'appartenance à la fenêtre. reason et issued_by sont NOT NULL sans
 * défaut → fournis à chaque insertion. Pas de FK sur user_id (vérifié) → pas de test
 * de cascade ici (ce serait une fiction).
 */
final class ForumModerationDbTest extends IntegrationTestCase
{
	public function test_active_warnings_count_window_filters_revoked_and_out_of_window(): void
	{
		$uid = $this->createUser();

		$now         = date('Y-m-d H:i:s');
		$forty_ago   = date('Y-m-d H:i:s', time() - 40 * 86400);
		$cutoff      = date('Y-m-d H:i:s', time() - 30 * 86400);

		// (a) avertissement du jour, non révoqué → compte.
		$this->exec("INSERT INTO nf_sanctions (user_id, type, reason, issued_by, revoked_at, created_at) VALUES (?, 'warning', 'r', 1, NULL, ?)", [$uid, $now]);
		// (b) avertissement du jour mais révoqué → exclu par revoked_at IS NULL.
		$this->exec("INSERT INTO nf_sanctions (user_id, type, reason, issued_by, revoked_at, created_at) VALUES (?, 'warning', 'r', 1, ?, ?)", [$uid, $now, $now]);
		// (c) avertissement vieux de 40 jours, non révoqué → exclu par la fenêtre.
		$this->exec("INSERT INTO nf_sanctions (user_id, type, reason, issued_by, revoked_at, created_at) VALUES (?, 'warning', 'r', 1, NULL, ?)", [$uid, $forty_ago]);
		// (d) un mute du jour → exclu par type = 'warning'.
		$this->exec("INSERT INTO nf_sanctions (user_id, type, reason, issued_by, revoked_at, created_at) VALUES (?, 'mute', 'r', 1, NULL, ?)", [$uid, $now]);

		// Requête exacte du listener d'escalade (modules/moderation/moderation.php:243-249).
		$count = (int) $this->scalar(
			"SELECT COUNT(*) FROM nf_sanctions WHERE user_id = ? AND type = 'warning' AND revoked_at IS NULL AND created_at > ?",
			[$uid, $cutoff]
		);

		$this->assertSame(1, $count, 'Seuls les avertissements actifs (non révoqués) et dans la fenêtre comptent.');
	}

	public function test_window_cutoff_is_strict_greater_than(): void
	{
		$uid = $this->createUser();

		$cutoff      = date('Y-m-d H:i:s', time() - 30 * 86400);
		$one_sec_new = date('Y-m-d H:i:s', time() - 30 * 86400 + 1);

		// Une ligne pile sur le cutoff (== cutoff) et une à cutoff + 1 s.
		$this->exec("INSERT INTO nf_sanctions (user_id, type, reason, issued_by, revoked_at, created_at) VALUES (?, 'warning', 'r', 1, NULL, ?)", [$uid, $cutoff]);
		$this->exec("INSERT INTO nf_sanctions (user_id, type, reason, issued_by, revoked_at, created_at) VALUES (?, 'warning', 'r', 1, NULL, ?)", [$uid, $one_sec_new]);

		// Requête exacte du listener (modules/moderation/moderation.php:241,248) : '>' strict, pas '>='.
		$count = (int) $this->scalar(
			"SELECT COUNT(*) FROM nf_sanctions WHERE user_id = ? AND type = 'warning' AND revoked_at IS NULL AND created_at > ?",
			[$uid, $cutoff]
		);

		$this->assertSame(1, $count, 'La ligne pile sur le cutoff est exclue ; seule la strictement plus récente compte.');
	}

	public function test_threshold_boundary_count_drives_escalation_tier(): void
	{
		$uid = $this->createUser();

		$now    = date('Y-m-d H:i:s');
		$cutoff = date('Y-m-d H:i:s', time() - 30 * 86400);

		// Exactement 3 avertissements actifs dans la fenêtre.
		for ($i = 0; $i < 3; $i++)
		{
			$this->exec("INSERT INTO nf_sanctions (user_id, type, reason, issued_by, revoked_at, created_at) VALUES (?, 'warning', 'r', 1, NULL, ?)", [$uid, $now]);
		}

		// Requête exacte du listener (modules/moderation/moderation.php:243-249).
		$count = (int) $this->scalar(
			"SELECT COUNT(*) FROM nf_sanctions WHERE user_id = ? AND type = 'warning' AND revoked_at IS NULL AND created_at > ?",
			[$uid, $cutoff]
		);

		$this->assertSame(3, $count);

		// Seuils RÉELS lus depuis la config (le modèle fait cfg(...) ?: défaut, moderation.php:238-240) —
		// on teste la décision d'escalade contre la valeur configurée, pas une constante codée en dur.
		$mute_th = (int) $this->scalar("SELECT value FROM nf_settings WHERE name = 'nf_moderation_warning_threshold_mute'") ?: 3;
		$ban_th  = (int) $this->scalar("SELECT value FROM nf_settings WHERE name = 'nf_moderation_warning_threshold_ban'")  ?: 5;

		$this->assertTrue($count >= $mute_th, 'count atteint le seuil mute réel → palier mute (>= inclusif).');
		$this->assertFalse($count >= $ban_th, 'count sous le seuil ban réel → palier ban non atteint.');
	}
}
