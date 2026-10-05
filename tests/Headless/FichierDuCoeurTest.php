<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Le modèle des fichiers lit la table du cœur, d'où qu'on le charge (2026-10-05).
 *
 * Le chargeur des modèles préfixe la table du module appelant : `$this->model2('file', …)` écrit dans
 * les réglages, le forum, la messagerie ou l'espace membre lisait `nf_settings_file`, `nf_forum_file`,
 * `nf_talks_file`, `nf_user_file` — des tables qui n'existent pas. Le favicon manquait au manifeste des
 * téléphones, et une pièce jointe supprimée ou les images d'un compte effacé restaient sur le disque.
 * Le modèle déclare désormais sa table (`$__table = 'file'`), comme Comment et User.
 */
final class FichierDuCoeurTest extends HeadlessTestCase
{
	/** Les modules qui chargent le modèle avec `$this->model2('file', …)`. */
	private const MODULES = ['settings', 'forum', 'talks', 'user'];

	/** @var list<string> */
	private array $fichiers = [];

	protected function tearDown(): void
	{
		foreach ($this->fichiers as $fichier)
		{
			if (is_file($fichier))
			{
				unlink($fichier);
			}
		}

		parent::tearDown();
	}

	/** Un vrai fichier sur le disque et sa ligne dans `nf_file` : [id, chemin]. */
	private function fichier(): array
	{
		$this->fichiers[] = $chemin = (string) tempnam(sys_get_temp_dir(), 'nf-fichier-');

		$id = (int) $this->db()->insert('nf_file', [
			'name' => 'epreuve.png',
			'path' => $chemin,
		]);

		return [$id, $chemin];
	}

	public function test_un_module_lit_la_table_des_fichiers_du_coeur(): void
	{
		[$id, $chemin] = $this->fichier();

		foreach (self::MODULES as $nom)
		{
			$fichier = \NeoFrag()->module($nom)->model2('file', $id);

			$this->assertSame($chemin, $fichier->path, "Chargé depuis le module $nom, le fichier doit venir de nf_file.");
		}
	}

	public function test_supprimer_depuis_un_module_efface_la_ligne_et_le_fichier(): void
	{
		foreach (self::MODULES as $nom)
		{
			[$id, $chemin] = $this->fichier();

			\NeoFrag()->module($nom)->model2('file', $id)->delete();

			$this->assertSame(0, (int) $this->db()->from('nf_file')->where('id', $id)->count(), "Supprimé depuis le module $nom : la ligne doit partir.");
			$this->assertFileDoesNotExist($chemin, "Supprimé depuis le module $nom : le fichier doit quitter le disque.");
		}
	}
}
