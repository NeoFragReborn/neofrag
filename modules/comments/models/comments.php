<?php
/**
 * https://neofr.ag
 * Modèle principal du module commentaires : logique de suppression réversible
 * (soft-delete), restauration et purge définitive — appelée par les contrôleurs
 * (ajax, admin) et par la corbeille (module trash). Isolée ici pour être testable.
 */

namespace NF\Modules\Comments\Models;

use NF\NeoFrag\Loadables\Model;

class Comments extends Model
{
	/** Envoie un commentaire à la corbeille (contenu conservé → restaurable). */
	public function soft_delete($comment_id, $deleted_by = NULL)
	{
		$this->db	->where('id', (int)$comment_id)
					->update('nf_comment', [
						'deleted_at' => date('Y-m-d H:i:s'),
						'deleted_by' => $deleted_by !== NULL ? (int)$deleted_by : NULL
					]);
	}

	/** Restaure un commentaire depuis la corbeille. */
	public function restore_comment($comment_id)
	{
		$this->db	->where('id', (int)$comment_id)
					->update('nf_comment', 'deleted_at = NULL, deleted_by = NULL');
	}

	/** Purge : suppression définitive d'un commentaire et de ses dépendances. */
	public function purge_comment($comment_id)
	{
		$comment_id = (int)$comment_id;

		// Promeut les réponses à la racine : évite que le ON DELETE CASCADE de
		// parent_id n'emporte des réponses encore vivantes avec le commentaire purgé.
		$this->db->where('parent_id', $comment_id)->update('nf_comment', 'parent_id = NULL');

		$this->db	->where('content_type', 'comment')
					->where('content_id', $comment_id)
					->delete('nf_reactions');

		$this->db->where('id', $comment_id)->delete('nf_comment');
	}

	/**
	 * Suppression réelle de TOUS les commentaires d'un contenu — appelée quand le
	 * contenu parent lui-même est purgé (purge_news/article/gallery).
	 */
	public function delete_all($module, $module_id)
	{
		$module_id = (int)$module_id;

		$ids = array_column(
			$this->db->select('id')->from('nf_comment')->where('module', $module)->where('module_id', $module_id)->get(FALSE),
			'id'
		);

		if ($ids)
		{
			$this->db->where('content_type', 'comment')->where('content_id', $ids)->delete('nf_reactions');
			$this->db->where('module', $module)->where('module_id', $module_id)->delete('nf_comment');
		}
	}

	/** Nombre de commentaires visibles (hors corbeille) d'un contenu. */
	public function count_live($module, $module_id)
	{
		$row = $this->db	->select('COUNT(*) AS n')
							->from('nf_comment')
							->where('module', $module)
							->where('module_id', (int)$module_id)
							->where('deleted_at IS NULL')
							->row(FALSE);

		return (int)($row['n'] ?? 0);
	}
}
