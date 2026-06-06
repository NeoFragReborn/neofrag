<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Clock\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($settings = [])
	{
		$show_clock    = !isset($settings['clock'])    || $settings['clock']    == '1';
		$show_calendar = !isset($settings['calendar']) || $settings['calendar'] == '1';
		$show_birthday = !isset($settings['birthday']) || $settings['birthday'] == '1';

		$birthdays = $show_birthday ? $this->model()->get_birthdays() : [];

		$this->css('clock');

		return $this->panel()
					->heading($this->lang('Horloge'), 'far fa-clock')
					->body($this->view('index', [
						'birthdays' => $birthdays,
						'birthday'  => $show_birthday,
						'clock'     => $show_clock,
						'calendar'  => $show_calendar
					]), FALSE);
	}
}
