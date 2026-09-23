<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * La bascule du journal de débogage.
 *
 * `NEOFRAG_LOGS` écrit toutes les requêtes SQL de chaque page servie, et rien ne bornait le fichier :
 * il avait atteint 1,7 Go sur l'atelier, sur un disque partagé avec la production. Ce qui mérite une
 * épreuve n'est pas l'écriture — elle marchait — mais la borne : qu'elle ne bascule PAS trop tôt,
 * qu'elle bascule au bon moment, et qu'elle n'empile pas les générations.
 */
final class LogRotateTest extends TestCase
{
	private string $dossier = '';

	protected function setUp(): void
	{
		$this->dossier = sys_get_temp_dir().'/nf-log-'.bin2hex(random_bytes(6));
		mkdir($this->dossier, 0775, TRUE);
	}

	protected function tearDown(): void
	{
		foreach (glob($this->dossier.'/*') ?: [] as $fichier)
		{
			@unlink($fichier);
		}

		@rmdir($this->dossier);
	}

	private function journal(int $octets): string
	{
		$fichier = $this->dossier.'/journal.log';
		file_put_contents($fichier, str_repeat('x', $octets));

		return $fichier;
	}

	public function test_sous_la_borne_rien_ne_bouge(): void
	{
		$fichier = $this->journal(1000);

		self::assertFalse(nf_log_rotate($fichier, 2000));
		self::assertFileExists($fichier);
		self::assertFileDoesNotExist($fichier.'.1');
		self::assertSame(1000, filesize($fichier));
	}

	public function test_a_la_borne_exacte_ca_bascule(): void
	{
		$fichier = $this->journal(2000);

		self::assertTrue(nf_log_rotate($fichier, 2000), 'la borne est atteinte, donc dépassée');
		self::assertFileDoesNotExist($fichier);
		self::assertSame(2000, filesize($fichier.'.1'));
	}

	public function test_au_dessus_de_la_borne_ca_bascule(): void
	{
		$fichier = $this->journal(3000);

		self::assertTrue(nf_log_rotate($fichier, 2000));
		self::assertFileDoesNotExist($fichier, 'le journal repart de zéro');
		self::assertSame(3000, filesize($fichier.'.1'));
	}

	/** Une seconde bascule ÉCRASE la génération précédente : un journal de débogage n'est pas une archive. */
	public function test_les_generations_ne_s_empilent_pas(): void
	{
		$fichier = $this->dossier.'/journal.log';

		file_put_contents($fichier, str_repeat('a', 3000));
		nf_log_rotate($fichier, 2000);

		file_put_contents($fichier, str_repeat('b', 3000));
		nf_log_rotate($fichier, 2000);

		self::assertFileDoesNotExist($fichier.'.2', 'aucune seconde génération');
		self::assertStringStartsWith('b', (string) file_get_contents($fichier.'.1'), 'la plus récente a écrasé la précédente');
		self::assertCount(1, glob($this->dossier.'/*') ?: [], 'un seul fichier reste');
	}

	public function test_un_journal_absent_ne_fait_rien_planter(): void
	{
		self::assertFalse(nf_log_rotate($this->dossier.'/pas-la.log', 2000));
	}

	public function test_une_borne_absurde_ne_bascule_pas(): void
	{
		$fichier = $this->journal(3000);

		self::assertFalse(nf_log_rotate($fichier, 0), 'une borne nulle ferait basculer à chaque page');
		self::assertFalse(nf_log_rotate($fichier, -1));
		self::assertFileExists($fichier);
	}
}
