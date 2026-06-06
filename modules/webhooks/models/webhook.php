<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Webhooks\Models;

use NF\NeoFrag\Loadables\Model;

class Webhook extends Model
{
	public function get_all()
	{
		return $this->db->select('*')->from('nf_webhooks')->order_by('title ASC')->get();
	}

	public function get($id)
	{
		return $this->db->select('*')->from('nf_webhooks')->where('id', $id)->row();
	}
}
