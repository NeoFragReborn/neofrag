<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Module Talks — model unifié (Phase 2 refonte 2026-05-04)
 * Supporte 3 types de conversations : public, group, direct.
 */

namespace NF\Modules\Talks\Models;

use NF\NeoFrag\Loadables\Model;

class Talks extends Model
{
	// =================================================================
	// Legacy methods (widget chatbox compat)
	// =================================================================

	public function get_messages($talks_id, $message_id = 0, $limit = FALSE)
	{
		$this->db	->select('m.message_id', 'm.talk_id', 'u.id as user_id', 'm.message', 'm.date', 'u.username', 'up.avatar', 'up.sex')
					->from('nf_talks_messages m')
					->join('nf_user         u',  'u.id = m.user_id AND u.deleted = "0"')
					->join('nf_user_profile up', 'u.id = up.id');

		if ($message_id && !$limit)
		{
			$this->db->where('message_id >=', $message_id);
		}
		else if ($message_id && $limit)
		{
			$this->db	->where('message_id <', $message_id)
						->limit(10);
		}
		else
		{
			$this->db->limit(10);
		}

		return $this->db	->where('talk_id', $talks_id)
							->where('m.deleted_at', NULL)
							->order_by('m.message_id DESC')
							->get();
	}

	public function get_talks()
	{
		// Privacy : la liste admin ne doit jamais exposer les conversations privées (direct/group).
		// Seuls les salons publics sont visibles/éditables depuis le panel admin.
		// Les conversations privées sont auto-configurées (permissions implicites = participants only)
		// et n'apparaissent ici qu'en cas de signalement, via un futur module signalement unifié.
		return $this->db->select('talk_id', 'name')
						->from('nf_talks')
						->where('deleted_at', NULL)
						->where('type', 'public')
						->order_by('name')
						->get();
	}

	public function check_talk($talk_id, $title)
	{
		$talk = $this->db	->select('talk_id', 'name', 'type', 'creator_id', 'description')
							->from('nf_talks')
							->where('talk_id', (int)$talk_id)
							->where('deleted_at', NULL)
							->row();

		if ($talk && $title == \url_title($talk['name']))
		{
			return $talk;
		}
		return FALSE;
	}

	public function add_talk($title)
	{
		$talk_id = $this->db->insert('nf_talks', [
			'name' => $title,
			'type' => 'public'
		]);
		$this->access->init('talks', 'talks', $talk_id);
		return $talk_id;
	}

	public function edit_talk($talk_id, $title)
	{
		$this->db	->where('talk_id', (int)$talk_id)
					->update('nf_talks', [
						'name' => $title
					]);
	}

	public function delete_talk($talk_id)
	{
		// Soft-delete au lieu de hard
		$this->db	->where('talk_id', (int)$talk_id)
					->update('nf_talks', 'deleted_at = CURRENT_TIMESTAMP');
		// L'ACL legacy reste mais ignorée (filtrée via deleted_at)
	}

	// =================================================================
	// Phase 2 — Unified conversations
	// =================================================================

	public function get_unread_count($user_id = NULL)
	{
		// Retourne le nombre TOTAL de messages non lus pour cet user, tous talks privés (direct+group) confondus.
		// Utilisé par le widget Espace membre, le sidebar et les badges.
		if (!$user_id)
		{
			return 0;
		}

		$row = $this->db->select('SUM(unread) AS total')
						->from('('
							.'SELECT (SELECT COUNT(*) FROM nf_talks_messages m WHERE m.talk_id = t.talk_id AND m.deleted_at IS NULL AND m.user_id != '.(int)$user_id.' AND (p.last_read_at IS NULL OR m.date > p.last_read_at)) AS unread '
							.'FROM nf_talks t '
							.'JOIN nf_talks_participants p ON p.talk_id = t.talk_id '
							.'WHERE p.user_id = '.(int)$user_id.' '
							.'AND p.archived_at IS NULL '
							.'AND p.deleted_at IS NULL '
							.'AND t.deleted_at IS NULL '
							.'AND t.type IN ("direct", "group")'
						.') sub')
						->row(FALSE);

		return is_array($row) ? (int)($row['total'] ?? 0) : (int)$row;
	}

	public function get_my_conversations($user_id)
	{
		// Retourne les talks (group, direct, public) où je suis participant ni archivé ni supprimé.
		return $this->db->select(
								't.talk_id', 't.name', 't.type', 't.description', 't.creator_id',
								't.created_at', 't.updated_at',
								'p.role', 'p.last_read_at', 'p.notify_email',
								'(SELECT COUNT(*) FROM nf_talks_messages m WHERE m.talk_id = t.talk_id AND m.deleted_at IS NULL AND (p.last_read_at IS NULL OR m.date > p.last_read_at)) as unread_count',
								'(SELECT COUNT(*) FROM nf_talks_participants p2 WHERE p2.talk_id = t.talk_id AND p2.archived_at IS NULL AND p2.deleted_at IS NULL) as participants_count',
								'(SELECT MAX(date) FROM nf_talks_messages m WHERE m.talk_id = t.talk_id AND m.deleted_at IS NULL) as last_message_date'
							)
						->from('nf_talks t')
						->join('nf_talks_participants p', 'p.talk_id = t.talk_id')
						->where('p.user_id', (int)$user_id)
						->where('p.archived_at', NULL)
						->where('p.deleted_at', NULL)
						->where('t.deleted_at', NULL)
						->order_by('last_message_date DESC, t.updated_at DESC')
						->get();
	}

	public function get_archived_conversations($user_id)
	{
		// Conversations archivées par l'user (volontairement masquées de la liste principale).
		// Exclut les supprimées (deleted_at NOT NULL) qui ont leur propre cycle (rétention 14j).
		return $this->db->select(
								't.talk_id', 't.name', 't.type', 't.description', 'p.archived_at',
								'(SELECT COUNT(*) FROM nf_talks_messages m WHERE m.talk_id = t.talk_id AND m.deleted_at IS NULL) as messages_count',
								'(SELECT COUNT(*) FROM nf_talks_participants p2 WHERE p2.talk_id = t.talk_id AND p2.archived_at IS NULL AND p2.deleted_at IS NULL) as participants_count',
								'(SELECT MAX(date) FROM nf_talks_messages m WHERE m.talk_id = t.talk_id AND m.deleted_at IS NULL) as last_message_date'
							)
						->from('nf_talks t')
						->join('nf_talks_participants p', 'p.talk_id = t.talk_id')
						->where('p.user_id', (int)$user_id)
						->where('p.archived_at !=', NULL)
						->where('p.deleted_at', NULL)
						->where('t.deleted_at', NULL)
						->order_by('p.archived_at DESC')
						->get();
	}

	public function get_public_channels($user_id = NULL, $is_admin = FALSE)
	{
		// Tous les talks publics ouverts à l'audience 'all' + ceux 'staff' si user est admin.
		// IMPORTANT : tout doit être en un seul select() — un 2ème appel écrase le 1er dans le builder NeoFrag.
		$selects = [
			't.talk_id', 't.name', 't.description', 't.creator_id', 't.created_at', 't.audience',
			'(SELECT COUNT(*) FROM nf_talks_participants p2 WHERE p2.talk_id = t.talk_id AND p2.archived_at IS NULL AND p2.deleted_at IS NULL) as participants_count',
			'(SELECT COUNT(*) FROM nf_talks_messages m WHERE m.talk_id = t.talk_id AND m.deleted_at IS NULL) as messages_count'
		];
		if ($user_id)
		{
			$selects[] = '(SELECT 1 FROM nf_talks_participants p WHERE p.talk_id = t.talk_id AND p.user_id = '.(int)$user_id.' AND p.archived_at IS NULL AND p.deleted_at IS NULL) as is_joined';
		}

		$this->db->select(...$selects)
				->from('nf_talks t')
				->where('t.type', 'public')
				->where('t.deleted_at', NULL);

		if (!$is_admin)
		{
			$this->db->where('t.audience', 'all');
		}

		return $this->db->order_by('t.name')->get();
	}

	public function get_conversation($talk_id)
	{
		return $this->db->select('talk_id', 'name', 'type', 'audience', 'creator_id', 'description', 'created_at', 'updated_at')
						->from('nf_talks')
						->where('talk_id', (int)$talk_id)
						->where('deleted_at', NULL)
						->row();
	}

	public function user_can_access($talk_id, $user_id, $is_admin = FALSE)
	{
		$talk = $this->get_conversation($talk_id);
		if (!$talk)
		{
			return FALSE;
		}

		// Audience staff : seulement les admins peuvent voir
		if (!empty($talk['audience']) && $talk['audience'] === 'staff' && !$is_admin)
		{
			return FALSE;
		}

		// Public : tout le monde authentifié peut lire (sauf staff-only filtré au-dessus)
		if ($talk['type'] === 'public' && $user_id)
		{
			return $talk;
		}

		// Group / direct : doit être participant ni archivé ni supprimé
		// (un user qui a soft-delete pour lui-même perd l'accès jusqu'à restauration dans 14j)
		$is_participant = $this->db	->select('1')
									->from('nf_talks_participants')
									->where('talk_id', (int)$talk_id)
									->where('user_id', (int)$user_id)
									->where('archived_at', NULL)
									->where('deleted_at', NULL)
									->row();

		return $is_participant ? $talk : FALSE;
	}

	public function get_participants($talk_id, $include_archived = FALSE)
	{
		$this->db	->select('p.user_id', 'p.role', 'p.joined_at', 'p.last_read_at', 'p.archived_at',
							'u.username', 'up.avatar', 'up.sex')
					->from('nf_talks_participants p')
					->join('nf_user u',          'u.id = p.user_id AND u.deleted = "0"')
					->join('nf_user_profile up', 'up.id = p.user_id')
					->where('p.talk_id', (int)$talk_id)
					->order_by('p.role DESC, u.username');

		if (!$include_archived)
		{
			$this->db->where('p.archived_at', NULL);
		}

		return $this->db->get();
	}

	public function create_conversation($creator_id, $type, $name, $description = '', $participant_ids = [])
	{
		if (!in_array($type, ['public', 'group', 'direct'], TRUE))
		{
			$type = 'group';
		}

		$this->db->transaction();

		try
		{
			$talk_id = $this->db->insert('nf_talks', [
				'name'        => trim((string)$name),
				'type'        => $type,
				'creator_id'  => (int)$creator_id,
				'description' => trim((string)$description)
			]);

			// Le créateur est admin participant
			$this->db->insert('nf_talks_participants', [
				'talk_id' => $talk_id,
				'user_id' => (int)$creator_id,
				'role'    => 'admin'
			]);

			// Ajouter les participants invités
			$participant_ids = array_filter(array_unique(array_map('intval', (array)$participant_ids)), function($id) use ($creator_id){
				return $id > 0 && $id !== (int)$creator_id;
			});

			foreach ($participant_ids as $uid)
			{
				$this->db->insert('nf_talks_participants', [
					'talk_id' => $talk_id,
					'user_id' => $uid,
					'role'    => 'member'
				]);
			}

			// Init ACL legacy (read/write/delete) pour rétrocompat ACL
			$this->access->init('talks', 'talks', $talk_id);

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		$this->events->fire('talks.conversation.created', [
			'talk_id'    => $talk_id,
			'type'       => $type,
			'creator_id' => (int)$creator_id,
			'name'       => $name
		]);

		return $talk_id;
	}

	public function add_participant($talk_id, $user_id, $role = 'member')
	{
		$this->db->insert('nf_talks_participants', [
			'talk_id' => (int)$talk_id,
			'user_id' => (int)$user_id,
			'role'    => $role
		]);
		return TRUE;
	}

	public function remove_participant($talk_id, $user_id)
	{
		$this->db	->where('talk_id', (int)$talk_id)
					->where('user_id', (int)$user_id)
					->delete('nf_talks_participants');
		return TRUE;
	}

	public function archive_for_user($talk_id, $user_id)
	{
		$this->db	->where('talk_id', (int)$talk_id)
					->where('user_id', (int)$user_id)
					->update('nf_talks_participants', 'archived_at = CURRENT_TIMESTAMP');
		return TRUE;
	}

	public function unarchive_for_user($talk_id, $user_id)
	{
		$this->db	->where('talk_id', (int)$talk_id)
					->where('user_id', (int)$user_id)
					->update('nf_talks_participants', 'archived_at = NULL');
		return TRUE;
	}

	public function delete_for_user($talk_id, $user_id)
	{
		// Soft-delete user-side : la conversation reste intacte pour les autres participants.
		// Conservée 14j pour modération unilatérale (autre participant peut signaler).
		// L'user peut "restaurer" dans cette fenêtre via _restore.
		$this->db	->where('talk_id', (int)$talk_id)
					->where('user_id', (int)$user_id)
					->update('nf_talks_participants', 'deleted_at = CURRENT_TIMESTAMP, archived_at = NULL');
		return TRUE;
	}

	public function restore_for_user($talk_id, $user_id)
	{
		$this->db	->where('talk_id', (int)$talk_id)
					->where('user_id', (int)$user_id)
					->update('nf_talks_participants', 'deleted_at = NULL');
		return TRUE;
	}

	public function get_recently_deleted_conversations($user_id, $retention_days = 14)
	{
		// Conversations soft-supprimées par l'user dans les $retention_days derniers jours.
		// Affichées dans /talks/trash pour permettre la restauration unilatérale.
		// Au-delà de la rétention, un cron (futur, task #27) hard-delete le participant.
		// Cutoff calculé côté PHP pour éviter les soucis d'expression SQL brute dans where()
		$cutoff = date('Y-m-d H:i:s', strtotime('-'.(int)$retention_days.' days'));

		return $this->db->select(
								't.talk_id', 't.name', 't.type', 't.description', 'p.deleted_at',
								'(SELECT COUNT(*) FROM nf_talks_messages m WHERE m.talk_id = t.talk_id AND m.deleted_at IS NULL) as messages_count',
								'TIMESTAMPDIFF(DAY, p.deleted_at, NOW()) as days_since_delete'
							)
						->from('nf_talks t')
						->join('nf_talks_participants p', 'p.talk_id = t.talk_id')
						->where('p.user_id', (int)$user_id)
						->where('p.deleted_at !=', NULL)
						->where('p.deleted_at >', $cutoff)
						->where('t.deleted_at', NULL)
						->order_by('p.deleted_at DESC')
						->get();
	}

	public function is_participant($talk_id, $user_id)
	{
		$row = $this->db	->select('role', 'archived_at')
							->from('nf_talks_participants')
							->where('talk_id', (int)$talk_id)
							->where('user_id', (int)$user_id)
							->row();
		if (!$row || !is_array($row))
		{
			return FALSE;
		}
		return empty($row['archived_at']) ? $row['role'] : FALSE;
	}

	public function send_message($talk_id, $user_id, $content, $parent_id = NULL)
	{
		$content = trim((string)$content);
		if ($content === '')
		{
			return FALSE;
		}

		// Sécurité : on stocke en clair, sanitization à l'affichage via bbcode + htmlspecialchars
		$message_id = $this->db->insert('nf_talks_messages', [
			'talk_id'   => (int)$talk_id,
			'user_id'   => $user_id ? (int)$user_id : NULL,
			'message'   => $content,
			'parent_id' => $parent_id ? (int)$parent_id : NULL
		]);

		// Update updated_at du talk pour ranking
		$this->db	->where('talk_id', (int)$talk_id)
					->update('nf_talks', 'updated_at = CURRENT_TIMESTAMP');

		// Auto-mark sender as read
		if ($user_id)
		{
			$this->db	->where('talk_id', (int)$talk_id)
						->where('user_id', (int)$user_id)
						->update('nf_talks_participants', 'last_read_at = CURRENT_TIMESTAMP');
		}

		$this->events->fire('talks.message.created', [
			'message_id' => $message_id,
			'talk_id'    => (int)$talk_id,
			'user_id'    => $user_id ? (int)$user_id : NULL,
			'message'    => $content,
			'is_system'  => $user_id === NULL
		]);

		return $message_id;
	}

	public function send_system_message($talk_id, $content)
	{
		// Message posté par le système (user_id = NULL) — utilisé pour les auto-posts
		return $this->send_message($talk_id, NULL, $content);
	}

	public function mark_read($talk_id, $user_id)
	{
		$this->db	->where('talk_id', (int)$talk_id)
					->where('user_id', (int)$user_id)
					->update('nf_talks_participants', 'last_read_at = CURRENT_TIMESTAMP');
		return TRUE;
	}

	// =================================================================
	// Search FT (Phase T4)
	// =================================================================

	public function search_messages($query, $user_id, $talk_id = NULL, $limit = 100)
	{
		$query = trim((string)$query);
		if ($query === '' || strlen($query) < 3)
		{
			return [];
		}

		// Boolean mode pour MATCH
		$boolean = $this->_to_boolean_query($query);
		if ($boolean === '')
		{
			return [];
		}

		// Talks accessibles par cet user (participant non archivé)
		$accessible = $this->db	->select('talk_id')
								->from('nf_talks_participants')
								->where('user_id', (int)$user_id)
								->where('archived_at', NULL)
								->get();
		if (empty($accessible))
		{
			return [];
		}
		$accessible_csv = implode(',', array_map('intval', $accessible));

		$this->db->select(	'm.message_id', 'm.talk_id', 'm.user_id',
							'm.message', 'UNIX_TIMESTAMP(m.date) as date',
							't.name as talk_name', 't.type',
							'u.username',
							'MATCH(m.message) AGAINST ('.$this->_quote($boolean).' IN BOOLEAN MODE) as relevance'
						)
				 ->from('nf_talks_messages m')
				 ->join('nf_talks t', 't.talk_id = m.talk_id')
				 ->join('nf_user u',  'u.id = m.user_id', 'LEFT')
				 ->where('m.talk_id IN ('.$accessible_csv.')')
				 ->where('m.deleted_at', NULL)
				 ->where('MATCH(m.message) AGAINST ('.$this->_quote($boolean).' IN BOOLEAN MODE) > 0');

		if ($talk_id)
		{
			$this->db->where('m.talk_id', (int)$talk_id);
		}

		return $this->db->order_by('relevance DESC')->limit((int)$limit)->get();
	}

	private function _to_boolean_query($query)
	{
		$tokens = preg_split('/\s+/', $query);
		$out = [];
		foreach ($tokens as $tok)
		{
			$tok = trim($tok);
			if ($tok === '') continue;
			if ($tok[0] === '"' && substr($tok, -1) === '"')
			{
				$out[] = $tok;
			}
			else if ($tok[0] === '-' && strlen($tok) > 1)
			{
				$out[] = '-'.preg_replace('/[^\p{L}\p{N}_]/u', '', substr($tok, 1));
			}
			else
			{
				$clean = preg_replace('/[^\p{L}\p{N}_]/u', '', $tok);
				if (strlen($clean) >= 3) $out[] = '+'.$clean.'*';
			}
		}
		return implode(' ', $out);
	}

	private function _quote($s)
	{
		return "'".str_replace("'", "''", (string)$s)."'";
	}

	// =================================================================
	// Attachments (Phase T3)
	// =================================================================

	public function attach_file($message_id, $file_id, $size, $mime)
	{
		return $this->db->insert('nf_talks_attachments', [
			'message_id' => (int)$message_id,
			'file_id'    => (int)$file_id,
			'file_size'  => (int)$size,
			'mime_type'  => (string)$mime
		]);
	}

	public function get_attachments($message_id)
	{
		return $this->db->select('a.attachment_id', 'a.file_id', 'a.file_size', 'a.mime_type', 'f.name', 'f.path')
						->from('nf_talks_attachments a')
						->join('nf_file f', 'f.id = a.file_id')
						->where('a.message_id', (int)$message_id)
						->get();
	}

	public function get_attachments_for_messages($message_ids)
	{
		if (empty($message_ids))
		{
			return [];
		}

		$ids_csv = implode(',', array_map('intval', $message_ids));
		$rows = $this->db	->select('a.attachment_id', 'a.message_id', 'a.file_id', 'a.file_size', 'a.mime_type', 'f.name', 'f.path')
							->from('nf_talks_attachments a')
							->join('nf_file f', 'f.id = a.file_id')
							->where('a.message_id IN ('.$ids_csv.')')
							->get();

		// Group par message_id pour usage view
		$result = [];
		foreach ($rows as $r)
		{
			$result[(int)$r['message_id']][] = $r;
		}
		return $result;
	}

	public function get_allowed_mimes()
	{
		$default = 'image/jpeg,image/png,image/gif,image/webp,application/pdf,text/plain,application/zip';
		$value = isset($this->config->talks_attachments_mimes) && $this->config->talks_attachments_mimes
				? $this->config->talks_attachments_mimes
				: $default;
		return array_filter(array_map('trim', explode(',', $value)));
	}

	public function get_max_size_bytes()
	{
		$kb = isset($this->config->talks_attachments_size_max_kb)
				? (int)$this->config->talks_attachments_size_max_kb
				: 5120; // 5 MB default
		return $kb * 1024;
	}

	public function delete_attachment($attachment_id)
	{
		// Lock anti-delete : si l'attachment est référencé par un report pending/reviewed,
		// on bloque la suppression. Copie défensive déjà prise mais on garde aussi l'original.
		if (isset($this->moderation) && ($locked_by = $this->moderation->is_attachment_locked('talks', (int)$attachment_id)))
		{
			return ['locked_by_report' => (int)$locked_by];
		}

		$attachment = $this->db	->select('file_id')
								->from('nf_talks_attachments')
								->where('attachment_id', (int)$attachment_id)
								->row();
		if (!$attachment)
		{
			return FALSE;
		}

		$this->db->transaction();

		try
		{
			$this->db	->where('attachment_id', (int)$attachment_id)
						->delete('nf_talks_attachments');

			if ($file = $this->model2('file', $attachment))
			{
				$file->delete();
			}

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		return TRUE;
	}

	public function get_paginated_messages($talk_id, $limit = 30, $before_id = 0)
	{
		$this->db	->select(	'm.message_id', 'm.talk_id', 'm.parent_id', 'm.user_id', 'm.message',
								'UNIX_TIMESTAMP(m.date) as date', 'm.edited_at', 'm.deleted_at',
								'u.username', 'up.avatar', 'up.sex')
					->from('nf_talks_messages m')
					->join('nf_user u',          'u.id = m.user_id', 'LEFT')
					->join('nf_user_profile up', 'up.id = m.user_id', 'LEFT')
					->where('m.talk_id', (int)$talk_id)
					->order_by('m.message_id DESC')
					->limit((int)$limit);

		if ($before_id)
		{
			$this->db->where('m.message_id <', (int)$before_id);
		}

		return array_reverse($this->db->get()); // chronologique pour l'affichage
	}
}
