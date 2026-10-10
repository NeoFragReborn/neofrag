<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../neofrag/helpers/consentement.php';
require_once __DIR__.'/../../neofrag/helpers/relais.php';

/**
 * Le consentement aux services tiers et le relais des images d'autres sites (2026-10-08) :
 * neofrag/helpers/consentement.php et neofrag/helpers/relais.php.
 *
 * Avant : un bandeau qui ne réglait que Google Analytics, pendant que les vidéos, le widget Discord, la carte
 * et toutes les images d'autres sites faisaient partir l'adresse IP du visiteur sans rien lui demander.
 */
final class ConsentementTest extends TestCase
{
    // ── Le cookie des choix ───────────────────────────────────────────────────────────────────────

    public function test_le_choix_se_lit_dans_le_cookie(): void
    {
        $this->assertSame(['empreinte' => '3fa2c1', 'jeton' => '9f3a0c1d2e4b5a6c', 'services' => ['youtube', 'discord']],
            nf_consentement_lire('3fa2c1.9f3a0c1d2e4b5a6c.youtube-discord'));
        $this->assertSame([], nf_consentement_lire('3fa2c1.9f3a0c1d2e4b5a6c.')['services'], 'tout refusé : la liste est vide');
        $this->assertSame(['youtube'], nf_consentement_lire('3fa2c1.9f3a0c1d2e4b5a6c.youtube-inconnu-youtube')['services'], 'un service inconnu ne compte pas, un doublon non plus');
    }

    public function test_un_cookie_illisible_ne_vaut_rien(): void
    {
        $this->assertNull(nf_consentement_lire(NULL));
        $this->assertNull(nf_consentement_lire(''));
        $this->assertNull(nf_consentement_lire('full'), 'l\'ancien « tout accepter » ne disait pas à quoi : la question se repose');
        $this->assertNull(nf_consentement_lire('3fa2c1.court.youtube'));
        $this->assertNull(nf_consentement_lire('3fa2c1.9f3a0c1d2e4b5a6c.YouTube'));
    }

    public function test_l_ancien_refus_vaut_encore_refus_de_tout(): void
    {
        $this->assertSame(['empreinte' => '*', 'jeton' => '', 'services' => []], nf_consentement_lire('essentials'));
        $this->assertFalse(nf_consentement_accepte('analytics', 'essentials'));
    }

    public function test_un_service_n_est_accepte_que_s_il_est_dans_la_liste(): void
    {
        $this->assertTrue(nf_consentement_accepte('youtube', '3fa2c1.9f3a0c1d2e4b5a6c.youtube-discord'));
        $this->assertFalse(nf_consentement_accepte('analytics', '3fa2c1.9f3a0c1d2e4b5a6c.youtube-discord'));
        $this->assertFalse(nf_consentement_accepte('youtube', NULL), 'sans réponse, rien n\'est accepté');
    }

    public function test_l_empreinte_ne_depend_que_de_la_liste(): void
    {
        $this->assertSame(nf_consentement_empreinte(['analytics', 'recaptcha']), nf_consentement_empreinte(['recaptcha', 'analytics']));
        $this->assertNotSame(nf_consentement_empreinte(['analytics']), nf_consentement_empreinte(['analytics', 'recaptcha']), 'un service ajouté repose la question');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{6}$/', nf_consentement_empreinte([]));
    }

    public function test_le_cookie_est_propre_a_chaque_site_du_domaine(): void
    {
        $this->assertSame(['nom' => 'nf_consent', 'chemin' => '/'], nf_consentement_cookie('/'));
        $this->assertSame(['nom' => 'nf_consent_club', 'chemin' => '/club/'], nf_consentement_cookie('/club/'));
    }

    // ── Les cadres ────────────────────────────────────────────────────────────────────────────────

    public function test_chaque_cadre_connu_releve_de_son_service(): void
    {
        $this->assertSame('youtube', nf_consentement_service_de('https://www.youtube-nocookie.com/embed/abc'));
        $this->assertSame('youtube', nf_consentement_service_de('//www.youtube.com/embed/abc'));
        $this->assertSame('twitch', nf_consentement_service_de('https://player.twitch.tv/?channel=x&parent=exemple.org'));
        $this->assertSame('discord', nf_consentement_service_de('https://discord.com/widget?id=1'));
        $this->assertNull(nf_consentement_service_de('https://www.google.com/maps/embed?pb=x'), 'plus de cartes Google : l\'éditeur ne les garde plus');
        $this->assertNull(nf_consentement_service_de('/fr/admin/live-editor'), 'un cadre du site lui-même');
        $this->assertNull(nf_consentement_service_de('https://inconnu.example/video'));
    }

    public function test_un_cadre_non_accepte_devient_son_avis(): void
    {
        $cadre = '<iframe src="https://www.youtube.com/embed/abc" width="560"></iframe>';
        $avis  = fn(string $service, string $html): string => '<div class="nf-tiers" data-nf-tiers="'.$service.'"><template>'.$html.'</template></div>';
        $html  = '<p>avant</p>'.$cadre.'<p>après</p>';

        $this->assertSame('<p>avant</p><div class="nf-tiers" data-nf-tiers="youtube"><template>'.$cadre.'</template></div><p>après</p>',
            nf_consentement_filtrer($html, fn(string $s): bool => FALSE, $avis));
        $this->assertSame($html, nf_consentement_filtrer($html, fn(string $s): bool => $s === 'youtube', $avis), 'accepté : le cadre reste');
    }

    public function test_le_filtre_ne_touche_ni_aux_scripts_ni_aux_champs_ni_aux_cadres_inconnus(): void
    {
        $avis = fn(string $service, string $html): string => 'AVIS';
        $html = '<script>var s = \'<iframe src="https://www.youtube.com/embed/x"></iframe>\';</script>'
            .'<textarea><iframe src="https://www.youtube.com/embed/y"></iframe></textarea>'
            .'<iframe src="https://inconnu.example/x"></iframe>'
            .'<iframe src="/fr/apercu"></iframe>';

        $this->assertSame($html, nf_consentement_filtrer($html, fn(string $s): bool => FALSE, $avis));
    }

    public function test_les_lecteurs_connus_sont_dans_la_politique_de_securite(): void
    {
        $cadres = nf_consentement_cadres();

        foreach (['https://www.youtube-nocookie.com', 'https://player.twitch.tv', 'https://discord.com', 'https://w.soundcloud.com'] as $origine)
        {
            $this->assertStringContainsString($origine, $cadres);
        }
    }

    public function test_l_avis_dit_qui_recoit_quoi_et_garde_le_cadre(): void
    {
        $lang = fn(string $texte, ...$valeurs): string => vsprintf($texte, $valeurs);
        $avis = nf_consentement_avis('youtube', '<iframe src="https://www.youtube.com/embed/abc"></iframe>', $lang);

        $this->assertStringContainsString('data-nf-tiers="youtube"', $avis);
        $this->assertStringContainsString('<template><iframe src="https://www.youtube.com/embed/abc"></iframe></template>', $avis);
        $this->assertStringContainsString('Google Ireland Limited', $avis);
        $this->assertStringContainsString('data-nf-tiers-afficher', $avis);
        $this->assertStringContainsString('data-nf-tiers-toujours', $avis);
        $this->assertStringContainsString('{nom}', nf_consentement_avis('', '', $lang), 'le modèle des scripts garde la place des noms');
    }

    // ── Le relais des images ──────────────────────────────────────────────────────────────────────

    public function test_seules_les_images_d_autres_sites_passent_par_le_relais(): void
    {
        $this->assertSame('https://cdn.discordapp.com/avatars/1/a.png', nf_relais_distante('https://cdn.discordapp.com/avatars/1/a.png', 'exemple.org'));
        $this->assertSame('https://media.giphy.com/x.gif', nf_relais_distante('//media.giphy.com/x.gif', 'exemple.org'), 'sans protocole : celui du web sécurisé');
        $this->assertNull(nf_relais_distante('https://exemple.org/upload/a.png', 'exemple.org'), 'une image du site lui-même');
        $this->assertNull(nf_relais_distante('/upload/a.png', 'exemple.org'));
        $this->assertNull(nf_relais_distante('data:image/png;base64,AAAA', 'exemple.org'));
        $this->assertNull(nf_relais_distante('javascript:alert(1)', 'exemple.org'));
    }

    public function test_le_relais_juge_l_hote_que_voit_le_navigateur(): void
    {
        $avant = $_SERVER['HTTP_HOST'] ?? NULL;

        try
        {
            // L'atelier déclare l'adresse de la production et se joint par 127.0.0.1:8250 : les images de la
            // production y sont d'une autre origine, que `img-src 'self'` refuse sans le relais.
            $_SERVER['HTTP_HOST'] = '127.0.0.1:8250';
            $this->assertSame('127.0.0.1', nf_relais_hote(), 'le port ne compte pas');
            $this->assertNotNull(nf_relais_distante('https://neofrag-reborn.xyz/marketplace/modules/ads.jpg', nf_relais_hote()));

            $_SERVER['HTTP_HOST'] = 'WWW.Exemple.org';
            $this->assertSame('www.exemple.org', nf_relais_hote());
            $this->assertNotNull(nf_relais_distante('https://exemple.org/upload/a.png', nf_relais_hote()), 'joint par www., le nom nu est une autre origine');
            $this->assertNull(nf_relais_distante('https://www.exemple.org/upload/a.png', nf_relais_hote()));
        }
        finally
        {
            if ($avant === NULL)
            {
                unset($_SERVER['HTTP_HOST']);
            }
            else
            {
                $_SERVER['HTTP_HOST'] = $avant;
            }
        }
    }

    public function test_le_relais_signe_et_rafraichit(): void
    {
        $a = 'https://cdn.discordapp.com/avatars/1/a.png';

        $this->assertSame(nf_relais_signature($a, 'cle'), nf_relais_signature($a, 'cle'));
        $this->assertNotSame(nf_relais_signature($a, 'cle'), nf_relais_signature($a, 'autre'), 'la signature dépend de la clé du site');
        $this->assertSame(604800, nf_relais_duree($a));
        $this->assertSame(600, nf_relais_duree('https://static-cdn.jtvnw.net/previews-ttv/live_user_x-440x248.jpg'), 'l\'aperçu d\'un direct change sans changer d\'adresse');

        $dossier = sys_get_temp_dir().'/nf-relais-'.bin2hex(random_bytes(4));
        $this->assertSame('/fr/ajax/user/relais/'.nf_relais_signature($a, 'cle').'/'.bin2hex($a), nf_relais_adresse($a, $dossier, 'cle', '/', '/fr/ajax/user/relais/'), 'pas encore relayée : l\'adresse du relais');

        mkdir($dossier.'/'.NF_RELAIS_DOSSIER, 0777, TRUE);
        file_put_contents($fichier = $dossier.'/'.NF_RELAIS_DOSSIER.'/'.nf_relais_empreinte($a).'.png', 'x');
        $this->assertSame('/'.NF_RELAIS_DOSSIER.'/'.nf_relais_empreinte($a).'.png?v='.filemtime($fichier), nf_relais_adresse($a, $dossier, 'cle', '/', '/fr/ajax/user/relais/'), 'déjà relayée : le fichier');

        touch($fichier, time() - 700000);
        clearstatcache();
        $this->assertStringStartsWith('/fr/ajax/user/relais/', nf_relais_adresse($a, $dossier, 'cle', '/', '/fr/ajax/user/relais/'), 'trop vieille : reprise par le relais');

        unlink($fichier);
        rmdir($dossier.'/'.NF_RELAIS_DOSSIER);
        rmdir($dossier.'/upload');
        rmdir($dossier);
    }

    public function test_le_filtre_des_images(): void
    {
        $relayer = fn(string $adresse): ?string => nf_relais_distante($adresse, 'exemple.org') === NULL ? NULL : '/relais?u='.$adresse;

        $this->assertSame('<img alt="a" src="/relais?u=https://cdn.discordapp.com/a.png?x=1&amp;s=1">',
            nf_relais_filtrer('<img alt="a" src="https://cdn.discordapp.com/a.png?x=1&amp;s=1">', $relayer), 'l\'adresse est relue telle que le navigateur la lirait, puis réécrite échappée');
        $this->assertSame('<img src="/upload/a.png">', nf_relais_filtrer('<img src="/upload/a.png">', $relayer), 'une image du site ne change pas');
        $this->assertSame('<div style="background: url(&quot;/relais?u=https://cdn.example/b.jpg&quot;) no-repeat">',
            nf_relais_filtrer('<div style="background: url(&quot;https://cdn.example/b.jpg&quot;) no-repeat">', $relayer), 'un fond posé en style');
        $this->assertSame("<div style=\"background:url('/relais?u=https://cdn.example/c.jpg')\">",
            nf_relais_filtrer("<div style=\"background:url('https://cdn.example/c.jpg')\">", $relayer));

        $script = '<script>var i = \'<img src="https://cdn.example/d.png">\';</script>';
        $this->assertSame($script, nf_relais_filtrer($script, $relayer), 'le contenu d\'un script ne change pas');
    }
}
