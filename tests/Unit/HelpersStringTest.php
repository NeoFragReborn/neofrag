<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests pilotes sur neofrag/helpers/string.php (fonctions pures).
 *
 * But : poser une fondation de tests sur le code réellement testable, et figer
 * le comportement de helpers utilisés partout (slugs d'URL, validation, etc.)
 * avant tout refactor du cœur.
 */
final class HelpersStringTest extends TestCase
{
    public function test_cc2u_converts_camelcase_to_underscored(): void
    {
        $this->assertSame('hello_world', cc2u('helloWorld'));
        $this->assertSame('neo_frag', cc2u('NeoFrag'));
    }

    public function test_u2lcc_converts_underscored_to_lower_camelcase(): void
    {
        $this->assertSame('helloWorld', u2lcc('hello_world'));
    }

    public function test_u2ucc_converts_underscored_to_upper_camelcase(): void
    {
        $this->assertSame('HelloWorld', u2ucc('hello_world'));
    }

    public function test_in_string_strict_is_case_sensitive(): void
    {
        $this->assertTrue(in_string('lo', 'hello'));
        $this->assertFalse(in_string('xy', 'hello'));
        $this->assertFalse(in_string('LO', 'hello'));          // strict par défaut
        $this->assertTrue(in_string('LO', 'hello', false));    // insensible à la casse
    }

    public function test_in_string_empty_needle_returns_false(): void
    {
        $this->assertFalse(in_string('', 'hello'));
    }

    #[DataProvider('emptyCases')]
    public function test_is_empty(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, is_empty($value));
    }

    public static function emptyCases(): array
    {
        return [
            'empty string'    => ['', true],
            'null'            => [null, true],
            'empty array'     => [[], true],
            'string zero'     => ['0', false],
            'int zero'        => [0, false],
            'non-empty array' => [[1], false],
            'whitespace'      => [' ', false],
        ];
    }

    public function test_strtoarray_splits_or_returns_empty(): void
    {
        $this->assertSame(['a', 'b', 'c'], strtoarray(',', 'a,b,c'));
        $this->assertSame([], strtoarray(',', ''));
        $this->assertSame(['0'], strtoarray(',', '0'));
    }

    public function test_url_title_slugifies(): void
    {
        $this->assertSame('hello-world', url_title('Hello World'));
        $this->assertSame('top-10-players', url_title('Top 10 Players!'));
    }

    public function test_is_valid_email(): void
    {
        $this->assertTrue(is_valid_email('john@example.com'));
        $this->assertFalse(is_valid_email('not-an-email'));
    }

    public function test_is_valid_url(): void
    {
        $this->assertTrue((bool) is_valid_url('https://neofr.ag'));
        $this->assertTrue((bool) is_valid_url('tel:+33123456789'));
        $this->assertFalse((bool) is_valid_url('nope'));
    }

    #[DataProvider('versionCases')]
    public function test_version_format(string $input, string $expected): void
    {
        $this->assertSame($expected, version_format($input));
    }

    public static function versionCases(): array
    {
        return [
            'plain'        => ['1.2.3', '1.2.3'],
            'prefixed'     => ['v2.0', '2.0'],
            'release cand' => ['1.2.3 RC2', '1.2.3rc2'],
        ];
    }

    public function test_print_number_uses_nbsp_thousands_separator(): void
    {
        $this->assertSame('1&nbsp;000', print_number(1000));
        $this->assertSame('1&nbsp;234&nbsp;567', print_number(1234567));
    }
}
