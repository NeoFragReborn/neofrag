<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires PURS de la substitution des emojis custom (helper global custom_emojis,
 * neofrag/helpers/string.php). On injecte la map → aucun accès DB ; pin le contrat consommé par
 * bbcode() (rendu `:nom:` → <img> pour les noms déclarés seulement).
 */
final class CustomEmojisTest extends TestCase
{
	public function test_replaces_declared_tokens(): void
	{
		$map = [':smile:' => '<img class="nf-emoji" src="/u/smile.png" alt=":smile:">'];
		$this->assertSame('hi <img class="nf-emoji" src="/u/smile.png" alt=":smile:"> there', custom_emojis('hi :smile: there', $map));
	}

	public function test_leaves_unknown_tokens_untouched(): void
	{
		$this->assertSame('hi :unknown: there', custom_emojis('hi :unknown: there', [':smile:' => '<img>']));
	}

	public function test_empty_map_is_noop(): void
	{
		$this->assertSame('hi :smile:', custom_emojis('hi :smile:', []));
	}

	public function test_replaces_every_occurrence(): void
	{
		$this->assertSame('X and X', custom_emojis(':a: and :a:', [':a:' => 'X']));
	}
}
