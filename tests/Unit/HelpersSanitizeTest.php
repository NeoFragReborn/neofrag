<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests du sanitizer HTML serveur (neofrag/helpers/sanitize.php — HTMLPurifier).
 *
 * Le contenu utilisateur riche est rendu en HTML brut dans les vues (commentaires, signatures,
 * forum BBCode…). On fige ici que les vecteurs XSS stockés connus sont neutralisés et que le
 * HTML légitime produit par l'éditeur est préservé. Les assertions portent sur la présence /
 * absence de tokens dangereux (robuste au formatage de sortie de HTMLPurifier).
 */
final class HelpersSanitizeTest extends TestCase
{
    /**
     * @return array<string, array{0:string}>
     */
    public static function xssPayloads(): array
    {
        return [
            'script tag'              => ['<script>alert(1)</script>'],
            'img onerror (unquoted)'  => ['<img src=x onerror=alert(1)>'],
            'img onerror (quoted)'    => ['<img src="x" onerror="alert(1)">'],
            'a href javascript'       => ['<a href="javascript:alert(1)">x</a>'],
            'a href JaVaScRiPt case'  => ['<a href="JaVaScRiPt:alert(1)">x</a>'],
            'a href vbscript'         => ['<a href="vbscript:msgbox(1)">x</a>'],
            'img src data html'       => ['<img src="data:text/html;base64,PHNjcmlwdD4=">'],
            'p onclick handler'       => ['<p onclick="evil()">hi</p>'],
            'svg onload'              => ['<svg onload=alert(1)>'],
            'iframe untrusted host'   => ['<iframe src="https://evil.example/x"></iframe>'],
            'style css js url'        => ['<p style="background:url(javascript:alert(1))">x</p>'],
            'bbcode font breakout'    => ['<span style="font-family: \'"><script>alert(1)</script>\';">x</span>'],
            'object embed'            => ['<object data="evil.swf"></object>'],
            'body onload'            => ['<body onload=alert(1)>'],
            'meta refresh'            => ['<meta http-equiv="refresh" content="0;url=javascript:alert(1)">'],
            'form formaction'         => ['<button formaction="javascript:alert(1)">x</button>'],
        ];
    }

    #[DataProvider('xssPayloads')]
    public function test_neutralizes_xss_payload(string $payload): void
    {
        $out = sanitize_html($payload);

        $this->assertStringNotContainsStringIgnoringCase('<script', $out);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $out);
        $this->assertStringNotContainsStringIgnoringCase('vbscript:', $out);
        $this->assertStringNotContainsStringIgnoringCase('onerror', $out);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $out);
        $this->assertStringNotContainsStringIgnoringCase('onload', $out);
        $this->assertStringNotContainsStringIgnoringCase('<svg', $out);
        $this->assertStringNotContainsStringIgnoringCase('<object', $out);
        $this->assertStringNotContainsStringIgnoringCase('formaction', $out);
        // data: schemes interdits sur les attributs src/href
        $this->assertStringNotContainsStringIgnoringCase('src="data:', $out);
    }

    public function test_strips_event_handler_but_keeps_safe_tag(): void
    {
        $out = sanitize_html('<p onclick="evil()">hello</p>');
        $this->assertStringContainsString('hello', $out);
        $this->assertStringContainsString('<p', $out);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $out);
    }

    public function test_preserves_basic_formatting(): void
    {
        $out = sanitize_html('<b>bold</b> <i>italic</i> <u>under</u> <strong>s</strong>');
        $this->assertStringContainsString('<b>bold</b>', $out);
        $this->assertStringContainsString('<i>italic</i>', $out);
        $this->assertStringContainsString('<strong>s</strong>', $out);
    }

    public function test_preserves_lists_and_headings(): void
    {
        $out = sanitize_html('<h2>Title</h2><ul><li>one</li><li>two</li></ul>');
        $this->assertStringContainsString('<h2>', $out);
        $this->assertStringContainsString('<ul>', $out);
        $this->assertStringContainsString('<li>one</li>', $out);
    }

    public function test_keeps_safe_external_link_and_hardens_it(): void
    {
        $out = sanitize_html('<a href="https://example.com/page">link</a>');
        $this->assertStringContainsString('href="https://example.com/page"', $out);
        $this->assertStringContainsString('>link</a>', $out);
        // Lien externe durci : nouvelle fenêtre + rel anti-tabnabbing / SEO
        $this->assertStringContainsStringIgnoringCase('target="_blank"', $out);
        $this->assertStringContainsStringIgnoringCase('noopener', $out);
    }

    public function test_keeps_safe_image(): void
    {
        $out = sanitize_html('<img src="https://example.com/cat.png" alt="cat">');
        $this->assertStringContainsString('src="https://example.com/cat.png"', $out);
        $this->assertStringContainsString('alt="cat"', $out);
    }

    public function test_allows_whitelisted_video_iframe(): void
    {
        $out = sanitize_html('<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="640" height="480"></iframe>');
        $this->assertStringContainsString('<iframe', $out);
        $this->assertStringContainsString('youtube.com/embed/dQw4w9WgXcQ', $out);
    }

    public function test_drops_untrusted_iframe(): void
    {
        $out = sanitize_html('<iframe src="https://evil.example/frame"></iframe>');
        $this->assertStringNotContainsString('evil.example', $out);
    }

    public function test_drops_google_maps_embed(): void
    {
        // La carte du produit est celle d'OpenStreetMap, servie par le site : Google Maps pose ses cookies (2026-10-08).
        $out = sanitize_html('<iframe src="https://www.google.com/maps/embed?pb=!1m18!2sParis" width="600" height="450"></iframe>');
        $this->assertStringNotContainsString('google.com/maps', $out);
    }

    public function test_preserves_safe_inline_color(): void
    {
        $out = sanitize_html('<span style="color:#ff0000">red</span>');
        $this->assertStringContainsString('red', $out);
        $this->assertStringContainsStringIgnoringCase('color', $out);
    }

    public function test_empty_input_returns_empty_string(): void
    {
        $this->assertSame('', sanitize_html(''));
        $this->assertSame('', sanitize_html(null));
    }

    public function test_is_idempotent(): void
    {
        $payload = '<p>hi <a href="https://x.test">l</a></p><script>alert(1)</script>';
        $once  = sanitize_html($payload);
        $twice = sanitize_html($once);
        $this->assertSame($once, $twice);
    }
}
