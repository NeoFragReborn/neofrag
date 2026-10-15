<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Le journal des versions et ses traductions (d09). L'outil qui le publie sur le site lit, pour une langue,
 * `CHANGELOG.<l>.md` et y cherche les sections par leur numéro, ou « unreleased » pour la section en
 * cours, dont le titre change avec la langue. Une traduction qui sauterait une version, ou la daterait
 * autrement, publierait un billet introuvable ou mal daté : ici, chaque traduction suit le français.
 *
 * Dans son propre processus, comme DiffusionTest : l'extracteur charge la bibliothèque des outils, qui définit
 * `nf_refus()` comme le CMS — dans le processus des tests qui démarrent le CMS, la suite entière s'arrêtait sur
 * « Cannot redeclare » (banc de la 1.2.50, 2026-10-15 ; la suite unitaire seule passait).
 */
#[RunTestsInSeparateProcesses]
final class ChangelogSectionTest extends TestCase
{
	private const LANGUES = ['en', 'de', 'es', 'it', 'pt'];

	protected function setUp(): void
	{
		require_once __DIR__.'/../../tools/changelog-section.php';
	}

	public function test_la_section_non_publiee_se_trouve_dans_les_six_langues(): void
	{
		foreach (['Non publié', 'Unreleased', 'Unveröffentlicht', 'Sin publicar', 'Non pubblicato', 'Não publicado'] as $titre)
		{
			$this->assertTrue(changelog_section_matches('unreleased', $titre), $titre);
			$this->assertFalse(changelog_section_matches('latest', $titre), $titre);
		}

		$this->assertFalse(changelog_section_matches('unreleased', '1.2.49'));
		$this->assertTrue(changelog_section_matches('latest', '1.2.49'));
	}

	public function test_chaque_traduction_a_les_versions_et_les_dates_du_francais(): void
	{
		$francais = $this->versions('CHANGELOG.md');
		$this->assertGreaterThan(50, count($francais));

		// Une section pas encore publiée n'existe qu'entre deux versions : le jour d'une version, elle prend son numéro.
		$non_publiee = changelog_section('unreleased', (string) file_get_contents(dirname(__DIR__, 2).'/CHANGELOG.md')) !== NULL;

		foreach (self::LANGUES as $l)
		{
			$this->assertSame($francais, $this->versions("CHANGELOG.$l.md"), "CHANGELOG.$l.md");

			$section = changelog_section('unreleased', (string) file_get_contents(dirname(__DIR__, 2)."/CHANGELOG.$l.md"));
			$this->assertSame($non_publiee, $section !== NULL, "CHANGELOG.$l.md : sa section non publiée suit celle du français");
		}
	}

	/** @return list<string> « 1.2.49 2026-10-10 », dans l'ordre du fichier. */
	private function versions(string $fichier): array
	{
		preg_match_all('/^## \[(\d+\.\d+\.\d+)\] — (\d{4}-\d{2}-\d{2})/m', (string) file_get_contents(dirname(__DIR__, 2).'/'.$fichier), $m, PREG_SET_ORDER);

		return array_map(static fn (array $v): string => $v[1].' '.$v[2], $m);
	}
}
