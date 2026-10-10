<?php
declare(strict_types=1);
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
			'title'       => $this->lang('Monitoring'),
			'description' => $this->lang('Surveillance de la santé du site : PHP, mémoire, base de données, mises à jour.'),
			'icon'        => 'fas fa-heartbeat',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			// Infrastructure : le site ne tourne pas sans lui, l'administration ne propose donc
			// pas de l'eteindre. Reprend a l'identique l'ancien Module/Widget/Theme::$core.
			'deactivatable' => FALSE,
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'admin'       => TRUE,
			'routes'      => [
				'admin'                                => 'index',
				'admin/update/{url_title}'             => 'update',
				'admin/download/{url_title}'           => '_backup_download',
				'admin/delete/{url_title}'             => '_backup_delete',
				'admin/restore/{url_title}'            => '_backup_restore',
				'admin/purge'                          => '_backups_purge',
				'admin/cron/reset'                     => '_cron_reset',
				'admin/webmaster'                      => 'webmaster',
				'admin/files'                          => 'files',
				'admin/journal'                        => '_journal',
				'admin/journal/telecharger'            => '_journal_telecharger',
				'admin/journal/vider'                  => '_journal_vider',
				'admin/diagnostic/(debogage|trace|traductions)/(allumer|eteindre)' => '_diagnostic',
				'admin/trace'                          => '_trace',
				'admin/trace/telecharger'              => '_trace_telecharger',
				'admin/trace/vider'                    => '_trace_vider',
				'admin/traductions'                    => '_traductions',
				'admin/traductions/vider'              => '_traductions_vider',
				'admin/introuvables'                   => '_introuvables',
				'admin/introuvables/oublier/{id}'      => '_introuvables_oublier',
				'admin/introuvables/vider'             => '_introuvables_vider',
				'admin/images-editeur'                 => '_images_editeur',
				'admin/adresse'                        => '_adresse',
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
			}, (array) ((json_decode((string) @file_get_contents('cache/monitoring/monitoring.json'), TRUE) ?: [])['notifications'] ?? [])))) as $class => $count)
			{
				if ($count)
				{
					return '<span class="float-end badge '.badge_class($class).'">'.$count.'</span>';
				}
			}
		}

		return '';
	}
}
