<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Les fuseaux horaires (neofrag/helpers/time.php) : le produit enregistre ses dates dans le fuseau du
 * serveur et les affiche — ou les reçoit d'un formulaire — dans celui de celui qui regarde.
 */
final class FuseauHoraireTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		// Le fuseau d'enregistrement est celui de PHP au premier appel : l'heure universelle, comme
		// sur le serveur de production.
		date_default_timezone_set('UTC');
		require_once __DIR__.'/../../neofrag/helpers/time.php';
	}

	protected function setUp(): void
	{
		if (nf_fuseau_stockage()->getName() !== 'UTC')
		{
			self::markTestSkipped('le fuseau d’enregistrement a été figé avant ce test');
		}

		nf_fuseau_membre(NULL, TRUE);
		unset($_COOKIE['nf_fuseau']);
	}

	protected function tearDown(): void
	{
		nf_fuseau_membre(NULL, TRUE);
		unset($_COOKIE['nf_fuseau']);
	}

	public function test_une_saisie_a_paris_s_enregistre_en_heure_universelle_heure_d_ete_comprise(): void
	{
		nf_fuseau_membre('Europe/Paris', TRUE);

		self::assertSame('2026-10-05 16:00:00', nf_heure_saisie('2026-10-05 18:00:00'), 'heure d’été : UTC+2');
		self::assertSame('2026-01-15 17:00:00', nf_heure_saisie('2026-01-15 18:00:00'), 'heure d’hiver : UTC+1');
	}

	public function test_l_affichage_rend_l_heure_saisie(): void
	{
		nf_fuseau_membre('America/New_York', TRUE);

		self::assertSame('2026-10-05 22:00:00', nf_heure_saisie('2026-10-05 18:00:00'));
		self::assertSame('2026-10-05 18:00:00', nf_heure_affichee(nf_heure_saisie('2026-10-05 18:00:00')));
	}

	public function test_une_date_seule_ou_une_valeur_vide_ne_change_pas(): void
	{
		nf_fuseau_membre('America/New_York', TRUE);

		self::assertSame('2026-10-05', nf_heure_saisie('2026-10-05'));
		self::assertSame('18:00:00', nf_heure_saisie('18:00:00'));
		self::assertSame('', nf_heure_saisie(''));
		self::assertNull(nf_heure_saisie(NULL));
	}

	public function test_le_fuseau_du_membre_passe_avant_celui_du_navigateur(): void
	{
		$_COOKIE['nf_fuseau'] = 'Asia/Tokyo';
		self::assertSame('Asia/Tokyo', nf_fuseau()->getName(), 'sans choix du membre : le navigateur');

		nf_fuseau_membre('Europe/Paris', TRUE);
		self::assertSame('Europe/Paris', nf_fuseau()->getName(), 'le choix du membre l’emporte');
	}

	public function test_un_fuseau_inconnu_est_ignore(): void
	{
		$_COOKIE['nf_fuseau'] = 'Mars/Olympus_Mons';
		nf_fuseau_membre('<script>', TRUE);

		self::assertNull(nf_fuseau_ouvrir('Mars/Olympus_Mons'));
		self::assertNull(nf_fuseau_membre());
		self::assertSame('UTC', nf_fuseau()->getName(), 'repli : le fuseau du site, ici celui de l’enregistrement');
	}

	public function test_now_reste_dans_le_fuseau_d_enregistrement(): void
	{
		nf_fuseau_membre('Asia/Tokyo', TRUE);

		self::assertSame('2026-10-05 16:00:00', now(strtotime('2026-10-05 16:00:00 UTC')));
	}
}
