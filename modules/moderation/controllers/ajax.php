<?php
/**
 * https://neofr.ag
 * Endpoints AJAX user-side du module Modération.
 * Routes : /ajax/moderation/report-modal et /ajax/moderation/report
 */

namespace NF\Modules\Moderation\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Ajax extends Controller_Module
{
	public function _ajax_report_modal()
	{
		// Reçoit type, id, url via GET params, retourne le HTML du modal
		return $this->view('modal/report', [
			'target_type' => trim((string)($_GET['type'] ?? '')),
			'target_id'   => trim((string)($_GET['id'] ?? '')),
			'url'         => trim((string)($_GET['url'] ?? ''))
		]);
	}

	public function _ajax_report_submit()
	{
		header('Content-Type: application/json');

		$target_type = trim((string)($_POST['target_type'] ?? ''));
		$target_id   = trim((string)($_POST['target_id'] ?? ''));
		$reason      = trim((string)($_POST['reason'] ?? 'other'));
		$comment     = trim((string)($_POST['comment'] ?? ''));
		$url         = trim((string)($_POST['url'] ?? ''));

		if ($target_type === '' || $target_id === '')
		{
			echo json_encode(['ok' => FALSE, 'error' => 'missing_params']);
			exit;
		}

		// Sans URL de contexte auto OU sur profile/user, le commentaire devient obligatoire (≥ 15 chars)
		// Empêche les reports vides côté admin avec bouton "voir contexte" pointant nulle part.
		$auto_context = ($url !== '' && !in_array($target_type, ['profile', 'user'], TRUE));
		if (!$auto_context && mb_strlen($comment) < 15)
		{
			echo json_encode(['ok' => FALSE, 'error' => 'context_required']);
			exit;
		}

		$target_user_id = $this->_resolve_target_user($target_type, $target_id);
		$snapshot = $this->_capture_content_snapshot($target_type, $target_id);

		$report_id = $this->moderation->report(
			$target_type,
			$target_id,
			(int)$this->user->id,
			$reason,
			$comment,
			$url,
			$target_user_id,
			$snapshot
		);

		if ($report_id === 0)
		{
			echo json_encode(['ok' => FALSE, 'error' => 'rate_limit_or_dup']);
			exit;
		}

		// Copie défensive des pièces jointes : duplique les binaires dans backups/moderation/reports/{id}/
		// pour que les modos puissent les récupérer même si l'auteur supprime ses fichiers.
		try
		{
			$this->_backup_attachments($target_type, $target_id, (int)$report_id);
		}
		catch (\Throwable $e)
		{
			// Ne casse pas le report si le backup échoue (snapshot textuel + métadata déjà en place)
			error_log('[moderation] backup_attachments failed for report '.$report_id.': '.$e->getMessage());
		}

		echo json_encode(['ok' => TRUE, 'report_id' => $report_id]);
		exit;
	}

	/**
	 * Copie défensive : duplique les fichiers physiques attachés vers
	 * backups/moderation/reports/{report_id}/ et enregistre dans nf_reports_attachments_snapshot.
	 *
	 * Plafond global : nf_moderation_snapshot_max_size_mb (50 MB par défaut, total par report).
	 */
	private function _backup_attachments(string $target_type, string $target_id, int $report_id): void
	{
		if (empty($this->config->nf_moderation_snapshot_attachments_enabled)) return;

		// Map target_type → (table_attachments, message_id à utiliser)
		$src = $this->_resolve_attachments_source($target_type, $target_id);
		if (!$src) return;

		$rows = $this->db->select('a.attachment_id', 'a.file_id', 'a.file_size', 'a.mime_type', 'f.name', 'f.path')
			->from($src['table'].' a')
			->join('nf_file f', 'f.id = a.file_id', 'LEFT')
			->where('a.message_id', (int)$src['message_id'])
			->get();
		if (empty($rows)) return;

		$max_total_bytes = ((int)$this->config->nf_moderation_snapshot_max_size_mb ?: 50) * 1024 * 1024;
		$cumulative = 0;

		// Dossier racine du report (chemin filesystem ; les paths nf_file sont relatifs au docroot)
		$docroot  = realpath(NEOFRAG_PATH);
		$base_dir = $docroot.'/backups/moderation/reports/'.$report_id;
		if (!is_dir($base_dir))
		{
			@mkdir($base_dir, 0755, TRUE);
		}

		foreach ($rows as $r)
		{
			$src_path = $docroot.'/'.ltrim((string)($r['path'] ?? ''), '/');
			if (empty($r['path']) || !is_file($src_path) || !is_readable($src_path)) continue;

			$size = (int)$r['file_size'] ?: filesize($src_path);
			if ($cumulative + $size > $max_total_bytes) break; // quota dépassé, on stoppe

			// Hash sha256 + nom unique : {hash}_{name_sanitized}
			$hash = hash_file('sha256', $src_path);
			$safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string)$r['name']) ?: 'attachment';
			$dest_name = substr($hash, 0, 12).'_'.$safe_name;
			$dest_path = $base_dir.'/'.$dest_name;

			if (!@copy($src_path, $dest_path)) continue;
			@chmod($dest_path, 0644);

			$this->db->insert('nf_reports_attachments_snapshot', [
				'report_id'        => $report_id,
				'original_file_id' => (int)$r['file_id'],
				'original_name'    => mb_substr((string)$r['name'], 0, 255),
				'mime_type'        => mb_substr((string)$r['mime_type'], 0, 100),
				'file_size'        => $size,
				'backup_path'      => 'moderation/reports/'.$report_id.'/'.$dest_name,
				'sha256_hash'      => $hash
			]);

			$cumulative += $size;
		}
	}

	private function _resolve_attachments_source(string $target_type, string $target_id): ?array
	{
		switch ($target_type)
		{
			case 'forum_message':
				return ['table' => 'nf_forum_attachments', 'message_id' => (int)$target_id];
			case 'forum_topic':
				$row = $this->db->select('message_id')->from('nf_forum_topics')->where('topic_id', (int)$target_id)->row(FALSE);
				return is_array($row) ? ['table' => 'nf_forum_attachments', 'message_id' => (int)$row['message_id']] : NULL;
			case 'talks_message':
				return ['table' => 'nf_talks_attachments', 'message_id' => (int)$target_id];
		}
		return NULL;
	}

	private function _resolve_target_user(string $target_type, string $target_id): ?int
	{
		switch ($target_type)
		{
			case 'forum_message':
				$row = $this->db->select('user_id')->from('nf_forum_messages')->where('message_id', (int)$target_id)->row(FALSE);
				return is_array($row) ? (int)$row['user_id'] : NULL;
			case 'forum_topic':
				// nf_forum_topics n'a pas de user_id : l'auteur est sur le message_id starter
				$row = $this->db->select('m.user_id')
					->from('nf_forum_topics t')
					->join('nf_forum_messages m', 'm.message_id = t.message_id')
					->where('t.topic_id', (int)$target_id)
					->row(FALSE);
				return is_array($row) ? (int)$row['user_id'] : NULL;
			case 'talks_message':
				$row = $this->db->select('user_id')->from('nf_talks_messages')->where('message_id', (int)$target_id)->row(FALSE);
				return is_array($row) ? (int)$row['user_id'] : NULL;
			case 'comment':
				$row = $this->db->select('user_id')->from('nf_comment')->where('id', (int)$target_id)->row(FALSE);
				return is_array($row) ? (int)$row['user_id'] : NULL;
			case 'profile':
			case 'user':
				return (int)$target_id;
			case 'guestbook':
				$row = $this->db->select('user_id')->from('nf_guestbook')->where('guestbook_id', (int)$target_id)->row(FALSE);
				return is_array($row) ? (int)$row['user_id'] : NULL;
		}
		return NULL;
	}

	private function _capture_content_snapshot(string $target_type, string $target_id): ?string
	{
		if (!$this->config->nf_moderation_preserve_content_snapshot)
		{
			return NULL;
		}
		switch ($target_type)
		{
			case 'forum_message':
				$row = $this->db->select('message')->from('nf_forum_messages')->where('message_id', (int)$target_id)->row(FALSE);
				if (!is_array($row)) return NULL;
				return mb_substr((string)$row['message'].$this->_attachments_snapshot('forum', (int)$target_id), 0, 5000);
			case 'forum_topic':
				$row = $this->db->select('t.title', 'm.message', 'm.message_id')
					->from('nf_forum_topics t')
					->join('nf_forum_messages m', 'm.message_id = t.message_id')
					->where('t.topic_id', (int)$target_id)
					->row(FALSE);
				if (!is_array($row)) return NULL;
				$body = '['.((string)$row['title']).'] '.((string)$row['message']);
				return mb_substr($body.$this->_attachments_snapshot('forum', (int)$row['message_id']), 0, 5000);
			case 'talks_message':
				$row = $this->db->select('message')->from('nf_talks_messages')->where('message_id', (int)$target_id)->row(FALSE);
				if (!is_array($row)) return NULL;
				return mb_substr((string)$row['message'].$this->_attachments_snapshot('talks', (int)$target_id), 0, 5000);
			case 'comment':
				$row = $this->db->select('content')->from('nf_comment')->where('id', (int)$target_id)->row(FALSE);
				return is_array($row) ? mb_substr((string)$row['content'], 0, 5000) : NULL;
			case 'guestbook':
				$row = $this->db->select('message')->from('nf_guestbook')->where('guestbook_id', (int)$target_id)->row(FALSE);
				return is_array($row) && !empty($row['message']) ? mb_substr((string)$row['message'], 0, 5000) : NULL;
			case 'profile':
			case 'user':
				// Snapshot du profil : username + signature + quote + location au moment du report
				// (le profil est dans nf_user + nf_user_profile)
				$row = $this->db->select('u.username', 'p.signature', 'p.quote', 'p.location', 'p.website', 'p.first_name', 'p.last_name')
					->from('nf_user u')
					->join('nf_user_profile p', 'p.id = u.id', 'LEFT')
					->where('u.id', (int)$target_id)
					->row(FALSE);
				if (!is_array($row)) return NULL;
				$parts = ['@'.($row['username'] ?? '?')];
				if (!empty($row['first_name']) || !empty($row['last_name'])) $parts[] = trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? ''));
				if (!empty($row['quote']))     $parts[] = 'Quote: '.$row['quote'];
				if (!empty($row['location']))  $parts[] = 'Location: '.$row['location'];
				if (!empty($row['website']))   $parts[] = 'Website: '.$row['website'];
				if (!empty($row['signature'])) $parts[] = "\n=== Signature ===\n".$row['signature'];
				return mb_substr(implode("\n", $parts), 0, 5000);
		}
		return NULL;
	}

	/**
	 * Liste les attachments d'un message (forum/talks) au moment du report.
	 * Format texte appendé au snapshot pour traçabilité même si le user supprime
	 * ses attachments après le signalement.
	 */
	private function _attachments_snapshot(string $module, int $message_id): string
	{
		$table = $module === 'talks' ? 'nf_talks_attachments' : 'nf_forum_attachments';
		$rows = $this->db->select('a.attachment_id', 'a.file_id', 'a.file_size', 'a.mime_type', 'f.name', 'f.path')
			->from($table.' a')
			->join('nf_file f', 'f.id = a.file_id', 'LEFT')
			->where('a.message_id', $message_id)
			->get();
		if (empty($rows)) return '';
		$out = "\n\n=== Pièces jointes au moment du report ===\n";
		foreach ($rows as $r)
		{
			$size_kb = $r['file_size'] ? round($r['file_size'] / 1024, 1).' KB' : '?';
			$out .= '- '.($r['name'] ?? 'unknown').' ('.$r['mime_type'].', '.$size_kb.', file_id='.$r['file_id'].', path='.($r['path'] ?? 'N/A').")\n";
		}
		return $out;
	}
}
