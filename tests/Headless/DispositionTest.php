<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Tests d'objet de la lib Disposition (encode/decode JSON de l'arbre du live editor, en
 * remplacement de serialize/unserialize). Verrouille : round-trip lossless, rétro-compat de
 * lecture (legacy PHP-serialized lu à l'identique), et les cas vides. Le harnais headless
 * suffit (db+config ; les factories row/col/widget ne touchent ni session ni output).
 */
final class DispositionTest extends HeadlessTestCase
{
	private function disposition()
	{
		return \NeoFrag()->disposition;
	}

	public function test_round_trip_is_lossless(): void
	{
		$d = $this->disposition();

		$input = [
			['style' => 'row-dark', 'cols' => [
				['size' => 'col-6', 'widgets' => [
					['id' => 12, 'style' => 'panel-default', 'size' => NULL],
					['id' => 34, 'style' => NULL, 'size' => NULL],
				]],
				['size' => NULL, 'widgets' => [
					['id' => 56, 'style' => NULL, 'size' => 'col-4'],
				]],
			]],
			['style' => NULL, 'cols' => []],
		];

		// from_array → encode → decode → to_array doit redonner exactement l'entrée.
		$tree  = $d->from_array($input);
		$json  = $d->encode($tree);
		$again = $d->to_array($d->decode($json));

		$this->assertSame($input, $again, 'Le round-trip JSON de la disposition est lossless.');
		$this->assertStringStartsWith('[', $json, 'encode() produit du JSON, pas du serialize.');
	}

	public function test_decode_reads_legacy_php_serialized_identically(): void
	{
		$d = $this->disposition();

		// Construit un arbre réel puis le PHP-serialize (format hérité).
		$tree   = $d->from_array([
			['style' => 'row-default', 'cols' => [
				['size' => NULL, 'widgets' => [['id' => 99, 'style' => NULL, 'size' => NULL]]],
			]],
		]);
		$legacy = serialize($tree);

		// decode() doit lire le legacy ET le JSON vers le MÊME tableau.
		$from_legacy = $d->to_array($d->decode($legacy));
		$from_json   = $d->to_array($d->decode($d->encode($tree)));

		$this->assertSame($from_json, $from_legacy, 'decode() lit le legacy PHP-serialized à l\'identique du JSON.');
		$this->assertSame(99, $from_legacy[0]['cols'][0]['widgets'][0]['id']);
	}

	public function test_empty_and_garbage_decode_to_empty(): void
	{
		$d = $this->disposition();

		$this->assertSame([], $d->to_array($d->decode('')));
		$this->assertSame([], $d->to_array($d->decode('   ')));
		$this->assertSame([], $d->to_array($d->decode('not json not serialize')));
	}
}
