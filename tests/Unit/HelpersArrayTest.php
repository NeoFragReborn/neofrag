<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests pilotes sur neofrag/helpers/array.php (fonctions pures).
 */
final class HelpersArrayTest extends TestCase
{
    public function test_array_last_key_returns_last_key(): void
    {
        $this->assertSame('c', array_last_key(['a' => 1, 'b' => 2, 'c' => 3]));
    }

    public function test_array_last_returns_last_value(): void
    {
        $this->assertSame(30, array_last([10, 20, 30]));
    }

    public function test_array_offset_left_drops_from_start(): void
    {
        $this->assertSame([2, 3, 4], array_offset_left([1, 2, 3, 4]));
        $this->assertSame([3, 4], array_offset_left([1, 2, 3, 4], 2));
    }

    public function test_array_offset_right_drops_from_end(): void
    {
        $this->assertSame([1, 2, 3], array_offset_right([1, 2, 3, 4]));
        $this->assertSame([1, 2], array_offset_right([1, 2, 3, 4], 2));
    }
}
