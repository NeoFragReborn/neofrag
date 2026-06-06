<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Monitoring;

use NF\NeoFrag\Addons\Module;

class Monitoring extends Module
{
	protected function __info()
	{
		return [
			'title'       => 'Monitoring',
			'description' => $this->lang('Surveillance de la santé du site : PHP, mémoire, base de données, mises à jour.'),
			'icon'        => 'fas fa-heartbeat',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'admin'       => FALSE,
			'routes'      => [
				'admin'                                => 'index',
				'admin/update/{url_title}'             => 'update',
				'admin/download/{url_title}'           => '_backup_download',
				'admin/delete/{url_title}'             => '_backup_delete',
				'admin/purge'                          => '_backups_purge',
				'admin/cron/reset'                     => '_cron_reset',
				'cron'                                 => 'cron'
			]
		];
	}

	public function need_checking()
	{
		// Re-check si pas de cache OU si le dernier check date de plus de 24h.
		// Logique simple, sans dépendance à strtotime('01:00') qui posait des problèmes
		// entre minuit et 1h du matin (cache jamais refresh dans cette fenêtre).
		if (!file_exists('cache/monitoring/monitoring.json'))
		{
			return TRUE;
		}
		$last = (int)$this->config->nf_monitoring_last_check;
		return $last === 0 || (time() - $last) > 86400;
	}

	public function display()
	{
		if (file_exists('cache/monitoring/monitoring.json'))
		{
			foreach (array_merge(array_fill_keys(['danger', 'warning', 'info'], 0), array_count_values(array_map(function($a){
				return $a[1];
			}, json_decode(file_get_contents('cache/monitoring/monitoring.json'))->notifications))) as $class => $count)
			{
				if ($count)
				{
					return '<span class="float-right badge badge-'.$class.'">'.$count.'</span>';
				}
			}
		}

		return '';
	}
}
