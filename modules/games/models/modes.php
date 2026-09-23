<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Games\Models;

use NF\NeoFrag\Loadables\Model;

class Modes extends Model
{
	public function get_modes($game_id = NULL)
	{
		if ($game_id)
		{
			$this->db->where('game_id', $game_id);
		}

		return $this->db->from('nf_games_modes')
						->order_by('title')
						->get();
	}

	public function get_modes_list()
	{
		$modes = [];

		foreach ($this->db	->select('m.mode_id', 'm.title', 'gl.title as game_title', 'gl2.title as game_title2')
							->from('nf_games_modes m')
							->join('nf_games g',        'm.game_id = g.game_id')
							->join('nf_games_lang gl',  'm.game_id = gl.game_id')
							->join('nf_games_lang gl2', 'g.parent_id = gl2.game_id')
							->order_by('If(g.parent_id IS NULL, gl.title, CONCAT(gl2.title, gl.title))', 'm.title')
							->get() as $mode)
		{
			$modes[$mode['mode_id']] = $mode['title'].' ('.implode(' - ', array_filter([$mode['game_title2'], $mode['game_title']])).')';
		}

		return $modes;
	}

	public function check_mode($mode_id, $title)
	{
		// Le PERMALIEN du jeu (`g.name`), comme `check_map()` : `_modes_edit()` en fait son lien de
		// retour vers `admin/games/{id}/{url_title}`. Le titre traduit qu'on rendait ici
		// (« Counter-Strike 2 ») donnait une adresse avec des espaces, et un 404 (2026-09-22).
		$mode = $this->db	->select('m.mode_id', 'm.game_id', 'm.title', 'g.name')
							->from('nf_games_modes m')
							->join('nf_games g',        'm.game_id = g.game_id')
							->where('m.mode_id', $mode_id)
							->row();

		if ($mode && url_title($mode['title']) == $title)
		{
			return $mode;
		}
		else
		{
			return FALSE;
		}
	}

	public function add_mode($game_id, $title)
	{
		return $this->db->insert('nf_games_modes', [
			'game_id' => $game_id,
			'title'   => $title
		]);
	}

	public function edit_mode($mode_id, $title)
	{
		$this->db	->where('mode_id', $mode_id)
					->update('nf_games_modes', [
						'title'    => $title
					]);
	}

	public function delete_mode($mode_id)
	{
		$this->db	->where('mode_id', $mode_id)
					->delete('nf_games_modes');
	}
}
