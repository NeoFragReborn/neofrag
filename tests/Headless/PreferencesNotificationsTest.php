<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Les préférences de notifications (chantier A, étape A4) : ce que le membre a coupé sur le site n'arrive plus
 * dans sa cloche, et rien d'autre ne change.
 *
 * Le vrai module `notifications` contre la base de test : push() et push_unique() — par où passent tous les envois
 * des modules — consultent `nf_notifications_preferences`. Sans ligne, le membre reçoit tout, comme avant. Chaque
 * test prend un membre neuf : veut() garde les réglages qu'il a déjà lus le temps d'une requête.
 */
final class PreferencesNotificationsTest extends HeadlessTestCase
{
	private function notifications(): \NF\Modules\Notifications\Notifications
	{
		return \NeoFrag()->module('notifications');
	}

	private function recues(int $user_id, string $type): int
	{
		return (int) $this->db()->select('COUNT(*)')->from('nf_notifications')->where('user_id', $user_id)->where('type', $type)->row();
	}

	private function couper(int $user_id, string $type, bool $site, bool $email): void
	{
		$this->db()->insert('nf_notifications_preferences', ['user_id' => $user_id, 'type' => $type, 'site' => (int) $site, 'email' => (int) $email]);
	}

	public function test_sans_reglage_le_membre_recoit_tout(): void
	{
		$membre = $this->createUser('pref');

		$this->assertNotNull($this->notifications()->push($membre, 'comment', 'Un commentaire', 'news/1/x'));
		$this->assertSame(1, $this->recues($membre, 'comment'));
		$this->assertTrue($this->notifications()->veut($membre, 'talks_message', 'email'), 'sans réglage, l’e-mail part aussi');
	}

	public function test_un_type_coupe_sur_le_site_n_arrive_plus(): void
	{
		$membre = $this->createUser('pref');
		$this->couper($membre, 'comment', FALSE, TRUE);

		$this->assertNull($this->notifications()->push($membre, 'comment', 'Un commentaire', 'news/1/x'));
		$this->assertNull($this->notifications()->push_unique($membre, 'comment', 'Un autre', 'news/2/y'));
		$this->assertSame(0, $this->recues($membre, 'comment'), 'rien n’est écrit pour un type coupé');

		$this->assertNotNull($this->notifications()->push($membre, 'reaction', 'Une réaction', 'news/1/x'), 'les autres types arrivent toujours');
		$this->assertTrue($this->notifications()->veut($membre, 'comment', 'email'), 'le site coupé ne coupe pas l’e-mail');
	}

	public function test_l_e_mail_coupe_laisse_la_cloche(): void
	{
		$membre = $this->createUser('pref');
		$this->couper($membre, 'talks_message', TRUE, FALSE);

		$this->assertFalse($this->notifications()->veut($membre, 'talks_message', 'email'));
		$this->assertTrue($this->notifications()->veut($membre, 'talks_message', 'site'));
		$this->assertNotNull($this->notifications()->push($membre, 'talks_message', 'Un message', 'talks/1/x'));
	}

	public function test_les_reglages_d_un_membre_ne_touchent_pas_les_autres(): void
	{
		$coupe  = $this->createUser('pref');
		$autre  = $this->createUser('pref');
		$this->couper($coupe, 'forum_reply', FALSE, FALSE);

		$this->assertNull($this->notifications()->push($coupe, 'forum_reply', 'Une réponse', 'forum/topic/1/x'));
		$this->assertNotNull($this->notifications()->push($autre, 'forum_reply', 'Une réponse', 'forum/topic/1/x'));
	}

	public function test_effacer_le_membre_efface_ses_reglages(): void
	{
		$membre = $this->createUser('pref');
		$this->couper($membre, 'news', FALSE, TRUE);

		$this->db()->where('id', $membre)->delete('nf_user');

		$this->assertTrue($this->db()->from('nf_notifications_preferences')->where('user_id', $membre)->empty(), 'la clé étrangère emporte les réglages');
	}
}
