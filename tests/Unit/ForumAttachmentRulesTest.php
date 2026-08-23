<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Modules\Forum\Lib\Forum_Attachment_Rules as Rules;

/**
 * Tests unitaires des règles PURES de pièces jointes forum (extraites du god-object :
 * parsing config + allow-list MIME + borne de taille). Pin les défauts et les décisions
 * appliquées par modules/forum/controllers/index.php.
 */
final class ForumAttachmentRulesTest extends TestCase
{
	public function test_parse_mimes_uses_default_when_unset_or_empty(): void
	{
		$default = Rules::parse_mimes(null);
		$this->assertContains('image/png', $default);
		$this->assertContains('application/pdf', $default);
		$this->assertSame($default, Rules::parse_mimes(''));
	}

	public function test_parse_mimes_splits_and_trims_configured_csv(): void
	{
		$this->assertSame(
			['image/png', 'image/gif'],
			array_values(Rules::parse_mimes('image/png ,  image/gif'))
		);
	}

	public function test_max_bytes_default_and_kb_conversion(): void
	{
		$this->assertSame(5120 * 1024, Rules::max_bytes(null));
		$this->assertSame(2048 * 1024, Rules::max_bytes(2048));
		$this->assertSame(0, Rules::max_bytes(0)); // 0 Ko configuré → 0 octet (comportement préservé)
	}

	public function test_is_allowed_mime(): void
	{
		$allowed = ['image/png', 'application/pdf'];
		$this->assertTrue(Rules::is_allowed_mime('image/png', $allowed));
		$this->assertFalse(Rules::is_allowed_mime('application/x-msdownload', $allowed));
		$this->assertFalse(Rules::is_allowed_mime('image/png', [])); // allow-list vide → rien n'est permis
	}

	public function test_is_within_size_boundary(): void
	{
		$this->assertTrue(Rules::is_within_size(1024, 1024));  // pile à la limite = accepté (<=)
		$this->assertTrue(Rules::is_within_size(1023, 1024));
		$this->assertFalse(Rules::is_within_size(1025, 1024)); // au-delà = refusé
	}
}
