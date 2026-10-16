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

    /**
     * Ce que le routeur des assets sert : les ressources du produit et les envois publics, rien d'autre. Le
     * 2026-10-16, `/fr/logs/php.log` rendait le journal de la démo : toute adresse à extension lisait le fichier
     * de ce nom sous la racine, le préfixe de langue la faisant passer à côté des règles du serveur web.
     */
    public function test_nf_asset_servi_sert_les_ressources_du_produit(): void
    {
        foreach (['css/bootstrap.min.css', 'js/neofrag.js', 'images/logo.png', 'fonts/fontawesome/fa-solid-900.woff2',
            'themes/nebula/css/style.css', 'themes/nebula/overrides/modules/events/js/events.js',
            'modules/forum/js/forum.js', 'widgets/navigation/js/navigation.js', 'modules/gallery/images/vide.jpg',
            'upload/news/image.webp', 'upload/downloads/archive.zip', 'js/tinymce/tinymce.min.js'] as $chemin)
        {
            $this->assertTrue(nf_asset_servi($chemin), $chemin);
        }
    }

    public function test_nf_asset_servi_refuse_tout_le_reste(): void
    {
        foreach ([
            // Les dossiers que les configurations livrées refusent, atteints derrière une langue.
            'logs/php.log', 'backups/site-2026-10-16.zip', 'backups/addons/forum.zip', 'cache/monitoring/version.json.css',
            'config/db.php.bak', 'install/schema.sql', 'tools/check-all.php.txt', 'tests/Browser/page.html', 'docs/architecture.html',
            'vendor/altcha-org/altcha/CHANGELOG.css', 'bot/dist/index.js', 'dist/neofrag.zip', 'marketplace/catalog.zip',
            // Les pièces jointes : leur module contrôle l'accès.
            'upload/forum/pj.png', 'upload/talks/pj.pdf', 'upload/Forum/pj.png',
            // Les chemins cachés, les remontées, les détours.
            '.github/workflows/ci.yml', '.git/config.js', '.env.css', 'js/.cache/x.js', 'js/../logs/php.css',
            '../logs/php.css', '/etc/passwd.css', 'js//x.js', 'js\\..\\logs\\x.css', "js/x.js\0.png", 'upload/forum./pj.png',
            'upload/forum /pj.png', '',
            // Dans un dossier servi, ce qu'un navigateur ne charge pas.
            'modules/forum/install.sql.log', 'themes/nebula/README.yml', 'modules/forum/controllers/index.phps',
        ] as $chemin)
        {
            $this->assertFalse(nf_asset_servi($chemin), $chemin);
        }
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
