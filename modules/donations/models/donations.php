<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Donations\Models;

use NF\NeoFrag\Loadables\Model;

class Donations extends Model
{
	/* ---------- CAMPAIGNS ---------- */

	public function get_campaigns($status = NULL)
	{
		$this->db->from('nf_donations_campaigns')->order_by('status ASC', 'created_at DESC');
		if ($status) $this->db->where('status', $status);
		return $this->db->get();
	}

	public function get_campaign($id_or_name)
	{
		$row = $this->db->from('nf_donations_campaigns');
		if (is_numeric($id_or_name))
		{
			$row->where('id', (int)$id_or_name);
		}
		else
		{
			$row->where('name', $id_or_name);
		}
		return $row->row();
	}

	public function get_active_campaigns()
	{
		return $this->db->from('nf_donations_campaigns')->where('status', 'active')->order_by('created_at DESC')->get();
	}

	public function save_campaign($data, $id = NULL)
	{
		if ($id)
		{
			$this->db->where('id', (int)$id)->update('nf_donations_campaigns', $data);
			return (int)$id;
		}
		return (int)$this->db->insert('nf_donations_campaigns', $data);
	}

	public function delete_campaign($id)
	{
		$this->db->where('id', (int)$id)->delete('nf_donations_campaigns');
	}

	/* ---------- DONATIONS ---------- */

	public function get_donations($campaign_id, $only_public = FALSE, $only_completed = TRUE)
	{
		$q = $this->db	->from('nf_donations')
						->where('campaign_id', (int)$campaign_id)
						->order_by('created_at DESC');
		if ($only_public) $q->where('is_public', TRUE);
		if ($only_completed) $q->where('status', 'completed');
		return $q->get();
	}

	public function get_donation($id)
	{
		return $this->db->from('nf_donations')->where('id', (int)$id)->row();
	}

	public function get_total($campaign_id)
	{
		$row = $this->db	->select('SUM(amount) AS total', 'COUNT(*) AS count')
							->from('nf_donations')
							->where('campaign_id', (int)$campaign_id)
							->where('status', 'completed')
							->row();
		return [
			'total' => (float)($row['total'] ?? 0),
			'count' => (int)($row['count'] ?? 0)
		];
	}

	public function get_top_donors($campaign_id, $limit = 5)
	{
		return $this->db	->select('donor_name', 'SUM(amount) AS total', 'COUNT(*) AS count', 'MAX(created_at) AS last_at')
							->from('nf_donations')
							->where('campaign_id', (int)$campaign_id)
							->where('status', 'completed')
							->where('is_public', TRUE)
							->where('is_anonymous', FALSE)
							->group_by('donor_name')
							->order_by('total DESC')
							->limit($limit)
							->get();
	}

	public function save_donation($data, $id = NULL)
	{
		if ($id)
		{
			$this->db->where('id', (int)$id)->update('nf_donations', $data);
			return (int)$id;
		}
		return (int)$this->db->insert('nf_donations', $data);
	}

	public function delete_donation($id)
	{
		$this->db->where('id', (int)$id)->delete('nf_donations');
	}
}
