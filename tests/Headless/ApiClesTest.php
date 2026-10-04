<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Les clés d'accès de l'API (2026-10-01).
 *
 * Ce test épingle les promesses de sécurité qu'aucun écran ne montre : seule l'empreinte d'une clé
 * est gardée ; une clé se vérifie par sa valeur exacte, et plus du tout une fois révoquée ; une
 * valeur mal formée est refusée sans requête. Les réponses HTTP de l'API (401, 403, 405, 429…) ont
 * été mesurées sur un site d'essai par de vraies requêtes.
 */
final class ApiClesTest extends HeadlessTestCase
{
	private function modele(): \NF\Modules\Api\Models\Api
	{
		$modele = \NeoFrag()->module('api')->model('api');
		$this->assertInstanceOf(\NF\Modules\Api\Models\Api::class, $modele);

		return $modele;
	}

	public function testSeuleLEmpreinteEstGardee(): void
	{
		$cle = $this->modele()->creer('Essai', ['members:read'], NULL);

		$this->assertMatchesRegularExpression('/^nfr_[0-9a-f]{40}$/', $cle);

		$ligne = $this->db()->select('prefix', 'hash')->from('nf_api_tokens')->where('hash', hash('sha256', $cle))->row();

		$this->assertIsArray($ligne);
		$this->assertSame(substr($cle, 0, 8), $ligne['prefix']);
		$this->assertSame(0, (int) $this->db()->select('COUNT(*)')->from('nf_api_tokens')->where('hash', $cle)->row(), 'la clé en clair n\'est nulle part');
	}

	public function testUneCleSeVerifieParSaValeurExacteEtPlusApresRevocation(): void
	{
		$cle   = $this->modele()->creer('Essai', ['members:read', 'forum:read'], NULL);
		$jeton = $this->modele()->verifier($cle);

		$this->assertNotNull($jeton);
		$this->assertSame(['members:read', 'forum:read'], $jeton['scopes']);
		$this->assertNull($this->modele()->verifier(substr($cle, 0, -1).(substr($cle, -1) === 'a' ? 'b' : 'a')), 'un caractère de différence');

		$this->modele()->revoquer($jeton['token_id']);

		$this->assertNull($this->modele()->verifier($cle), 'une clé révoquée ne s\'utilise plus');
	}

	public function testUneValeurMalFormeeEstRefusee(): void
	{
		foreach (['', 'nfr_', 'Bearer x', 'nfr_'.str_repeat('a', 39), 'xyz_'.str_repeat('a', 40)] as $valeur)
		{
			$this->assertNull($this->modele()->verifier($valeur), $valeur);
		}
	}
}
