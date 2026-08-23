<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Tests d'intégration du KARMA (réputation dérivée et cachée, table nf_karma) contre
 * la vraie base. Le karma n'a PAS de table de votes (pas de ligne par (votant,cible),
 * pas de bornes up/down, pas de fenêtre anti-abus) : c'est un agrégat recalculé puis
 * mis en cache. Chaque test reflète le SQL EXACT du modèle gamification, pas une
 * fiction — au-delà de la cascade FK déjà couverte dans GamificationDbTest.
 */
final class KarmaDbTest extends IntegrationTestCase
{
	public function test_karma_one_row_per_user_pk(): void
	{
		$uid = $this->createUser();

		// recompute() insère-si-absent / sinon-update sur user_id (gamification.php:186-193).
		// La PRIMARY KEY(user_id) garantit qu'un 2ᵉ INSERT pour le même membre est rejeté,
		// donc la branche « insert » ne peut jamais créer une 2ᵉ ligne karma concurrente.
		$this->exec("INSERT INTO nf_karma (user_id, score) VALUES (?, 10)", [$uid]);

		$dup = self::$pdo->prepare("INSERT INTO nf_karma (user_id, score) VALUES (?, 20)");
		$dup->bind_param('i', $uid);
		$ok = @$dup->execute();

		$this->assertFalse($ok, 'Une 2ᵉ ligne karma pour le même user_id doit être rejetée par la clé primaire.');
		$this->assertSame(1, (int) $this->scalar("SELECT COUNT(*) FROM nf_karma WHERE user_id = ?", [$uid]));
	}

	public function test_karma_reactions_received_join(): void
	{
		$owner   = $this->createUser('owner');
		$reactorA = $this->createUser('reactA');
		$reactorB = $this->createUser('reactB');
		$stranger = $this->createUser('stranger');

		// Le membre cible possède un commentaire ; un autre commentaire appartient à un tiers.
		$this->exec("INSERT INTO nf_comment (user_id, module_id, module, content) VALUES (?, 1, 'comments', 'mine')", [$owner]);
		$ownComment = (int) self::$pdo->insert_id;
		$this->exec("INSERT INTO nf_comment (user_id, module_id, module, content) VALUES (?, 1, 'comments', 'his')", [$stranger]);
		$strangerComment = (int) self::$pdo->insert_id;

		// Deux réactions ciblent le commentaire du membre (= réactions REÇUES par lui).
		$this->exec("INSERT INTO nf_reactions (user_id, content_type, content_id) VALUES (?, 'comment', ?)", [$reactorA, $ownComment]);
		$this->exec("INSERT INTO nf_reactions (user_id, content_type, content_id) VALUES (?, 'comment', ?)", [$reactorB, $ownComment]);
		// Une réaction sur le commentaire d'autrui ne doit PAS compter pour ce membre.
		$this->exec("INSERT INTO nf_reactions (user_id, content_type, content_id) VALUES (?, 'comment', ?)", [$reactorA, $strangerComment]);

		// Requête exacte de recompute() (gamification.php:152-157) pour ['comment','nf_comment','id'].
		// Aucun filtre deleted_at/reaction n'est appliqué par le modèle → le mirror n'en ajoute aucun.
		$received = (int) $this->scalar(
			"SELECT COUNT(*) FROM nf_reactions r JOIN nf_comment t ON t.id = r.content_id WHERE r.content_type = 'comment' AND t.user_id = ?",
			[$owner]
		);

		$this->assertSame(2, $received, 'On compte les réactions reçues sur le contenu du membre, pas celles qu\'il a émises.');
	}

	public function test_karma_content_published_count(): void
	{
		$owner = $this->createUser('owner');
		$other = $this->createUser('other');

		// Le membre publie 2 commentaires ; un 3e appartient à un tiers.
		$this->exec("INSERT INTO nf_comment (user_id, module_id, module, content) VALUES (?, 1, 'comments', 'a')", [$owner]);
		$this->exec("INSERT INTO nf_comment (user_id, module_id, module, content) VALUES (?, 1, 'comments', 'b')", [$owner]);
		$this->exec("INSERT INTO nf_comment (user_id, module_id, module, content) VALUES (?, 1, 'comments', 'c')", [$other]);

		// Requête exacte du terme « contenu publié » de recompute() (gamification.php:163-166)
		// pour CONTENT_SOURCES[0] = ['nf_comment','user_id'] : on ne compte que le contenu du membre.
		$content = (int) $this->scalar("SELECT COUNT(*) FROM nf_comment WHERE user_id = ?", [$owner]);

		$this->assertSame(2, $content, 'Le terme « contenu » du karma compte le contenu du membre, pas celui des autres.');
	}
}
