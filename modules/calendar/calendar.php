<?php
/**
 * https://neofr.ag
 * Module Calendar — calendrier généraliste avec export iCal.
 * Différent du module Events (qui contient des features gaming-flavored : matches, tournaments).
 */

namespace NF\Modules\Calendar;

use NF\NeoFrag\Addons\Module;

class Calendar extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Calendrier'),
			'description' => $this->lang('Calendrier d\'événements avec export iCal RFC 5545.'),
			'icon'        => 'far fa-calendar',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				''                              => 'index',
				'past'                          => '_past',
				'ical'                          => '_ical',
				'{id}/{url_title}'              => '_event',
				'admin{pages}'                  => 'index',
				'admin/add'                     => '_add',
				'admin/{id}/{url_title}'        => '_edit',
				'admin/delete/{id}/{url_title}' => '_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => 'Calendrier',
						'icon'   => 'far fa-calendar',
						'access' => [
							'manage' => ['title' => 'Gérer événements', 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}

	public static function format_dt($dt, $all_day = FALSE, $end_dt = NULL)
	{
		$start = strtotime($dt);
		if ($all_day)
		{
			$out = date('j F Y', $start);
			if ($end_dt && date('Y-m-d', strtotime($end_dt)) !== date('Y-m-d', $start))
			{
				$out .= ' – '.date('j F Y', strtotime($end_dt));
			}
			$out .= ' '.NeoFrag()->lang('(toute la journée)');
			return $out;
		}

		$out = date('j F Y H:i', $start);
		if ($end_dt && strtotime($end_dt) > $start)
		{
			$end = strtotime($end_dt);
			if (date('Y-m-d', $end) === date('Y-m-d', $start))
			{
				$out .= ' – '.date('H:i', $end);
			}
			else
			{
				$out .= ' → '.date('j F Y H:i', $end);
			}
		}
		return $out;
	}
}
