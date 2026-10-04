<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * La réponse « solution » et les préfixes de sujet du forum (2026-10-01).
 *
 * Ce test épingle les règles que l'écran ne montre pas : seule une RÉPONSE de ce sujet, encore là,
 * peut résoudre le sujet ; une solution supprimée ou déplacée ne laisse pas le sujet « Résolu »,
 * et restaurée elle le redevient ; un préfixe inconnu vaut « aucun » ; supprimer un préfixe en
 * détache les sujets sans les supprimer ; le filtre par préfixe ne rend que les sujets qui le portent.
 *
 * Le lien protégé, l'encart, la marque et le filtre à l'écran ont été mesurés sur un site d'essai.
 */
final class ForumSolutionPrefixesTest extends HeadlessTestCase
{
	private ?int $categorie = NULL;
	private ?int $forum     = NULL;
	private ?int $sujet     = NULL;
	private ?int $autre     = NULL;
	private ?int $prefixe   = NULL;

	/** @var array{question: int, reponse: int, ailleurs: int} */
	private array $messages = ['question' => 0, 'reponse' => 0, 'ailleurs' => 0];

	protected function tearDown(): void
	{
		foreach ([$this->sujet, $this->autre] as $sujet)
		{
			if ($sujet !== NULL)
			{
				$this->db()->where('topic_id', $sujet)->update('nf_forum_topics', ['message_id' => NULL, 'last_message_id' => NULL]);
				$this->db()->where('topic_id', $sujet)->delete('nf_forum_messages');
				$this->db()->where('topic_id', $sujet)->delete('nf_forum_topics');
			}
		}

		if ($this->prefixe !== NULL)
		{
			$this->db()->where('prefix_id', $this->prefixe)->delete('nf_forum_prefixes');
		}

		if ($this->forum !== NULL)
		{
			$this->db()->where('forum_id', $this->forum)->delete('nf_forum');
		}

		if ($this->categorie !== NULL)
		{
			$this->db()->where('category_id', $this->categorie)->delete('nf_forum_categories');
		}

		$this->sujet = $this->autre = $this->prefixe = $this->forum = $this->categorie = NULL;

		parent::tearDown();
	}

	private function modele(): \NF\Modules\Forum\Models\Forum
	{
		$modele = \NeoFrag()->module('forum')->model();
		$this->assertInstanceOf(\NF\Modules\Forum\Models\Forum::class, $modele);

		return $modele;
	}

	private function semer(): void
	{
		$this->categorie = (int) $this->db()->insert('nf_forum_categories', ['title' => 'Aide test', 'order' => 99]);
		$this->forum     = (int) $this->db()->insert('nf_forum', ['parent_id' => $this->categorie, 'is_subforum' => '0', 'title' => 'Questions test', 'description' => '', 'order' => 99]);
		$this->sujet     = (int) $this->db()->insert('nf_forum_topics', ['forum_id' => $this->forum, 'title' => 'Ma question test']);
		$this->autre     = (int) $this->db()->insert('nf_forum_topics', ['forum_id' => $this->forum, 'title' => 'Un autre sujet test']);

		$this->messages['question'] = (int) $this->db()->insert('nf_forum_messages', ['topic_id' => $this->sujet, 'message' => 'La question']);
		$this->messages['reponse']  = (int) $this->db()->insert('nf_forum_messages', ['topic_id' => $this->sujet, 'message' => 'La réponse']);
		$this->messages['ailleurs'] = (int) $this->db()->insert('nf_forum_messages', ['topic_id' => $this->autre, 'message' => 'Hors sujet']);

		$this->db()->where('topic_id', $this->sujet)->update('nf_forum_topics', ['message_id' => $this->messages['question'], 'last_message_id' => $this->messages['reponse']]);
		$this->db()->where('topic_id', $this->autre)->update('nf_forum_topics', ['message_id' => $this->messages['ailleurs'], 'last_message_id' => $this->messages['ailleurs']]);
	}

	/** La solution telle qu'elle s'affiche (la requête de la liste et de la page du sujet). */
	private function solution_affichee(): ?int
	{
		$id = $this->db()->select($this->modele()->solution_vivante('t'))->from('nf_forum_topics t')->where('t.topic_id', $this->sujet)->row();

		return $id ? (int) $id : NULL;
	}

	public function testSeuleUneReponseDeCeSujetResoutLeSujet(): void
	{
		$this->semer();

		$this->assertFalse($this->modele()->set_solution($this->sujet, $this->messages['question']), 'la question ne se résout pas elle-même');
		$this->assertFalse($this->modele()->set_solution($this->sujet, $this->messages['ailleurs']), 'un message d\'un autre sujet');
		$this->assertNull($this->solution_affichee());

		$this->assertTrue($this->modele()->set_solution($this->sujet, $this->messages['reponse']));
		$this->assertSame($this->messages['reponse'], $this->solution_affichee());

		$this->assertTrue($this->modele()->set_solution($this->sujet, NULL), 'retirer la solution');
		$this->assertNull($this->solution_affichee());
	}

	public function testUneSolutionSupprimeeOuDeplaceeNeLaissePasLeSujetResolu(): void
	{
		$this->semer();
		$this->modele()->set_solution($this->sujet, $this->messages['reponse']);

		$this->db()->where('message_id', $this->messages['reponse'])->update('nf_forum_messages', 'message = NULL, deleted_at = CURRENT_TIMESTAMP');
		$this->assertNull($this->solution_affichee(), 'réponse mise à la corbeille');
		$this->assertFalse($this->modele()->set_solution($this->sujet, $this->messages['reponse']), 'une réponse supprimée ne se marque pas');

		$this->db()->where('message_id', $this->messages['reponse'])->update('nf_forum_messages', ['message' => 'La réponse', 'deleted_at' => NULL]);
		$this->assertSame($this->messages['reponse'], $this->solution_affichee(), 'restaurée, elle redevient la solution');

		$this->db()->where('message_id', $this->messages['reponse'])->update('nf_forum_messages', ['topic_id' => $this->autre]);
		$this->assertNull($this->solution_affichee(), 'déplacée dans un autre sujet');
	}

	public function testLesPrefixesSePosentSeFiltrentEtSeSuppriment(): void
	{
		$this->semer();
		$this->prefixe = $this->modele()->add_prefix('Question test', 'info', 99);

		$this->modele()->set_prefix($this->sujet, $this->prefixe);
		$this->modele()->set_prefix($this->autre, 999999);

		$porte = fn (int $sujet) => $this->db()->select('prefix_id')->from('nf_forum_topics')->where('topic_id', $sujet)->row();

		$this->assertSame($this->prefixe, (int) $porte($this->sujet));
		$this->assertNull($porte($this->autre), 'un préfixe inconnu vaut « aucun »');

		$filtres = array_map(static fn ($t) => (int) $t['topic_id'], (array) $this->modele()->get_topics($this->forum, $this->prefixe));
		$this->assertSame([$this->sujet], $filtres, 'le filtre ne rend que les sujets qui portent le préfixe');

		$this->modele()->delete_prefix($this->prefixe);
		$this->prefixe = NULL;

		$this->assertNull($porte($this->sujet), 'le sujet perd son préfixe');
		$this->assertSame('Ma question test', $this->db()->select('title')->from('nf_forum_topics')->where('topic_id', $this->sujet)->row(), 'et reste en place');
	}
}
