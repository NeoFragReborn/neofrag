<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Modules\Forum\Lib\Forum_Threading;

/**
 * Tests unitaires du threading PUR (extrait du god-object forum). Pin l'algorithme de
 * profondeur (dupliqué auparavant dans get_messages + get_messages_with_threading) et la
 * règle de limite de _validate_parent().
 */
final class ForumThreadingTest extends TestCase
{
	/** @param array<int,array{message_id:int,parent_id:int}> $rows */
	private function depths(array $rows): array
	{
		return array_map(static fn(array $m): int => $m['depth'], Forum_Threading::assign_depths($rows));
	}

	public function test_flat_thread_all_depth_zero(): void
	{
		$rows = [
			['message_id' => 1, 'parent_id' => 0],
			['message_id' => 2, 'parent_id' => 0],
			['message_id' => 3, 'parent_id' => 0],
		];
		$this->assertSame([0, 0, 0], $this->depths($rows));
	}

	public function test_nested_replies_increment_depth(): void
	{
		// 1 ─ 2 (rép. à 1) ─ 3 (rép. à 2) ; 4 (rép. à 1)
		$rows = [
			['message_id' => 1, 'parent_id' => 0],
			['message_id' => 2, 'parent_id' => 1],
			['message_id' => 3, 'parent_id' => 2],
			['message_id' => 4, 'parent_id' => 1],
		];
		$this->assertSame([0, 1, 2, 1], $this->depths($rows));
	}

	public function test_unknown_parent_falls_back_to_root(): void
	{
		// parent_id 99 n'a pas été vu avant → depth 0 (racine), pas une erreur.
		$rows = [
			['message_id' => 5, 'parent_id' => 99],
			['message_id' => 6, 'parent_id' => 5],
		];
		$this->assertSame([0, 1], $this->depths($rows));
	}

	public function test_assign_depths_preserves_other_fields_and_order(): void
	{
		$rows = [
			['message_id' => 1, 'parent_id' => 0, 'message' => 'a'],
			['message_id' => 2, 'parent_id' => 1, 'message' => 'b'],
		];
		$out = Forum_Threading::assign_depths($rows);
		$this->assertSame('a', $out[0]['message']);
		$this->assertSame('b', $out[1]['message']);
		$this->assertSame(1, $out[1]['depth']);
		$this->assertCount(2, $out);
	}

	public function test_empty_set(): void
	{
		$this->assertSame([], Forum_Threading::assign_depths([]));
	}

	public function test_parent_depth_allows_reply_is_strict_less_than_max(): void
	{
		// _validate_parent rejette si depth >= max → autorisé ssi depth < max (max défaut = 3).
		$this->assertTrue(Forum_Threading::parent_depth_allows_reply(1, 3));
		$this->assertTrue(Forum_Threading::parent_depth_allows_reply(2, 3));
		$this->assertFalse(Forum_Threading::parent_depth_allows_reply(3, 3)); // atteint le max → refus
		$this->assertFalse(Forum_Threading::parent_depth_allows_reply(4, 3));
	}
}
