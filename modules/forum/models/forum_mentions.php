<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum\Models;

/**
 * Mentions @user (nf_forum_mentions, Phase 4) — extrait du modèle Forum.
 * NB : render_mentions vit dans le module class (forum.php) pour être accessible
 * depuis les vues via $this->output->module()->render_mentions(...).
 */
trait Forum_Mentions
{
	public function parse_mentions($content)
	{
		// Capture @username ou @"User Name" (avec espaces si guillemets)
		// Username NeoFrag = varchar(100), pas de regex char class restrictive sur ce fork
		// On accepte: lettres, chiffres, _, -, espaces (si entre guillemets)
		$mentions = [];

		if (preg_match_all('/(?:^|[\s\(\[\>])@(?:"([^"]+)"|([a-zA-Z0-9_\-]+))/u', $content, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$username = !empty($match[1]) ? $match[1] : $match[2];
				$mentions[$username] = TRUE;
			}
		}

		return array_keys($mentions);
	}

	public function record_mentions($message_id, $mentioner_user_id, $content)
	{
		$usernames = $this->parse_mentions($content);

		if (empty($usernames))
		{
			return [];
		}

		$users = $this->db	->select('id', 'username', 'email')
							->from('nf_user')
							->where('username', $usernames)
							->where('deleted', '0')
							->where('id !=', (int)$mentioner_user_id)
							->get();

		if (empty($users))
		{
			return [];
		}

		// Cleanup les mentions existantes pour ce message (cas edit)
		$this->db	->where('message_id', (int)$message_id)
					->delete('nf_forum_mentions');

		$mentioned = [];

		foreach ($users as $user)
		{
			$this->db->insert('nf_forum_mentions', [
				'message_id'        => (int)$message_id,
				'mentioned_user_id' => (int)$user['id'],
				'mentioner_user_id' => (int)$mentioner_user_id
			]);

			$mentioned[] = $user;
		}

		return $mentioned;
	}

	public function get_unread_mentions($user_id)
	{
		return $this->db->select(	'mn.mention_id',
									'mn.message_id',
									'mn.created_at',
									'm.topic_id',
									't.title as topic_title',
									'um.username as mentioner_username'
								)
						->from('nf_forum_mentions mn')
						->join('nf_forum_messages m', 'm.message_id = mn.message_id')
						->join('nf_forum_topics t',   't.topic_id = m.topic_id')
						->join('nf_user um',          'um.id = mn.mentioner_user_id')
						->where('mn.mentioned_user_id', (int)$user_id)
						->where('mn.read_at', NULL)
						->order_by('mn.created_at DESC')
						->get();
	}

	public function mark_mention_read($mention_id, $user_id)
	{
		$this->db	->where('mention_id', (int)$mention_id)
					->where('mentioned_user_id', (int)$user_id)
					->update('nf_forum_mentions', 'read_at = CURRENT_TIMESTAMP');
	}

	public function mark_all_mentions_read($user_id)
	{
		$this->db	->where('mentioned_user_id', (int)$user_id)
					->where('read_at', NULL)
					->update('nf_forum_mentions', 'read_at = CURRENT_TIMESTAMP');
	}
}
