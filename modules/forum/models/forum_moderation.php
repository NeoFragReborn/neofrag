<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum\Models;

/** Modération avancée (split / merge / trash, Phase 7-bis) — extrait du modèle Forum. */
trait Forum_Moderation
{
	public function split_topic($source_topic_id, array $message_ids, $new_title)
	{
		$message_ids = array_filter(array_map('intval', $message_ids));
		if (empty($message_ids))
		{
			return FALSE;
		}

		$source = $this->db	->select('forum_id', 'message_id as starter_id')
							->from('nf_forum_topics')
							->where('topic_id', (int)$source_topic_id)
							->row();
		if (!$source)
		{
			return FALSE;
		}

		// Sécurité : on ne permet pas de split le starter (il deviendrait orphelin de topic)
		$message_ids = array_diff($message_ids, [(int)$source['starter_id']]);
		if (empty($message_ids))
		{
			return FALSE;
		}

		// Le nouveau starter du new topic = le plus ancien message déplacé
		sort($message_ids);
		$new_starter_id = (int)$message_ids[0];

		$this->db->transaction();

		try
		{
			// Crée le nouveau topic
			$new_topic_id = $this->db->ignore_foreign_keys()->insert('nf_forum_topics', [
				'forum_id'   => (int)$source['forum_id'],
				'message_id' => $new_starter_id,
				'status'     => '0'
			]);

			// Title du nouveau topic
			$this->db	->where('topic_id', $new_topic_id)
						->update('nf_forum_topics', ['title' => (string)$new_title]);

			// Déplace les messages sélectionnés vers le nouveau topic
			$ids_csv = implode(',', $message_ids);
			$this->db	->where('message_id IN ('.$ids_csv.')')
						->update('nf_forum_messages', ['topic_id' => $new_topic_id]);

			// Recompte les counts
			$source_count = $this->db->from('nf_forum_messages')->where('topic_id', (int)$source_topic_id)->count() - 1;
			$new_count    = $this->db->from('nf_forum_messages')->where('topic_id', $new_topic_id)->count() - 1;

			$source_last = $this->db->select('message_id')->from('nf_forum_messages')->where('topic_id', (int)$source_topic_id)->order_by('message_id DESC')->row();
			$new_last    = $this->db->select('message_id')->from('nf_forum_messages')->where('topic_id', $new_topic_id)->order_by('message_id DESC')->row();

			$this->db	->where('topic_id', (int)$source_topic_id)
						->update('nf_forum_topics', [
							'count_messages'  => max(0, $source_count),
							'last_message_id' => $source_last ?: NULL
						]);

			$this->db	->where('topic_id', $new_topic_id)
						->update('nf_forum_topics', [
							'count_messages'  => max(0, $new_count),
							'last_message_id' => $new_last ?: NULL
						]);

			// Update forum count_topics
			$this->db	->where('forum_id', (int)$source['forum_id'])
						->update('nf_forum', 'count_topics = count_topics + 1');

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		$this->events->fire('forum.topic.split', [
			'source_topic_id' => (int)$source_topic_id,
			'new_topic_id'    => (int)$new_topic_id,
			'message_ids'     => $message_ids,
			'forum_id'        => (int)$source['forum_id']
		]);

		return $new_topic_id;
	}

	public function merge_topics($source_topic_id, $target_topic_id)
	{
		if ((int)$source_topic_id === (int)$target_topic_id)
		{
			return FALSE;
		}

		$source = $this->db	->select('forum_id', 'count_messages')
							->from('nf_forum_topics')
							->where('topic_id', (int)$source_topic_id)
							->row();
		$target = $this->db	->select('forum_id', 'count_messages')
							->from('nf_forum_topics')
							->where('topic_id', (int)$target_topic_id)
							->row();

		if (!$source || !$target)
		{
			return FALSE;
		}

		$this->db->transaction();

		try
		{
			// Déplacer tous les messages du source vers le target
			$this->db	->where('topic_id', (int)$source_topic_id)
						->update('nf_forum_messages', ['topic_id' => (int)$target_topic_id]);

			// Supprimer le source topic (mais garder ses messages déjà déplacés)
			// On set message_id et last_message_id NULL avant pour éviter FK violation
			$this->db	->where('topic_id', (int)$source_topic_id)
						->update('nf_forum_topics', ['message_id' => NULL, 'last_message_id' => NULL]);
			$this->db	->where('topic_id', (int)$source_topic_id)
						->delete('nf_forum_topics');

			// Recompte target
			$target_count = $this->db->from('nf_forum_messages')->where('topic_id', (int)$target_topic_id)->count() - 1;
			$target_last  = $this->db->select('message_id')->from('nf_forum_messages')->where('topic_id', (int)$target_topic_id)->order_by('message_id DESC')->row();

			$this->db	->where('topic_id', (int)$target_topic_id)
						->update('nf_forum_topics', [
							'count_messages'  => max(0, $target_count),
							'last_message_id' => $target_last ?: NULL
						]);

			// Update forum count_topics (-1 car source disparu)
			$this->db	->where('forum_id', (int)$source['forum_id'])
						->update('nf_forum', 'count_topics = GREATEST(count_topics - 1, 0)');

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		$this->events->fire('forum.topics.merged', [
			'source_topic_id' => (int)$source_topic_id,
			'target_topic_id' => (int)$target_topic_id,
			'forum_id'        => (int)$target['forum_id']
		]);

		return TRUE;
	}

	public function get_trashed_messages($limit = 100)
	{
		return $this->db->select(	'm.message_id',
									'm.topic_id',
									'm.user_id',
									'm.deleted_at',
									'm.deleted_by',
									'm.deleted_reason',
									't.title as topic_title',
									't.forum_id',
									$this->titre_forum('f').' AS forum_title',
									'u.username',
									'ud.username as deleter_username'
								)
						->from('nf_forum_messages m')
						->join('nf_forum_topics t', 't.topic_id = m.topic_id')
						->join('nf_forum f',        'f.forum_id = t.forum_id')
						->join('nf_user u',         'u.id = m.user_id')
						->join('nf_user ud',        'ud.id = m.deleted_by')
						->where('m.deleted_at IS NOT NULL')
						->order_by('m.deleted_at DESC')
						->limit((int)$limit)
						->get();
	}

	public function restore_message($message_id)
	{
		$msg = $this->db	->select('topic_id', 'message_id')
							->from('nf_forum_messages')
							->where('message_id', (int)$message_id)
							->where('deleted_at IS NOT NULL')
							->row();

		if (!$msg)
		{
			return FALSE;
		}

		// On ne restaure pas le message text (NULL) car on l'a perdu au soft-delete legacy
		// Mais on enlève les flags deleted_*
		$this->db	->where('message_id', (int)$message_id)
					->update('nf_forum_messages', 'deleted_at = NULL, deleted_by = NULL, deleted_reason = NULL');

		return TRUE;
	}

	public function hard_delete_message($message_id)
	{
		$this->db->transaction();

		try
		{
			$msg = $this->db	->select('topic_id')
								->from('nf_forum_messages')
								->where('message_id', (int)$message_id)
								->row();

			if (!$msg)
			{
				$this->db->rollback();
				return FALSE;
			}

			$this->db	->where('message_id', (int)$message_id)
						->delete('nf_forum_messages');

			// Update topic count
			$count = $this->db->from('nf_forum_messages')->where('topic_id', (int)$msg)->count() - 1;
			$last  = $this->db->select('message_id')->from('nf_forum_messages')->where('topic_id', (int)$msg)->order_by('message_id DESC')->row();

			$this->db	->where('topic_id', (int)$msg)
						->update('nf_forum_topics', [
							'count_messages'  => max(0, $count),
							'last_message_id' => $last ?: NULL
						]);

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		return TRUE;
	}
}
