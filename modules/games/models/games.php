<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Games\Models;

use NF\NeoFrag\Loadables\Model;

class Games extends Model
{
	public function check_game($game_id, $name, $lang = 'default')
	{
		if ($lang == 'default')
		{
			$lang = $this->config->lang->info()->name;
		}

		return $this->db->select('g.game_id', 'g.parent_id', 'g.image_id', 'g.icon_id', 'gl.title', 'g.name')
						->from('nf_games g')
						->join_lang('nf_games_lang gl', 'game_id', 'g.game_id', $lang)
						->where('g.game_id', $game_id)
						->where('g.name', $name)
						->row();
	}

	public function get_games()
	{
		return $this->db->select('g.*', 'gl.title')
						->from('nf_games g')
						->join_lang('nf_games_lang gl', 'game_id', 'g.game_id')
						->join('nf_games g2',       'g2.game_id = g.parent_id')
						->join_lang('nf_games_lang gl2', 'game_id', 'g2.game_id')
						->order_by('If(g.parent_id IS NULL, gl.title, CONCAT(gl2.title, gl.title))')
						->get();
	}

	public function get_games_list($all = FALSE, $game_id = NULL)
	{
		$list = [];

		foreach ($this->get_games() as $game)
		{
			if ($game_id == $game['game_id'] || ($game_id && $game_id == $game['parent_id']))
			{
				continue;
			}

			if (empty($game['parent_id']))
			{
				$list[$game['game_id']] = $game['title'];
			}
			else if ($all)
			{
				$list['g'.$game['game_id']] = str_repeat('&nbsp;', 10).$game['title'];
			}
		}

		return $list;
	}

	public function add_game($title, $parent_id, $image_id, $icon_id)
	{
		$game_id = $this->db->insert('nf_games', [
			'name'      => url_title($title),
			'parent_id' => $parent_id ?: NULL,
			'image_id'  => $image_id,
			'icon_id'   => $icon_id
		]);

		$this->db->insert('nf_games_lang', [
			'game_id'   => $game_id,
			'lang'      => $this->config->lang->info()->name,
			'title'     => $title
		]);

		return $game_id;
	}

	public function edit_game($game_id, $title, $parent_id, $image_id, $icon_id)
	{
		$this->db	->where('game_id', $game_id)
					->update('nf_games', [
						'parent_id' => $parent_id ?: NULL,
						'image_id'  => $image_id,
						'icon_id'   => $icon_id,
						'name'      => url_title($title)
					]);

		$this->db	->where('game_id', $game_id)
					->where('lang', $this->config->lang->info()->name)
					->update('nf_games_lang', [
						'title'     => $title
					]);
	}

	public function delete_game($game_id)
	{
		$files = [];

		foreach ($this->db->select('image_id', 'icon_id')->from('nf_games')->where('parent_id', $game_id)->get() as $game)
		{
			$files[] = $game['image_id'];
			$files[] = $game['icon_id'];
		}

		$files = array_merge(
			array_values($this->db->select('image_id', 'icon_id')->from('nf_games')->where('game_id', $game_id)->row()),
			array_filter($files)
		);

		foreach ($files as $file)
		{
			NeoFrag()->model2('file', $file)->delete();
		}

		// couplage(teams): purge a la suppression d'un jeu — on retire les groupes de permission des
		// equipes qui lui etaient rattachees. Sans le module `teams` installe, il n'y a tout
		// simplement rien a purger : on saute. Ce sens du couplage etait declare DUR, ce qui formait
		// un cycle avec `teams` (qui a besoin de `games` pour la donnee elle-meme) et rendait les
		// deux modules impossibles a installer separement. Assoupli le 2026-09-15.
		if ($this->db->table_exists('nf_teams'))
		{
			foreach ($this->db->select('team_id')->from('nf_teams')->where('game_id', $game_id)->get() as $team_id)
			{
				$this->groups->delete('teams', $team_id);
			}
		}

		$this->db	->where('game_id', $game_id)
					->delete('nf_games');
	}
}
