<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum\Models;

/**
 * Pièces jointes des messages (`nf_forum_attachments` adossé à `nf_file`) et leur
 * administration — extrait du modèle Forum.
 *
 * Les RÈGLES (types autorisés, taille maximale) sont pures et vivent dans
 * `Lib\Forum_Attachment_Rules` ; ce trait n'expose que les accès en base.
 */
trait Forum_Attachments
{
	public function get_all_attachments($limit = 200)
	{
		return $this->db->select(	'a.attachment_id',
									'a.message_id',
									'a.file_id',
									'a.file_size',
									'a.mime_type',
									'f.name',
									'f.path',
									'f.date as uploaded_at',
									'm.topic_id',
									'm.user_id as uploader_id',
									'u.username as uploader_username',
									't.title as topic_title',
									't.forum_id',
									'fr.title as forum_title'
								)
						->from('nf_forum_attachments a')
						->join('nf_file f',           'f.id = a.file_id')
						->join('nf_forum_messages m', 'm.message_id = a.message_id')
						->join('nf_forum_topics t',   't.topic_id = m.topic_id')
						->join('nf_forum fr',         'fr.forum_id = t.forum_id')
						->join('nf_user u',           'u.id = m.user_id')
						->order_by('a.attachment_id DESC')
						->limit((int)$limit)
						->get();
	}

	public function get_attachments_stats()
	{
		$row = $this->db	->select('COUNT(*) as total', 'COALESCE(SUM(file_size), 0) as total_size', 'COALESCE(AVG(file_size), 0) as avg_size')
							->from('nf_forum_attachments')
							->row();
		return is_array($row) ? $row : ['total' => 0, 'total_size' => 0, 'avg_size' => 0];
	}

	public function find_orphan_attachments()
	{
		// Attachments dont le message_id n'existe plus (théoriquement impossible vu FK CASCADE,
		// mais on check au cas où).
		return $this->db->select('a.attachment_id', 'a.file_id', 'a.message_id', 'f.name', 'f.path')
						->from('nf_forum_attachments a')
						->join('nf_file f', 'f.id = a.file_id')
						->where('a.message_id NOT IN (SELECT message_id FROM nf_forum_messages)')
						->get();
	}

	public function find_orphan_files()
	{
		// Files dans nf_file qui ne sont liés à aucun forum_attachment (et qui pointent vers /upload/forum/)
		// Détection simple : tous les nf_file dont le path commence par 'upload/forum/' et qui ne sont pas dans nf_forum_attachments
		return $this->db->select('f.id as file_id', 'f.name', 'f.path', 'f.date', 'f.user_id')
						->from('nf_file f')
						->where('f.path LIKE', 'upload/forum/%')
						->where('f.id NOT IN (SELECT file_id FROM nf_forum_attachments)')
						->get();
	}

	public function attach_file($message_id, $file_id, $size, $mime)
	{
		return $this->db->insert('nf_forum_attachments', [
			'message_id' => (int)$message_id,
			'file_id'    => (int)$file_id,
			'file_size'  => (int)$size,
			'mime_type'  => $mime
		]);
	}

	public function get_attachments($message_id)
	{
		return $this->db->select(	'a.attachment_id',
									'a.file_id',
									'a.file_size',
									'a.mime_type',
									'f.name',
									'f.path',
									'f.user_id as uploader_id'
								)
						->from('nf_forum_attachments a')
						->join('nf_file f', 'f.id = a.file_id')
						->where('a.message_id', (int)$message_id)
						->order_by('a.attachment_id')
						->get();
	}

	public function get_attachments_by_topic($topic_id, $limit = NULL)
	{
		$this->db	->select(	'a.attachment_id',
								'a.file_id',
								'a.file_size',
								'a.mime_type',
								'a.message_id',
								'f.name',
								'f.path',
								'm.user_id as uploader_id',
								'u.username as uploader_username'
							)
					->from('nf_forum_attachments a')
					->join('nf_file f',           'f.id = a.file_id')
					->join('nf_forum_messages m', 'm.message_id = a.message_id')
					->join('nf_user u',           'u.id = m.user_id')
					->where('m.topic_id', (int)$topic_id)
					->order_by('a.attachment_id');

		if ($limit)
		{
			$this->db->limit((int)$limit);
		}

		return $this->db->get();
	}

	public function delete_attachment($attachment_id)
	{
		// Lock anti-delete : si l'attachment ou le message parent est référencé par un report
		// pending/reviewed, on bloque la suppression (le fichier doit rester accessible aux modos).
		// La copie défensive existe déjà mais on garde aussi l'original tant que report ouvert.
		if (isset($this->moderation) && ($locked_by = $this->moderation->is_attachment_locked('forum', (int)$attachment_id)))
		{
			return ['locked_by_report' => (int)$locked_by];
		}

		// Récupère le file_id pour pouvoir cascade-delete le nf_file aussi
		$attachment = $this->db	->select('file_id')
								->from('nf_forum_attachments')
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
						->delete('nf_forum_attachments');

			// Supprime le fichier physique + record nf_file
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

	public function get_allowed_mimes()
	{
		$configured = (isset($this->config->forum_attachments_mimes) && $this->config->forum_attachments_mimes)
				? (string)$this->config->forum_attachments_mimes
				: NULL;
		return \NF\Modules\Forum\Lib\Forum_Attachment_Rules::parse_mimes($configured);
	}

	public function get_max_size_bytes()
	{
		$kb = isset($this->config->forum_attachments_size_max_kb)
				? (int)$this->config->forum_attachments_size_max_kb
				: NULL;
		return \NF\Modules\Forum\Lib\Forum_Attachment_Rules::max_bytes($kb);
	}
}
