<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\NeoFrag\Installer;
use ZipArchive;
use RuntimeException;

require_once __DIR__ . '/../../neofrag/installer.php';

/**
 * Application d'un paquet de mise à jour du cœur, sans réseau ni base : le paquet est fabriqué de
 * toutes pièces et déposé sur une arborescence jetable.
 *
 * L'auto-updater était cassé en quatre endroits, dont deux que ces tests figent :
 *
 *   - le paquet produit par tools/build-release.php rangeait tout sous un dossier `neofrag-reborn/`
 *     alors que l'updater écrit chaque entrée à son propre chemin. Une mise à jour aurait donc créé
 *     un sous-dossier de ce nom et n'aurait rien remplacé, sans la moindre erreur ;
 *   - le balayage des vestiges de `neofrag/` supprimait tous les fichiers absents du paquet — donc
 *     le framework ENTIER si le paquet n'en livrait aucun.
 *
 * S'y ajoutent les garanties d'origine (sanitize_update_url), couvertes plus bas.
 */
final class UpdatePackageTest extends TestCase
{
	private string $root = '';

	protected function tearDown(): void
	{
		if ($this->root !== '' && is_dir($this->root))
		{
			$this->rmdir_recursive($this->root);
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

	/** Paquet jetable. $entries : nom d'entrée => contenu. */
	private function package(array $entries): string
	{
		$path = tempnam(sys_get_temp_dir(), 'nfup');
		$zip  = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);

		foreach ($entries as $name => $content)
		{
			$zip->addFromString($name, $content);
		}

		$zip->close();

		return $path;
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

	// ------------------------------------------------------------------ application nominale

	public function test_un_paquet_plat_remplace_les_fichiers_a_leur_place(): void
	{
		$root = $this->site([
			'index.php'          => 'ancien',
			'neofrag/kernel.php' => 'ancien noyau',
			'css/style.css'      => 'ancien css',
		]);

		$zip = $this->package([
			'index.php'          => 'nouveau',
			'neofrag/kernel.php' => 'nouveau noyau',
			'css/style.css'      => 'nouveau css',
		]);

		$r = Installer::apply_update_package($zip, $root);
		@unlink($zip);

		$this->assertSame(3, $r['written']);
		$this->assertSame('nouveau',       file_get_contents($root . '/index.php'));
		$this->assertSame('nouveau noyau', file_get_contents($root . '/neofrag/kernel.php'));
		$this->assertSame('nouveau css',   file_get_contents($root . '/css/style.css'));

		// Aucun dossier « neofrag-reborn/ » créé : c'est exactement ce que produisait l'ancien
		// format de paquet, et la mise à jour passait alors pour réussie sans rien remplacer.
		$this->assertDirectoryDoesNotExist($root . '/neofrag-reborn');
	}

	public function test_un_fichier_nouveau_est_cree_avec_son_dossier(): void
	{
		$root = $this->site(['index.php' => 'x']);
		$zip  = $this->package(['index.php' => 'x', 'modules/nouveau/nouveau.php' => 'neuf']);

		Installer::apply_update_package($zip, $root);
		@unlink($zip);

		$this->assertSame('neuf', file_get_contents($root . '/modules/nouveau/nouveau.php'));
	}

	// ------------------------------------------------------------------ règles d'application

	public function test_la_configuration_d_un_site_en_service_n_est_jamais_reecrite(): void
	{
		$root = $this->site([
			'index.php'     => 'x',
			'config/db.php' => 'SECRET EN PLACE',
			'install/db.txt' => 'VERROU EN PLACE',
		]);

		$zip = $this->package([
			'index.php'      => 'x',
			'config/db.php'  => 'ECRASE',
			'install/db.txt' => 'ECRASE',
		]);

		Installer::apply_update_package($zip, $root);
		@unlink($zip);

		$this->assertSame('SECRET EN PLACE', file_get_contents($root . '/config/db.php'));
		$this->assertSame('VERROU EN PLACE', file_get_contents($root . '/install/db.txt'));
	}

	/**
	 * Le reste d'`install/` est du code du produit, et suit les versions. Jusqu'au 2026-10-01, la mise
	 * à jour n'y réécrivait rien : un site gardait l'installeur — et avec lui le code même de la mise à
	 * jour et de la place de marché — du jour de son installation.
	 */
	public function test_le_code_d_installation_suit_la_version(): void
	{
		$root = $this->site([
			'index.php'                => 'x',
			'install/lib/installer.php' => 'ancien',
			'install/schema.sql'        => 'ancien schema',
		]);

		$zip = $this->package([
			'index.php'                 => 'x',
			'install/lib/installer.php' => 'nouveau',
			'install/schema.sql'        => 'nouveau schema',
			'install/nouveau.sql'       => 'fichier neuf',
		]);

		Installer::apply_update_package($zip, $root);
		@unlink($zip);

		$this->assertSame('nouveau', file_get_contents($root . '/install/lib/installer.php'));
		$this->assertSame('nouveau schema', file_get_contents($root . '/install/schema.sql'));
		$this->assertSame('fichier neuf', file_get_contents($root . '/install/nouveau.sql'));
	}

	/** Sans dossier `install/` (supprimé après l'installation), la bibliothèque reste utilisable. */
	public function test_la_bibliotheque_ne_depend_pas_du_dossier_install(): void
	{
		$source = (string) file_get_contents(dirname(__DIR__, 2) . '/neofrag/installer.php');

		$this->assertStringNotContainsString("require_once __DIR__.'/langue.php'", $source);
		$this->assertMatchesRegularExpression("#if \\(is_file\\(\\\$nf_langue_installeur = dirname\\(__DIR__\\)\\.'/install/lib/langue\\.php'\\)\\)#", $source);
	}

	public function test_les_fichiers_de_racine_ne_sont_pas_superposes_sauf_index(): void
	{
		$root = $this->site(['index.php' => 'ancien', 'COPYING' => 'licence du site']);
		$zip  = $this->package(['index.php' => 'nouveau', 'COPYING' => 'ECRASE', '.editorconfig' => 'ECRASE']);

		$r = Installer::apply_update_package($zip, $root);
		@unlink($zip);

		$this->assertSame(1, $r['written']);
		$this->assertSame('nouveau',         file_get_contents($root . '/index.php'));
		$this->assertSame('licence du site', file_get_contents($root . '/COPYING'));
		$this->assertFileDoesNotExist($root . '/.editorconfig');
	}

	// ------------------------------------------------------------------ balayage des vestiges

	public function test_un_vestige_de_version_anterieure_est_retire(): void
	{
		$root = $this->site([
			'index.php'                   => 'x',
			'neofrag/kernel.php'          => 'ancien',
			'neofrag/libraries/mort.php'  => 'classe supprimée en 1.1',
		]);

		$zip = $this->package(['index.php' => 'x', 'neofrag/kernel.php' => 'nouveau']);

		$r = Installer::apply_update_package($zip, $root);
		@unlink($zip);

		$this->assertSame(1, $r['removed']);
		$this->assertFileDoesNotExist($root . '/neofrag/libraries/mort.php');
		$this->assertFileExists($root . '/neofrag/kernel.php');
	}

	/**
	 * Le garde-fou qui compte. Sans lui, un paquet ne livrant aucun fichier de neofrag/ faisait
	 * calculer array_diff(tout neofrag/, rien) — et le framework entier partait à la corbeille.
	 */
	public function test_un_paquet_sans_neofrag_ne_supprime_rien(): void
	{
		$root = $this->site([
			'index.php'          => 'x',
			'neofrag/kernel.php' => 'vital',
			'neofrag/loader.php' => 'vital aussi',
		]);

		$zip = $this->package(['index.php' => 'x', 'css/style.css' => 'seulement du css']);

		$r = Installer::apply_update_package($zip, $root);
		@unlink($zip);

		$this->assertSame(0, $r['removed']);
		$this->assertSame('vital',      file_get_contents($root . '/neofrag/kernel.php'));
		$this->assertSame('vital aussi', file_get_contents($root . '/neofrag/loader.php'));
	}

	// ------------------------------------------------------------------ refus

	// ------------------------------------------------------------------ manifeste declare

	/** Compose un manifeste de paquet. */
	private function manifeste(array $proteges = [], array $retires = []): string
	{
		return (string) json_encode([
			'format'    => 1,
			'version'   => '9.9.9',
			'protected' => $proteges,
			'remove'    => $retires,
		]);
	}

	public function test_le_manifeste_lui_meme_ne_s_installe_pas(): void
	{
		$root = $this->site(['neofrag/core.php' => 'ancien']);
		$zip  = $this->package([
			'nf-manifest.json' => $this->manifeste(),
			'neofrag/core.php' => 'neuf',
		]);

		Installer::apply_update_package($zip, $root);

		self::assertFileDoesNotExist($root . '/nf-manifest.json', 'le manifeste decrit le paquet, il ne se pose pas sur le site');
		self::assertSame('neuf', file_get_contents($root . '/neofrag/core.php'));

		@unlink($zip);
	}

	public function test_un_dossier_declare_protege_n_est_pas_ecrit(): void
	{
		$root = $this->site(['upload/avatar.png' => 'photo du membre']);
		$zip  = $this->package([
			'nf-manifest.json'  => $this->manifeste(['upload/']),
			'neofrag/core.php'  => 'neuf',
			'upload/avatar.png' => 'ECRASE',
		]);

		Installer::apply_update_package($zip, $root);

		self::assertSame('photo du membre', file_get_contents($root . '/upload/avatar.png'),
			'un dossier declare protege ne doit pas etre ecrit, meme si le paquet en porte une version');

		@unlink($zip);
	}

	public function test_un_fichier_declare_retire_est_supprime(): void
	{
		$root = $this->site([
			'neofrag/core.php'    => 'ancien',
			'neofrag/obsolete.php' => 'a retirer',
		]);
		$zip = $this->package([
			'nf-manifest.json' => $this->manifeste([], ['neofrag/obsolete.php']),
			'neofrag/core.php' => 'neuf',
		]);

		$r = Installer::apply_update_package($zip, $root);

		self::assertFileDoesNotExist($root . '/neofrag/obsolete.php');
		self::assertSame(1, $r['removed']);

		@unlink($zip);
	}

	/**
	 * Le point clé : une liste DECLAREE remplace le balayage deduit.
	 *
	 * Sans manifeste, `obsolete.php` disparaitrait parce que le paquet ne le livre pas. Avec, il
	 * reste : le paquet n'a pas dit de le retirer, et c'est lui qui fait foi.
	 */
	public function test_avec_un_manifeste_le_balayage_deduit_ne_s_execute_plus(): void
	{
		$root = $this->site([
			'neofrag/core.php'     => 'ancien',
			'neofrag/obsolete.php' => 'non declare',
		]);
		$zip = $this->package([
			'nf-manifest.json' => $this->manifeste(),
			'neofrag/core.php' => 'neuf',
		]);

		$r = Installer::apply_update_package($zip, $root);

		self::assertFileExists($root . '/neofrag/obsolete.php',
			'le paquet ne l\'a pas declare retire : le balayage deduit ne doit plus decider a sa place');
		self::assertSame(0, $r['removed']);

		@unlink($zip);
	}

	/** Compatibilite : un paquet d'avant cette version n'a pas de manifeste et s'applique comme avant. */
	public function test_sans_manifeste_le_balayage_deduit_s_applique_toujours(): void
	{
		$root = $this->site([
			'neofrag/core.php'     => 'ancien',
			'neofrag/obsolete.php' => 'vestige',
		]);
		$zip = $this->package(['neofrag/core.php' => 'neuf']);

		$r = Installer::apply_update_package($zip, $root);

		self::assertFileDoesNotExist($root . '/neofrag/obsolete.php');
		self::assertSame(1, $r['removed']);

		@unlink($zip);
	}

	public function test_un_manifeste_illisible_est_traite_comme_absent(): void
	{
		$root = $this->site([
			'neofrag/core.php'     => 'ancien',
			'neofrag/obsolete.php' => 'vestige',
		]);
		$zip = $this->package([
			'nf-manifest.json' => 'ceci n\'est pas du JSON {',
			'neofrag/core.php' => 'neuf',
		]);

		$r = Installer::apply_update_package($zip, $root);

		self::assertSame('neuf', file_get_contents($root . '/neofrag/core.php'),
			'un manifeste illisible ne doit pas empecher la mise a jour');
		self::assertSame(1, $r['removed'], 'faute de manifeste exploitable, le balayage reprend la main');

		@unlink($zip);
	}

	/**
	 * Un manifeste qui remonte hors du site ne doit RIEN pouvoir effacer : le paquet vient du
	 * reseau, et son contenu ne se croit pas sur parole.
	 */
	public function test_un_retrait_hors_du_site_est_ignore(): void
	{
		$root   = $this->site(['neofrag/core.php' => 'ancien']);
		$voisin = $root . '-voisin.txt';
		file_put_contents($voisin, 'fichier d\'un autre dossier');

		$zip = $this->package([
			'nf-manifest.json' => $this->manifeste([], [
				'../' . basename($voisin),
				'/etc/passwd',
				'neofrag/../../' . basename($voisin),
			]),
			'neofrag/core.php' => 'neuf',
		]);

		$r = Installer::apply_update_package($zip, $root);

		self::assertFileExists($voisin, 'aucun chemin du manifeste ne doit sortir de la racine du site');
		self::assertSame(0, $r['removed']);

		@unlink($voisin);
		@unlink($zip);
	}

	public function test_un_retrait_ne_supprime_jamais_un_dossier(): void
	{
		$root = $this->site(['neofrag/core.php' => 'ancien', 'neofrag/sous/x.php' => 'x']);
		$zip  = $this->package([
			'nf-manifest.json' => $this->manifeste([], ['neofrag/sous']),
			'neofrag/core.php' => 'neuf',
		]);

		$r = Installer::apply_update_package($zip, $root);

		self::assertDirectoryExists($root . '/neofrag/sous', 'un manifeste errone ne doit pas emporter une arborescence');
		self::assertSame(0, $r['removed']);

		@unlink($zip);
	}

	public function test_une_entree_qui_remonte_d_un_cran_fait_rejeter_tout_le_paquet(): void
	{
		$root = $this->site(['index.php' => 'intact']);
		$zip  = $this->package(['index.php' => 'ECRASE', '../evade.php' => 'charge utile']);

		try
		{
			Installer::apply_update_package($zip, $root);
			$this->fail('Le paquet aurait dû être rejeté.');
		}
		catch (RuntimeException $e)
		{
			$this->assertStringContainsString('non sûre', $e->getMessage());
		}
		finally
		{
			@unlink($zip);
		}

		// Rejet GLOBAL : aucune entrée du paquet n'a été appliquée, même les inoffensives.
		$this->assertSame('intact', file_get_contents($root . '/index.php'));
		$this->assertFileDoesNotExist(dirname($root) . '/evade.php');
	}

	public function test_un_paquet_sans_fichier_applicable_est_refuse(): void
	{
		$root = $this->site(['index.php' => 'intact']);
		$zip  = $this->package(['LISEZMOI' => 'que des fichiers de racine']);

		$this->expectException(RuntimeException::class);

		try
		{
			Installer::apply_update_package($zip, $root);
		}
		finally
		{
			@unlink($zip);
		}
	}

	public function test_une_archive_illisible_est_refusee(): void
	{
		$root = $this->site(['index.php' => 'intact']);
		$pas_un_zip = tempnam(sys_get_temp_dir(), 'nfup');
		file_put_contents($pas_un_zip, 'ceci n\'est pas une archive');

		$this->expectException(RuntimeException::class);

		try
		{
			Installer::apply_update_package($pas_un_zip, $root);
		}
		finally
		{
			@unlink($pas_un_zip);
		}
	}

	// ------------------------------------------------------------------ origine (anti-SSRF)

	/**
	 * Le réglage nf_monitoring_check_url était VIDE par défaut et déclaré nulle part : version.json
	 * n'était jamais téléchargé, donc aucune mise à jour n'était jamais signalée. Il a désormais un
	 * défaut valide, validé contre la MÊME allow-list que le marketplace.
	 */
	public function test_l_origine_par_defaut_est_celle_du_projet(): void
	{
		$this->assertSame(Installer::UPDATE_URL_DEFAULT, Installer::sanitize_update_url(NULL));
		$this->assertSame(Installer::UPDATE_URL_DEFAULT, Installer::sanitize_update_url(''));
		$this->assertStringStartsWith('https://', Installer::UPDATE_URL_DEFAULT);
	}

	public function test_une_origine_autorisee_est_conservee(): void
	{
		foreach (['https://neofrag-reborn.xyz/update',
		          'https://www.neofrag-reborn.xyz/update'] as $url)
		{
			$this->assertSame($url, Installer::sanitize_update_url($url));
		}

		$this->assertSame('https://neofrag-reborn.xyz/update',
			Installer::sanitize_update_url('https://neofrag-reborn.xyz/update/'));
	}

	// ------------------------------------------------------------------ forme des manifestes

	/**
	 * Le defaut qui a motive ces deux predicats : le site de distribution repondait 200 avec
	 * {"redirect":"\/fr\/marketplace\/catalog.json"} sur un chemin que son routeur interceptait.
	 * Un simple is_array() aurait pris ce corps pour un manifeste, l'aurait mis en cache, et le
	 * theme d'administration aurait ensuite lu ->neofrag->version sur un objet inexistant.
	 */
	public function test_un_corps_de_redirection_n_est_pas_un_manifeste(): void
	{
		$redirection = ['redirect' => '/fr/update/version.json'];

		$this->assertFalse(Installer::is_version_manifest($redirection));
		$this->assertFalse(Installer::is_checksum_manifest($redirection));
	}

	public function test_la_forme_de_version_json(): void
	{
		$this->assertTrue(Installer::is_version_manifest(['neofrag' => ['version' => '1.2.0']]));

		$this->assertFalse(Installer::is_version_manifest([]),                                'vide');
		$this->assertFalse(Installer::is_version_manifest(['version' => '1.2.0']),            'sans le bloc neofrag');
		$this->assertFalse(Installer::is_version_manifest(['neofrag' => []]),                 'bloc neofrag sans version');
		$this->assertFalse(Installer::is_version_manifest(['neofrag' => ['version' => '']]),  'version vide');
		$this->assertFalse(Installer::is_version_manifest(['neofrag' => ['version' => 120]]), 'version qui n\'est pas une chaine');
	}

	public function test_la_forme_de_checksum_json(): void
	{
		$this->assertTrue(Installer::is_checksum_manifest(['index.php' => str_repeat('a', 32)]));

		$this->assertFalse(Installer::is_checksum_manifest([]),                              'vide');
		$this->assertFalse(Installer::is_checksum_manifest(['index.php' => 'pas-un-md5']),   'empreinte mal formee');
		$this->assertFalse(Installer::is_checksum_manifest(['index.php' => str_repeat('a', 31)]), 'empreinte trop courte');
		$this->assertFalse(Installer::is_checksum_manifest(['index.php' => ['imbrique']]),   'valeur qui n\'est pas une chaine');
	}

	/**
	 * Le garde-fou NEOFRAG_ALLOW_AUTOUPDATE existait parce que l'updater visait `neofrag.download`,
	 * la release UPSTREAM, qui aurait écrasé le code de ce fork. C'est l'allow-list qui tient ce rôle
	 * maintenant, et elle le tient même si la valeur vient d'une base compromise.
	 */
	public function test_toute_autre_origine_retombe_sur_le_defaut(): void
	{
		foreach ([
			'https://neofrag.download'                  => 'la release upstream du projet d\'origine',
			'https://neofrag-reborn.xyz.evil.tld/u'     => 'un sous-domaine qui imite le nôtre',
			'http://neofrag-reborn.xyz/update'          => 'le même hôte, mais en clair',
			'https://neofrag-reborn.xyz:8443/update'    => 'le même hôte, sur un port détourné',
			'https://user:pw@neofrag-reborn.xyz/update' => 'des identifiants glissés dans l\'URL',
			'https://127.0.0.1/update'                  => 'la boucle locale',
			'https://169.254.169.254/latest'            => 'les métadonnées d\'instance cloud',
			'file:///etc/passwd'                        => 'un schéma qui n\'est pas HTTPS',
		] as $url => $quoi)
		{
			$this->assertSame(Installer::UPDATE_URL_DEFAULT, Installer::sanitize_update_url($url),
				"Origine refusée attendue ($quoi) : $url");
		}
	}
}
