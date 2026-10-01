<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * `Db::join_lang()` — la meilleure traduction d'un objet, sans jamais le perdre ni le dédoubler.
 *
 * LE DÉFAUT ÉPINGLÉ ICI. Les équipes, jeux, catégories et partenaires étaient joints à leur table de
 * traduction puis filtrés sur la langue affichée. L'administration n'enregistre le titre que dans la
 * langue où l'on écrit : un objet saisi en français DISPARAISSAIT des cinq autres langues. Mesuré
 * sur la démonstration le 2026-10-01 : trois équipes et trois palmarès en français, aucun en
 * anglais — et le groupe de chaque équipe, avec ses droits, disparaissait avec elle. Les jointures
 * qui ne filtraient pas, elles, rendaient une ligne par langue, ou un titre au hasard.
 *
 * Les trois propriétés : chaque objet sort UNE fois ; dans la langue demandée si elle existe ;
 * sinon en français, sinon dans la langue qui existe.
 */
final class JoinLangTest extends HeadlessTestCase
{
	private const MARQUE = 'nf_test_join_lang_';

	private array $categories = [];

	protected function tearDown(): void
	{
		foreach ($this->categories as $category_id)
		{
			$this->db()->where('category_id', $category_id)->delete('nf_news_categories_lang');
			$this->db()->where('category_id', $category_id)->delete('nf_news_categories');
		}

		$this->categories = [];

		parent::tearDown();
	}

	/** Une catégorie jetable, titrée exactement dans les langues demandées. */
	private function semer(string $nom, array $langues): int
	{
		$category_id = (int) $this->db()->insert('nf_news_categories', ['name' => self::MARQUE.$nom]);

		$this->categories[] = $category_id;

		foreach ($langues as $langue)
		{
			$this->db()->insert('nf_news_categories_lang', [
				'category_id' => $category_id,
				'lang'        => $langue,
				'title'       => self::MARQUE.$nom.'_'.$langue,
			]);
		}

		return $category_id;
	}

	/** Les titres obtenus par catégorie, dans la langue demandée — une entrée PAR LIGNE rendue. */
	private function titres(string $langue): array
	{
		$lignes = $this->db()	->select('c.category_id', 'cl.title')
								->from('nf_news_categories c')
								->join_lang('nf_news_categories_lang cl', 'category_id', 'c.category_id', $langue)
								->where('c.category_id', $this->categories)
								->get();

		$titres = [];

		foreach ($lignes as $ligne)
		{
			$titres[] = [(int) $ligne['category_id'], $ligne['title']];
		}

		return $titres;
	}

	public function testUnObjetSaisiDansUneSeuleLangueResteVisibleDansLesAutres(): void
	{
		$fr = $this->semer('fr', ['fr']);
		$de = $this->semer('de', ['de']);

		$titres = $this->titres('en');

		$this->assertContains([$fr, self::MARQUE.'fr_fr'], $titres);
		$this->assertContains([$de, self::MARQUE.'de_de'], $titres);
	}

	public function testLaLangueDemandeePuisLeFrancais(): void
	{
		$id = $this->semer('trois', ['de', 'fr', 'en']);

		$this->assertSame([[$id, self::MARQUE.'trois_en']], $this->titres('en'));
		$this->assertSame([[$id, self::MARQUE.'trois_fr']], $this->titres('it'));
	}

	public function testChaqueObjetSortUneSeuleFois(): void
	{
		$this->semer('a', ['fr', 'en', 'de']);
		$this->semer('b', ['es', 'it']);

		$ids = array_column($this->titres('pt'), 0);

		$this->assertCount(2, $ids);
		$this->assertSame($ids, array_values(array_unique($ids)));
	}
}
