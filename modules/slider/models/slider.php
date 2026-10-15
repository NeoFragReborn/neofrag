<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Slider\Models;

use NF\NeoFrag\Loadables\Model;

class Slider extends Model
{
	/*
	 * Le titre et la légende d'une vue vivent dans `nf_slider_slides_lang`, une ligne par langue (m22, 2026-10-13).
	 * `join_lang()` sert la MEILLEURE : celle de la langue demandée, sinon le français, sinon la première écrite —
	 * une vue traduite dans une seule langue reste visible dans les cinq autres, au lieu de disparaître.
	 */
	public function get_slides($only_active = FALSE, $langue = NULL)
	{
		if ($only_active)
		{
			$this->db->where('s.active', 1);
		}
		return $this->db->select('s.id', 's.image_url', 'l.title', 'l.caption', 's.link', 's.sort_order', 's.active', 'UNIX_TIMESTAMP(s.created_at) as created_at', 'UNIX_TIMESTAMP(s.updated_at) as updated_at')
						->from('nf_slider_slides s')
						->join_lang('nf_slider_slides_lang l', 'slide_id', 's.id', $langue, 'LEFT')
						->order_by('s.sort_order', 's.id')
						->get();
	}

	public function get_slide($id)
	{
		return $this->db->select('s.id', 's.image_url', 'l.title', 'l.caption', 's.link', 's.sort_order', 's.active')
						->from('nf_slider_slides s')
						->join_lang('nf_slider_slides_lang l', 'slide_id', 's.id', NULL, 'LEFT')
						->where('s.id', (int)$id)
						->row();
	}

	/*
	 * La vue telle qu'elle est écrite DANS UNE LANGUE, sans repli : c'est ce que l'administration édite — sinon on
	 * proposerait la traduction française à corriger dans le formulaire anglais, et on l'écrirait en anglais.
	 */
	public function get_slide_lang($id, $langue)
	{
		return $this->db->select('title', 'caption')
						->from('nf_slider_slides_lang')
						->where('slide_id', (int)$id)
						->where('lang', $langue)
						->row();
	}

	public function add_slide($data)
	{
		// Position = max(sort_order) + 1
		$max = $this->db->select('MAX(sort_order)')->from('nf_slider_slides')->row();
		$next = is_array($max) ? 0 : ((int)$max + 1);

		$id = $this->db->insert('nf_slider_slides', [
			'image_url' => (string)($data['image_url'] ?? ''),
			'link'      => (string)($data['link']      ?? ''),
			'active'    => !empty($data['active']) ? 1 : 0,
			'sort_order'=> $next
		]);

		$this->set_slide_lang($id, (string)($data['title'] ?? ''), (string)($data['caption'] ?? ''));

		return $id;
	}

	/*
	 * Le texte de la vue dans la langue où l'on travaille. `replace()` et non `update()` : la première fois qu'on
	 * traduit une vue dans une langue, sa ligne n'existe pas — un `update` n'aurait rien écrit, en silence.
	 */
	public function set_slide_lang($id, $title, $caption, $langue = NULL)
	{
		$this->db->replace('nf_slider_slides_lang', [
			'slide_id' => (int)$id,
			'lang'     => (string)($langue ?: $this->config->lang->info()->name),
			'title'    => (string)$title,
			'caption'  => (string)$caption
		]);
	}

	public function update_slide($id, $data)
	{
		$update = [];
		foreach (['image_url', 'link'] as $k)
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

		$texte = array_key_exists('title', $data) || array_key_exists('caption', $data);

		if (empty($update) && !$texte)
		{
			return FALSE;
		}

		if ($update)
		{
			$this->db	->where('id', (int)$id)
						->update('nf_slider_slides', $update);
		}

		if ($texte)
		{
			$this->set_slide_lang($id, (string)($data['title'] ?? ''), (string)($data['caption'] ?? ''));
		}

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
