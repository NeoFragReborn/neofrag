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

    /**
     * Le texte rangé codé par le formulaire (`&eacute;`) et le même texte arrivé brut donnent la même
     * sortie : codée UNE fois. `htmlspecialchars()` faisait `&amp;eacute;`, que la page affichait
     * « &eacute; » (vu par le mainteneur le 2026-10-05).
     */
    public function test_nf_texte_encodes_once_whatever_the_origin(): void
    {
        $range = utf8_htmlentities('Réunion — l\'été <b>"R&D"</b>');
        $brut  = 'Réunion — l\'été <b>"R&D"</b>';

        $this->assertSame(nf_texte($brut), nf_texte($range));
        $this->assertSame('Réunion — l&#039;été &lt;b&gt;&quot;R&amp;D&quot;&lt;/b&gt;', nf_texte($range));

        // Une balise reçue d'un service extérieur reste du texte.
        $this->assertSame('&lt;script&gt;', nf_texte('<script>'));
        // Une entité tapée telle quelle par quelqu'un (rangée « &amp;eacute; ») se relit telle quelle.
        $this->assertSame('&amp;eacute;', nf_texte(utf8_htmlentities('&eacute;')));
        $this->assertSame('', nf_texte(NULL));
    }

    public function test_nf_texte_shortens_the_decoded_text_without_cutting_an_entity(): void
    {
        $this->assertSame('Été…', nf_texte('&Eacute;t&eacute; indien', 4));
        $this->assertSame('Court', nf_texte('Court', 10));
    }

    /**
     * La version texte d'un courriel : strip_tags() collait les paragraphes, et le lien de validation se
     * lisait « navigateur :https://…/validation/…Si tu n'es pas… » (épreuve du 2026-10-05).
     */
    public function test_nf_texte_depuis_html_keeps_a_mail_link_usable(): void
    {
        $html  = '<style>p{color:red}</style><p>Copie cette URL dans ton navigateur :</p><p>https://site.test/user/validation/abc</p>'
            .'<p>Si tu n&#039;es pas à l&eacute;origine</p><p><a href="https://site.test/user/validation/abc">Valider</a></p>';
        $texte = nf_texte_depuis_html($html);

        $this->assertStringContainsString("navigateur :\n\nhttps://site.test/user/validation/abc\n\nSi tu n'es pas à léorigine", $texte);
        $this->assertStringContainsString('Valider (https://site.test/user/validation/abc)', $texte);
        $this->assertStringNotContainsString('color:red', $texte);
    }

    /**
     * Une signature écrite dans l'éditeur riche : rangée codée par form2 jusqu'au 2026-10-05, elle
     * s'affichait en code (`<p><img …></p>` à l'écran) ; décodée, elle redevient une image.
     */
    public function test_nf_contenu_editeur_shows_html_once_and_keeps_old_text(): void
    {
        $range = utf8_htmlentities('<p><img src="/upload/editeur/2026/10/a.jpg" alt="" width="800" height="420"></p>');
        $this->assertStringContainsString('<img src="/upload/editeur/2026/10/a.jpg"', nf_contenu_editeur($range));
        $this->assertStringNotContainsString('&lt;', nf_contenu_editeur($range));

        // Du HTML neuf : assaini (le script part), pas de <br /> glissé entre les paragraphes.
        $html = nf_contenu_editeur("<p>Un</p>\n<p>Deux<script>alert(1)</script></p>");
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<br', $html);

        // Un ancien texte simple : mis en forme comme avant (sauts de ligne, liens).
        $this->assertStringContainsString('<br', nf_contenu_editeur("ligne 1\nligne 2"));
    }

    public function test_nf_texte_brut_decodes_for_plain_text_readers(): void
    {
        // L'objet d'un courriel, un message Discord : « é », jamais « &eacute; » ni « &#039; ».
        $this->assertSame('l\'été "R&D"', nf_texte_brut('l&#039;&eacute;t&eacute; &quot;R&amp;D&quot;'));
        $this->assertSame('l\'été', nf_texte_brut('l&apos;été'));
    }
}
