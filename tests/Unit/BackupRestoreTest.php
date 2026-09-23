<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Install\Lib\Installer;
use ZipArchive;
use RuntimeException;

require_once __DIR__ . '/../../install/lib/installer.php';

/**
 * Remise en place d'une sauvegarde, sans HTTP ni base : l'archive est fabriquée de toutes pièces et
 * déposée sur une arborescence jetable.
 *
 * Le CMS savait depuis toujours PRENDRE une sauvegarde complète avant d'écrire — et la prenait bien,
 * juste avant chaque mise à jour du cœur. Il ne savait pas s'en reservir : aucun code, nulle part,
 * ne lisait ces archives. Une mise à jour interrompue à mi-parcours laissait donc un site
 * mi-ancien mi-neuf, et le filet de sécurité posé à côté, intact et inutile.
 *
 * Ces épreuves figent ce que la restauration doit faire, et surtout ce qu'elle doit REFUSER de
 * faire — car une restauration naïve casse quatre choses en croyant bien faire :
 *
 *   - écrire DATABASE.sql dans l'arborescence du site, c'est-à-dire déposer toute la base en clair
 *     à un endroit que le serveur web peut servir ;
 *   - rendre à un site en service les identifiants de base d'il y a trois semaines ;
 *   - écraser les journaux de l'incident qu'on est précisément en train de réparer ;
 *   - remettre un cache compilé par la version qu'on vient d'annuler.
 */
final class BackupRestoreTest extends TestCase
{
	private string $root = '';
	private string $sql  = '';

	protected function tearDown(): void
	{
		if ($this->root !== '' && is_dir($this->root))
		{
			$this->rmdir_recursive($this->root);
		}

		if ($this->sql !== '' && file_exists($this->sql))
		{
			@unlink($this->sql);
		}
	}

	/** Arborescence de site jetable. $files : chemin relatif => contenu. */
	private function site(array $files): string
	{
		$this->root = sys_get_temp_dir() . '/nf-site-' . bin2hex(random_bytes(6));

		foreach ($files as $rel => $content)
		{
			@mkdir(dirname($this->root . '/' . $rel), 0777, TRUE);
			file_put_contents($this->root . '/' . $rel, $content);
		}

		return $this->root;
	}

	/** Archive jetable. $entries : nom d'entrée => contenu. */
	private function archive(array $entries): string
	{
		$path = tempnam(sys_get_temp_dir(), 'nfbak');
		$zip  = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);

		foreach ($entries as $name => $content)
		{
			$zip->addFromString($name, $content);
		}

		$zip->close();

		return $path;
	}

	/** Destination hors du site pour la copie de la base. */
	private function sql_dest(): string
	{
		return $this->sql = sys_get_temp_dir() . '/nf-dump-' . bin2hex(random_bytes(6)) . '.sql';
	}

	private function rmdir_recursive(string $dir): void
	{
		foreach (array_diff((array) scandir($dir), ['.', '..']) as $f)
		{
			$p = $dir . '/' . $f;
			is_dir($p) ? $this->rmdir_recursive($p) : @unlink($p);
		}

		@rmdir($dir);
	}

	// ------------------------------------------------------------------ restauration nominale

	public function test_les_fichiers_sont_remis_a_leur_place(): void
	{
		$root = $this->site([
			'index.php'          => 'version cassee',
			'neofrag/kernel.php' => 'noyau casse',
			'css/style.css'      => 'css casse',
		]);

		$zip = $this->archive([
			Installer::BACKUP_SQL_ENTRY => '-- dump',
			'index.php'                 => 'version saine',
			'neofrag/kernel.php'        => 'noyau sain',
			'css/style.css'             => 'css sain',
		]);

		$r = Installer::restore_backup_package($zip, $root, $this->sql_dest());
		@unlink($zip);

		$this->assertSame(3, $r['restored']);
		$this->assertTrue($r['sql']);
		$this->assertSame('version saine', file_get_contents($root . '/index.php'));
		$this->assertSame('noyau sain',    file_get_contents($root . '/neofrag/kernel.php'));
		$this->assertSame('css sain',      file_get_contents($root . '/css/style.css'));
	}

	public function test_un_fichier_disparu_est_recree_avec_son_dossier(): void
	{
		$root = $this->site(['index.php' => 'x']);
		$zip  = $this->archive([
			Installer::BACKUP_SQL_ENTRY  => '-- dump',
			'index.php'                  => 'x',
			'modules/forum/forum.php'    => 'le module effacé',
		]);

		Installer::restore_backup_package($zip, $root, $this->sql_dest());
		@unlink($zip);

		$this->assertSame('le module effacé', file_get_contents($root . '/modules/forum/forum.php'));
	}

	public function test_la_progression_est_rapportee_jusqu_au_bout(): void
	{
		$root = $this->site(['index.php' => 'x']);
		$zip  = $this->archive([
			Installer::BACKUP_SQL_ENTRY => '-- dump',
			'index.php'                 => 'x',
			'css/style.css'             => 'y',
		]);

		$vus = [];

		Installer::restore_backup_package($zip, $root, $this->sql_dest(), function ($n, $total) use (&$vus) {
			$vus[] = [$n, $total];
		});
		@unlink($zip);

		// Deux fichiers + la base : le compteur doit atteindre son total, sinon la barre de
		// progression reste bloquée avant la fin alors que l'opération est terminée.
		$this->assertSame([[1, 3], [2, 3], [3, 3]], $vus);
	}

	// ------------------------------------------------------------------ la base ne s'écrit pas dans le site

	public function test_le_dump_part_vers_la_destination_choisie_et_jamais_dans_le_site(): void
	{
		$root = $this->site(['index.php' => 'x']);
		$zip  = $this->archive([
			Installer::BACKUP_SQL_ENTRY => '-- toute la base en clair',
			'index.php'                 => 'x',
		]);

		Installer::restore_backup_package($zip, $root, $dest = $this->sql_dest());
		@unlink($zip);

		$this->assertSame('-- toute la base en clair', file_get_contents($dest));

		// Le point du dispositif : rien qui porte la base ne doit atterrir sous la racine du site,
		// où la configuration du serveur décide seule s'il est servi ou non.
		$this->assertFileDoesNotExist($root . '/' . Installer::BACKUP_SQL_ENTRY);
	}

	public function test_sans_destination_la_base_n_est_pas_extraite_du_tout(): void
	{
		$root = $this->site(['index.php' => 'x']);
		$zip  = $this->archive([
			Installer::BACKUP_SQL_ENTRY => '-- dump',
			'index.php'                 => 'neuf',
		]);

		$r = Installer::restore_backup_package($zip, $root);
		@unlink($zip);

		$this->assertFalse($r['sql']);
		$this->assertSame('neuf', file_get_contents($root . '/index.php'));
		$this->assertFileDoesNotExist($root . '/' . Installer::BACKUP_SQL_ENTRY);
	}

	public function test_une_archive_sans_base_est_refusee_avant_d_avoir_touche_un_fichier(): void
	{
		$root = $this->site(['index.php' => 'en service']);
		$zip  = $this->archive(['index.php' => 'ancien']);

		try
		{
			Installer::restore_backup_package($zip, $root, $this->sql_dest());
			$this->fail('une archive sans DATABASE.sql aurait dû être refusée');
		}
		catch (RuntimeException $e)
		{
			$this->assertStringContainsString(Installer::BACKUP_SQL_ENTRY, $e->getMessage());
		}
		finally
		{
			@unlink($zip);
		}

		// Restaurer les fichiers sans la base derrière laisserait un site PLUS incohérent qu'avant :
		// le refus doit donc tomber avant la première écriture, pas au milieu.
		$this->assertSame('en service', file_get_contents($root . '/index.php'));
	}

	// ------------------------------------------------------------------ les quatre dossiers épargnés

	public function test_la_configuration_du_site_en_service_n_est_jamais_restauree(): void
	{
		$root = $this->site([
			'index.php'     => 'x',
			'config/db.php' => 'IDENTIFIANTS ACTUELS',
		]);

		$zip = $this->archive([
			Installer::BACKUP_SQL_ENTRY => '-- dump',
			'index.php'                 => 'x',
			'config/db.php'             => 'IDENTIFIANTS PERIMES',
		]);

		Installer::restore_backup_package($zip, $root, $this->sql_dest());
		@unlink($zip);

		// Une mise à jour ne réécrit jamais config/ : il n'y a rien à y annuler. Rendre au site des
		// identifiants périmés le couperait, lui, de sa propre base.
		$this->assertSame('IDENTIFIANTS ACTUELS', file_get_contents($root . '/config/db.php'));
	}

	public function test_les_journaux_de_l_incident_ne_sont_pas_ecrases(): void
	{
		$root = $this->site([
			'index.php'     => 'x',
			'logs/php.log'  => 'la trace de ce qui vient de casser',
		]);

		$zip = $this->archive([
			Installer::BACKUP_SQL_ENTRY => '-- dump',
			'index.php'                 => 'x',
			'logs/php.log'              => 'journal d\'avant, sans intérêt',
		]);

		Installer::restore_backup_package($zip, $root, $this->sql_dest());
		@unlink($zip);

		$this->assertSame('la trace de ce qui vient de casser', file_get_contents($root . '/logs/php.log'));
	}

	public function test_le_cache_n_est_pas_restaure(): void
	{
		$root = $this->site(['index.php' => 'x']);
		$zip  = $this->archive([
			Installer::BACKUP_SQL_ENTRY => '-- dump',
			'index.php'                 => 'x',
			'cache/compile/a.php'       => 'artefact ancien',
		]);

		Installer::restore_backup_package($zip, $root, $this->sql_dest());
		@unlink($zip);

		// Un cache se reconstruit. Le remettre reviendrait à faire cohabiter des artefacts compilés
		// par deux versions différentes — pire que de repartir de zéro.
		$this->assertFileDoesNotExist($root . '/cache/compile/a.php');
	}

	public function test_les_sauvegardes_elles_memes_ne_sont_pas_restaurees(): void
	{
		$root = $this->site(['index.php' => 'x']);
		$zip  = $this->archive([
			Installer::BACKUP_SQL_ENTRY  => '-- dump',
			'index.php'                  => 'x',
			'backups/20200101000000.zip' => 'archive imbriquée',
		]);

		Installer::restore_backup_package($zip, $root, $this->sql_dest());
		@unlink($zip);

		$this->assertFileDoesNotExist($root . '/backups/20200101000000.zip');
	}

	// ------------------------------------------------------------------ balayage du cœur

	public function test_un_fichier_du_coeur_ajoute_par_la_version_annulee_est_retire(): void
	{
		$root = $this->site([
			'index.php'              => 'x',
			'neofrag/kernel.php'     => 'noyau neuf',
			'neofrag/nouveaute.php'  => 'arrivé avec la version qu\'on annule',
		]);

		$zip = $this->archive([
			Installer::BACKUP_SQL_ENTRY => '-- dump',
			'index.php'                 => 'x',
			'neofrag/kernel.php'        => 'noyau ancien',
		]);

		$r = Installer::restore_backup_package($zip, $root, $this->sql_dest());
		@unlink($zip);

		$this->assertSame(1, $r['removed']);
		$this->assertFileDoesNotExist($root . '/neofrag/nouveaute.php');
		$this->assertSame('noyau ancien', file_get_contents($root . '/neofrag/kernel.php'));
	}

	public function test_une_archive_sans_coeur_ne_balaie_pas_le_coeur(): void
	{
		$root = $this->site([
			'index.php'          => 'x',
			'neofrag/kernel.php' => 'le framework entier',
		]);

		// Archive partielle (thèmes seuls) : sans cette garde, elle effacerait tout neofrag/.
		$zip = $this->archive([
			Installer::BACKUP_SQL_ENTRY => '-- dump',
			'themes/nebula/theme.php'   => 'un thème',
		]);

		$r = Installer::restore_backup_package($zip, $root, $this->sql_dest());
		@unlink($zip);

		$this->assertSame(0, $r['removed']);
		$this->assertSame('le framework entier', file_get_contents($root . '/neofrag/kernel.php'));
	}

	// ------------------------------------------------------------------ refus

	public function test_une_archive_illisible_est_refusee(): void
	{
		$root = $this->site(['index.php' => 'x']);
		$faux = tempnam(sys_get_temp_dir(), 'nfbak');
		file_put_contents($faux, 'ceci n\'est pas un zip');

		$this->expectException(RuntimeException::class);

		try
		{
			Installer::restore_backup_package($faux, $root, $this->sql_dest());
		}
		finally
		{
			@unlink($faux);
		}
	}

	public function test_une_entree_qui_remonte_hors_de_la_racine_fait_rejeter_l_archive(): void
	{
		$root = $this->site(['index.php' => 'intact']);
		$zip  = $this->archive([
			Installer::BACKUP_SQL_ENTRY => '-- dump',
			'index.php'                 => 'x',
			'../../etc/passwd'          => 'racine:x:0:0',
		]);

		try
		{
			Installer::restore_backup_package($zip, $root, $this->sql_dest());
			$this->fail('une entrée remontant hors de la racine aurait dû faire rejeter l\'archive');
		}
		catch (RuntimeException $e)
		{
			$this->assertStringContainsString('non sûre', $e->getMessage());
		}
		finally
		{
			@unlink($zip);
		}

		// Rejet GLOBAL, pas au fil de l'eau : une archive piégée ne doit pas avoir eu le temps
		// d'écrire ses entrées honnêtes avant qu'on s'aperçoive de la piégée.
		$this->assertSame('intact', file_get_contents($root . '/index.php'));
	}

	public function test_une_archive_ne_contenant_que_des_dossiers_epargnes_est_refusee(): void
	{
		$root = $this->site(['index.php' => 'en service']);
		$zip  = $this->archive([
			Installer::BACKUP_SQL_ENTRY => '-- dump',
			'cache/a.php'               => 'x',
			'logs/php.log'              => 'y',
			'config/db.php'             => 'z',
		]);

		try
		{
			Installer::restore_backup_package($zip, $root, $this->sql_dest());
			$this->fail('une archive sans fichier restaurable aurait dû être refusée');
		}
		catch (RuntimeException $e)
		{
			$this->assertStringContainsString('aucun fichier restaurable', $e->getMessage());
		}
		finally
		{
			@unlink($zip);
		}

		$this->assertSame('en service', file_get_contents($root . '/index.php'));
	}

	// ------------------------------------------------------------------ symétrie avec la sauvegarde

	public function test_les_deux_moities_s_accordent_sur_le_nom_du_dump(): void
	{
		// La constante est ce qui empêche les deux moitiés du dispositif — celle qui fabrique
		// l'archive, celle qui la remet en place — de dériver en silence. Une archive dont le dump
		// s'appellerait autrement se restaurerait « avec succès » sans toucher à la base.
		$this->assertSame('DATABASE.sql', Installer::BACKUP_SQL_ENTRY);

		$source = (string) file_get_contents(__DIR__ . '/../../modules/monitoring/controllers/admin_ajax.php');

		$this->assertStringContainsString(
			'$zip->addFile($dump, Installer::BACKUP_SQL_ENTRY);',
			$source,
			'la sauvegarde doit nommer le dump par la constante partagée, pas par une chaîne à elle'
		);
	}
}
