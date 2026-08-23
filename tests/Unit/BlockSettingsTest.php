<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Modules\Pages\Lib\Block_Settings;

/**
 * Palier 0 page-builder — parsing/validation des paramètres d'un bloc [block:clé p=v …].
 * Pin le contrat : seuls les champs DÉCLARÉS passent (params inconnus ignorés), coercition par
 * type, clamp min/max, defaults, valeurs quotées. La frontière reste sûre quel que soit l'input.
 */
final class BlockSettingsTest extends TestCase
{
	private function def(): array
	{
		return ['fields' => [
			'id'    => ['type' => 'int', 'default' => 0, 'min' => 0],
			'count' => ['type' => 'int', 'default' => 5, 'min' => 1, 'max' => 20],
			'cat'   => ['type' => 'string', 'default' => '', 'max_length' => 10],
			'full'  => ['type' => 'bool', 'default' => false],
		]];
	}

	public function test_parses_and_coerces_declared_params(): void
	{
		$this->assertSame(
			['id' => 3, 'count' => 5, 'cat' => '', 'full' => false],
			Block_Settings::parse($this->def(), ' id=3')
		);
	}

	public function test_defaults_applied_when_param_absent(): void
	{
		$this->assertSame(
			['id' => 0, 'count' => 5, 'cat' => '', 'full' => false],
			Block_Settings::parse($this->def(), '')
		);
	}

	public function test_unknown_params_are_ignored(): void
	{
		$out = Block_Settings::parse($this->def(), ' id=2 evil=<script> onclick=alert');

		$this->assertSame(['id', 'count', 'cat', 'full'], array_keys($out));
		$this->assertArrayNotHasKey('evil', $out);
		$this->assertArrayNotHasKey('onclick', $out);
	}

	public function test_int_clamped_to_min_and_max(): void
	{
		$this->assertSame(20, Block_Settings::parse($this->def(), ' count=999')['count']);
		$this->assertSame(1,  Block_Settings::parse($this->def(), ' count=0')['count']);
		$this->assertSame(7,  Block_Settings::parse($this->def(), ' count=7')['count']);
	}

	public function test_string_truncated_to_max_length(): void
	{
		$this->assertSame('abcdefghij', Block_Settings::parse($this->def(), ' cat=abcdefghijKLMNOP')['cat']);
	}

	public function test_quoted_value_keeps_spaces(): void
	{
		$def = ['fields' => ['cat' => ['type' => 'string', 'default' => '', 'max_length' => 50]]];

		$this->assertSame('hello world', Block_Settings::parse($def, ' cat="hello world"')['cat']);
	}

	public function test_bool_truthy_tokens(): void
	{
		$this->assertTrue(Block_Settings::parse($this->def(), ' full=1')['full']);
		$this->assertTrue(Block_Settings::parse($this->def(), ' full=true')['full']);
		$this->assertTrue(Block_Settings::parse($this->def(), ' full=on')['full']);
		$this->assertFalse(Block_Settings::parse($this->def(), ' full=0')['full']);
		$this->assertFalse(Block_Settings::parse($this->def(), ' full=nope')['full']);
	}

	public function test_block_without_fields_yields_empty_settings(): void
	{
		$this->assertSame([], Block_Settings::parse(['title' => 'x'], ' id=3 count=5'));
	}

	public function test_non_numeric_int_falls_back_to_zero_then_clamped(): void
	{
		// "abc" → (int) 0 → clamp min 1 = 1 (jamais d'erreur, valeur bornée).
		$this->assertSame(1, Block_Settings::parse($this->def(), ' count=abc')['count']);
	}

	// ── from_array (composer / instances : valeurs déjà typées) ──────────────────

	public function test_from_array_keeps_declared_only_and_coerces(): void
	{
		$out = Block_Settings::from_array($this->def(), ['id' => '4', 'count' => 99, 'evil' => '<x>']);

		$this->assertSame(['id' => 4, 'count' => 20, 'cat' => '', 'full' => false], $out); // count clampé, evil ignoré
	}

	public function test_from_array_preserves_native_false_over_truthy_default(): void
	{
		$def = ['fields' => ['vis' => ['type' => 'bool', 'default' => true]]];

		// Clé présente avec false → false (le défaut TRUE ne s'applique pas, contrairement au shortcode).
		$this->assertFalse(Block_Settings::from_array($def, ['vis' => false])['vis']);
		// Clé absente → défaut TRUE.
		$this->assertTrue(Block_Settings::from_array($def, [])['vis']);
	}

	public function test_from_array_zero_present_is_coerced_not_defaulted(): void
	{
		// count=0 présent → coercition (clamp min 1), pas retour au défaut 5.
		$this->assertSame(1, Block_Settings::from_array($this->def(), ['count' => 0])['count']);
	}
}
