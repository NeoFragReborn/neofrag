<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Tests d'intégration de la sémantique multi-emoji des réactions (add / switch /
 * toggle / comptes par emoji) contre la vraie base. On reflète le SQL exact du
 * contrôleur AJAX du module reactions (modules/reactions/controllers/ajax.php) :
 * 1 réaction par couple (user, contenu), la valeur emoji étant simplement
 * basculée, retirée, ou agrégée par GROUP BY pour l'affichage des tallies.
 *
 * La contrainte UNIQUE (user_id, content_type, content_id) est déjà couverte par
 * GamificationDbTest::test_reaction_unique_constraint — ici on couvre le
 * comportement de mutation, pas la contrainte.
 */
final class ReactionsEmojiDbTest extends IntegrationTestCase
{
	public function test_switch_emoji_updates_same_row(): void
	{
		$uid = $this->createUser();

		// Ligne existante 'love', l'utilisateur bascule sur 'haha'.
		$this->exec("INSERT INTO nf_reactions (user_id, content_type, content_id, reaction) VALUES (?, 'comment', 9001, 'love')", [$uid]);
		$rid = (int) self::$pdo->insert_id;

		// Requête exacte de la branche `else if ($existing)` — ajax.php:45-49 :
		// UPDATE de la MÊME ligne (matchée par id), jamais un 2e INSERT.
		$this->exec("UPDATE nf_reactions SET reaction = 'haha' WHERE id = ?", [$rid]);

		$this->assertSame(
			1,
			(int) $this->scalar("SELECT COUNT(*) FROM nf_reactions WHERE user_id = ? AND content_type='comment' AND content_id=9001", [$uid]),
			'Le switch ne doit pas créer une 2e ligne pour le même couple (user, contenu).'
		);
		$this->assertSame(
			'haha',
			(string) $this->scalar("SELECT reaction FROM nf_reactions WHERE user_id = ? AND content_type='comment' AND content_id=9001", [$uid]),
			'La valeur emoji est basculée love → haha sur la ligne existante.'
		);
	}

	public function test_toggle_same_emoji_removes_row(): void
	{
		$uid = $this->createUser();

		$this->exec("INSERT INTO nf_reactions (user_id, content_type, content_id, reaction) VALUES (?, 'comment', 9002, 'love')", [$uid]);
		$rid = (int) self::$pdo->insert_id;

		// Requête exacte de la branche `if ($existing && $existing['reaction'] === $reaction)`
		// — ajax.php:41-44 : re-cliquer le MÊME emoji supprime la réaction (un-react).
		$this->exec("DELETE FROM nf_reactions WHERE id = ?", [$rid]);

		$this->assertSame(
			0,
			(int) $this->scalar("SELECT COUNT(*) FROM nf_reactions WHERE user_id = ? AND content_type='comment' AND content_id=9002", [$uid]),
			'Re-cliquer le même emoji retire la réaction (zéro ligne restante).'
		);
	}

	public function test_per_emoji_counts_group_by_reaction(): void
	{
		// La clé unique force des utilisateurs distincts par ligne sur un même contenu.
		$u1 = $this->createUser();
		$u2 = $this->createUser();
		$u3 = $this->createUser();
		$u4 = $this->createUser();

		$this->exec("INSERT INTO nf_reactions (user_id, content_type, content_id, reaction) VALUES (?, 'comment', 9003, 'love')", [$u1]);
		$this->exec("INSERT INTO nf_reactions (user_id, content_type, content_id, reaction) VALUES (?, 'comment', 9003, 'love')", [$u2]);
		$this->exec("INSERT INTO nf_reactions (user_id, content_type, content_id, reaction) VALUES (?, 'comment', 9003, 'love')", [$u3]);
		$this->exec("INSERT INTO nf_reactions (user_id, content_type, content_id, reaction) VALUES (?, 'comment', 9003, 'haha')", [$u4]);

		// Requête exacte du tally par emoji — ajax.php:64-69 (= Reactions::counts(),
		// reactions.php:67-72) : GROUP BY reaction filtré sur (content_type, content_id).
		$res = $this->exec(
			"SELECT reaction, COUNT(*) AS n FROM nf_reactions WHERE content_type='comment' AND content_id=9003 GROUP BY reaction"
		)->get_result();

		$counts = [];
		while ($row = $res->fetch_assoc())
		{
			$counts[$row['reaction']] = (int) $row['n'];
		}

		$this->assertSame(3, $counts['love'] ?? 0, 'Bucket love = 3 utilisateurs distincts.');
		$this->assertSame(1, $counts['haha'] ?? 0, 'Bucket haha = 1 utilisateur.');
		$this->assertSame(4, array_sum($counts), 'Total des réactions sur le contenu = 4.');
	}
}
