<?php
/**
 * https://neofr.ag
 *
 * Moderation library — système de modération étendu site-wide.
 * Phase 1 (2026-05-04) : status checks (is_banned, is_muted, can_*), reports, sanctions.
 *
 * Tables :
 *   - nf_reports   : signalements user → modérateurs
 *   - nf_sanctions : sanctions actives + historique (warning/mute/ban_temp/ban_perm/restrict_*)
 *   - nf_audit_log : tracking générique des actions admin (lib existante)
 *   - nf_rate_limit : anti-spam des reports (lib existante)
 *
 * Tous les status checks sont cachés par requête (static array) pour éviter
 * les N queries en boucle (ex: rendu d'une liste de messages forum).
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;

class Moderation extends Library
{
	// Cache static par requête : key = "user_id:scope" → bool|null
	private static $_cache_active_sanctions = [];

	// === STATUS CHECKS ===

	/**
	 * Retourne TRUE si l'user a un ban (temp ou perm) actif sur le scope demandé OU global.
	 * Use case : à appeler avant tout login / action sensible.
	 */
	public function is_banned(int $user_id, string $scope = 'global'): bool
	{
		if (!$user_id) return FALSE;
		foreach ($this->active_sanctions($user_id) as $s)
		{
			if (in_array($s['type'], ['ban_temp', 'ban_perm'], TRUE)
				&& ($s['scope'] === 'global' || $s['scope'] === $scope))
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Retourne TRUE si l'user est mute sur le scope demandé OU global.
	 * Use case : avant chaque post (forum/talks/comments).
	 */
	public function is_muted(int $user_id, string $scope = 'global'): bool
	{
		if (!$user_id) return FALSE;
		foreach ($this->active_sanctions($user_id) as $s)
		{
			if ($s['type'] === 'mute'
				&& ($s['scope'] === 'global' || $s['scope'] === $scope))
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	public function can_upload_files(int $user_id): bool
	{
		return !$this->_has_active_restriction($user_id, 'restrict_upload');
	}

	public function can_post_links(int $user_id): bool
	{
		return !$this->_has_active_restriction($user_id, 'restrict_links');
	}

	public function can_change_avatar(int $user_id): bool
	{
		return !$this->_has_active_restriction($user_id, 'restrict_avatar');
	}

	public function can_change_signature(int $user_id): bool
	{
		return !$this->_has_active_restriction($user_id, 'restrict_signature');
	}

	public function can_comment(int $user_id): bool
	{
		return !$this->_has_active_restriction($user_id, 'restrict_comment')
		    && !$this->is_muted($user_id, 'comments');
	}

	private function _has_active_restriction(int $user_id, string $type): bool
	{
		if (!$user_id) return FALSE;
		foreach ($this->active_sanctions($user_id) as $s)
		{
			if ($s['type'] === $type)
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Retourne toutes les sanctions actives d'un user (non révoquées, non expirées,
	 * et approuvées si requires_approval). Cache static par requête.
	 */
	public function active_sanctions(int $user_id): array
	{
		if (!$user_id) return [];
		if (isset(self::$_cache_active_sanctions[$user_id]))
		{
			return self::$_cache_active_sanctions[$user_id];
		}

		// On récupère TOUTES les sanctions non révoquées pour cet user (peu, généralement < 10),
		// puis on filtre en PHP. Évite les OR raw que le query builder NeoFrag ne supporte pas.
		$rows = $this->db	->select('id', 'type', 'scope', 'reason', 'starts_at', 'expires_at',
									'duration_seconds', 'issued_by', 'requires_approval', 'approved_at', 'created_at')
							->from('nf_sanctions')
							->where('user_id', $user_id)
							->where('revoked_at', NULL)
							->order_by('created_at DESC')
							->get();

		$now_ts = time();
		$filtered = [];
		foreach (is_array($rows) ? $rows : [] as $r)
		{
			// Pas encore commencé ?
			if (!empty($r['starts_at']) && strtotime($r['starts_at']) > $now_ts) continue;
			// Expirée ?
			if (!empty($r['expires_at']) && strtotime($r['expires_at']) <= $now_ts) continue;
			// Approval requise mais pas approuvée ?
			if (!empty($r['requires_approval']) && empty($r['approved_at'])) continue;
			$filtered[] = $r;
		}

		self::$_cache_active_sanctions[$user_id] = $filtered;
		return $filtered;
	}

	/**
	 * Retourne le nombre de secondes restantes avant expiration du ban actif (pour message UX).
	 * NULL si pas de ban actif. -1 si ban permanent.
	 */
	public function ban_remaining_seconds(int $user_id, string $scope = 'global'): ?int
	{
		foreach ($this->active_sanctions($user_id) as $s)
		{
			if (in_array($s['type'], ['ban_temp', 'ban_perm'], TRUE)
				&& ($s['scope'] === 'global' || $s['scope'] === $scope))
			{
				if ($s['type'] === 'ban_perm' || empty($s['expires_at'])) return -1;
				return max(0, strtotime($s['expires_at']) - time());
			}
		}
		return NULL;
	}

	/**
	 * Retourne un message lisible expliquant le blocage (pour affichage dans les forms).
	 * NULL si pas de blocage. Utilisé par les vues comme :
	 *   $reason = $this->moderation->block_message_for_user($uid, 'forum');
	 *   if ($reason) { afficher banner explicatif }
	 */
	public function block_message_for_user(int $user_id, string $scope = 'global'): ?string
	{
		if (!$user_id) return NULL;

		foreach ($this->active_sanctions($user_id) as $s)
		{
			if (!in_array($s['scope'], ['global', $scope], TRUE)) continue;

			$remaining = '';
			if (!empty($s['expires_at']))
			{
				$secs = max(0, strtotime($s['expires_at']) - time());
				$remaining = ' '.$this->lang('(jusqu\'au %s, soit %s)', $s['expires_at'], $this->_format_duration($secs));
			}
			else if (in_array($s['type'], ['ban_perm', 'mute'], TRUE) && empty($s['duration_seconds']))
			{
				$remaining = ' '.$this->lang('(permanent)');
			}

			$reason = !empty($s['reason']) ? ' — '.$s['reason'] : '';

			switch ($s['type'])
			{
				case 'ban_perm':         return $this->lang('Compte banni définitivement').$remaining.$reason;
				case 'ban_temp':         return $this->lang('Compte banni temporairement').$remaining.$reason;
				case 'mute':             return $this->lang('Tu es muet sur ce scope').$remaining.$reason;
				case 'restrict_upload':  return $this->lang('Upload de fichiers désactivé').$remaining.$reason;
				case 'restrict_links':   return $this->lang('Liens externes désactivés').$remaining.$reason;
				case 'restrict_avatar':  return $this->lang('Modification de l\'avatar désactivée').$remaining.$reason;
				case 'restrict_comment': return $this->lang('Commentaires désactivés').$remaining.$reason;
				case 'restrict_signature': return $this->lang('Modification de la signature désactivée').$remaining.$reason;
			}
		}
		return NULL;
	}

	private function _format_duration(int $seconds): string
	{
		if ($seconds <= 0)        return $this->lang('expirée');
		if ($seconds < 60)        return $seconds.'s';
		if ($seconds < 3600)      return floor($seconds / 60).' min';
		if ($seconds < 86400)     return floor($seconds / 3600).' h';
		return floor($seconds / 86400).' j';
	}

	// ================================================================
	// R1.8 — Coordination permissions ↔ sanctions
	// ================================================================

	/**
	 * Check si l'user est bloqué pour une permission donnée par une sanction active.
	 *
	 * Lookup dans modules/moderation/sanction_mapping.php pour trouver les checks à appliquer.
	 * Retourne :
	 *   - NULL si aucun blocage (la permission peut être évaluée par roles normalement)
	 *   - ['blocked' => true, 'reason' => str, 'message' => str, 'sanction' => row]
	 *     si un check est bloqué par une sanction active
	 *
	 * Pattern d'appel applicatif :
	 *   if ($block = $this->moderation->is_blocked_for($user_id, 'forum.message_post')) {
	 *       notify($block['message'], 'danger');
	 *       return;
	 *   }
	 *   if (!$this->access('forum', 'message_post', $cat_id)) { ... }
	 */
	public function is_blocked_for(int $user_id, string $permission, int $scope_id = 0): ?array
	{
		if (!$user_id) return NULL;

		static $mapping = NULL;
		if ($mapping === NULL)
		{
			$mapping_file = NEOFRAG_CMS.'/modules/moderation/sanction_mapping.php';
			$mapping = file_exists($mapping_file) ? include $mapping_file : [];
		}

		if (!isset($mapping[$permission]))
		{
			return NULL; // permission non mappée → aucune sanction applicable
		}

		$rules = $mapping[$permission];

		// Évalue chaque check. Premier match → blocked.
		foreach ($rules as $check => $arg)
		{
			$blocked = FALSE;
			$check_scope = NULL;

			switch ($check)
			{
				case 'is_muted':
					$check_scope = (string)$arg;
					$blocked = $this->is_muted($user_id, $check_scope);
					break;
				case 'is_banned':
					$check_scope = (string)$arg;
					$blocked = $this->is_banned($user_id, $check_scope);
					break;
				case 'is_banned_global':
					$check_scope = 'global';
					$blocked = $this->is_banned($user_id, 'global');
					break;
				case 'can_upload_files':
					$blocked = !$this->can_upload_files($user_id);
					break;
				case 'can_post_links':
					$blocked = !$this->can_post_links($user_id);
					break;
				case 'can_change_avatar':
					$blocked = !$this->can_change_avatar($user_id);
					break;
				case 'can_change_signature':
					$blocked = !$this->can_change_signature($user_id);
					break;
				case 'can_comment':
					$blocked = !$this->can_comment($user_id);
					break;
			}

			if ($blocked)
			{
				$message = $this->block_message_for_user($user_id, $check_scope ?: 'global');
				return [
					'blocked'    => TRUE,
					'reason'     => $check,
					'check'      => $check,
					'scope'      => $check_scope,
					'message'    => $message ?: $this->lang('Action bloquée par une sanction active'),
					'permission' => $permission
				];
			}
		}

		return NULL;
	}

	// === REPORTS ===

	/**
	 * Crée un signalement. Retourne l'id, ou 0 en cas de rate-limit / erreur.
	 *
	 * Anti-abus :
	 *   - rate-limit par user/IP via nf_rate_limit (5/h par défaut)
	 *   - duplicate detection : si même reporter+target dans dernières 24h → status=duplicate
	 */
	public function report(string $target_type, string $target_id, ?int $reporter_id,
	                       string $reason, string $comment = '', string $url = '',
	                       ?int $target_user_id = NULL, ?string $content_snapshot = NULL): int
	{
		$reporter_ip = $_SERVER['REMOTE_ADDR'] ?? '';
		$valid_reasons = ['spam','harassment','illegal','nsfw','misinformation','duplicate','other'];
		if (!in_array($reason, $valid_reasons, TRUE))
		{
			$reason = 'other';
		}

		// Rate-limit : 5 reports/h par reporter (config)
		$max_per_hour = (int)$this->config->nf_moderation_report_rate_limit_per_hour ?: 5;
		$rl_key = 'moderation:report:'.($reporter_id ? 'u'.$reporter_id : 'ip:'.$reporter_ip);
		$rl = $this->rate_limit->check($rl_key);
		if (!$rl['allowed'])
		{
			return 0;
		}
		$this->rate_limit->hit($rl_key, $max_per_hour, 3600, 3600);

		// Duplicate detection : même reporter+target dans les 24h ?
		$is_duplicate = FALSE;
		if ($reporter_id)
		{
			$existing = $this->db	->select('id')
									->from('nf_reports')
									->where('reporter_id', $reporter_id)
									->where('target_type', $target_type)
									->where('target_id', $target_id)
									->where('created_at >', date('Y-m-d H:i:s', time() - 86400))
									->row();
			$is_duplicate = !empty($existing);
		}

		$id = $this->db->insert('nf_reports', [
			'reporter_id'      => $reporter_id ?: NULL,
			'reporter_ip'      => $reporter_ip,
			'target_type'      => $target_type,
			'target_id'        => $target_id,
			'target_user_id'   => $target_user_id ?: NULL,
			'reason'           => $reason,
			'comment'          => mb_substr($comment, 0, 500),
			'url'              => mb_substr($url, 0, 500),
			'content_snapshot' => $this->config->nf_moderation_preserve_content_snapshot ? $content_snapshot : NULL,
			'status'           => $is_duplicate ? 'duplicate' : 'pending'
		]);

		// Émet event "report.created" pour permettre aux modules d'écouter (notifs modos, etc.)
		if ($id && !empty($this->events))
		{
			try
			{
				$this->events->fire('moderation.report.created', [
					'report_id'      => $id,
					'reporter_id'    => $reporter_id,
					'target_type'    => $target_type,
					'target_id'      => $target_id,
					'target_user_id' => $target_user_id,
					'reason'         => $reason
				]);
			}
			catch (\Throwable $e) {}
		}

		return (int)$id;
	}

	/**
	 * Liste les signalements en attente, filtrable.
	 * $filter = ['status' => 'pending', 'target_type' => 'forum_message', 'reporter_id' => 5, 'target_user_id' => 12]
	 */
	public function get_pending_reports(array $filter = [], int $page = 0, int $per_page = 50): array
	{
		$this->db	->select('r.*', 'u.username as reporter_username', 'tu.username as target_username')
					->from('nf_reports r')
					->join('nf_user u',  'u.id = r.reporter_id',    'LEFT')
					->join('nf_user tu', 'tu.id = r.target_user_id', 'LEFT');

		foreach ($filter as $key => $value)
		{
			if (in_array($key, ['status', 'target_type', 'reporter_id', 'target_user_id', 'reason'], TRUE))
			{
				$this->db->where('r.'.$key, $value);
			}
		}

		return $this->db	->order_by('r.created_at DESC')
							->limit($page * $per_page, $per_page)
							->get();
	}

	public function get_report(int $id): ?array
	{
		$row = $this->db	->select('r.*', 'u.username as reporter_username', 'tu.username as target_username')
							->from('nf_reports r')
							->join('nf_user u',  'u.id = r.reporter_id',    'LEFT')
							->join('nf_user tu', 'tu.id = r.target_user_id', 'LEFT')
							->where('r.id', $id)
							->row();
		return is_array($row) && !empty($row) ? $row : NULL;
	}

	public function get_user_reports_received(int $user_id, int $limit = 100): array
	{
		return $this->db	->select('r.*', 'u.username as reporter_username')
							->from('nf_reports r')
							->join('nf_user u', 'u.id = r.reporter_id', 'LEFT')
							->where('r.target_user_id', $user_id)
							->order_by('r.created_at DESC')
							->limit($limit)
							->get();
	}

	public function get_user_reports_made(int $user_id, int $limit = 100): array
	{
		return $this->db	->select('r.*', 'tu.username as target_username')
							->from('nf_reports r')
							->join('nf_user tu', 'tu.id = r.target_user_id', 'LEFT')
							->where('r.reporter_id', $user_id)
							->order_by('r.created_at DESC')
							->limit($limit)
							->get();
	}

	public function update_report_status(int $report_id, string $status, int $handler_id, ?string $note = NULL, ?int $action_id = NULL): bool
	{
		if (!in_array($status, ['pending', 'reviewed', 'actioned', 'dismissed', 'duplicate'], TRUE))
		{
			return FALSE;
		}
		$this->db	->where('id', $report_id)
					->update('nf_reports', [
						'status'             => $status,
						'handled_by'         => $handler_id,
						'handled_at'         => date('Y-m-d H:i:s'),
						'handled_note'       => $note,
						'handled_action_id'  => $action_id
					]);

		// Émet event pour notif reporter
		if (!empty($this->events) && in_array($status, ['actioned', 'dismissed'], TRUE))
		{
			try
			{
				$this->events->fire('moderation.report.handled', [
					'report_id' => $report_id,
					'status'    => $status,
					'handler_id' => $handler_id
				]);
			}
			catch (\Throwable $e) {}
		}

		return TRUE;
	}

	// === SANCTIONS ===

	/**
	 * Crée une sanction.
	 *
	 * $opts = [
	 *   'scope'              => 'global' (default) | 'forum' | 'talks' | ...
	 *   'reason'             => 'texte explicatif obligatoire',
	 *   'duration_seconds'   => null (= permanent) | int,
	 *   'requires_approval'  => 0|1,
	 *   'related_report_id'  => null|int,
	 *   'notify_user'        => 1|0
	 * ]
	 *
	 * Retourne l'id de la sanction.
	 */
	public function sanction(int $user_id, string $type, array $opts): int
	{
		$valid_types = ['warning','mute','ban_temp','ban_perm','restrict_upload','restrict_links',
		                'restrict_avatar','restrict_signature','restrict_comment','shadow_ban'];
		if (!in_array($type, $valid_types, TRUE))
		{
			return 0;
		}

		// Shadow_ban : par nature silencieux côté user (jamais de notif, sinon ce serait pas "shadow")
		// + raison écrite obligatoire pour traçabilité staff
		if ($type === 'shadow_ban')
		{
			$opts['notify_user'] = 0;
			if (trim((string)($opts['reason'] ?? '')) === '')
			{
				return 0;
			}
		}

		// === Self-protection hiérarchique ===
		// Empêche un user de tier inférieur/égal de sanctionner un user de tier supérieur.
		// Ex : modo junior ne peut pas ban un modo senior, modo senior ne peut pas ban un admin global.
		$issued_by = (int)($opts['issued_by'] ?? 0);
		if ($issued_by > 0 && $issued_by !== $user_id) // 0 = système (escalade auto), même user = nonsense filtré ci-dessous
		{
			$issuer_tier = $this->_user_tier($issued_by);
			$target_tier = $this->_user_tier($user_id);
			if ($target_tier >= $issuer_tier)
			{
				try
				{
					(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('moderation.sanction.blocked_hierarchy', [
						'attempted_by'  => $issued_by,
						'target_user'   => $user_id,
						'issuer_tier'   => $issuer_tier,
						'target_tier'   => $target_tier,
						'attempted_type' => $type
					], FALSE);
				}
				catch (\Throwable $e) {}
				return 0;
			}
		}
		// Empêche aussi de se sanctionner soi-même
		if ($issued_by === $user_id)
		{
			return 0;
		}

		$scope = $opts['scope'] ?? 'global';
		$valid_scopes = ['global','forum','talks','comments','wiki','gallery','guestbook','profile','bugtracker','recruits'];
		if (!in_array($scope, $valid_scopes, TRUE))
		{
			$scope = 'global';
		}

		$duration = isset($opts['duration_seconds']) ? (int)$opts['duration_seconds'] : NULL;
		$expires_at = NULL;
		if ($duration > 0)
		{
			$expires_at = date('Y-m-d H:i:s', time() + $duration);
		}
		// Permanent par défaut pour ban_perm
		if ($type === 'ban_perm')
		{
			$duration = NULL;
			$expires_at = NULL;
		}

		// Approval auto-required selon settings + tier issuer
		// Un admin/senior (tier ≥ 4) bypass les settings et la validation hiérarchique :
		// il est au sommet de la chaîne, ses sanctions sont auto-appliquées.
		$issuer_tier = $issued_by > 0 ? $this->_user_tier($issued_by) : 0;
		$requires_approval = (int)($opts['requires_approval'] ?? 0);

		if ($issuer_tier < 4)
		{
			if ($type === 'ban_perm' && $this->config->nf_moderation_require_approval_ban_perm)
			{
				$requires_approval = 1;
			}
			else if ($type === 'ban_temp' && $this->config->nf_moderation_require_approval_ban_temp)
			{
				$requires_approval = 1;
			}

			// Auto requires_approval pour les modos junior qui appliquent des sanctions sévères :
			// Tier 2 (modo junior) ne peut pas faire ban_temp/ban_perm/shadow_ban sans validation senior+
			// Tier 3 (modo senior) ne peut pas faire ban_perm/shadow_ban sans validation admin
			if ($issued_by > 0)
			{
				if (in_array($type, ['ban_temp', 'ban_perm', 'shadow_ban'], TRUE) && $issuer_tier <= 2)
				{
					$requires_approval = 1;
				}
				else if (in_array($type, ['ban_perm', 'shadow_ban'], TRUE) && $issuer_tier <= 3)
				{
					$requires_approval = 1;
				}
			}
		}

		$id = $this->db->insert('nf_sanctions', [
			'user_id'           => $user_id,
			'type'              => $type,
			'scope'             => $scope,
			'reason'            => trim((string)($opts['reason'] ?? '')),
			'duration_seconds'  => $duration,
			'starts_at'         => date('Y-m-d H:i:s'),
			'expires_at'        => $expires_at,
			'issued_by'         => (int)($opts['issued_by'] ?? 0),
			'requires_approval' => $requires_approval,
			'related_report_id' => isset($opts['related_report_id']) ? (int)$opts['related_report_id'] : NULL,
			'notify_user'       => isset($opts['notify_user']) ? (int)$opts['notify_user'] : 1
		]);

		// Invalide le cache pour ce user
		unset(self::$_cache_active_sanctions[$user_id]);

		// Audit log
		try
		{
			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('moderation.sanction.created', [
				'sanction_id' => $id,
				'user_id'     => $user_id,
				'type'        => $type,
				'scope'       => $scope,
				'duration'    => $duration,
				'reason'      => $opts['reason'] ?? '',
				'issued_by'   => $opts['issued_by'] ?? 0,
				'requires_approval' => $requires_approval
			]);
		}
		catch (\Throwable $e) {}

		// Event
		if (!empty($this->events))
		{
			try
			{
				$this->events->fire('moderation.sanction.created', [
					'sanction_id'       => $id,
					'user_id'           => $user_id,
					'type'              => $type,
					'scope'             => $scope,
					'requires_approval' => $requires_approval
				]);
			}
			catch (\Throwable $e) {}
		}

		return (int)$id;
	}

	public function approve(int $sanction_id, int $admin_id): bool
	{
		$s = $this->db->select('id', 'user_id', 'requires_approval', 'approved_at')->from('nf_sanctions')->where('id', $sanction_id)->row(FALSE);
		if (!is_array($s) || empty($s) || !$s['requires_approval'] || !empty($s['approved_at']))
		{
			return FALSE;
		}
		$this->db	->where('id', $sanction_id)
					->update('nf_sanctions', [
						'approved_by' => $admin_id,
						'approved_at' => date('Y-m-d H:i:s')
					]);
		unset(self::$_cache_active_sanctions[(int)$s['user_id']]);

		try
		{
			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('moderation.sanction.approved', [
				'sanction_id' => $sanction_id,
				'admin_id'    => $admin_id
			]);
		}
		catch (\Throwable $e) {}

		return TRUE;
	}

	public function revoke(int $sanction_id, int $admin_id, string $reason): bool
	{
		// row(FALSE) pour empêcher le cast auto NeoFrag (1 col → valeur scalaire)
		$s = $this->db->select('user_id')->from('nf_sanctions')->where('id', $sanction_id)->where('revoked_at', NULL)->row(FALSE);
		if (!is_array($s) || empty($s)) return FALSE;

		$this->db	->where('id', $sanction_id)
					->update('nf_sanctions', [
						'revoked_at'    => date('Y-m-d H:i:s'),
						'revoked_by'    => $admin_id,
						'revoke_reason' => $reason
					]);
		unset(self::$_cache_active_sanctions[(int)$s['user_id']]);

		try
		{
			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('moderation.sanction.revoked', [
				'sanction_id' => $sanction_id,
				'admin_id'    => $admin_id,
				'reason'      => $reason
			]);
		}
		catch (\Throwable $e) {}

		return TRUE;
	}

	/**
	 * Historique complet d'un user : reports reçus + reports émis + sanctions actives + sanctions passées.
	 * Format mergé chronologique pour affichage timeline admin.
	 */
	public function user_history(int $user_id): array
	{
		$reports_received = $this->db	->select('id', 'reporter_id', 'target_type', 'target_id', 'reason', 'status', 'created_at',
												"'report_received' as event_type")
										->from('nf_reports')
										->where('target_user_id', $user_id)
										->get();

		$reports_made = $this->db	->select('id', 'target_user_id', 'target_type', 'target_id', 'reason', 'status', 'created_at',
											"'report_made' as event_type")
									->from('nf_reports')
									->where('reporter_id', $user_id)
									->get();

		$sanctions = $this->db	->select('id', 'type', 'scope', 'reason', 'duration_seconds',
										'starts_at', 'expires_at', 'issued_by', 'revoked_at', 'created_at',
										"'sanction' as event_type")
								->from('nf_sanctions')
								->where('user_id', $user_id)
								->get();

		$timeline = array_merge($reports_received, $reports_made, $sanctions);
		usort($timeline, function($a, $b){
			return strtotime($b['created_at']) - strtotime($a['created_at']);
		});

		return $timeline;
	}

	// === HIÉRARCHIE / TIER (anti-bypass) ===

	/**
	 * Retourne le tier hiérarchique d'un user :
	 *   0 = visiteur / membre
	 *   2 = modérateur (a view_reports)
	 *   3 = modérateur senior (a ban_temp)
	 *   4 = admin senior moderation (a ban_perm)
	 *   5 = admin global (user.admin = 1)
	 */
	private function _user_tier(int $user_id): int
	{
		if (!$user_id) return 0;
		$user = $this->db->select('admin')->from('nf_user')->where('id', $user_id)->row(FALSE);
		if (is_array($user) && !empty($user['admin']) && $user['admin'] === '1') return 5;

		// Vérification permissions modération
		// On regarde via Access lib si l'user a la permission, mais on ne peut pas appeler
		// $this->access(...) pour un autre user facilement → on regarde nf_access via groups
		$has = function($action) use ($user_id) {
			$groups = (array)$this->groups($user_id);
			$rows = $this->db->select('1')->from('nf_access')
				->where('module', 'moderation')
				->where('action', $action)
				->where('id', $groups)
				->limit(1)
				->row();
			return !empty($rows);
		};

		if ($has('ban_perm'))    return 4;
		if ($has('ban_temp'))    return 3;
		if ($has('view_reports')) return 2;
		return 0;
	}

	// === LOCK ANTI-DELETE ===

	/**
	 * Retourne le report_id qui locke ce message (status pending OU reviewed),
	 * ou NULL si pas de lock. Empêche les users de supprimer un contenu signalé
	 * tant que le staff n'a pas tranché.
	 *
	 * @param string $module 'forum' ou 'talks' ou 'comment' ou 'guestbook'
	 * @param int    $message_id ID du message dans son module d'origine
	 */
	public function is_message_locked(string $module, int $message_id): ?int
	{
		$target_type = match ($module) {
			'forum'     => 'forum_message',
			'talks'     => 'talks_message',
			'comment'   => 'comment',
			'guestbook' => 'guestbook',
			default     => $module,
		};
		// where IN non supporté nativement → 2 queries simples (cf piège query builder)
		foreach (['pending', 'reviewed'] as $status)
		{
			$row = $this->db->select('id')
				->from('nf_reports')
				->where('target_type', $target_type)
				->where('target_id', (string)$message_id)
				->where('status', $status)
				->limit(1)
				->row(FALSE);
			if (is_array($row) && !empty($row['id'])) return (int)$row['id'];
		}
		return NULL;
	}

	/**
	 * Variante pour un attachment spécifique : remonte au message_id puis check.
	 */
	public function is_attachment_locked(string $module, int $attachment_id): ?int
	{
		$table = $module === 'talks' ? 'nf_talks_attachments' : 'nf_forum_attachments';
		$row = $this->db->select('message_id')->from($table)->where('attachment_id', $attachment_id)->row(FALSE);
		if (!is_array($row) || empty($row['message_id'])) return NULL;
		return $this->is_message_locked($module, (int)$row['message_id']);
	}

	// === MÉDIATION ===

	/**
	 * Ouvre une médiation entre reporter + signalé sur un report.
	 * Crée un talk de type 'group' (ou 'mediation' si on étend l'enum) avec :
	 *   - le modérateur qui ouvre la médiation
	 *   - le reporter (si identifié)
	 *   - le user signalé (si identifié)
	 * Met le report en statut 'reviewed' avec une note pointant vers le talk_id.
	 *
	 * Retourne le talk_id créé, ou 0 si échec.
	 */
	public function open_mediation(int $report_id, int $moderator_id): int
	{
		$report = $this->db->select('reporter_id', 'target_user_id', 'target_type', 'target_id', 'reason')
		                   ->from('nf_reports')
		                   ->where('id', $report_id)
		                   ->row(FALSE);
		if (!is_array($report) || empty($report))    return 0;
		if (!$report['reporter_id'] || !$report['target_user_id']) return 0;

		// Crée un talk group via le module talks
		try
		{
			if (!($talks = $this->module('talks'))) return 0;

			$participants = array_unique(array_filter([
				(int)$report['reporter_id'],
				(int)$report['target_user_id']
			]));

			$talk_id = $talks->model()->create_conversation(
				$moderator_id,
				'group',
				$this->lang('Médiation #%d — %s', $report_id, $report['reason']),
				$this->lang('Espace de médiation ouvert par un modérateur suite au signalement #%d. Soyez respectueux.', $report_id),
				$participants
			);
			if (!$talk_id) return 0;

			// Premier message système expliquant le contexte
			$intro = $this->lang('Bonjour. Cette discussion a été ouverte par un modérateur suite à un signalement (raison : %s). L\'objectif est de comprendre les deux points de vue et d\'arriver à une issue. Merci de rester courtois.', $report['reason']);
			$talks->model()->send_system_message($talk_id, $intro);

			// Audit log
			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('moderation.mediation.opened', [
				'report_id'    => $report_id,
				'talk_id'      => $talk_id,
				'moderator_id' => $moderator_id,
				'reporter_id'  => $report['reporter_id'],
				'target_user_id' => $report['target_user_id']
			]);

			// Stocke le talk_id dans handled_note du report (et marque comme reviewed)
			$this->update_report_status($report_id, 'reviewed', $moderator_id,
				'Médiation ouverte (talk_id='.$talk_id.')');

			return (int)$talk_id;
		}
		catch (\Throwable $e)
		{
			error_log('[moderation open_mediation] '.$e->getMessage());
			return 0;
		}
	}

	// === RAPPORT QUALITY (légitimité d'un reporter) ===

	/**
	 * Calcule un score de "qualité" du reporter basé sur l'historique :
	 *   - Reports actionned (validés par modo) → +1
	 *   - Reports dismissed (rejetés)          → -1
	 *   - Reports duplicate                    → 0 (neutre)
	 * Score peut être négatif si le user fait surtout des faux signalements.
	 * Utilisé pour flag "reporter douteux" dans l'UI admin.
	 */
	public function reporter_quality_score(int $user_id): array
	{
		$rows = $this->db	->select('status', 'COUNT(*) as cnt')
							->from('nf_reports')
							->where('reporter_id', $user_id)
							->group_by('status')
							->get();

		$counts = ['pending' => 0, 'reviewed' => 0, 'actioned' => 0, 'dismissed' => 0, 'duplicate' => 0];
		foreach ($rows as $r)
		{
			$counts[$r['status']] = (int)$r['cnt'];
		}

		$total = array_sum($counts);
		$score = $counts['actioned'] - $counts['dismissed'];

		return [
			'counts'    => $counts,
			'total'     => $total,
			'score'     => $score,
			'is_suspect' => $total >= 5 && $score < 0  // 5+ reports avec balance négative = suspect
		];
	}
}
