<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum\Models;

/** Abonnements aux sujets (nf_forum_track, Phase 3) — extrait du modèle Forum. */
trait Forum_Subscriptions
{
	public function is_subscribed($topic_id, $user_id)
	{
		return (bool)$this->db	->select('1')
								->from('nf_forum_track')
								->where('topic_id', (int)$topic_id)
								->where('user_id',  (int)$user_id)
								->where('type',     'topic')
								->row();
	}

	public function subscribe($topic_id, $user_id)
	{
		if ($this->is_subscribed($topic_id, $user_id))
		{
			return FALSE;
		}

		$this->db->insert('nf_forum_track', [
			'topic_id' => (int)$topic_id,
			'user_id'  => (int)$user_id,
			'type'     => 'topic'
		]);

		return TRUE;
	}

	public function unsubscribe($topic_id, $user_id)
	{
		$this->db	->where('topic_id', (int)$topic_id)
					->where('user_id',  (int)$user_id)
					->where('type',     'topic')
					->delete('nf_forum_track');

		return TRUE;
	}

	public function get_subscriptions($user_id)
	{
		return $this->db->select(	't.topic_id',
									't.title',
									't.forum_id',
									't.last_message_id',
									't.count_messages',
									'f.title as forum_title',
									'tr.created_at as subscribed_at',
									'tr.last_notified_at',
									'um.username as last_username',
									'm.date as last_message_date'
								)
						->from('nf_forum_track tr')
						->join('nf_forum_topics t',   't.topic_id = tr.topic_id')
						->join('nf_forum f',          'f.forum_id = t.forum_id')
						->join('nf_forum_messages m', 'm.message_id = t.last_message_id')
						->join('nf_user um',          'um.id = m.user_id AND um.deleted = "0"')
						->where('tr.user_id', (int)$user_id)
						->where('tr.type',    'topic')
						->order_by('m.date DESC')
						->get();
	}

	public function get_subscribers($topic_id, $exclude_user_id = NULL)
	{
		$this->db	->select('tr.user_id', 'u.username', 'u.email')
					->from('nf_forum_track tr')
					->join('nf_user u', 'u.id = tr.user_id AND u.deleted = "0"')
					->where('tr.topic_id', (int)$topic_id)
					->where('tr.type',     'topic')
					->where('u.email !=',  '');

		if ($exclude_user_id !== NULL)
		{
			$this->db->where('tr.user_id !=', (int)$exclude_user_id);
		}

		return $this->db->get();
	}

	public function mark_subscribers_notified($topic_id, $user_ids)
	{
		if (empty($user_ids))
		{
			return;
		}

		$this->db	->where('topic_id', (int)$topic_id)
					->where('user_id',  array_map('intval', $user_ids))
					->where('type',     'topic')
					->update('nf_forum_track', 'last_notified_at = CURRENT_TIMESTAMP');
	}

	// ── Administration ────────────────────────────────────────────────────────────
	//
	// Vue d'ensemble et désabonnement d'autorité, réservés au panneau : même sujet que
	// ci-dessus, même table, donc même fichier.

	public function get_all_subscriptions($limit = 500)
	{
		return $this->db->select(	'tr.topic_id',
									'tr.user_id',
									'tr.created_at',
									'tr.last_notified_at',
									'tr.type',
									'u.username',
									'u.email',
									't.title as topic_title',
									't.forum_id',
									'f.title as forum_title'
								)
						->from('nf_forum_track tr')
						->join('nf_user u',         'u.id = tr.user_id')
						->join('nf_forum_topics t', 't.topic_id = tr.topic_id')
						->join('nf_forum f',        'f.forum_id = t.forum_id')
						->order_by('tr.created_at DESC')
						->limit((int)$limit)
						->get();
	}

	public function admin_unsubscribe($topic_id, $user_id)
	{
		$this->db	->where('topic_id', (int)$topic_id)
					->where('user_id',  (int)$user_id)
					->where('type',     'topic')
					->delete('nf_forum_track');
		return TRUE;
	}
}
