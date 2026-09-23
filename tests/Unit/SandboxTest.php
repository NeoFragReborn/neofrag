<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Modules\Sandbox\Lib\Diff;

/**
 * Le bac à sable de mise en forme.
 *
 * Ce qui y mérite une épreuve n'est pas le rendu — il est produit par `bbcode()`, le même que le
 * forum — mais la comparaison qui dit au membre CE QUI A ÉTÉ RETIRÉ. C'est la seule chose que le
 * produit n'explique nulle part ailleurs : pourquoi un tableau disparaît, pourquoi un attribut
 * saute. Une comparaison fausse dirait « rien n'a été retiré » là où tout l'a été.
 *
 * Test PUR : `Diff` ne connaît ni la base ni le service locator.
 */
final class SandboxTest extends TestCase
{
	public function test_les_balises_sont_relevees_une_seule_fois(): void
	{
		self::assertSame(['b', 'p'], Diff::balises('<p>un <b>deux</b> <b>trois</b></p>'));
		self::assertSame(['br'], Diff::balises('<BR /><br>'), 'la casse ne compte pas');
		self::assertSame([], Diff::balises('du texte sans balise'));
		self::assertSame([], Diff::balises('2 < 3 et 5 > 4'), 'une comparaison n\'est pas une balise');
		self::assertSame([], Diff::balises(NULL));
	}

	public function test_les_attributs_ne_sont_cherches_que_dans_les_balises(): void
	{
		self::assertSame(['href'], Diff::attributs('<a href="/x">lien</a>'));
		self::assertSame(['class', 'href'], Diff::attributs('<a class="c" href="/x">lien</a>'));

		// Le piège : le mot « style » dans le TEXTE ne doit pas compter pour un attribut.
		self::assertSame([], Diff::attributs('<p>le style = une question de goût</p>'));
		self::assertSame(['style'], Diff::attributs('<p style="color:red">rouge</p>'));
		self::assertSame([], Diff::attributs(NULL));
	}

	public function test_ce_que_l_assainissement_a_retire(): void
	{
		$avant = '<p>bonjour <script>alert(1)</script> <b onclick="x()">gras</b></p>';
		$apres = '<p>bonjour  <b>gras</b></p>';

		$retire = Diff::retire($avant, $apres);

		self::assertSame(['script'], $retire['tags']);
		self::assertSame(['onclick'], $retire['attributes']);
		self::assertFalse($retire['truncated']);
	}

	public function test_quand_rien_n_a_ete_retire(): void
	{
		$html = '<p>bonjour <b>tout le monde</b></p>';

		$retire = Diff::retire($html, $html);

		self::assertSame([], $retire['tags']);
		self::assertSame([], $retire['attributes']);
	}

	/** Ce qui a été AJOUTÉ par le rendu — les auto-liens, les émojis — n'est pas « retiré ». */
	public function test_ce_que_le_rendu_ajoute_n_est_pas_compte_comme_retire(): void
	{
		$avant = '<p>voir https://exemple.org</p>';
		$apres = '<p>voir <a href="https://exemple.org" rel="nofollow">https://exemple.org</a></p>';

		$retire = Diff::retire($avant, $apres);

		self::assertSame([], $retire['tags'], 'le <a> ajouté n\'est pas une perte');
		self::assertSame([], $retire['attributes']);
	}

	public function test_une_liste_trop_longue_est_annoncee_comme_coupee(): void
	{
		$balises = '';

		for ($i = 0; $i < Diff::MAX_ELEMENTS + 5; $i++)
		{
			// Des noms de balises distincts et syntaxiquement valides.
			$balises .= '<x'.$i.'>';
		}

		$retire = Diff::retire($balises, '');

		self::assertCount(Diff::MAX_ELEMENTS, $retire['tags']);
		self::assertTrue($retire['truncated']);
	}

	public function test_le_contenu_est_borne(): void
	{
		self::assertSame('court', Diff::contenu('court'));
		self::assertSame(10, mb_strlen(Diff::contenu(str_repeat('a', 50), 10)));
		self::assertSame('', Diff::contenu(NULL));
		self::assertSame('', Diff::contenu(['du texte']));
		self::assertSame('éé', Diff::contenu('ééé', 2), 'la coupe compte des caractères, pas des octets');
	}
}
