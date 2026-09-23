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

    // ── highlight() : l'extrait des résultats de recherche ────────────────────────────────
    //
    // Cette fonction rendait une page 404 au visiteur. `substr($texte, strpos($texte, '<mark>'))`
    // lève une TypeError en PHP 8 dès que `strpos` rend FALSE, le dispatcher l'attrape et sert une
    // page d'erreur à la place des résultats. Or FALSE est un cas NORMAL, atteint de deux façons
    // distinctes — et les deux se produisent sur un site francophone ordinaire.

    public function test_highlight_marks_the_keyword_and_frames_the_excerpt_on_it(): void
    {
        $sortie = highlight('Bonjour le monde du gaming', ['monde']);

        $this->assertStringContainsString('<mark>monde</mark>', $sortie);
        $this->assertStringStartsWith('<mark>', $sortie, 'l\'extrait doit commencer à la première occurrence');
    }

    public function test_highlight_survives_a_keyword_absent_from_this_field(): void
    {
        // Le cas réel : la requête SQL cherche dans PLUSIEURS colonnes. Un sujet de forum intitulé
        // « Salut tout le monde ! » répond à la recherche « le » par son TITRE, et le message
        // affiché sous lui ne contient pas le mot. Avant correction : TypeError, donc page 404.
        $sortie = highlight('Nouveau ici, hâte de jouer avec vous.', ['zzzintrouvable']);

        $this->assertStringContainsString('Nouveau ici', $sortie);
        $this->assertStringNotContainsString('<mark>', $sortie);
    }

    public function test_highlight_matches_accents_like_the_database_does(): void
    {
        // MySQL compare sans les accents : `'éléphant' LIKE '%ele%'` est VRAI. La ligne remonte
        // donc dans les résultats, et le marquage doit suivre — sinon rien n'est marqué et on
        // retombe sur le FALSE ci-dessus.
        $this->assertStringContainsString('<mark>élé</mark>', highlight('un éléphant rose', ['ele']));

        // Et réciproquement : chercher « éléphant » doit marquer « elephant » écrit sans accents.
        $this->assertStringContainsString('<mark>elephant</mark>', highlight('un elephant rose', ['éléphant']));
    }

    public function test_highlight_without_keywords_marks_nothing(): void
    {
        // Une liste vide produisait le motif `//i` — une alternance vide, qui marque CHAQUE
        // position du texte et rendait l'extrait illisible.
        $this->assertStringNotContainsString('<mark>', highlight('Texte quelconque', []));
    }
}
