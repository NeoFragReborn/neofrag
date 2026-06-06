<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Awards\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($settings = [])
	{
		if ($awards = $this->module('awards')->model()->get_awards())
		{
			$this->module('awards')->css('awards');

			$count = max(1, min(20, (int)($settings['count'] ?? 5)));
			$view  = $this->view('index', [
				'awards' => array_slice($awards, 0, $count)
			]);

			if (($settings['display_panel'] ?? 'oui') === 'non')
			{
				return $view;
			}

			return $this->panel()
						->heading($this->lang('Nos derniers palmarès'))
						->body($view, FALSE)
						->footer('<a href="'.url('awards').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Tous nos palmarès').'</a>', 'right');
		}
		else
		{
			return $this->panel()
						->heading($this->lang('Palmarès'))
						->body($this->lang('Aucun palmarès pour le moment...'));
		}
	}

	public function best_team($settings = [])
	{
		if ($best_team = $this->module('awards')->model()->get_best_team_awards())
		{
			return $this->panel()
						->heading($this->lang('Palmarès'))
						->body($this->view('best_team', [
							'team_id'    => $best_team[0]['team_id'],
							'name'       => $best_team[0]['name'],
							'team_title' => $best_team[0]['team_title'],
							'nb_awards'  => $best_team[0]['nb_awards']
						]))
						->footer('<a href="'.url('awards').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Tous nos palmarès').'</a>', 'right');
		}
		else
		{
			return $this->panel()
						->heading($this->lang('Palmarès'))
						->body($this->lang('Aucun palmarès pour le moment...'));
		}
	}

	public function best_game($settings = [])
	{
		if ($best_game = $this->module('awards')->model()->get_best_game_awards())
		{
			return $this->panel()
						->heading($this->lang('Palmarès'))
						->body($this->view('best_game', [
							'game_id'    => $best_game[0]['game_id'],
							'name'       => $best_game[0]['name'],
							'game_title' => $best_game[0]['game_title'],
							'nb_awards'  => $best_game[0]['nb_awards']
						]))
						->footer('<a href="'.url('awards').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Tous nos palmarès').'</a>', 'right');
		}
		else
		{
			return $this->panel()
						->heading($this->lang('Palmarès'))
						->body($this->lang('Aucun palmarès pour le moment...'));
		}
	}
}
