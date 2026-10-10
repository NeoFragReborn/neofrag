<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Le relevé des pages introuvables (neofrag/helpers/seo.php, ligne 0.41 du reste-à-faire, 2026-10-10) : ce qui le range
 * parmi les sondes de robots, et la page d'où venait le visiteur, notée sans ce qui pourrait désigner quelqu'un.
 */
final class PagesIntrouvablesTest extends TestCase
{
	public function test_les_sondes_des_robots_sont_reconnues(): void
	{
		foreach (['wp-login.php', 'wp-admin/setup-config.php', 'xmlrpc.php', '.env', 'api/.env', '.git/config', 'phpmyadmin/index.php', 'cgi-bin/luci', 'backup.sql', 'site.zip', 'vendor/phpunit/phpunit/src/Util/PHP/eval-stdin.php', 'config.yml'] as $chemin)
		{
			$this->assertTrue(nf_sonde_introuvable($chemin), $chemin);
		}
	}

	public function test_l_adresse_d_un_ancien_site_reste_a_rediriger(): void
	{
		// Une adresse en .php est celle d'un ancien site autant que d'une sonde : elle doit pouvoir se rediriger.
		foreach (['page.php', 'index.php', 'forum/viewtopic.php', 'blog/42/ancien-titre', 'news/3/annonce', 'wikipedia', 'environnement'] as $chemin)
		{
			$this->assertFalse(nf_sonde_introuvable($chemin), $chemin);
		}
	}

	public function test_la_provenance_perd_ses_parametres(): void
	{
		$this->assertSame('exemple.fr/liens', nf_provenance_introuvable('https://exemple.fr/liens?jeton=secret#ancre', 'neofrag-reborn.xyz'));
		$this->assertSame('/fr/forum', nf_provenance_introuvable('https://neofrag-reborn.xyz/fr/forum?page=2', 'neofrag-reborn.xyz'), 'une page du site, par son chemin');
		$this->assertSame('/fr/forum', nf_provenance_introuvable('https://NeoFrag-Reborn.xyz/fr/forum', 'neofrag-reborn.xyz'));
		$this->assertSame('exemple.fr/', nf_provenance_introuvable('http://exemple.fr', 'neofrag-reborn.xyz'));
	}

	public function test_une_provenance_qui_n_est_pas_une_adresse_web_ne_se_note_pas(): void
	{
		foreach (['', 'android-app://com.google.android.gm', 'javascript:alert(1)', 'file:///etc/passwd', 'pas une adresse'] as $referent)
		{
			$this->assertSame('', nf_provenance_introuvable($referent, 'neofrag-reborn.xyz'), $referent);
		}
	}
}
