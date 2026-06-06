<?php
/**
 * https://neofr.ag
 * Model du module Modération — wraps la lib core neofrag/libraries/moderation.php
 * en y ajoutant des queries spécifiques à l'UI admin (stats dashboard, etc.).
 */

namespace NF\Modules\Moderation\Models;

use NF\NeoFrag\Loadables\Model;

class Moderation extends Model
{
	/**
	 * Stats pour le dashboard.
	 */
	public function dashboard_stats(): array
	{
		$pending = (int)$this->db->select('COUNT(*)')->from('nf_reports')->where('status', 'pending')->row();
		$last_7d_reports = (int)$this->db->select('COUNT(*)')->from('nf_reports')
			->where('created_at >', date('Y-m-d H:i:s', time() - 7 * 86400))->row();

		// Active sanctions = non-revoked + (permanent OR not yet expired) + (no approval needed OR approved).
		// NeoFrag query builder ne supporte pas les OR raw → fetch puis filter PHP.
		$rows = $this->db	->select('expires_at', 'requires_approval', 'approved_at')
							->from('nf_sanctions')
							->where('revoked_at', NULL)
							->get();
		$now_ts = time();
		$active_sanctions = 0;
		foreach ($rows as $r)
		{
			if (!empty($r['expires_at']) && strtotime($r['expires_at']) <= $now_ts) continue;
			if (!empty($r['requires_approval']) && empty($r['approved_at'])) continue;
			$active_sanctions++;
		}

		$pending_approval = (int)$this->db->select('COUNT(*)')->from('nf_sanctions')
			->where('requires_approval', 1)
			->where('approved_at', NULL)
			->where('revoked_at', NULL)
			->row();

		return [
			'pending'           => $pending,
			'last_7d_reports'   => $last_7d_reports,
			'active_sanctions'  => $active_sanctions,
			'pending_approval'  => $pending_approval
		];
	}


	/**
	 * Top 10 users les plus signalés sur les 30 derniers jours.
	 */
	public function top_reported_users(int $limit = 10): array
	{
		return $this->db->select('r.target_user_id', 'u.username', 'COUNT(*) as report_count')
						->from('nf_reports r')
						->join('nf_user u', 'u.id = r.target_user_id', 'INNER')
						->where('r.target_user_id !=', NULL)
						->where('r.created_at >', date('Y-m-d H:i:s', time() - 30 * 86400))
						->group_by('r.target_user_id')
						->order_by('report_count DESC')
						->limit($limit)
						->get();
	}

	/**
	 * Top 10 reporters récents (utile pour repérer abuseurs ou contributeurs zélés).
	 */
	public function top_reporters(int $limit = 10): array
	{
		return $this->db->select('r.reporter_id', 'u.username', 'COUNT(*) as report_count',
								'SUM(CASE WHEN r.status = "actioned" THEN 1 ELSE 0 END) as actioned',
								'SUM(CASE WHEN r.status = "dismissed" THEN 1 ELSE 0 END) as dismissed')
						->from('nf_reports r')
						->join('nf_user u', 'u.id = r.reporter_id', 'INNER')
						->where('r.reporter_id !=', NULL)
						->where('r.created_at >', date('Y-m-d H:i:s', time() - 30 * 86400))
						->group_by('r.reporter_id')
						->order_by('report_count DESC')
						->limit($limit)
						->get();
	}

	/**
	 * Liste paginée de sanctions (active + historique selon filter).
	 */
	public function get_sanctions(array $filter = [], int $page = 0, int $per_page = 50): array
	{
		$this->db	->select('s.*', 'u.username as user_username', 'iu.username as issuer_username',
							'au.username as approver_username', 'ru.username as revoker_username')
					->from('nf_sanctions s')
					->join('nf_user u',  'u.id = s.user_id',     'LEFT')
					->join('nf_user iu', 'iu.id = s.issued_by',  'LEFT')
					->join('nf_user au', 'au.id = s.approved_by', 'LEFT')
					->join('nf_user ru', 'ru.id = s.revoked_by',  'LEFT');

		if (!empty($filter['active_only']))
		{
			// "active" = non-revoked + (permanent OR not expired). Le OR raw n'étant pas supporté
			// par le builder, on limite ici à "non-revoked" et on filtre les expirées en PHP.
			$this->db->where('s.revoked_at', NULL);
		}
		if (!empty($filter['type']))
		{
			$this->db->where('s.type', $filter['type']);
		}
		if (!empty($filter['user_id']))
		{
			$this->db->where('s.user_id', (int)$filter['user_id']);
		}
		if (!empty($filter['pending_approval']))
		{
			$this->db	->where('s.requires_approval', 1)
						->where('s.approved_at', NULL)
						->where('s.revoked_at', NULL);
		}

		$rows = $this->db->order_by('s.created_at DESC')
						->limit($page * $per_page, $per_page)
						->get();

		// Filtre PHP "active_only" : exclure expirées
		if (!empty($filter['active_only']))
		{
			$now_ts = time();
			$rows = array_values(array_filter($rows, function($r) use ($now_ts){
				return empty($r['expires_at']) || strtotime($r['expires_at']) > $now_ts;
			}));
		}

		return $rows;
	}

	public function get_sanction(int $id): ?array
	{
		$row = $this->db	->select('s.*', 'u.username as user_username', 'iu.username as issuer_username',
									'au.username as approver_username', 'ru.username as revoker_username')
							->from('nf_sanctions s')
							->join('nf_user u',  'u.id = s.user_id',     'LEFT')
							->join('nf_user iu', 'iu.id = s.issued_by',  'LEFT')
							->join('nf_user au', 'au.id = s.approved_by', 'LEFT')
							->join('nf_user ru', 'ru.id = s.revoked_by',  'LEFT')
							->where('s.id', $id)
							->row();
		return is_array($row) && !empty($row) ? $row : NULL;
	}

	/**
	 * Compte de reports actifs (pending) contre un user — pour badge dans la liste.
	 */
	public function user_pending_report_count(int $user_id): int
	{
		return (int)$this->db	->select('COUNT(*)')
								->from('nf_reports')
								->where('target_user_id', $user_id)
								->where('status', 'pending')
								->row();
	}
}
