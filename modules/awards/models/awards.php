<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Awards\Models;

use NF\NeoFrag\Loadables\Model;

class Awards extends Model
{
	public function get_years()
	{
		return $this->db->select('DISTINCT YEAR(date)')
						->from('nf_awards')
						->order_by('date DESC')
						->get();
	}

	/*
	 * Le nom, le lieu et le récit d'un titre vivent dans `nf_awards_lang`, une ligne par langue (m31,
	 * 2026-10-15). `join_lang()` sert la MEILLEURE : la langue demandée, sinon le français, sinon la
	 * première écrite — un titre décrit dans une seule langue reste lisible dans les cinq autres, au
	 * lieu de disparaître du palmarès.
	 */
	public function get_awards($filter = '', $filter_data = '')
	{
		$this->db	->select('a.*', 'al.name', 'al.location', 'al.description', 't.team_id', 't.name as team_name', 'tl.title as team_title', 'g.game_id', 'g.name as game_name', 'gl.title as game_title')
					->from('nf_awards a')
					->join_lang('nf_awards_lang al', 'award_id', 'a.award_id', NULL, 'LEFT')
					->join('nf_teams t', 'a.team_id = t.team_id')
					->join_lang('nf_teams_lang tl', 'team_id', 't.team_id')
					->join('nf_games g', 'a.game_id = g.game_id')
					->join_lang('nf_games_lang gl', 'game_id', 'a.game_id')
					->order_by('a.date DESC');

		if (!empty($filter) && !empty($filter_data))
		{
			if ($filter == 'date')
			{
				$this->db->where('YEAR(date)', $filter_data);
			}
			else if ($filter == 'team')
			{
				$this->db->where('a.team_id', $filter_data);
			}
			else if ($filter == 'game')
			{
				$this->db->where('a.game_id', $filter_data);
			}
		}

		return $this->db->get();
	}

	public function count_awards($ranking = '', $filter = '', $filter_data = '')
	{
		$this->db	->select('COUNT(award_id) as nb_awards')
					->from('nf_awards');

		if (!empty($ranking))
		{
			$this->db->where('ranking', $ranking);
		}

		if (!empty($filter) && !empty($filter_data))
		{
			if ($filter == 'team')
			{
				$this->db->where('team_id', $filter_data);
			}
			else if ($filter == 'game')
			{
				$this->db->where('game_id', $filter_data);
			}
		}

		return $this->db->get();
	}

	public function check_awards($award_id, $name)
	{
		return $this->db->select('a.*', 'al.name', 'al.location', 'al.description', 't.team_id', 't.name as team_name', 'tl.title as team_title', 'g.game_id', 'g.name as game_name', 'gl.title as game_title')
						->from('nf_awards a')
						->join_lang('nf_awards_lang al', 'award_id', 'a.award_id', NULL, 'LEFT')
						->join('nf_teams t', 'a.team_id = t.team_id')
						->join_lang('nf_teams_lang tl', 'team_id', 't.team_id')
						->join('nf_games g', 'a.game_id = g.game_id')
						->join_lang('nf_games_lang gl', 'game_id', 'a.game_id')
						->where('a.award_id', $award_id)
						->row();
	}

	public function check_team($team_id, $name)
	{
		return $this->db->select('t.team_id', 't.name', 'tl.title', 't.image_id', 't.icon_id', 'tl.description', 't.game_id')
						->from('nf_teams t')
						->join_lang('nf_teams_lang tl', 'team_id', 't.team_id')
						->where('t.team_id', $team_id)
						->where('t.name', $name)
						->row();
	}

	public function check_game($game_id, $name)
	{
		return $this->db->select('g.game_id', 'g.name', 'g.image_id', 'gl.title')
						->from('nf_games g')
						->join_lang('nf_games_lang gl', 'game_id', 'g.game_id')
						->where('g.game_id', $game_id)
						->where('g.name', $name)
						->row();
	}

	public function get_teams()
	{
		return $this->db->select('t.team_id', 't.name', 'tl.title')
						->from('nf_teams t')
						->join_lang('nf_teams_lang tl', 'team_id', 't.team_id')
						->order_by('tl.title')
						->get();
	}

	public function get_teams_list()
	{
		$list = [];

		foreach ($this->get_teams() as $team)
		{
			$list[$team['team_id']] = $team['title'];
		}

		array_natsort($list);

		return $list;
	}

	public function get_teams_ranking($limit = '')
	{
		$this->db	->select('t.team_id', 't.name', 'tl.title as team_title', 'COUNT(DISTINCT CASE WHEN a.ranking = \'1\' THEN a.award_id END) as total_gold', 'COUNT(DISTINCT CASE WHEN a.ranking = \'2\' THEN a.award_id END) as total_silver', 'COUNT(DISTINCT CASE WHEN a.ranking = \'3\' THEN a.award_id END) as total_bronze', 'COUNT(DISTINCT CASE WHEN a.ranking >= \'4\' THEN a.award_id END) as total_other')
					->from('nf_teams t')
					->join_lang('nf_teams_lang tl', 'team_id', 't.team_id')
					->join('nf_awards a', 't.team_id = a.team_id')
					->group_by('t.team_id')
					->order_by('total_gold DESC', 'total_silver DESC', 'total_bronze DESC', 'total_other DESC');

		if (!empty($limit))
		{
			$this->db->limit($limit);
		}

		return $this->db->get();
	}

	public function get_best_team_awards()
	{
		return $this->db->select('COUNT(a.award_id) as nb_awards', 't.team_id', 't.name', 'tl.title as team_title')
						->from('nf_awards a')
						->join('nf_teams t', 'a.team_id = t.team_id')
						->join_lang('nf_teams_lang tl', 'team_id', 't.team_id')
						->group_by('t.team_id')
						->order_by('nb_awards DESC')
						->limit(1)
						->get();
	}

	public function get_best_game_awards()
	{
		return $this->db->select('COUNT(a.award_id) as nb_awards', 'g.game_id', 'g.name', 'gl.title as game_title')
						->from('nf_awards a')
						->join('nf_games g', 'a.game_id = g.game_id')
						->join_lang('nf_games_lang gl', 'game_id', 'g.game_id')
						->group_by('g.game_id')
						->order_by('nb_awards DESC')
						->limit(1)
						->get();
	}

	public function get_games_list()
	{
		$list = [];

		foreach ($this->db->select('g.game_id', 'gl.title')->from('nf_games g')->join_lang('nf_games_lang gl', 'game_id', 'g.game_id')->where('g.parent_id', NULL)->get() as $game)
		{
			$list[$game['game_id']] = $game['title'];
		}

		return $list;
	}

	public function add_awards($date, $team_id, $game_id, $platform, $location, $name, $ranking, $participants, $description, $image_id)
	{
		$award_id = $this->db->insert('nf_awards', [
			'team_id'      => $team_id,
			'date'         => $date,
			'platform'     => $platform,
			'game_id'      => $game_id,
			'ranking'      => $ranking,
			'participants' => $participants,
			'image_id'     => $image_id
		]);

		$this->_ecrire_texte((int) $award_id, $name, $location, $description);

		return $award_id;
	}

	public function edit_awards($award_id, $date, $team_id, $game_id, $platform, $location, $name, $ranking, $participants, $description, $image_id)
	{
		$this->db	->where('award_id', $award_id)
					->update('nf_awards', [
						'team_id'      => $team_id,
						'date'         => $date,
						'platform'     => $platform,
						'game_id'      => $game_id,
						'ranking'      => $ranking,
						'participants' => $participants,
						'image_id'     => $image_id
					]);

		$this->_ecrire_texte((int) $award_id, $name, $location, $description);
	}

	/**
	 * Le texte d'un titre DANS LA LANGUE OÙ L'ON TRAVAILLE. `ecrire_lang()` crée la ligne si elle manque :
	 * la première fois qu'on décrit un titre dans une langue, un `update` n'aurait rien écrit (m30).
	 */
	private function _ecrire_texte(int $award_id, string $name, string $location, string $description): void
	{
		$this->db->ecrire_lang('nf_awards_lang', ['award_id' => $award_id, 'lang' => (string) $this->config->lang->info()->name], [
			'name'        => $name,
			'location'    => $location,
			'description' => $description
		]);
	}

	/**
	 * Le texte tel qu'il est écrit DANS UNE LANGUE, sans repli : c'est ce que l'administration édite — sinon
	 * on proposerait la version française à corriger dans l'écran anglais, et on l'écrirait en anglais.
	 *
	 * @return array{name: string, location: string, description: string}|null
	 */
	public function texte_dans(int $award_id, string $langue): ?array
	{
		$ligne = $this->db	->select('name', 'location', 'description')
							->from('nf_awards_lang')
							->where('award_id', $award_id)
							->where('lang', $langue)
							->row();

		return is_array($ligne) && $ligne ? $ligne : NULL;
	}

	public function delete_awards($award_id)
	{
		NeoFrag()->model2('file', $this->db->select('image_id')->from('nf_awards')->where('award_id', $award_id)->row())->delete();

		$this->db	->where('award_id', $award_id)
					->delete('nf_awards');
	}
}
