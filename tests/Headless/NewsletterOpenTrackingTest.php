<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Tests d'OBJET du suivi d'ouverture newsletter (modèle réel, DB de test, transaction rollback).
 * Couvre : génération d'un track_token par destinataire (_build_queue) et `record_open()` —
 * pose opened_at + incrément opened_to de la campagne, une seule fois (claim atomique), token inconnu ignoré.
 * Chemin 100 % data (pas de lang/SMTP) → testable en objet réel.
 */
final class NewsletterOpenTrackingTest extends HeadlessTestCase
{
	private function model(): \NF\Modules\Newsletter\Models\Newsletter
	{
		return new \NF\Modules\Newsletter\Models\Newsletter(\NeoFrag());
	}

	private function clear(): void
	{
		$this->db()->where('id >', 0)->delete('nf_newsletter_queue');
		$this->db()->where('id >', 0)->delete('nf_newsletter_campaigns');
		$this->db()->where('id >', 0)->delete('nf_newsletter_subscribers');
	}

	private function makeCampaign(): int
	{
		return (int) $this->db()->insert('nf_newsletter_campaigns', [
			'subject' => 'S', 'content' => 'c', 'user_id' => $this->createUser('nl'), 'status' => 'sent',
		]);
	}

	public function test_build_queue_generates_a_track_token_per_recipient(): void
	{
		$this->clear();
		foreach (['a@x.test', 'b@x.test'] as $e)
		{
			$this->db()->insert('nf_newsletter_subscribers', ['email' => $e, 'token' => substr(md5($e), 0, 32), 'confirmed' => 1]);
		}
		$cid = $this->makeCampaign();

		$model = $this->model();
		$ref   = new \ReflectionMethod($model, '_build_queue');
		$ref->setAccessible(true);
		$ref->invoke($model, $cid);

		$tokens = $this->db()->select('track_token')->from('nf_newsletter_queue')->where('campaign_id', $cid)->get();
		$this->assertCount(2, $tokens);
		$this->assertCount(2, array_unique($tokens), 'un token distinct par destinataire');
		$this->assertNotContains('', array_map('strval', $tokens), 'aucun token vide');
	}

	public function test_record_open_marks_opened_and_increments_campaign(): void
	{
		$this->clear();
		$cid = $this->makeCampaign();
		$this->db()->insert('nf_newsletter_queue', ['campaign_id' => $cid, 'email' => 'a@x.test', 'token' => 't', 'track_token' => 'TK_open_1', 'status' => 'sent']);

		$this->assertTrue($this->model()->record_open('TK_open_1'), 'première ouverture = nouvelle');

		$this->assertNotNull($this->db()->select('opened_at')->from('nf_newsletter_queue')->where('track_token', 'TK_open_1')->row());
		$this->assertSame(1, (int) $this->db()->select('opened_to')->from('nf_newsletter_campaigns')->where('id', $cid)->row());
	}

	public function test_record_open_is_idempotent(): void
	{
		$this->clear();
		$cid = $this->makeCampaign();
		$this->db()->insert('nf_newsletter_queue', ['campaign_id' => $cid, 'email' => 'a@x.test', 'token' => 't', 'track_token' => 'TK_idem', 'status' => 'sent']);

		$this->assertTrue($this->model()->record_open('TK_idem'));
		$this->assertFalse($this->model()->record_open('TK_idem'), 'réouverture ne recompte pas');
		$this->assertSame(1, (int) $this->db()->select('opened_to')->from('nf_newsletter_campaigns')->where('id', $cid)->row(), 'opened_to ne double pas');
	}

	public function test_record_open_unknown_token_is_noop(): void
	{
		$this->clear();
		$this->assertFalse($this->model()->record_open('does-not-exist'));
	}
}
