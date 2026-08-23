<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Modules\Forum\Lib\Forum_Search;

/**
 * Tests unitaires de la logique PURE de recherche forum (extraite du god-object
 * modules/forum/models/forum.php). Pin le contrat EXACT de _to_boolean_query() tel
 * qu'il était : tokens AND avec stemming, phrases mono-token, exclusions, min 3 car.
 */
final class ForumSearchTest extends TestCase
{
	public function test_words_become_and_prefix_wildcards(): void
	{
		$this->assertSame('+foo* +bar*', Forum_Search::to_boolean_query('foo bar'));
	}

	public function test_token_shorter_than_three_chars_is_dropped(): void
	{
		$this->assertSame('', Forum_Search::to_boolean_query('a'));
		$this->assertSame('', Forum_Search::to_boolean_query('ab'));
		$this->assertSame('+abc*', Forum_Search::to_boolean_query('abc'));
		// Un token court est ignoré mais les autres restent.
		$this->assertSame('+foo*', Forum_Search::to_boolean_query('foo ab'));
	}

	public function test_single_quoted_token_is_preserved_as_phrase(): void
	{
		// La branche phrase ne matche qu'un token qui commence ET finit par " (donc sans espace interne).
		$this->assertSame('"hello"', Forum_Search::to_boolean_query('"hello"'));
		// Une "phrase à espaces" est splitée par \s+ → guillemets retirés, chaque mot devient +mot*.
		$this->assertSame('+hello* +world*', Forum_Search::to_boolean_query('"hello world"'));
	}

	public function test_exclusion_is_cleaned_and_has_no_min_length(): void
	{
		$this->assertSame('-spam', Forum_Search::to_boolean_query('-spam'));
		$this->assertSame('-spam', Forum_Search::to_boolean_query('-sp!am')); // non-alphanum retiré
		$this->assertSame('-x', Forum_Search::to_boolean_query('-x'));        // pas de min 3 sur les exclusions
	}

	public function test_unicode_letters_are_kept(): void
	{
		$this->assertSame('+café*', Forum_Search::to_boolean_query('café'));
	}

	public function test_empty_and_whitespace_yield_empty(): void
	{
		$this->assertSame('', Forum_Search::to_boolean_query(''));
		$this->assertSame('', Forum_Search::to_boolean_query('   '));
	}

	public function test_mixed_query_combines_branches(): void
	{
		$this->assertSame('+foo* "bar" -baz', Forum_Search::to_boolean_query('foo "bar" -baz x'));
	}

	public function test_quote_doubles_single_quotes(): void
	{
		$this->assertSame("'x'", Forum_Search::quote('x'));
		$this->assertSame("'a''b'", Forum_Search::quote("a'b"));
		$this->assertSame("''''", Forum_Search::quote("'"));
	}
}
