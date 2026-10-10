<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests sur neofrag/helpers/file.php (fonctions pures).
 *
 * Couvre en priorité les helpers de sécurité upload ajoutés en 2026-06
 * (detect_mime_type via magic bytes, is_dangerous_upload) + extension /
 * get_mime_by_extension / human_size.
 */
final class HelpersFileTest extends TestCase
{
    public function test_extension_is_lowercased_and_from_last_dot(): void
    {
        $this->assertSame('php', extension('shell.PHP'));
        $this->assertSame('gz', extension('archive.tar.gz'));
        $this->assertSame('png', extension('/path/to/Image.PNG'));
        $this->assertSame('', extension('noextension'));
    }

    public function test_extension_ignores_query_string(): void
    {
        // extension() parse l'URL → l'éventuel ?v=... ne doit pas polluer l'extension.
        $this->assertSame('css', extension('style.css?v=123'));
    }

    public function test_extension_survives_an_address_parse_url_cannot_read(): void
    {
        // Ces formes viennent de vraies requêtes de robots : parse_url() rend FALSE (ou NULL sans chemin),
        // et la production a rendu cinq erreurs 500 le 2026-09-16 à la place d'un 404.
        $this->assertSame('', extension('/fr/a:80'));
        $this->assertSame('', extension('/:80'));
        $this->assertSame('', extension('///x'));
        $this->assertSame('', extension('//x'));
        $this->assertSame('', extension('http://'));
        $this->assertSame('php', extension('/fr/a:80/x.php?y=1'));
    }

    public function test_get_mime_by_extension_known_types(): void
    {
        $this->assertSame('image/png', get_mime_by_extension('png'));
        $this->assertSame('image/jpeg', get_mime_by_extension('jpg'));
        $this->assertSame('application/json', get_mime_by_extension('json'));
        $this->assertSame('application/zip', get_mime_by_extension('zip'));
    }

    public function test_is_dangerous_upload_blocks_executables_case_insensitive(): void
    {
        foreach (['shell.php', 'x.PHTML', 'a.pHp5', 'evil.phar', 'cmd.cgi', 'run.sh', 'page.HTML', 'vector.svg'] as $f) {
            $this->assertTrue(is_dangerous_upload($f), "$f doit être bloqué");
        }
    }

    public function test_is_dangerous_upload_allows_legitimate_files(): void
    {
        foreach (['photo.png', 'image.JPG', 'doc.pdf', 'archive.zip', 'clip.mp4', 'song.mp3', 'data.json', 'notes.txt'] as $f) {
            $this->assertFalse(is_dangerous_upload($f), "$f doit être autorisé");
        }
    }

    /** La pièce jointe d'un membre : en plus, rien qu'un navigateur exécute servi depuis le site (audit du 2026-10-09). */
    public function test_member_attachment_refuses_browser_active_types(): void
    {
        foreach (['x.js', 'x.MJS', 'style.css', 'feed.xml', 'page.xsl', 'anim.swf', 'shell.php', 'vector.svg'] as $f) {
            $this->assertTrue(nf_piece_jointe_refusee($f), "$f doit être refusé");
        }

        foreach (['photo.png', 'doc.pdf', 'archive.zip', 'notes.txt', 'config.cfg'] as $f) {
            $this->assertFalse(nf_piece_jointe_refusee($f), "$f doit être accepté");
        }
    }

    public function test_detect_mime_type_reads_real_content_not_extension(): void
    {
        $png = tempnam(sys_get_temp_dir(), 'nf_png_');
        // 1x1 PNG transparent.
        file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQDJ/pLvAAAAAElFTkSuQmCC'));

        // Un payload PHP, peu importe le "nom" : detect_mime_type lit le contenu réel.
        $fake = tempnam(sys_get_temp_dir(), 'nf_fake_');
        file_put_contents($fake, "<?php echo 'x';");

        try {
            $this->assertSame('image/png', detect_mime_type($png));
            $this->assertNotSame('image/png', detect_mime_type($fake));
            $this->assertSame('', detect_mime_type($png . '_does_not_exist'));
        } finally {
            @unlink($png);
            @unlink($fake);
        }
    }

    public function test_human_size_formats_with_unit(): void
    {
        // Des octets n'ont pas de décimales.
        $this->assertSame('0 B', human_size(0, 2, 'en'));
        $this->assertSame('500 B', human_size(500, 2, 'en'));
        $this->assertSame('685 o', human_size(685, 2, 'fr'));
        $this->assertSame('1.00 MB', human_size(1048576, 2, 'en'));
    }

    public function test_une_taille_se_lit_dans_la_langue_de_la_page(): void
    {
        $this->assertSame('40,04 Ko', human_size(41000, 2, 'fr'));
        $this->assertSame('1,00 Mo', human_size(1048576, 2, 'fr'));
        $this->assertSame('500 o', human_size(500, 0, 'fr'));
        $this->assertSame('40,04 KB', human_size(41000, 2, 'de'));
        $this->assertSame('40.04 KB', human_size(41000, 2, 'en'));
    }

    public function test_les_sauvegardes_anciennes_se_retirent_jamais_les_cinq_dernieres(): void
    {
        $maintenant = strtotime('2026-11-01 12:00:00');
        $jour       = 86400;
        $sauvegardes = [];

        // Huit sauvegardes : une par semaine, la plus récente hier.
        for ($i = 0; $i < 8; $i++)
        {
            $date = $maintenant - $jour - $i * 7 * $jour;
            $sauvegardes[date('YmdHis', $date).'-'.str_repeat(dechex($i), 16).'.zip'] = $date;
        }

        $sauvegardes['.htaccess']     = $maintenant - 400 * $jour;
        $sauvegardes['mon-export.zip'] = $maintenant - 400 * $jour;

        $retirer = nf_sauvegardes_a_retirer($sauvegardes, $maintenant);

        $this->assertCount(3, $retirer, 'les cinq plus récentes restent, les trois autres ont plus de trente jours');
        $this->assertNotContains('mon-export.zip', $retirer, 'un fichier que la sauvegarde n\'a pas fait n\'est jamais retiré');
        $this->assertNotContains('.htaccess', $retirer);

        $recentes = array_slice($sauvegardes, 0, 3, TRUE);
        $this->assertSame([], nf_sauvegardes_a_retirer($recentes, $maintenant), 'moins de cinq : rien ne part');
        $this->assertSame([], nf_sauvegardes_a_retirer(array_map(static fn () => $maintenant - $jour, $sauvegardes), $maintenant), 'toutes récentes : rien ne part');
    }

    public function test_jamais_plus_de_dix_sauvegardes_meme_recentes(): void
    {
        $maintenant = strtotime('2026-11-01 12:00:00');
        $sauvegardes = [];

        // Quarante mises à jour en deux semaines : toutes ont moins de trente jours.
        for ($i = 0; $i < 40; $i++)
        {
            $date = $maintenant - 3600 - $i * 8 * 3600;
            $sauvegardes[date('YmdHis', $date).'.zip'] = $date;
        }

        $sauvegardes['mon-export.zip'] = $maintenant - 3600;

        $retirer = nf_sauvegardes_a_retirer($sauvegardes, $maintenant);

        $this->assertCount(30, $retirer, 'dix restent, les trente plus anciennes partent malgré leur âge');
        $this->assertNotContains(array_key_first($sauvegardes), $retirer, 'la plus récente reste');
        $this->assertNotContains('mon-export.zip', $retirer, 'un fichier que la sauvegarde n\'a pas fait n\'est jamais compté ni retiré');
        $this->assertSame([], nf_sauvegardes_a_retirer(array_slice($sauvegardes, 0, 10, TRUE), $maintenant), 'dix récentes : rien ne part');
    }
}
