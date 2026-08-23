<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\NeoFrag\Fields\Serialized;

require_once __DIR__ . '/../../neofrag/fields/serialized.php';

/**
 * Régression : la désérialisation bornée d'un champ serialized doit conserver un \DateTime imbriqué
 * COMPLET. Cas réel : anti_flood stocke un Date (qui encapsule un \DateTime) dans la session ; au 2e
 * appel, sans \DateTime dans allowed_classes, le _datetime redevient __PHP_Incomplete_Class → fatal
 * « call a method on an incomplete object » dès timestamp() → cassait email, lost-password, etc.
 * (HTTP 500). Indépendant de la version PHP : doit passer sur 8.2/8.3/8.4/8.5.
 */
final class SerializedFieldTest extends TestCase
{
	public function test_unserialize_safe_keeps_nested_datetime_complete(): void
	{
		$payload = serialize(['ts' => new \DateTime('2026-06-07 12:00:00')]);

		$out = Serialized::unserialize_safe($payload);

		$this->assertIsArray($out);
		$this->assertInstanceOf(\DateTime::class, $out['ts']);
		$this->assertNotInstanceOf(\__PHP_Incomplete_Class::class, $out['ts']);
		// Appeler une méthode ne doit PAS fataliser (le symptôme exact du bug).
		$this->assertSame('2026', $out['ts']->format('Y'));
	}

	public function test_unserialize_safe_still_blocks_arbitrary_classes(): void
	{
		// La protection anti-POP reste en place : une classe non autorisée n'est pas instanciée.
		$out = Serialized::unserialize_safe(serialize(new \ArrayObject(['x' => 1])));

		$this->assertInstanceOf(\__PHP_Incomplete_Class::class, $out);
	}
}
