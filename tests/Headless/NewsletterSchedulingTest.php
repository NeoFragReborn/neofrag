<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Tests d'OBJET du state-machine d'envoi programmé de la newsletter (modèle réel, DB de test,
 * transaction rollback). On exerce schedule / process_due / cancel SANS déclencher d'envoi SMTP :
 * _send_one() n'est touché que pour une campagne échue AYANT des abonnés confirmés — chaque cas
 * ci-dessous l'évite (0 abonné, snapshot via réflexion, ou file pré-traitée). L'envoi réel est
 * vérifié en live (Playwright + mailpit). Couvre : transitions scheduled→sending→sent, échéance,
 * snapshot file (confirmés only), comptage sent/failed, annulation, idempotence.
 */
final class NewsletterSchedulingTest extends HeadlessTestCase
{
	private function model(): \NF\Modules\Newsletter\Models\Newsletter
	{
		return new \NF\Modules\Newsletter\Models\Newsletter(\NeoFrag());
	}

	// Table rase déterministe (dans la transaction, rollback après).
	private function clear(): void
	{
		$this->db()->where('id >', 0)->delete('nf_newsletter_queue');
		$this->db()->where('id >', 0)->delete('nf_newsletter_campaigns');
		$this->db()->where('id >', 0)->delete('nf_newsletter_subscribers');
	}

	private function addSubscriber(string $email, bool $confirmed): void
	{
		$this->db()->insert('nf_newsletter_subscribers', [
			'email'     => $email,
			'token'     => substr(md5($email.uniqid('', true)), 0, 32),
			'confirmed' => $confirmed ? 1 : 0,
		]);
	}

	public function test_schedule_creates_scheduled_campaign_with_estimate(): void
	{
		$this->clear();
		$this->addSubscriber('a@x.test', true);
		$this->addSubscriber('b@x.test', true);
		$this->addSubscriber('c@x.test', false);

		$uid = $this->createUser('nl');
		$id  = $this->model()->schedule('Sujet', '<p>hi</p>', $uid, '2099-01-01 10:00:00');

		$row = $this->db()->select('status', 'scheduled_at', 'recipients_total')->from('nf_newsletter_campaigns')->where('id', $id)->row(FALSE);
		$this->assertSame('scheduled', $row['status']);
		$this->assertSame('2099-01-01 10:00:00', $row['scheduled_at']);
		$this->assertSame(2, (int) $row['recipients_total'], 'estimate = abonnés confirmés au moment du schedule');
	}

	public function test_future_campaign_is_not_claimed(): void
	{
		$this->clear();
		$this->addSubscriber('a@x.test', true);
		$uid = $this->createUser('nl');
		$id  = $this->model()->schedule('S', 'c', $uid, '2099-01-01 10:00:00');

		$r = $this->model()->process_due();

		$this->assertSame('scheduled', (string) $this->db()->select('status')->from('nf_newsletter_campaigns')->where('id', $id)->row());
		$this->assertSame(0, (int) $this->db()->from('nf_newsletter_queue')->where('campaign_id', $id)->count(), 'pas de file tant que pas échue');
		$this->assertSame(0, $r['sent']);
	}

	public function test_due_campaign_with_no_subscriber_finalizes_empty(): void
	{
		$this->clear(); // 0 abonné
		$uid = $this->createUser('nl');
		$id  = $this->model()->schedule('S', 'c', $uid, NULL); // NULL = maintenant → échue

		$r = $this->model()->process_due();

		$row = $this->db()->select('status', 'sent_to', 'recipients_total')->from('nf_newsletter_campaigns')->where('id', $id)->row(FALSE);
		$this->assertSame('sent', $row['status']);
		$this->assertSame(0, (int) $row['sent_to']);
		$this->assertSame(0, (int) $row['recipients_total']);
		$this->assertSame(1, $r['campaigns'], 'une campagne finalisée');
		$this->assertSame(0, $r['sent']);
	}

	public function test_build_queue_snapshots_confirmed_only(): void
	{
		$this->clear();
		$this->addSubscriber('ok1@x.test', true);
		$this->addSubscriber('ok2@x.test', true);
		$this->addSubscriber('pending@x.test', false);
		$uid = $this->createUser('nl');
		$id  = $this->model()->schedule('S', 'c', $uid, NULL);

		// _build_queue par réflexion : teste le snapshot SANS la boucle d'envoi.
		$model = $this->model();
		$ref   = new \ReflectionMethod($model, '_build_queue');
		$ref->setAccessible(true);
		$total = $ref->invoke($model, $id);

		$this->assertSame(2, $total, 'seuls les confirmés entrent dans la file');
		$emails = $this->db()->select('email')->from('nf_newsletter_queue')->where('campaign_id', $id)->order_by('email ASC')->get();
		$this->assertSame(['ok1@x.test', 'ok2@x.test'], $emails);
	}

	public function test_finalize_counts_sent_and_failed(): void
	{
		$this->clear();
		$uid = $this->createUser('nl');

		// Campagne en cours avec file déjà traitée (aucune 'pending' → la boucle d'envoi ne tourne pas).
		$id = (int) $this->db()->insert('nf_newsletter_campaigns', [
			'subject'          => 'S',
			'content'          => 'c',
			'user_id'          => $uid,
			'status'           => 'sending',
			'scheduled_at'     => date('Y-m-d H:i:s'),
			'recipients_total' => 3,
		]);
		foreach (['sent', 'sent', 'failed'] as $i => $st)
		{
			$this->db()->insert('nf_newsletter_queue', ['campaign_id' => $id, 'email' => $i.'@x.test', 'token' => 't'.$i, 'status' => $st]);
		}

		$r = $this->model()->process_due();

		$row = $this->db()->select('status', 'sent_to', 'failed_to')->from('nf_newsletter_campaigns')->where('id', $id)->row(FALSE);
		$this->assertSame('sent', $row['status']);
		$this->assertSame(2, (int) $row['sent_to']);
		$this->assertSame(1, (int) $row['failed_to']);
		$this->assertSame(1, $r['campaigns']);
	}

	public function test_cancel_only_when_scheduled(): void
	{
		$this->clear();
		$uid = $this->createUser('nl');
		$id  = $this->model()->schedule('S', 'c', $uid, '2099-01-01 10:00:00');

		$this->assertTrue($this->model()->cancel($id));
		$this->assertSame('cancelled', (string) $this->db()->select('status')->from('nf_newsletter_campaigns')->where('id', $id)->row());
		$this->assertFalse($this->model()->cancel($id), 'une campagne déjà annulée ne se ré-annule pas');
	}

	public function test_process_due_is_idempotent_after_sent(): void
	{
		$this->clear();
		$uid = $this->createUser('nl');
		$id  = $this->model()->schedule('S', 'c', $uid, NULL); // échue, 0 abonné
		$this->model()->process_due();                          // → sent

		$r = $this->model()->process_due();                     // 2e passage
		$this->assertSame(0, $r['campaigns'], 'une campagne déjà sent n\'est pas re-finalisée');
		$this->assertSame('sent', (string) $this->db()->select('status')->from('nf_newsletter_campaigns')->where('id', $id)->row());
	}
}
