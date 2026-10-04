<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Modules\Payments\Payments;

/**
 * Le module Paiements vend des points et des jours de VIP, que seul le module Gamification sait
 * créditer. Il ne le déclarait pas, et sans lui `fulfill()` enregistrait le paiement comme traité
 * puis ne créditait rien : l'argent était encaissé, le membre ne recevait rien (2026-10-04).
 */
final class PaiementsGamificationTest extends TestCase
{
	public function test_la_dependance_est_declaree(): void
	{
		$source = (string) file_get_contents(__DIR__.'/../../modules/payments/payments.php');

		self::assertSame(1, preg_match("/'requires'\s*=>\s*\[([^\]]*)\]/", $source, $m));
		self::assertStringContainsString("'gamification'", $m[1]);
	}

	/**
	 * Sans Gamification, `fulfill()` refuse AVANT d'écrire la ligne d'idempotence : le paiement
	 * n'est pas marqué traité, et Stripe le représentera. L'objet est construit sans le cœur — s'il
	 * touchait la base, l'épreuve échouerait sur l'absence de `NeoFrag()`.
	 */
	public function test_sans_gamification_rien_n_est_marque_traite(): void
	{
		$paiements = (new \ReflectionClass(PaiementsSansGamification::class))->newInstanceWithoutConstructor();

		self::assertFalse($paiements->fulfill('evt_test', 'cs_test', 7, 'points', 100));
		self::assertFalse($paiements->fulfill('evt_test', 'cs_test', 7, 'vip', 30));
	}

	public function test_la_vente_et_le_webhook_consultent_la_garde(): void
	{
		$racine = __DIR__.'/../../modules/payments';

		self::assertStringContainsString('vente_possible()', (string) file_get_contents($racine.'/controllers/index.php'), 'la page publique');
		self::assertStringContainsString('vente_possible()', (string) file_get_contents($racine.'/controllers/ajax.php'), 'la création de la session Stripe');
		self::assertStringContainsString('->gamification()', (string) file_get_contents($racine.'/controllers/index.php'), 'le webhook');
		self::assertStringContainsString('http_response_code(503)', (string) file_get_contents($racine.'/controllers/index.php'), 'Stripe représentera l\'événement');
	}
}

/** Le module Paiements, sur un site où Gamification manque. */
final class PaiementsSansGamification extends Payments
{
	public function gamification()
	{
		return NULL;
	}
}
