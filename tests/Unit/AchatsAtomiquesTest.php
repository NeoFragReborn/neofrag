<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Modules\Gamification\Gamification;

/**
 * Les échanges de points : chaque écriture qui peut manquer est jugée par la base, en une requête.
 *
 * Le solde était lu puis débité, le stock lu puis remplacé, l'échéance VIP lue puis réécrite : deux
 * demandes simultanées passaient toutes les deux (2026-10-04). Ces épreuves font tourner les vraies
 * méthodes de Gamification sur une base factice qui note les requêtes : elles disent QUELLE requête
 * part, et ce qu'en fait le code selon la réponse de la base.
 */
final class AchatsAtomiquesTest extends TestCase
{
	private const RACINE = __DIR__.'/../..';

	private function gamification(BaseFactice $db): Gamification
	{
		$g = (new \ReflectionClass(Gamification::class))->newInstanceWithoutConstructor();
		$g->db = $db; // propriété dynamique : `$this->db` ne passe plus par le cœur

		return $g;
	}

	public function test_la_depense_est_verifiee_et_debitee_par_la_meme_requete(): void
	{
		$db = new BaseFactice(1);

		self::assertTrue($this->gamification($db)->spend_points(7, 40, 'objet'));

		self::assertSame([['user_id', 7], ['total >=', 40]], $db->conditions[0], 'le solde est vérifié PAR la requête de débit');
		self::assertSame(['nf_user_points', 'total = total - 40, spent = spent + 40'], $db->mises_a_jour[0]);
		self::assertSame('nf_points_log', $db->insertions[0][0]);
		self::assertSame(-40, $db->insertions[0][1]['amount'], 'le journal note la dépense');
	}

	public function test_un_solde_insuffisant_ne_debite_ni_ne_journalise(): void
	{
		$db = new BaseFactice(0); // la base n'a touché aucune ligne : `total >= 40` était faux

		self::assertFalse($this->gamification($db)->spend_points(7, 40, 'objet'));
		self::assertSame([], $db->insertions);
	}

	public function test_une_depense_nulle_ou_sans_membre_est_refusee_sans_requete(): void
	{
		$db = new BaseFactice(1);

		self::assertFalse($this->gamification($db)->spend_points(7, 0));
		self::assertFalse($this->gamification($db)->spend_points(0, 10));
		self::assertSame([], $db->mises_a_jour);
	}

	public function test_un_credit_s_ajoute_en_une_requete(): void
	{
		$db = new BaseFactice(1);

		$this->gamification($db)->add_points(7, 25, 'stripe', 'Recharge');

		self::assertCount(1, $db->executions);
		self::assertStringStartsWith('INSERT INTO nf_user_points (user_id, total, earned, spent) VALUES (7, 25, 25, 0)', $db->executions[0]);
		self::assertStringContainsString('ON DUPLICATE KEY UPDATE total = GREATEST(0, total + (25)), earned = earned + 25, spent = spent + 0', $db->executions[0]);
		self::assertSame([], $db->mises_a_jour, 'plus de lecture puis réécriture');
	}

	public function test_un_debit_brut_ne_descend_pas_sous_zero(): void
	{
		$db = new BaseFactice(1);

		$this->gamification($db)->add_points(7, -3, 'spend');

		self::assertStringContainsString('VALUES (7, 0, 0, 3)', $db->executions[0]);
		self::assertStringContainsString('total = GREATEST(0, total + (-3)), earned = earned + 0, spent = spent + 3', $db->executions[0]);
	}

	public function test_le_vip_se_prolonge_en_une_requete(): void
	{
		$db = new BaseFactice(1);

		$this->gamification($db)->grant_vip(7, 30, "bou'tique");

		self::assertCount(1, $db->executions);
		self::assertStringStartsWith('INSERT INTO nf_vip (user_id, expires_at, source) VALUES (7, ', $db->executions[0]);
		self::assertMatchesRegularExpression("/ON DUPLICATE KEY UPDATE expires_at = IF\(expires_at > '\d{4}-\d\d-\d\d \d\d:\d\d:\d\d', expires_at \+ INTERVAL 30 DAY, '\d{4}-\d\d-\d\d \d\d:\d\d:\d\d'\)/", $db->executions[0]);
		self::assertStringContainsString("source = 'bou\\'tique'", $db->executions[0], 'la source est échappée');
	}

	public function test_l_achat_de_la_boutique(): void
	{
		$ajax = (string) file_get_contents(self::RACINE.'/modules/shop/controllers/ajax.php');
		$vue  = (string) file_get_contents(self::RACINE.'/modules/shop/views/index.tpl.php');

		// Le jeton, vérifié avant toute lecture de l'objet.
		self::assertLessThan(strpos($ajax, "from('nf_shop_items')"), strpos($ajax, '$this->csrf_valide()'));
		self::assertStringContainsString("'?_='.rawurlencode((string) (\$jeton ?? ''))", $vue, 'la page pose le jeton dans l\'adresse du bouton');

		// Une transaction, et le stock décrémenté par la base, jamais sous zéro.
		self::assertStringContainsString('$this->db->transaction();', $ajax);
		self::assertStringContainsString('$this->db->commit();', $ajax);
		self::assertStringContainsString("->where('stock >', 0)->update('nf_shop_items', 'stock = stock - 1')", $ajax);
		self::assertStringNotContainsString("['stock' => (int)\$item['stock'] - 1]", $ajax, 'plus de lecture puis réécriture du stock');

		// La possession est relue APRÈS le débit, qui verrouille la ligne du membre.
		self::assertLessThan(strpos($ajax, "from('nf_shop_purchases')"), strpos($ajax, '->spend_points('));
	}

	public function test_la_session_de_paiement_exige_le_meme_jeton(): void
	{
		$ajax = (string) file_get_contents(self::RACINE.'/modules/payments/controllers/ajax.php');
		$vue  = (string) file_get_contents(self::RACINE.'/modules/payments/views/index.tpl.php');

		self::assertStringContainsString('$this->csrf_valide()', $ajax);
		self::assertStringContainsString("'?_='.rawurlencode((string) (\$jeton ?? ''))", $vue);
	}
}

/** Une base factice : elle note les requêtes, et répond le nombre de lignes qu'on lui dit. */
final class BaseFactice
{
	/** @var list<list<array{0: string, 1: mixed}>> */
	public array $conditions = [];

	/** @var list<array{0: string, 1: mixed}> */
	public array $mises_a_jour = [];

	/** @var list<array{0: string, 1: array<string, mixed>}> */
	public array $insertions = [];

	/** @var list<string> */
	public array $executions = [];

	/** @var list<array{0: string, 1: mixed}> */
	private array $en_cours = [];

	public function __construct(private int $lignes)
	{
	}

	public function where(string $nom, mixed $valeur = NULL): self
	{
		$this->en_cours[] = [$nom, $valeur];

		return $this;
	}

	public function update(string $table, mixed $donnees): int
	{
		$this->conditions[]   = $this->en_cours;
		$this->en_cours       = [];
		$this->mises_a_jour[] = [$table, $donnees];

		return $this->lignes;
	}

	/** @param array<string, mixed> $donnees */
	public function insert(string $table, array $donnees): int
	{
		$this->insertions[] = [$table, $donnees];

		return 1;
	}

	public function execute(string $requete): self
	{
		$this->executions[] = $requete;

		return $this;
	}

	public function escape_string(string $texte): string
	{
		return addslashes($texte);
	}

	/** La relecture du solde (`get_points()`) : elle n'écrit rien, elle rend zéro. */
	public function select(string ...$colonnes): self
	{
		return $this;
	}

	public function from(string $table): self
	{
		$this->en_cours = [];

		return $this;
	}

	public function row(): int
	{
		return 0;
	}
}
