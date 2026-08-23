<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\NeoFrag\Fields\Json;

require_once __DIR__ . '/../../neofrag/fields/json.php';

/**
 * Champ JSON (settings widgets + futures colonnes structurées). Garantit :
 *  - encode→decode lossless sur les formes réelles de settings (bools/ints/strings/sous-arrays/unicode) ;
 *  - decode RÉTRO-COMPATIBLE avec le legacy PHP-serialized (le temps que les lignes soient ré-écrites) ;
 *  - jamais d'objet instancié à la lecture (anti-POP : allowed_classes=false).
 */
final class JsonFieldTest extends TestCase
{
	public function test_encode_decode_roundtrip_on_widget_like_settings(): void
	{
		$settings = [
			'display' => true,
			'count'   => 5,
			'ratio'   => 0.5,
			'title'   => '',
			'links'   => [
				['title' => 'Accueil', 'url' => ''],
				['title' => 'Actualités', 'url' => 'news'],
			],
		];

		$encoded = Json::encode($settings);

		$this->assertJson($encoded);
		$this->assertSame($settings, Json::decode($encoded));
	}

	public function test_decode_reads_legacy_php_serialized(): void
	{
		$legacy = serialize(['display' => true, 'links' => [['title' => 'A', 'url' => 'a']]]);

		$this->assertSame(['display' => true, 'links' => [['title' => 'A', 'url' => 'a']]], Json::decode($legacy));
	}

	public function test_decode_empty_or_garbage_returns_empty_array(): void
	{
		$this->assertSame([], Json::decode(''));
		$this->assertSame([], Json::decode('   '));
		$this->assertSame([], Json::decode('not-serialized-not-json'));
		$this->assertSame([], Json::decode('{bad json'));
	}

	public function test_encode_empty_returns_empty_string(): void
	{
		$this->assertSame('', Json::encode([]));
		$this->assertSame('', Json::encode(null));
	}

	public function test_non_sequential_integer_keys_survive_roundtrip(): void
	{
		// json_encode rend {"1":..,"3":..} ; json_decode(true) renormalise les clés numériques en int → identité.
		$data = [1 => 'a', 3 => 'b'];

		$this->assertSame($data, Json::decode(Json::encode($data)));
	}

	public function test_unicode_and_slashes_are_preserved_unescaped(): void
	{
		$data    = ['url' => 'https://neofr.ag/path', 'label' => 'Équipes & dé/co'];
		$encoded = Json::encode($data);

		$this->assertStringContainsString('https://neofr.ag/path', $encoded); // slashes non échappés
		$this->assertStringContainsString('Équipes', $encoded);               // unicode non échappé
		$this->assertSame($data, Json::decode($encoded));
	}

	public function test_decode_never_instantiates_objects_from_legacy(): void
	{
		// Un array legacy contenant un objet : la lecture ne l'instancie pas (anti-POP). On reste un array.
		$payload = serialize(['x' => new \ArrayObject(['k' => 1])]);
		$out     = Json::decode($payload);

		$this->assertIsArray($out);
		$this->assertInstanceOf(\__PHP_Incomplete_Class::class, $out['x']);
	}

	public function test_normalize_converts_nested_framework_arrays(): void
	{
		// Objet exposant __toArray (comme Array_) → converti récursivement avant json_encode.
		$leaf = new class {
			public function __toArray(): array { return ['title' => 'leaf']; }
		};
		$root = new class($leaf) {
			private $leaf;
			public function __construct($leaf) { $this->leaf = $leaf; }
			public function __toArray(): array { return ['child' => $this->leaf]; }
		};

		$this->assertSame(['child' => ['title' => 'leaf']], Json::decode(Json::encode($root)));
	}
}
