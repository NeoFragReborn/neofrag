<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Tests d'OBJET des rappels d'événement (modèle réel `Events`, DB de test, transaction rollback).
 * Couvre : fenêtre d'échéance (défaut 24 h), idempotence (`reminder_sent_at`), exclusion des
 * événements non publiés / passés / trop lointains, et sélection des destinataires (absents exclus).
 *
 * ⚠ Plafond headless : l'objet « langue » courant n'est pas booté (session/routing absents) → un
 * `$this->lang()` dans le code de prod crashe hors requête. On exerce donc l'ordonnancement avec
 * **0 participant** (la boucle de notification — seul endroit qui appelle lang() — ne tourne pas),
 * et la sélection des destinataires via `reminder_recipients()` (requête pure). Le PUSH réel de la
 * notification (lang inclus) est vérifié en live via le cron (cf. vérif Playwright/cron).
 *
 * Les événements pré-existants sont neutralisés (`reminder_sent_at` posé) au début de chaque test.
 */
final class EventReminderTest extends HeadlessTestCase
{
	private function model(): \NF\Modules\Events\Models\Events
	{
		return new \NF\Modules\Events\Models\Events(\NeoFrag());
	}

	private function neutralizeExisting(): void
	{
		$this->db()->where('reminder_sent_at', NULL)->update('nf_events', ['reminder_sent_at' => date('Y-m-d H:i:s')]);
	}

	private function makeEvent(string $date, string $published = '1'): int
	{
		$uid = $this->createUser('org');
		$tid = (int) $this->db()->insert('nf_events_types', ['type' => 0, 'title' => 'T', 'color' => '#000', 'icon' => 'fa']);

		return (int) $this->db()->insert('nf_events', [
			'type_id'             => $tid,
			'user_id'             => $uid,
			'title'               => 'Tournoi',
			'description'         => '',
			'private_description' => '',
			'location'            => '',
			'date'                => $date,
			'published'           => $published,
		]);
	}

	public function test_due_event_is_claimed_within_window(): void
	{
		$this->neutralizeExisting();
		$eid = $this->makeEvent(date('Y-m-d H:i:s', strtotime('+2 hours'))); // 0 participant → pas de notif/lang

		$this->assertSame(1, $this->model()->send_due_reminders(), 'un événement échu est rappelé');
		$this->assertNotNull($this->db()->select('reminder_sent_at')->from('nf_events')->where('event_id', $eid)->row());
	}

	public function test_idempotent_second_pass_does_nothing(): void
	{
		$this->neutralizeExisting();
		$this->makeEvent(date('Y-m-d H:i:s', strtotime('+2 hours')));

		$this->assertSame(1, $this->model()->send_due_reminders());
		$this->assertSame(0, $this->model()->send_due_reminders(), '2e passage : reminder_sent_at déjà posé');
	}

	public function test_event_outside_window_is_not_reminded(): void
	{
		$this->neutralizeExisting();
		$this->makeEvent(date('Y-m-d H:i:s', strtotime('+48 hours'))); // > 24 h défaut

		$this->assertSame(0, $this->model()->send_due_reminders());
	}

	public function test_past_event_is_not_reminded(): void
	{
		$this->neutralizeExisting();
		$this->makeEvent(date('Y-m-d H:i:s', strtotime('-2 hours')));

		$this->assertSame(0, $this->model()->send_due_reminders());
	}

	public function test_unpublished_event_is_not_reminded(): void
	{
		$this->neutralizeExisting();
		$this->makeEvent(date('Y-m-d H:i:s', strtotime('+2 hours')), '0');

		$this->assertSame(0, $this->model()->send_due_reminders());
	}

	public function test_recipients_exclude_absent_participants(): void
	{
		$this->neutralizeExisting();
		$eid = $this->makeEvent(date('Y-m-d H:i:s', strtotime('+2 hours')));

		$going    = $this->createUser('going');
		$maybe    = $this->createUser('maybe');
		$pending  = $this->createUser('pending');
		$declined = $this->createUser('declined');
		$this->db()->insert('nf_events_participants', ['event_id' => $eid, 'user_id' => $going,    'status' => 1]);
		$this->db()->insert('nf_events_participants', ['event_id' => $eid, 'user_id' => $maybe,    'status' => 3]);
		$this->db()->insert('nf_events_participants', ['event_id' => $eid, 'user_id' => $pending,  'status' => 0]);
		$this->db()->insert('nf_events_participants', ['event_id' => $eid, 'user_id' => $declined, 'status' => 2]);

		$recipients = array_map('intval', $this->model()->reminder_recipients($eid));
		sort($recipients);
		$expected = [$going, $maybe, $pending];
		sort($expected);

		$this->assertSame($expected, $recipients, 'tous sauf l\'absent (status 2)');
	}
}
