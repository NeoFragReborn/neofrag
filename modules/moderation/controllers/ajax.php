<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Endpoints AJAX user-side du module Modération.
 * Routes : /ajax/moderation/report-modal et /ajax/moderation/report
 *
 * couplage(forum): les requetes sur les tables du forum ne sont atteintes que depuis les branches
 * `forum_message` / `forum_topic` du switch sur $target_type. Sans le module forum, aucun
 * signalement de ce type ne peut exister en base, donc ces branches sont inatteignables.
 * couplage(guestbook): meme raisonnement, branche `guestbook`.
 * couplage(talks): un message de la messagerie ne se signale que si la conversation est lisible par qui signale ;
 * sans le module, le contrôle refuse (aucun message n'existe).
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
		// L'adresse de contexte, envoyée par le membre et ouverte par le modérateur : celle d'une page DU SITE seulement — une
		// adresse quelconque menait le modérateur vers un faux écran de connexion (audit du 2026-10-09).
		$url         = nf_url_sure($url) && (str_starts_with($url, '/') && !str_starts_with($url, '//') || str_starts_with($url, site_origin().'/')) ? $url : '';

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

		// Un message de la messagerie ne se signale que depuis une conversation qu'on peut lire : un membre qui n'y
		// participait pas en faisait copier le texte et les pièces jointes dans la file de modération (audit du
		// 2026-10-09). Même réponse qu'un message inconnu.
		if ($target_type === 'talks_message' && !$this->_conversation_lisible((int) $target_id))
		{
			echo json_encode(['ok' => FALSE, 'error' => 'not_found']);
			exit;
		}

		// Livre d'or, image de la galerie, petite annonce, ticket : seulement ce que celui qui signale peut voir — sinon le
		// signalement copiait dans la file de modération un message en attente ou l'image d'un album fermé.
		if (!$this->_contenu_signalable($target_type, (int) $target_id))
		{
			echo json_encode(['ok' => FALSE, 'error' => 'not_found']);
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

		// Un doublon (le même membre, le même contenu, moins de 24 h) est enregistré comme tel : on le lui dit, plutôt que
		// « un modérateur va l'examiner » (audit du 2026-10-09).
		$doublon = (string) $this->db->select('status')->from('nf_reports')->where('id', (int) $report_id)->row() === 'duplicate';

		echo json_encode(['ok' => TRUE, 'report_id' => $report_id, 'duplicate' => $doublon]);
		exit;
	}

	/**
	 * Les contenus que l'on signale depuis le 2026-10-09 (livre d'or, galerie, petites annonces, tickets) : existent-ils,
	 * et celui qui signale peut-il les voir ? Les autres types gardent leurs propres gardes (TRUE ici).
	 */
	private function _contenu_signalable(string $target_type, int $target_id): bool
	{
		switch ($target_type)
		{
			case 'guestbook':
				return (string) $this->db->select('status')->from('nf_guestbook')->where('id', $target_id)->row() === 'approved';
			case 'gallery_image':
				return \NF\NeoFrag\Addons\Module::content_visible_of('gallery', $target_id);
			case 'classified':
				return $this->module('classifieds') && (string) $this->db->select('status')->from('nf_classifieds')->where('id', $target_id)->row() === 'published'; // couplage: classifieds — signaler une annonce ; refusé sans le module (_contenu_signalable)
			case 'bug_ticket':
				if (!$this->module('bugtracker')) return FALSE;
				$auteur = $this->db->select('user_id')->from('nf_bug_tickets')->where('id', $target_id)->row(FALSE); // couplage: bugtracker — signaler un ticket ; refusé sans le module (_contenu_signalable)
				return is_array($auteur) && $auteur && !in_array((int) $auteur['user_id'], $this->moderation->auteurs_masques(), TRUE);
			case 'bug_comment':
				if (!$this->module('bugtracker')) return FALSE;
				$auteur = $this->db->select('user_id')->from('nf_bug_comments')->where('id', $target_id)->row(FALSE); // couplage: bugtracker — signaler un commentaire de ticket ; refusé sans le module (_contenu_signalable)
				return is_array($auteur) && $auteur && !in_array((int) $auteur['user_id'], $this->moderation->auteurs_masques(), TRUE);
		}

		return TRUE;
	}

	/** La conversation d'un message de la messagerie, si celui qui signale peut la lire (Models\Talks::user_can_access()). */
	private function _conversation_lisible(int $message_id): bool
	{
		$talk_id = (int) $this->db->select('talk_id')->from('nf_talks_messages')->where('message_id', $message_id)->row();
		$talks   = $this->module('talks');
		$modele  = $talks ? $talks->model('talks') : NULL;

		return $talk_id > 0
			&& $modele instanceof \NF\Modules\Talks\Models\Talks
			&& (bool) $modele->user_can_access($talk_id, (int) $this->user->id, (bool) $this->access->effective_admin());
	}

	/**
	 * Copie défensive : duplique les fichiers physiques attachés vers
	 * backups/moderation/reports/{report_id}/ et enregistre dans nf_reports_attachments_snapshot.
	 *
	 * Plafond global : nf_moderation_snapshot_max_size_mb (50 MB par défaut, total par report).
	 */
	private function _backup_attachments(string $target_type, string $target_id, int $report_id): void
	{
		// Sur la démonstration, aucune copie : elles s'accumulaient sous backups/, que la remise à zéro
		// ne nettoie pas, à chaque signalement d'un visiteur (audit du 2026-10-02).
		if (nf_demo() || empty($this->config->nf_moderation_snapshot_attachments_enabled)) return;

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
		$docroot  = realpath(NEOFRAG_CMS); // NEOFRAG_PATH n'existait pas : la copie défensive levait une erreur, avalée (audit du 2026-10-09)
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
				return is_array($row) && $row ? ['table' => 'nf_forum_attachments', 'message_id' => (int)$row['message_id']] : NULL;
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
				return is_array($row) && $row ? (int)$row['user_id'] : NULL;
			case 'forum_topic':
				// nf_forum_topics n'a pas de user_id : l'auteur est sur le message_id starter
				$row = $this->db->select('m.user_id')
					->from('nf_forum_topics t')
					->join('nf_forum_messages m', 'm.message_id = t.message_id')
					->where('t.topic_id', (int)$target_id)
					->row(FALSE);
				return is_array($row) && $row ? (int)$row['user_id'] : NULL;
			case 'talks_message':
				$row = $this->db->select('user_id')->from('nf_talks_messages')->where('message_id', (int)$target_id)->row(FALSE);
				return is_array($row) && $row ? (int)$row['user_id'] : NULL;
			case 'comment':
				$row = $this->db->select('user_id')->from('nf_comment')->where('id', (int)$target_id)->row(FALSE);
				return is_array($row) && $row ? (int)$row['user_id'] : NULL;
			case 'profile':
			case 'user':
				return (int)$target_id;
			// La clé du livre d'or est `id` : `guestbook_id` n'existe pas, et la requête échouait.
			case 'guestbook':
				$row = $this->db->select('user_id')->from('nf_guestbook')->where('id', (int)$target_id)->row(FALSE);
				return is_array($row) && $row && $row['user_id'] ? (int)$row['user_id'] : NULL;
			case 'classified':
				$row = $this->db->select('user_id')->from('nf_classifieds')->where('id', (int)$target_id)->row(FALSE); // couplage: classifieds — signaler une annonce ; refusé sans le module (_contenu_signalable)
				return is_array($row) && $row && $row['user_id'] ? (int)$row['user_id'] : NULL;
			case 'bug_ticket':
				$row = $this->db->select('user_id')->from('nf_bug_tickets')->where('id', (int)$target_id)->row(FALSE); // couplage: bugtracker — signaler un ticket ; refusé sans le module (_contenu_signalable)
				return is_array($row) && $row && $row['user_id'] ? (int)$row['user_id'] : NULL;
			case 'bug_comment':
				$row = $this->db->select('user_id')->from('nf_bug_comments')->where('id', (int)$target_id)->row(FALSE); // couplage: bugtracker — signaler un commentaire de ticket ; refusé sans le module (_contenu_signalable)
				return is_array($row) && $row && $row['user_id'] ? (int)$row['user_id'] : NULL;
			// Une image de la galerie ne garde pas son auteur : le signalement se traite sans sanction directe.
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
				if (!is_array($row) || !$row) return NULL;
				return mb_substr((string)$row['message'].$this->_attachments_snapshot('forum', (int)$target_id), 0, 5000);
			case 'forum_topic':
				$row = $this->db->select('t.title', 'm.message', 'm.message_id')
					->from('nf_forum_topics t')
					->join('nf_forum_messages m', 'm.message_id = t.message_id')
					->where('t.topic_id', (int)$target_id)
					->row(FALSE);
				if (!is_array($row) || !$row) return NULL;
				$body = '['.((string)$row['title']).'] '.((string)$row['message']);
				return mb_substr($body.$this->_attachments_snapshot('forum', (int)$row['message_id']), 0, 5000);
			case 'talks_message':
				$row = $this->db->select('message')->from('nf_talks_messages')->where('message_id', (int)$target_id)->row(FALSE);
				if (!is_array($row) || !$row) return NULL;
				return mb_substr((string)$row['message'].$this->_attachments_snapshot('talks', (int)$target_id), 0, 5000);
			case 'comment':
				$row = $this->db->select('content')->from('nf_comment')->where('id', (int)$target_id)->row(FALSE);
				return is_array($row) && $row ? mb_substr((string)$row['content'], 0, 5000) : NULL;
			case 'guestbook':
				$row = $this->db->select('message')->from('nf_guestbook')->where('id', (int)$target_id)->row(FALSE);
				return is_array($row) && !empty($row['message']) ? mb_substr((string)$row['message'], 0, 5000) : NULL;
			case 'classified':
				$row = $this->db->select('title', 'description')->from('nf_classifieds')->where('id', (int)$target_id)->row(FALSE); // couplage: classifieds — signaler une annonce ; refusé sans le module (_contenu_signalable)
				return is_array($row) && $row ? mb_substr('['.$row['title'].'] '.$row['description'], 0, 5000) : NULL;
			case 'bug_ticket':
				$row = $this->db->select('title', 'description')->from('nf_bug_tickets')->where('id', (int)$target_id)->row(FALSE); // couplage: bugtracker — signaler un ticket ; refusé sans le module (_contenu_signalable)
				return is_array($row) && $row ? mb_substr('['.$row['title'].'] '.$row['description'], 0, 5000) : NULL;
			case 'bug_comment':
				$row = $this->db->select('content')->from('nf_bug_comments')->where('id', (int)$target_id)->row(FALSE); // couplage: bugtracker — signaler un commentaire de ticket ; refusé sans le module (_contenu_signalable)
				return is_array($row) && $row ? mb_substr((string) $row['content'], 0, 5000) : NULL;
			case 'gallery_image':
				$row = $this->db->select('title', 'description')->from('nf_gallery_images')->where('image_id', (int)$target_id)->row(FALSE); // couplage: gallery — signaler une image ; refusé sans le module (_contenu_signalable)
				return is_array($row) && $row ? mb_substr('['.$row['title'].'] '.$row['description'], 0, 5000) : NULL;
			case 'profile':
			case 'user':
				// Snapshot du profil : username + signature + quote + location au moment du report
				// (le profil est dans nf_user + nf_user_profile)
				$row = $this->db->select('u.username', 'p.signature', 'p.quote', 'p.location', 'p.website', 'p.first_name', 'p.last_name')
					->from('nf_user u')
					->join('nf_user_profile p', 'p.id = u.id', 'LEFT')
					->where('u.id', (int)$target_id)
					->row(FALSE);
				if (!is_array($row) || !$row) return NULL;
				$parts = ['@'.($row['username'] ?? '?')];
				if (!empty($row['first_name']) || !empty($row['last_name'])) $parts[] = trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? ''));
				// Les intitulés sont figés dans le snapshot, dans la langue du site au moment du signalement.
				if (!empty($row['quote']))     $parts[] = (string) $this->lang('Citation : %s', $row['quote']);
				if (!empty($row['location']))  $parts[] = (string) $this->lang('Localisation : %s', $row['location']);
				if (!empty($row['website']))   $parts[] = (string) $this->lang('Site web : %s', $row['website']);
				if (!empty($row['signature'])) $parts[] = "\n=== ".$this->lang('Signature')." ===\n".$row['signature'];
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
		$out = "\n\n=== ".$this->lang('Pièces jointes au moment du signalement')." ===\n";
		foreach ($rows as $r)
		{
			$size_kb = $r['file_size'] ? round($r['file_size'] / 1024, 1).' KB' : '?';
			// Le nom de fichier vient du CLIENT (nf_file.name, stocké brut) : échappé ici car
			// le snapshot est rendu en HTML dans le panneau de modération (XSS stocké sinon).
			$out .= '- '.nf_texte($r['name'] ?? 'unknown').' ('.$r['mime_type'].', '.$size_kb.', file_id='.$r['file_id'].', path='.nf_texte($r['path'] ?? 'N/A').")\n";
		}
		return $out;
	}
}
