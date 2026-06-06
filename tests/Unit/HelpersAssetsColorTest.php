<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests sur les helpers purs assets.php (icon, is_asset) et color.php (get_colors).
 * Fige notamment le comportement fa-fw de icon() (utilisé pour l'alignement des menus).
 */
final class HelpersAssetsColorTest extends TestCase
{
    public function test_icon_adds_fixed_width_to_fontawesome(): void
    {
        $this->assertSame('<i class="icon fas fa-user fa-fw"></i>', icon('fas fa-user'));
        $this->assertSame('<i class="icon far fa-eye fa-fw"></i>', icon('far fa-eye'));
    }

    public function test_icon_pe7s_has_no_fixed_width(): void
    {
        $this->assertSame('<i class="icon pe-7s-cash"></i>', icon('pe-7s-cash'));
    }

    public function test_icon_unknown_wraps_raw(): void
    {
        $this->assertSame('<i class="icon">whatever</i>', icon('whatever'));
    }

    public function test_is_asset_true_for_static_extensions(): void
    {
        $this->assertTrue(is_asset('css'));
        $this->assertTrue(is_asset('png'));
        $this->assertTrue(is_asset('js'));
    }

    public function test_is_asset_false_for_app_extensions(): void
    {
        $this->assertFalse(is_asset('php'));
        $this->assertFalse(is_asset('json'));
        $this->assertFalse(is_asset('xml'));
    }

    public function test_get_colors_resolves_named_to_hex(): void
    {
        $this->assertSame('#007bff', get_colors('primary'));
        $this->assertSame('#dc3545', get_colors('danger'));
    }

    public function test_get_colors_without_convert_returns_name(): void
    {
        $this->assertSame('primary', get_colors('primary', false));
    }

    public function test_get_colors_passes_through_valid_hex(): void
    {
        $this->assertSame('#fff', get_colors('#fff'));
        $this->assertSame('#1a2b3c', get_colors('#1a2b3c'));
    }

    public function test_get_colors_null_returns_palette_without_empties(): void
    {
        $palette = get_colors();
        $this->assertArrayHasKey('primary', $palette);
        $this->assertArrayNotHasKey('link', $palette); // '' filtré
    }
}
