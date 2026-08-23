<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\NeoFrag\Libraries\Forms\Options_Encoder;

require_once __DIR__ . '/../../neofrag/libraries/forms/options_encoder.php';

/**
 * Régression : récursion infinie au rendu d'un <select> dont une option a pour VALEUR
 * un objet stringable (cas réel : champ « timezone » du profil, option
 * '' => $this->lang('Fuseau par défaut du site') — lang() renvoie un objet Lang).
 *
 * Le cast (array)$objet exposait les propriétés internes de l'objet, dont __caller (le
 * module appelant). Stringifié, ce module re-rendait toute la page via Module::__toString,
 * laquelle contient ce select → empilement jusqu'au fatal « Maximum call stack size »
 * (HTTP 500 sur /user/profile, ~10 s d'empilement). Indépendant de la version PHP.
 */
final class SelectOptionsEncoderTest extends TestCase
{
	public function test_object_option_value_is_not_property_unpacked(): void
	{
		$module = new RecursiveModuleStub();
		$lang   = new LangStub('Fuseau par défaut du site', $module);

		$json = Options_Encoder::encode(['' => $lang, 'Europe/Paris' => 'Europe/Paris']);

		// Le symptôme exact du bug : le module ne doit JAMAIS être stringifié pendant l'encodage.
		$this->assertSame(0, $module->stringified, 'le module fuit dans les options → récursion');

		// L'option objet est rendue par sa chaîne, pas par ses propriétés internes.
		$decoded = json_decode(html_entity_decode($json, ENT_COMPAT, 'UTF-8'), true);
		$this->assertSame([['', 'Fuseau par défaut du site'], ['Europe/Paris', 'Europe/Paris']], $decoded);
	}

	public function test_array_option_value_is_preserved(): void
	{
		// Les options multi-colonnes (valeur = tableau) doivent rester intactes.
		$json = Options_Encoder::encode([1 => ['Alice', 'a@x.io']]);

		$decoded = json_decode(html_entity_decode($json, ENT_COMPAT, 'UTF-8'), true);
		$this->assertSame([[1, 'Alice', 'a@x.io']], $decoded);
	}
}

/**
 * Imite NF\NeoFrag\Addons\Module::__toString : retournerait le contenu de page (donc le select).
 * On compte les stringifications pour détecter la fuite sans réellement récurser.
 */
class RecursiveModuleStub
{
	public int $stringified = 0;

	public function __toString(): string
	{
		$this->stringified++;
		return '<page content containing the select again>';
	}
}

/**
 * Imite NF\NeoFrag\Libraries\Lang : objet stringable détenant une référence au module
 * appelant (__caller), comme la base NF\NeoFrag\Library.
 */
class LangStub
{
	protected string $_name;
	protected $__caller;

	public function __construct(string $name, $caller)
	{
		$this->_name   = $name;
		$this->__caller = $caller;
	}

	public function __toString(): string
	{
		return $this->_name;
	}
}
