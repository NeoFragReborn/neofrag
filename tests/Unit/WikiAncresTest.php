<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Modules\Wiki\Lib\Wiki_Ancres;

/**
 * Les ancres des titres du wiki (m19). Les guides renvoient à des sections de niveau 3 — `admin#le-référencement`,
 * `bot-discord#collectifs` — que le wiki n'ancrait pas : seul le niveau 2 portait un `id`, et un lien vers une section
 * menait en haut de la page, dans les six langues. tools/check-wiki-docs.php vérifie que chaque renvoi des guides
 * mène à son titre ; ici, ce que le wiki pose.
 */
final class WikiAncresTest extends TestCase
{
	public function test_les_titres_de_niveau_2_a_4_recoivent_leur_ancre(): void
	{
		$html = '<h1>Titre</h1><h2>Le référencement</h2><h3>Contenu traduit : <code>join_lang()</code></h3><h4>Détail</h4><h5>Note</h5>';

		$this->assertSame(
			'<h1>Titre</h1><h2 id="le-référencement">Le référencement</h2><h3 id="contenu-traduit-join-lang">Contenu traduit : <code>join_lang()</code></h3><h4 id="détail">Détail</h4><h5>Note</h5>',
			Wiki_Ancres::poser($html)
		);
		$this->assertSame(['le-référencement', 'contenu-traduit-join-lang', 'détail'], Wiki_Ancres::ids($html));
	}

	public function test_un_titre_qui_porte_deja_un_attribut_reste_tel_quel(): void
	{
		$html = '<h2 class="h4 mt-4" id="a">A</h2>';

		$this->assertSame($html, Wiki_Ancres::poser($html));
	}

	public function test_une_version_du_journal_a_l_ancre_de_son_numero(): void
	{
		$this->assertSame('1-2-14', Wiki_Ancres::ancre('[1.2.14] — 2026-10-02'));
		$this->assertSame('tables-table2', Wiki_Ancres::ancre('Tables — table2()'));
		$this->assertSame('section', Wiki_Ancres::ancre('—'));
	}

	public function test_le_texte_d_un_titre_est_celui_que_lit_le_visiteur(): void
	{
		$this->assertSame('Thèmes & Addons', Wiki_Ancres::texte(' <em>Thèmes</em> &amp; Addons '));
	}
}
