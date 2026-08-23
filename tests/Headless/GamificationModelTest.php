<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Premier test d'OBJET réel (pas un miroir SQL) : on instancie le VRAI module Gamification
 * bootstrapé headless et on exerce recompute() — le scoring karma effectif (réactions reçues
 * + contenu publié + ancienneté × poids), exécuté par le code de prod contre la DB de test.
 */
final class GamificationModelTest extends HeadlessTestCase
{
	private function gamification(): \NF\Modules\Gamification\Gamification
	{
		// Pas de loader (qui appellerait __init → $this->url) : instanciation directe. recompute()
		// ne touche que $this->db / $this->config, résolus via le singleton global booté.
		return new \NF\Modules\Gamification\Gamification(\NeoFrag());
	}

	public function test_recompute_scores_and_persists_with_default_weights(): void
	{
		$owner   = $this->createUser('owner');
		$reactor = $this->createUser('reactor');

		// owner publie 2 commentaires ; l'un d'eux reçoit 1 réaction (de reactor).
		$c1 = (int) $this->db()->insert('nf_comment', ['user_id' => $owner, 'module_id' => 1, 'module' => 'comments', 'content' => 'a']);
		$this->db()->insert('nf_comment', ['user_id' => $owner, 'module_id' => 1, 'module' => 'comments', 'content' => 'b']);
		$this->db()->insert('nf_reactions', ['user_id' => $reactor, 'content_type' => 'comment', 'content_id' => $c1]);

		// Ancienneté = 0 mois (registration_date = maintenant).
		$this->db()->where('id', $owner)->update('nf_user', ['registration_date' => date('Y-m-d H:i:s')]);

		$score = $this->gamification()->recompute($owner);

		// Poids défaut (gam_karma_* non seedés → cfg() retombe sur 5/1/2) : received=1, content=2, months=0.
		$this->assertSame(1 * 5 + 2 * 1 + 0 * 2, $score, 'recompute() applique le barème réel sur les compteurs réels.');

		// Le score ET les compteurs sont persistés dans nf_karma.
		$row = $this->db()->select('score', 'reactions_received', 'content_count')->from('nf_karma')->where('user_id', $owner)->row(FALSE);
		$this->assertSame(7, (int) $row['score']);
		$this->assertSame(1, (int) $row['reactions_received']);
		$this->assertSame(2, (int) $row['content_count']);
	}

	/**
	 * earn() crédite le gain PAR DÉFAUT du barème quand aucun gam_pt_* n'est configuré.
	 * Régression du bug cfg() (Config::__get → FALSE → (int)0) qui faisait gagner 0 point par
	 * défaut : sans le correctif, earn() court-circuite sur `$amount <= 0` et ne crédite rien.
	 */
	public function test_earn_awards_default_points_when_config_unset(): void
	{
		$uid = $this->createUser('earner');

		$credited = $this->gamification()->earn($uid, 'comment');

		$this->assertSame(5, $credited, 'earn(comment) crédite le gain par défaut (5), pas 0.');
		$this->assertSame(5, $this->gamification()->get_points($uid), 'Le solde reflète le crédit.');
	}

	public function test_earn_is_capped_per_day(): void
	{
		$uid = $this->createUser('capper');
		$g   = $this->gamification();

		// comment = 5 pts/action, plafond 50/jour (défauts) → 10 gains plafonnent, le 11e crédite 0.
		$last = 0;
		for ($i = 0; $i < 12; $i++)
		{
			$last = $g->earn($uid, 'comment');
		}

		$this->assertSame(0, $last, 'Au-delà du plafond quotidien, earn() crédite 0.');
		$this->assertSame(50, $g->get_points($uid), 'Le solde est plafonné au cap journalier (50).');
	}

	public function test_earn_unknown_action_credits_nothing(): void
	{
		$uid = $this->createUser('noop');
		$this->assertSame(0, $this->gamification()->earn($uid, 'does_not_exist'));
		$this->assertSame(0, $this->gamification()->get_points($uid));
	}

	public function test_add_points_bookkeeping_total_earned_spent(): void
	{
		$uid = $this->createUser('book');
		$g   = $this->gamification();

		$g->add_points($uid, 10, 'test');
		$g->add_points($uid, -3, 'spend');

		$row = $this->db()->select('total', 'earned', 'spent')->from('nf_user_points')->where('user_id', $uid)->row(FALSE);
		$this->assertSame(7, (int) $row['total'],  'total = crédits - débits');
		$this->assertSame(10, (int) $row['earned'], 'earned cumule les crédits');
		$this->assertSame(3, (int) $row['spent'],  'spent cumule les débits');
	}

	public function test_spend_points_refused_when_insufficient(): void
	{
		$uid = $this->createUser('spender');
		$g   = $this->gamification();

		$g->add_points($uid, 20, 'seed');

		$this->assertFalse($g->spend_points($uid, 50), 'Dépense refusée si solde insuffisant.');
		$this->assertSame(20, $g->get_points($uid), 'Solde inchangé après refus.');

		$this->assertTrue($g->spend_points($uid, 15), 'Dépense acceptée si solde suffisant.');
		$this->assertSame(5, $g->get_points($uid), 'Solde décrémenté du montant dépensé.');
	}

	public function test_tier_thresholds(): void
	{
		$g = $this->gamification();

		$this->assertSame('Novice',  $g->tier(0)['name']);
		$this->assertSame('Novice',  $g->tier(49)['name']);
		$this->assertSame('Bronze',  $g->tier(50)['name']);   // pile au seuil
		$this->assertSame('Bronze',  $g->tier(149)['name']);
		$this->assertSame('Argent',  $g->tier(150)['name']);
		$this->assertSame('Diamant', $g->tier(2500)['name']);
		$this->assertSame('Diamant', $g->tier(99999)['name']);
	}
}
