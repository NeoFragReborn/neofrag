<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum\Models;

/**
 * Côté BASE du threading : lire les messages d'un sujet, et valider qu'une réponse peut
 * s'accrocher à son parent — extrait du modèle Forum.
 *
 * Le CALCUL des profondeurs, lui, est pur et vit dans `Lib\Forum_Threading`. D'où le suffixe
 * `_Db` : les deux traitent du même sujet, l'un parle à la base, l'autre non, et les confondre
 * ferait retomber la logique pure dans ce qui n'est testable qu'avec une base.
 */
trait Forum_Threading_Db
{
	private function _validate_parent($parent_id, $topic_id)
	{
		if (!$parent_id)
		{
			return NULL;
		}

		$parent = $this->db	->select('topic_id', 'parent_id')
							->from('nf_forum_messages')
							->where('message_id', (int)$parent_id)
							->row();

		// Le parent doit appartenir au même topic
		if (!$parent || (int)$parent['topic_id'] !== (int)$topic_id)
		{
			return NULL;
		}

		// Max depth depuis settings (default 3 niveaux)
		$max_depth = isset($this->config->forum_threading_max_depth)
				? max(1, (int)$this->config->forum_threading_max_depth)
				: 3;

		// Calcule la depth actuelle du parent en remontant la chain
		$depth = 1;
		$current = $parent;
		while (!empty($current['parent_id']) && $depth < $max_depth + 5)
		{
			// `row(FALSE)` garde la LIGNE : à une colonne, `row()` rendrait l'entier seul, et
			// `$current['parent_id']` vaudrait NULL au tour suivant — la remontée s'arrêtait après un
			// niveau, et la profondeur maximale des réponses n'était jamais atteinte.
			$current = $this->db	->select('parent_id')
									->from('nf_forum_messages')
									->where('message_id', (int)$current['parent_id'])
									->row(FALSE);
			if (!$current) break;
			$depth++;
		}

		if (!\NF\Modules\Forum\Lib\Forum_Threading::parent_depth_allows_reply($depth, $max_depth))
		{
			return NULL;
		}

		return (int)$parent_id;
	}

	public function get_messages_with_threading($topic_id)
	{
		// Retourne les messages avec leur depth pré-calculée pour l'affichage
		$messages = $this->db	->select('message_id', 'parent_id', 'user_id', 'message', 'UNIX_TIMESTAMP(date) as date')
								->from('nf_forum_messages')
								->where('topic_id', (int)$topic_id)
								->order_by('message_id')
								->get();

		return \NF\Modules\Forum\Lib\Forum_Threading::assign_depths($messages);
	}
}
