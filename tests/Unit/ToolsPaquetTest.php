<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * tools/lib/paquet.php — ce qui a le droit d'entrer dans un paquet d'installation ou de mise à jour.
 *
 * Fige l'incident de l'audit des releases (2026-10-04) : un mémo d'exploitation posé à la racine du
 * dépôt est parti dans les paquets de la 1.2.8 à la 1.2.18, parce que la fabrique excluait des fichiers
 * un par un au lieu d'admettre une liste. La liste blanche ne doit laisser passer que le site.
 *
 * Il tourne dans son propre processus : la bibliothèque des outils (`tools/lib/outil.php`) définit
 * `nf_refus()`, comme le CMS (`neofrag/helpers/input.php`) — chargées dans le même processus que les
 * tests qui démarrent le CMS, les deux s'arrêtaient sur « Cannot redeclare function » (2026-10-04).
 */
#[RunTestsInSeparateProcesses]
final class ToolsPaquetTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../tools/lib/paquet.php';
    }

    public function test_un_fichier_pose_a_la_racine_n_entre_pas(): void
    {
        foreach (['public', 'demo', 'update'] as $variant) {
            $this->assertTrue(nf_paquet_exclu('NeoFrag-Cloudflare-sous-domaine-et-emails.md', $variant), 'le mémo de 2026-10-01');
            $this->assertTrue(nf_paquet_exclu('NOTES.md', $variant), 'toute note inconnue');
            $this->assertTrue(nf_paquet_exclu('README.md', $variant));
            $this->assertTrue(nf_paquet_exclu('docs/internal/journal.md', $variant));
            $this->assertTrue(nf_paquet_exclu('tools/check-all.php', $variant));
            $this->assertTrue(nf_paquet_exclu('tests/bootstrap.php', $variant));
            $this->assertTrue(nf_paquet_exclu('bot/src/index.ts', $variant), 'le bot a son archive à lui');
            $this->assertTrue(nf_paquet_exclu('.claude/settings.local.json', $variant));
            $this->assertTrue(nf_paquet_exclu('graphify-out/graph.json', $variant));
        }
    }

    public function test_les_secrets_et_la_vitrine_restent_dehors(): void
    {
        foreach (['config/db.php', 'config/crypt.php', 'config/url.php', 'config/webmaster.php', 'config/db-test.php', 'install/db.txt'] as $secret) {
            $this->assertTrue(nf_paquet_exclu($secret, 'public'), $secret);
        }

        $this->assertFalse(nf_paquet_exclu('config/db.php.dist', 'public'), 'un gabarit passe');
        $this->assertFalse(nf_paquet_exclu('config/.htaccess', 'update'));
        $this->assertTrue(nf_paquet_exclu('themes/vitrine/vitrine.php', 'public'));
        $this->assertTrue(nf_paquet_exclu('widgets/landing/landing.php', 'demo'));
        $this->assertTrue(nf_paquet_exclu('install/vitrine.sql', 'demo'));
        $this->assertTrue(nf_paquet_exclu('upload/avatars/x.png', 'public'), 'les envois des visiteurs');
        $this->assertFalse(nf_paquet_exclu('upload/.htaccess', 'public'));
        $this->assertTrue(nf_paquet_exclu('marketplace/modules/forum.zip', 'public'), 'les archives sont servies à part');
        $this->assertFalse(nf_paquet_exclu('marketplace/catalog.json', 'public'));
        $this->assertTrue(nf_paquet_exclu('marketplace/catalog.json', 'update'), 'la mise à jour ne remplace pas le catalogue vivant de la vitrine');
    }

    public function test_le_site_entre(): void
    {
        foreach (['index.php', '.htaccess', 'neofrag/core/url.php', 'modules/forum/forum.php', 'widgets/clock/clock.php',
                  'themes/nebula/nebula.php', 'install/schema.sql', 'migrations/2026_10_03_referencement.up.sql',
                  'vendor/autoload.php', 'COPYING.LESSER', 'ROADMAP.md',
                  'nginx.conf', 'Caddyfile', 'NOTICE', 'LICENSES/GPL-2.0-or-later.txt'] as $fichier) {
            $this->assertFalse(nf_paquet_exclu($fichier, 'update'), $fichier);
        }

        $this->assertFalse(nf_paquet_exclu('install/demo.sql', 'demo'), 'le contenu de la démonstration, dans son paquet');
        $this->assertTrue(nf_paquet_exclu('install/demo.sql', 'public'));
        $this->assertTrue(nf_paquet_exclu('css/style.css.map', 'public'), 'fichiers de travail');
    }

    /**
     * Jusqu'à la 1.2.22, aucun paquet ne portait `logs/` et rien ne le créait : un site installé depuis le
     * paquet n'enregistrait aucune erreur. Les dossiers d'exécution partent vides, avec leur garde, dans
     * les paquets d'installation ; la mise à jour, qui les protège, ne les porte pas.
     */
    public function test_les_dossiers_d_execution_partent_vides_avec_leur_garde(): void
    {
        foreach (['public', 'demo'] as $variant) {
            foreach (['logs', 'cache', 'backups'] as $dossier) {
                $this->assertFalse(nf_paquet_exclu("{$dossier}/.htaccess", $variant), "{$dossier}/.htaccess, paquet {$variant}");
                $this->assertTrue(nf_paquet_exclu("{$dossier}/.htaccess", 'update'), "{$dossier}/ est protégé par la mise à jour");
            }
        }

        foreach (['logs/php.log', 'logs/.gitkeep', 'cache/monitoring/version.json', 'backups/20261004120000.zip'] as $contenu) {
            $this->assertTrue(nf_paquet_exclu($contenu, 'public'), $contenu);
        }
    }
}
