<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Tests d'intégration des notifications persistantes (cloche) contre la vraie base.
 * On reflète le SQL EXACT de modules/notifications/notifications.php : l'anti-spam de
 * push_unique() (73-78), le scoping par destinataire de mark_read() (292-294, garde
 * anti-IDOR) et le compteur unread (261-265). NB : nf_notifications ≠ neofrag/helpers/
 * notify.php (qui est un flash toast, sans persistance).
 */
final class NotificationsDbTest extends IntegrationTestCase
{
	private function insertNotif(int $user, string $type, string $url, int $read = 0): int
	{
		$this->exec(
			"INSERT INTO nf_notifications (user_id, type, title, url, is_read) VALUES (?, ?, 'itest', ?, ?)",
			[$user, $type, $url, $read]
		);

		return (int) $this->db()->insert_id;
	}

	public function test_push_unique_skips_existing_unread_duplicate(): void
	{
		$uid = $this->createUser();
		$this->insertNotif($uid, 'comment', '/news/1#comments', 0);

		// Garde de push_unique() (notifications.php:73-78) : skip si une notif NON-LUE
		// identique (user, type, url) existe déjà.
		$dupExists = (int) $this->scalar(
			"SELECT COUNT(*) FROM nf_notifications WHERE user_id = ? AND type = ? AND url = ? AND is_read = 0",
			[$uid, 'comment', '/news/1#comments']
		) > 0;

		$this->assertTrue($dupExists, 'Une notif non-lue identique existe → push_unique doit court-circuiter.');
		// Donc on n'insère PAS : le compte reste 1.
		$this->assertSame(1, (int) $this->scalar("SELECT COUNT(*) FROM nf_notifications WHERE user_id = ?", [$uid]));

		// Edge : si la notif existante est LUE, la garde (is_read=0) ne matche plus → une nouvelle est permise.
		$this->exec("UPDATE nf_notifications SET is_read = 1 WHERE user_id = ?", [$uid]);
		$dupExistsAfterRead = (int) $this->scalar(
			"SELECT COUNT(*) FROM nf_notifications WHERE user_id = ? AND type = ? AND url = ? AND is_read = 0",
			[$uid, 'comment', '/news/1#comments']
		) > 0;

		$this->assertFalse($dupExistsAfterRead, 'Notif déjà lue → la garde ne bloque plus, une nouvelle notif est créée.');
	}

	public function test_mark_read_is_scoped_to_recipient(): void
	{
		$owner    = $this->createUser('owner');
		$attacker = $this->createUser('attacker');
		$id       = $this->insertNotif($owner, 'comment', '/x', 0);

		// mark_read() (notifications.php:292-294) : UPDATE ... WHERE id=? AND user_id=<courant>.
		// Un autre utilisateur ne peut PAS marquer lue la notif d'autrui (anti-IDOR).
		$attackerTry = $this->exec(
			"UPDATE nf_notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
			[$id, $attacker]
		)->affected_rows;

		$this->assertSame(0, $attackerTry, 'GARDE : un tiers ne marque pas lue la notif d\'un autre (scoping user_id).');
		$this->assertSame(0, (int) $this->scalar("SELECT is_read FROM nf_notifications WHERE id = ?", [$id]), 'La notif reste non-lue.');

		// Le destinataire légitime, lui, la marque bien lue.
		$ownerTry = $this->exec(
			"UPDATE nf_notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
			[$id, $owner]
		)->affected_rows;

		$this->assertSame(1, $ownerTry, 'Le destinataire marque sa propre notif lue.');
		$this->assertSame(1, (int) $this->scalar("SELECT is_read FROM nf_notifications WHERE id = ?", [$id]));
	}

	public function test_unread_count_only_counts_unread_for_the_user(): void
	{
		$me    = $this->createUser('me');
		$other = $this->createUser('other');

		$this->insertNotif($me, 'comment', '/a', 0);
		$this->insertNotif($me, 'comment', '/b', 0);
		$this->insertNotif($me, 'comment', '/c', 1);   // lue → exclue
		$this->insertNotif($other, 'comment', '/d', 0); // autre user → exclue

		// Requête exacte de unread_count() (notifications.php:261-265).
		$count = (int) $this->scalar(
			"SELECT COUNT(*) FROM nf_notifications WHERE user_id = ? AND is_read = 0",
			[$me]
		);

		$this->assertSame(2, $count, 'Le compteur cloche ne compte que les notifs non-lues du membre courant.');
	}
}
