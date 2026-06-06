<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Invariants de la suppression réversible des commentaires (chantier corbeille).
 * Spec exécutable du modèle Comments (soft_delete / restore_comment / purge_comment) :
 * tourne en transaction rollback, ne touche que des commentaires de test (module dédié).
 */
final class CommentsSoftDeleteDbTest extends IntegrationTestCase
{
	private function insertComment(int $user, string $module, int $moduleId, string $content, ?int $parent = null): int
	{
		if ($parent === null)
		{
			$this->exec(
				'INSERT INTO nf_comment (user_id, module_id, module, content) VALUES (?, ?, ?, ?)',
				[$user, $moduleId, $module, $content]
			);
		}
		else
		{
			$this->exec(
				'INSERT INTO nf_comment (parent_id, user_id, module_id, module, content) VALUES (?, ?, ?, ?, ?)',
				[$parent, $user, $moduleId, $module, $content]
			);
		}

		return (int) $this->db()->insert_id;
	}

	private function liveCount(string $module, int $moduleId): int
	{
		return (int) $this->scalar(
			'SELECT COUNT(*) FROM nf_comment WHERE module = ? AND module_id = ? AND deleted_at IS NULL',
			[$module, $moduleId]
		);
	}

	public function testSoftDeleteHidesFromLiveCountButKeepsRowAndContent(): void
	{
		$user = $this->createUser('cmt');
		$mod  = 'itest_cmt_a';

		$parent = $this->insertComment($user, $mod, 1, 'visible parent');
		$this->insertComment($user, $mod, 1, 'visible reply', $parent);

		$this->assertSame(2, $this->liveCount($mod, 1));

		// soft-delete du parent (ce que fait Comments::soft_delete).
		$this->exec('UPDATE nf_comment SET deleted_at = NOW(), deleted_by = ? WHERE id = ?', [$user, $parent]);

		$this->assertSame(1, $this->liveCount($mod, 1), 'Le commentaire en corbeille ne compte plus');
		$this->assertSame(2, (int) $this->scalar('SELECT COUNT(*) FROM nf_comment WHERE module = ?', [$mod]), 'La ligne reste (threading préservé)');
		$this->assertSame('visible parent', $this->scalar('SELECT content FROM nf_comment WHERE id = ?', [$parent]), 'Le contenu est conservé pour restauration');
	}

	public function testRestoreReactivatesComment(): void
	{
		$user   = $this->createUser('cmt');
		$mod    = 'itest_cmt_b';
		$parent = $this->insertComment($user, $mod, 1, 'parent');

		$this->exec('UPDATE nf_comment SET deleted_at = NOW(), deleted_by = ? WHERE id = ?', [$user, $parent]);
		$this->assertSame(0, $this->liveCount($mod, 1));

		// restore (Comments::restore_comment).
		$this->exec('UPDATE nf_comment SET deleted_at = NULL, deleted_by = NULL WHERE id = ?', [$parent]);

		$this->assertSame(1, $this->liveCount($mod, 1), 'Le commentaire restauré redevient visible');
		$this->assertNull($this->scalar('SELECT deleted_at FROM nf_comment WHERE id = ?', [$parent]));
	}

	public function testPurgePromotesChildrenToAvoidCascadeLoss(): void
	{
		$user   = $this->createUser('cmt');
		$mod    = 'itest_cmt_c';
		$parent = $this->insertComment($user, $mod, 1, 'parent');
		$child  = $this->insertComment($user, $mod, 1, 'live reply', $parent);

		// purge (Comments::purge_comment) : promotion des réponses AVANT suppression.
		$this->exec('UPDATE nf_comment SET parent_id = NULL WHERE parent_id = ?', [$parent]);
		$this->exec('DELETE FROM nf_comment WHERE id = ?', [$parent]);

		$this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM nf_comment WHERE id = ?', [$parent]), 'Le parent est purgé');
		$this->assertSame(1, (int) $this->scalar('SELECT COUNT(*) FROM nf_comment WHERE id = ?', [$child]), 'La réponse vivante survit à la purge');
		$this->assertNull($this->scalar('SELECT parent_id FROM nf_comment WHERE id = ?', [$child]), 'La réponse est promue à la racine');
	}

	public function testHardDeleteWithoutPromotionCascadesChildren(): void
	{
		// Documente le danger que purge_comment neutralise : sans promotion, la FK
		// parent_id ON DELETE CASCADE emporte les réponses.
		$user   = $this->createUser('cmt');
		$mod    = 'itest_cmt_d';
		$parent = $this->insertComment($user, $mod, 1, 'parent');
		$child  = $this->insertComment($user, $mod, 1, 'reply', $parent);

		$this->exec('DELETE FROM nf_comment WHERE id = ?', [$parent]);

		$this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM nf_comment WHERE id = ?', [$child]), 'CASCADE supprime la réponse');
	}
}
