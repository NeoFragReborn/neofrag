<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Les titres traduits du forum (2026-10-01).
 *
 * Une catégorie et un forum gardent leur titre par défaut et peuvent en recevoir un par langue.
 * Ce test épingle les trois promesses qui ne se voient pas à l'écran : une traduction s'enregistre et
 * se relit ; un champ vidé la retire ; une adresse reste valable avec le titre par défaut comme avec
 * chaque traduction — un lien partagé en français ne casse pas en anglais. La suppression d'un forum
 * emporte ses traductions (clé étrangère en cascade).
 *
 * L'affichage dans la langue du visiteur demande une page servie : il a été mesuré sur un site d'essai
 * (français et allemand sur le titre par défaut, anglais sur sa traduction).
 */
final class ForumTitresTraduitsTest extends HeadlessTestCase
{
	private ?int $categorie = NULL;
	private ?int $forum     = NULL;

	protected function tearDown(): void
	{
		if ($this->forum !== NULL)
		{
			$this->db()->where('forum_id', $this->forum)->delete('nf_forum');
		}

		if ($this->categorie !== NULL)
		{
			$this->db()->where('category_id', $this->categorie)->delete('nf_forum_categories');
		}

		$this->forum = $this->categorie = NULL;

		parent::tearDown();
	}

	private function modele()
	{
		return \NeoFrag()->module('forum')->model();
	}

	private function semer(): void
	{
		$this->categorie = (int) $this->db()->insert('nf_forum_categories', ['title' => 'Aide test', 'order' => 99]);
		$this->forum     = (int) $this->db()->insert('nf_forum', ['parent_id' => $this->categorie, 'is_subforum' => '0', 'title' => 'Questions test', 'description' => 'Posez-les ici', 'order' => 99]);
	}

	public function testUneTraductionSEnregistreEtUnChampVideLaRetire(): void
	{
		$this->semer();

		$this->modele()->enregistrer_traductions('forum', $this->forum, [
			'en' => ['title' => 'Test questions', 'description' => 'Ask them here'],
			'de' => ['title' => '', 'description' => ''],
		]);

		$traductions = $this->modele()->traductions('forum', $this->forum);

		$this->assertSame('Test questions', $traductions['en']['title']);
		$this->assertSame('Ask them here', $traductions['en']['description']);
		$this->assertArrayNotHasKey('de', $traductions, 'un champ vide n\'enregistre rien');

		$this->modele()->enregistrer_traductions('forum', $this->forum, ['en' => ['title' => '', 'description' => '']]);

		$this->assertSame([], $this->modele()->traductions('forum', $this->forum), 'vider le champ retire la traduction');
	}

	public function testUneAdresseResteValableDansToutesLesLangues(): void
	{
		$this->semer();
		$this->modele()->enregistrer_traductions('category', $this->categorie, ['en' => ['title' => 'Test help']]);

		$methode = new \ReflectionMethod($this->modele(), '_titre_d_adresse');
		$methode->setAccessible(TRUE);

		$valable = fn (string $slug): bool => $methode->invoke($this->modele(), 'category', $this->categorie, $slug, 'Aide test');

		$this->assertTrue($valable('aide-test'), 'le titre par défaut');
		$this->assertTrue($valable('test-help'), 'la traduction');
		$this->assertFalse($valable('autre-chose'), 'une adresse inventée');
	}

	public function testSupprimerUnForumEmporteSesTraductions(): void
	{
		$this->semer();
		$this->modele()->enregistrer_traductions('forum', $this->forum, ['en' => ['title' => 'Test questions']]);

		$this->db()->where('forum_id', $this->forum)->delete('nf_forum');

		$this->assertSame(0, (int) $this->db()->select('COUNT(*)')->from('nf_forum_lang')->where('forum_id', $this->forum)->row());

		$this->forum = NULL;
	}
}
