<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../neofrag/helpers/theme.php';

/**
 * Le cookie du choix de thème est propre à chaque site.
 *
 * Le 2026-09-23, un visiteur qui choisissait Forge sur la démonstration voyait aussi le site vitrine
 * en Forge : le cookie `nf_theme` était posé pour tout le domaine, et la démonstration est servie
 * depuis un sous-dossier du site vitrine. Ces épreuves figent la règle : deux sites du même domaine
 * n'ont ni le même nom de cookie, ni le même chemin.
 */
final class HelpersThemeCookieTest extends TestCase
{
	public function testUnSiteALaRacineGardeLeNomHistorique(): void
	{
		$this->assertSame(['nom' => 'nf_theme', 'epoque' => 'nf_theme_epoch', 'chemin' => '/'], nf_theme_cookie('/'));
		$this->assertSame(nf_theme_cookie('/'), nf_theme_cookie(''));
	}

	public function testUnSiteEnSousDossierAUnCookieASonNomEtASonChemin(): void
	{
		$this->assertSame(['nom' => 'nf_theme_demo', 'epoque' => 'nf_theme_demo_epoch', 'chemin' => '/demo/'], nf_theme_cookie('/demo/'));
		$this->assertSame(nf_theme_cookie('/demo/'), nf_theme_cookie('/demo'));
	}

	public function testLaDemonstrationEtLeSiteVitrineNePartagentRien(): void
	{
		$vitrine = nf_theme_cookie('/');
		$demo    = nf_theme_cookie('/demo/');

		$this->assertNotSame($vitrine['nom'], $demo['nom']);
		$this->assertNotSame($vitrine['epoque'], $demo['epoque']);
		$this->assertNotSame($vitrine['chemin'], $demo['chemin']);
	}

	public function testUnCheminComposeDonneUnNomDeCookieValide(): void
	{
		$cookie = nf_theme_cookie('/clan/Site-2/');

		$this->assertSame('nf_theme_clan_site_2', $cookie['nom']);
		$this->assertSame('/clan/Site-2/', $cookie['chemin']);
		$this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $cookie['nom']);
	}
}
