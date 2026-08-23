<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Tests d'OBJET de la segmentation newsletter (modèle réel, DB de test, transaction rollback).
 * Couvre `segment_count()` et le filtrage de `_build_queue()` selon le segment :
 * 'all' (tous confirmés) | 'members' (confirmés rattachés à un compte) | 'group' (membres d'un groupe).
 * Chemin 100 % data → testable en objet réel (pas de lang/SMTP).
 */
final class NewsletterSegmentationTest extends HeadlessTestCase
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

	private function addSub(string $email, bool $confirmed, ?int $user_id = NULL): void
	{
		$this->db()->insert('nf_newsletter_subscribers', [
			'email'     => $email,
			'token'     => substr(md5($email.uniqid('', true)), 0, 32),
			'confirmed' => $confirmed ? 1 : 0,
			'user_id'   => $user_id,
		]);
	}

	private function makeGroup(): int
	{
		return (int) $this->db()->insert('nf_groups', ['name' => 'seg_'.substr(md5(uniqid('', true)), 0, 8), 'color' => '#000', 'icon' => 'fa']);
	}

	private function queuedEmails(int $campaign_id): array
	{
		$model = $this->model();
		$ref   = new \ReflectionMethod($model, '_build_queue');
		$ref->setAccessible(true);
		$ref->invoke($model, $campaign_id);

		return $this->db()->select('email')->from('nf_newsletter_queue')->where('campaign_id', $campaign_id)->order_by('email ASC')->get();
	}

	public function test_segment_count_all_members_group(): void
	{
		$this->clear();
		$this->addSub('guest@x.test', true);                                  // invité confirmé
		$m1 = $this->createUser('m1'); $this->addSub('m1@x.test', true, $m1); // membre confirmé hors groupe
		$m2 = $this->createUser('m2'); $this->addSub('m2@x.test', true, $m2); // membre confirmé du groupe
		$m3 = $this->createUser('m3'); $this->addSub('m3@x.test', false, $m3);// membre NON confirmé (exclu partout)

		$gid = $this->makeGroup();
		$this->db()->insert('nf_users_groups', ['user_id' => $m2, 'group_id' => $gid]);

		$this->assertSame(3, $this->model()->segment_count('all'), 'tous les confirmés (invité + 2 membres)');
		$this->assertSame(2, $this->model()->segment_count('members'), 'confirmés rattachés à un compte');
		$this->assertSame(1, $this->model()->segment_count('group', $gid), 'confirmés membres du groupe');
	}

	public function test_build_queue_members_only(): void
	{
		$this->clear();
		$this->addSub('guest@x.test', true);
		$m = $this->createUser('m'); $this->addSub('member@x.test', true, $m);

		$cid = $this->model()->schedule('S', 'c', $this->createUser('author'), NULL, 'members');

		$this->assertSame(['member@x.test'], $this->queuedEmails($cid), 'seul le membre est mis en file');
	}

	public function test_build_queue_group(): void
	{
		$this->clear();
		$in  = $this->createUser('in');  $this->addSub('in@x.test', true, $in);
		$out = $this->createUser('out'); $this->addSub('out@x.test', true, $out);
		$this->addSub('guest@x.test', true);

		$gid = $this->makeGroup();
		$this->db()->insert('nf_users_groups', ['user_id' => $in, 'group_id' => $gid]);

		$cid = $this->model()->schedule('S', 'c', $this->createUser('author'), NULL, 'group', $gid);

		$this->assertSame(['in@x.test'], $this->queuedEmails($cid), 'seul le membre du groupe est mis en file');
	}

	public function test_invalid_segment_falls_back_to_all(): void
	{
		$this->clear();
		$this->addSub('a@x.test', true);
		$this->addSub('b@x.test', true);

		$cid = $this->model()->schedule('S', 'c', $this->createUser('author'), NULL, 'bogus');

		$this->assertSame('all', (string) $this->db()->select('segment')->from('nf_newsletter_campaigns')->where('id', $cid)->row());
		$this->assertSame(['a@x.test', 'b@x.test'], $this->queuedEmails($cid));
	}
}
