<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Slider\Models;

use NF\NeoFrag\Loadables\Model;

class Slider extends Model
{
	public function get_slides($only_active = FALSE)
	{
		if ($only_active)
		{
			$this->db->where('active', 1);
		}
		return $this->db->select('id', 'image_url', 'title', 'caption', 'link', 'sort_order', 'active', 'UNIX_TIMESTAMP(created_at) as created_at', 'UNIX_TIMESTAMP(updated_at) as updated_at')
						->from('nf_slider_slides')
						->order_by('sort_order', 'id')
						->get();
	}

	public function get_slide($id)
	{
		return $this->db->select('id', 'image_url', 'title', 'caption', 'link', 'sort_order', 'active')
						->from('nf_slider_slides')
						->where('id', (int)$id)
						->row();
	}

	public function add_slide($data)
	{
		// Position = max(sort_order) + 1
		$max = $this->db->select('MAX(sort_order)')->from('nf_slider_slides')->row();
		$next = is_array($max) ? 0 : ((int)$max + 1);

		return $this->db->insert('nf_slider_slides', [
			'image_url' => (string)($data['image_url'] ?? ''),
			'title'     => (string)($data['title']     ?? ''),
			'caption'   => (string)($data['caption']   ?? ''),
			'link'      => (string)($data['link']      ?? ''),
			'active'    => !empty($data['active']) ? 1 : 0,
			'sort_order'=> $next
		]);
	}

	public function update_slide($id, $data)
	{
		$update = [];
		foreach (['image_url', 'title', 'caption', 'link'] as $k)
		{
			if (array_key_exists($k, $data))
			{
				$update[$k] = (string)$data[$k];
			}
		}
		if (array_key_exists('active', $data))
		{
			$update['active'] = !empty($data['active']) ? 1 : 0;
		}

		if (empty($update))
		{
			return FALSE;
		}

		$this->db	->where('id', (int)$id)
					->update('nf_slider_slides', $update);
		return TRUE;
	}

	public function delete_slide($id)
	{
		$this->db	->where('id', (int)$id)
					->delete('nf_slider_slides');
		return TRUE;
	}

	public function toggle_slide($id)
	{
		$this->db	->where('id', (int)$id)
					->update('nf_slider_slides', 'active = 1 - active');
		return TRUE;
	}

	public function reorder($ordered_ids)
	{
		$ordered_ids = array_filter(array_map('intval', (array)$ordered_ids));
		if (empty($ordered_ids))
		{
			return FALSE;
		}

		$this->db->transaction();
		try
		{
			foreach ($ordered_ids as $position => $id)
			{
				$this->db	->where('id', $id)
							->update('nf_slider_slides', ['sort_order' => (int)$position]);
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
}
