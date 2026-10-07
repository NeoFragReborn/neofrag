<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Un module en charge un autre avec `$this->module(…)` (2026-10-07).
 *
 * `NeoFrag::__call()` passait la main au chargeur des addons par `forward_static_call_array()`, qui
 * transmet la classe appelante : depuis un module, le chargeur se croyait la classe de ce module
 * (`NF\Modules\User\User`), cherchait un type d'addon de ce nom et rendait NULL. Le message de bienvenue
 * ne partait jamais (le module Membres ne trouvait pas la messagerie), et un webhook de commentaire ne
 * trouvait pas le module commenté — un avertissement au journal de la vitrine, et rien d'autre.
 */
final class ModuleDepuisUnModuleTest extends HeadlessTestCase
{
	/** @var array<string, mixed> */
	private array $reglages = [];

	protected function tearDown(): void
	{
		foreach ($this->reglages as $nom => $valeur)
		{
			\NeoFrag()->config->$nom = $valeur;
		}

		parent::tearDown();
	}

	public function test_un_module_trouve_un_autre_module(): void
	{
		foreach (['user' => 'talks', 'webhooks' => 'forum', 'comments' => 'news'] as $depuis => $cherche)
		{
			$module = \NeoFrag()->module($depuis)->module($cherche);

			$this->assertInstanceOf(\NF\NeoFrag\Addons\Module::class, $module, "Depuis le module $depuis, le module $cherche doit se charger.");
			$this->assertSame($cherche, $module->info()->name);
		}
	}

	public function test_le_message_de_bienvenue_part(): void
	{
		$config = \NeoFrag()->config;
		$auteur = $this->createUser('hbienvenue_auteur');
		$membre = $this->createUser('hbienvenue_membre');

		foreach (['nf_welcome' => 1, 'nf_welcome_user_id' => $auteur, 'nf_welcome_title' => 'Bienvenue', 'nf_welcome_content' => '<p>Salut [pseudo] !</p>'] as $nom => $valeur)
		{
			$this->reglages[$nom] = $config->$nom;
			$config->$nom = $valeur;
		}

		\NeoFrag()->module('user')->bienvenue($membre, 'nouveau');

		$message = $this->db()	->select('m.message')
								->from('nf_talks_messages m')
								->join('nf_talks_participants p', 'p.talk_id = m.talk_id')
								->where('p.user_id', $membre)
								->where('m.user_id', $auteur)
								->row();

		$this->assertSame('Salut @nouveau !', is_string($message) ? trim($message) : NULL, 'Le nouveau membre doit recevoir le message de bienvenue.');
	}
}
