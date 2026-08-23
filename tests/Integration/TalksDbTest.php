<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Tests d'intégration de la confidentialité des Talks (chat + MP unifiés) contre la
 * vraie base : soft-delete PAR participant, restauration, gate d'audience 'staff' sur
 * les canaux publics, et la clé primaire composite qui rend l'état soft-delete par-user
 * non ambigu. Chaque test mime le SQL exact des méthodes du modèle talks.
 */
final class TalksDbTest extends IntegrationTestCase
{
	/* --------------------------------------------------- Soft-delete par-user */

	public function test_soft_delete_is_per_user(): void
	{
		$userA = $this->createUser('talk_a');
		$userB = $this->createUser('talk_b');

		$this->exec("INSERT INTO nf_talks (name, type, audience) VALUES ('Direct AB', 'direct', 'all')");
		$talk = (int) self::$pdo->insert_id;

		$this->exec("INSERT INTO nf_talks_participants (talk_id, user_id, role) VALUES (?, ?, 'member')", [$talk, $userA]);
		$this->exec("INSERT INTO nf_talks_participants (talk_id, user_id, role) VALUES (?, ?, 'member')", [$talk, $userB]);

		// Requête exacte de delete_for_user() (talks.php:352-361).
		$this->exec(
			"UPDATE nf_talks_participants SET deleted_at = CURRENT_TIMESTAMP, archived_at = NULL WHERE talk_id = ? AND user_id = ?",
			[$talk, $userA]
		);

		// Prédicat de visibilité exact de get_my_conversations() (talks.php:130-148).
		// Le modèle joint via ->join() = LEFT JOIN (db.php:220-223) ; on le reflète tel quel.
		$visible = "SELECT COUNT(*) FROM nf_talks t LEFT JOIN nf_talks_participants p ON p.talk_id = t.talk_id WHERE p.user_id = ? AND p.archived_at IS NULL AND p.deleted_at IS NULL AND t.deleted_at IS NULL";

		$this->assertSame(0, (int) $this->scalar($visible, [$userA]), 'userA a supprimé : la conversation disparaît de SA liste.');
		$this->assertSame(1, (int) $this->scalar($visible, [$userB]), 'userB conserve sa copie intacte.');
	}

	public function test_restore_for_user_revives_visibility(): void
	{
		$userA = $this->createUser('talk_a');
		$userB = $this->createUser('talk_b');

		$this->exec("INSERT INTO nf_talks (name, type, audience) VALUES ('Direct AB', 'direct', 'all')");
		$talk = (int) self::$pdo->insert_id;

		$this->exec("INSERT INTO nf_talks_participants (talk_id, user_id, role) VALUES (?, ?, 'member')", [$talk, $userA]);
		$this->exec("INSERT INTO nf_talks_participants (talk_id, user_id, role) VALUES (?, ?, 'member')", [$talk, $userB]);

		// delete_for_user() (talks.php:352-361) puis restore_for_user() (talks.php:363-369).
		$this->exec("UPDATE nf_talks_participants SET deleted_at = CURRENT_TIMESTAMP, archived_at = NULL WHERE talk_id = ? AND user_id = ?", [$talk, $userA]);
		$this->exec("UPDATE nf_talks_participants SET deleted_at = NULL WHERE talk_id = ? AND user_id = ?", [$talk, $userA]);

		// Prédicat de visibilité exact de get_my_conversations() (talks.php:130-148).
		// Le modèle joint via ->join() = LEFT JOIN (db.php:220-223) ; on le reflète tel quel.
		$visible = "SELECT COUNT(*) FROM nf_talks t LEFT JOIN nf_talks_participants p ON p.talk_id = t.talk_id WHERE p.user_id = ? AND p.archived_at IS NULL AND p.deleted_at IS NULL AND t.deleted_at IS NULL";

		$this->assertSame(1, (int) $this->scalar($visible, [$userA]), 'Restauration : la conversation réapparaît dans la liste de userA.');
	}

	/* ------------------------------------------------- Audience staff (canaux) */

	public function test_audience_staff_scoping_for_public_channels(): void
	{
		$this->exec("INSERT INTO nf_talks (name, type, audience) VALUES ('Public All', 'public', 'all')");
		$talk_all = (int) self::$pdo->insert_id;

		$this->exec("INSERT INTO nf_talks (name, type, audience) VALUES ('Public Staff', 'public', 'staff')");
		$talk_staff = (int) self::$pdo->insert_id;

		// Requête exacte de get_public_channels() (talks.php:171-196), non-admin : filtre audience='all'.
		$non_admin = (int) $this->scalar(
			"SELECT COUNT(*) FROM nf_talks t WHERE t.type = 'public' AND t.deleted_at IS NULL AND t.audience = 'all' AND t.talk_id IN (?, ?)",
			[$talk_all, $talk_staff]
		);
		$this->assertSame(1, $non_admin, 'Non-admin : seul le canal audience=all est visible.');

		// Même requête côté admin : pas de filtre audience.
		$admin = (int) $this->scalar(
			"SELECT COUNT(*) FROM nf_talks t WHERE t.type = 'public' AND t.deleted_at IS NULL AND t.talk_id IN (?, ?)",
			[$talk_all, $talk_staff]
		);
		$this->assertSame(2, $admin, 'Admin : les deux canaux (all + staff) sont visibles.');
	}

	/* --------------------------------------------------- Intégrité (clé PK) */

	public function test_participant_primary_key_rejects_duplicate(): void
	{
		$uid = $this->createUser('talk_dup');

		$this->exec("INSERT INTO nf_talks (name, type, audience) VALUES ('PK Talk', 'group', 'all')");
		$talk = (int) self::$pdo->insert_id;

		$this->exec("INSERT INTO nf_talks_participants (talk_id, user_id, role) VALUES (?, ?, 'member')", [$talk, $uid]);

		// La PRIMARY KEY (talk_id, user_id) (schema.sql:464) interdit la 2ème ligne d'adhésion.
		$dup = self::$pdo->prepare("INSERT INTO nf_talks_participants (talk_id, user_id, role) VALUES (?, ?, 'member')");
		$dup->bind_param('ii', $talk, $uid);
		$ok = @$dup->execute();

		$this->assertFalse($ok, 'Le doublon (talk_id, user_id) doit être rejeté par la clé primaire.');
		$this->assertSame(1, (int) $this->scalar("SELECT COUNT(*) FROM nf_talks_participants WHERE talk_id = ? AND user_id = ?", [$talk, $uid]));
	}
}
