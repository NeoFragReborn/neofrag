<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Le déplacement d'un élément (`Array_::move()`) avec une position venue du navigateur.
 *
 * Le glisser-déposer envoie sa position en texte (`position=2`). Sous `strict_types`,
 * `array_slice()` refuse une chaîne : l'éditeur en direct, le tri des langues et des
 * connecteurs répondaient 500 sur tous les sites, et seul le journal de la démonstration
 * l'a montré (2026-10-02). Les checkers des autres déplacements convertissent à l'entrée ;
 * la bibliothèque, qui reçoit aussi des positions de l'extérieur, convertit elle-même.
 */
final class ArrayMoveTest extends HeadlessTestCase
{
	public function test_une_position_en_texte_deplace_l_element(): void
	{
		$liste = \NeoFrag()->array(['a' => 1, 'b' => 2, 'c' => 3])->move('a', '2');

		$this->assertSame(['b', 'c', 'a'], $liste->keys());
	}

	public function test_une_position_entiere_reste_acceptee(): void
	{
		$liste = \NeoFrag()->array(['a' => 1, 'b' => 2, 'c' => 3])->move('c', 0);

		$this->assertSame(['c', 'a', 'b'], $liste->keys());
	}
}
