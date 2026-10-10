<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Le bus d'événements du cœur (neofrag/core/events.php) : les modules y publient
 * (`permissions.role.created`, `forum.post.created`…) et s'y abonnent. Il n'avait aucun test ;
 * il en reçoit un au moment où il passe en `strict_types`, pour que la conversion soit prouvée
 * et non supposée.
 */
final class EventsTest extends HeadlessTestCase
{
	private function events()
	{
		return \NeoFrag()->events;
	}

	protected function tearDown(): void
	{
		// tearDown() tourne même quand setUp() a sauté le test (framework non amorcé) : rien à désabonner alors.
		if (self::amorce())
		{
			$this->events()->off('test.evenement')->off('test.autre');
		}

		parent::tearDown();
	}

	public function test_un_abonne_recoit_les_arguments_et_son_retour_est_rendu(): void
	{
		$recu = NULL;

		$this->events()->on('test.evenement', function ($a, $b) use (&$recu) {
			$recu = [$a, $b];
			return $a + $b;
		});

		$resultats = $this->events()->fire('test.evenement', 2, 3);

		$this->assertSame([2, 3], $recu);
		$this->assertSame([5], $resultats);
	}

	public function test_plusieurs_abonnes_sont_appeles_dans_l_ordre(): void
	{
		$ordre = [];

		$this->events()->on('test.evenement', function () use (&$ordre) { $ordre[] = 'premier'; });
		$this->events()->on('test.evenement', function () use (&$ordre) { $ordre[] = 'second'; });

		$this->events()->fire('test.evenement');

		$this->assertSame(['premier', 'second'], $ordre);
	}

	public function test_un_abonne_qui_leve_n_empeche_pas_les_suivants(): void
	{
		$appele = FALSE;

		$this->events()->on('test.evenement', function () { throw new \RuntimeException('boum'); });
		$this->events()->on('test.evenement', function () use (&$appele) { $appele = TRUE; return 'ok'; });

		$resultats = $this->events()->fire('test.evenement');

		$this->assertTrue($appele, "L'exception d'un abonné ne doit pas priver les suivants de l'événement.");
		$this->assertSame(['ok'], $resultats, "Seul le retour de l'abonné qui a réussi est rendu.");
	}

	public function test_off_desabonne_et_has_dit_la_verite(): void
	{
		$this->assertFalse($this->events()->has('test.evenement'));

		$this->events()->on('test.evenement', function () {});
		$this->assertTrue($this->events()->has('test.evenement'));
		$this->assertCount(1, $this->events()->listeners('test.evenement'));

		$this->events()->off('test.evenement');
		$this->assertFalse($this->events()->has('test.evenement'));
		$this->assertSame([], $this->events()->fire('test.evenement'), 'Sans abonné, fire() rend une liste vide.');
	}
}
