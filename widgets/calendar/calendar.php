<?php
namespace NF\Widgets\Calendar;
use NF\NeoFrag\Addons\Widget;

class Calendar extends Widget
{
	protected function __info()
	{
		return [
			'title'   => $this->lang('Calendrier'),
			'description' => $this->lang('Prochains événements du calendrier.'),
			'author'  => 'NeoFrag',
			'license' => 'LGPLv3',
			'version' => '1.0',
			'depends' => ['neofrag' => '0.2.0'],
			'types'   => ['upcoming' => $this->lang('Prochains événements')]
		];
	}
}
