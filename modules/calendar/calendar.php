<?php
declare(strict_types=1);
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
			'description' => $this->lang('Calendrier des activités avec lieu et description, export iCal vers un agenda et rappel aux membres qui suivent un événement. Pour une association ou un club.'),
			'icon'        => 'far fa-calendar',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['association', 'gaming'],
			'requires'    => [],
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
			],
			/**
			 * Combien d'heures avant le début un rappel part aux membres qui SUIVENT l'événement
			 *. 0 désactive.
			 *
			 * Même forme et même valeur par défaut que `events_reminder_hours` : les deux rappels se
			 * règlent pareil, ce qui évite d'avoir deux choses à comprendre au lieu d'une.
			 */
			'settings'    => function(){
				return $this	->form2()
								->rule($this->form_number('calendar_reminder_hours')
											->title($this->lang('Rappel avant un événement suivi (heures, 0 = désactivé)'))
											->value($this->config->calendar_reminder_hours === FALSE ? '24' : (string) $this->config->calendar_reminder_hours)
								)
								->success(function($data){
									$this->config('calendar_reminder_hours', max(0, (int) $data['calendar_reminder_hours']));

									notify($this->lang('Configuration modifiée'));
									refresh();
								});
			}
		];
	}

	/**
	 * Descripteurs de contenu — cf. `Module::content_types()`.
	 *
	 * `subscribable` est ce qui donne au calendrier son bouton « Suivre » et, par ricochet, ses
	 * destinataires de rappel. Le mécanisme est celui des actualités et des articles :
	 * rien de neuf n'a été inventé pour le calendrier.
	 *
	 * Pas de `reactable` ni de `revisable` : on ne réagit pas à une date, et un événement n'a pas
	 * d'historique de rédaction.
	 */
	public function declare_content_types()
	{
		return [
			'calendar-event' => [
				'table'        => 'nf_calendar_events',
				'pk'           => 'id',
				'author'       => 'user_id',
				'subscribable' => TRUE,
			],
		];
	}

	/** L'adresse publique d'un événement, pour la notification. */
	public function content_url($type, $id)
	{
		if ($type !== 'calendar-event')
		{
			return '';
		}

		// Une requête à UNE colonne rend la valeur, pas la ligne (`Db::row()`) : lire `['title']` sur
		// le titre lui-même levait une TypeError sous PHP 8, à chaque lien vers un événement.
		$title = NeoFrag()->db->select('title')->from('nf_calendar_events')->where('id', (int) $id)->row();

		return is_string($title) && $title !== '' ? 'calendar/'.(int) $id.'/'.url_title($title) : '';
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Calendrier'),
						'icon'   => 'far fa-calendar',
						'access' => [
							'manage' => ['title' => $this->lang('Gérer les événements'), 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}

	public static function format_dt($dt, $all_day = FALSE, $end_dt = NULL)
	{
		// Méthode statique : pas de $this. Les clés vivent dans les langs du module, pas dans celles du cœur —
		// demandées au cœur, elles étaient « Unfound » dans toute langue autre que le français.
		// Les dates passent par timetostr() et un format traduit : date() seul écrivait les mois en anglais
		// (« 5 September 2026 ») sur un site français, et l'ordre des mots n'est pas le même partout.
		$nf    = NeoFrag();
		$mod   = $nf->module('calendar') ?: $nf;
		$start = strtotime($dt);
		if ($all_day)
		{
			// Une journée entière est une date de calendrier : passée en « Y-m-d », timetostr() ne la
			// convertit pas de fuseau (le 5 octobre resterait sinon le 4 à l'ouest de l'heure universelle).
			$out = timetostr($mod->lang('j F Y'), substr((string) $dt, 0, 10));
			if ($end_dt && substr((string) $end_dt, 0, 10) !== substr((string) $dt, 0, 10))
			{
				$out .= ' – '.timetostr($mod->lang('j F Y'), substr((string) $end_dt, 0, 10));
			}
			$out .= ' '.$mod->lang('(toute la journée)');
			return $out;
		}

		$out = timetostr($mod->lang('j F Y H:i'), $start);
		if ($end_dt && strtotime($end_dt) > $start)
		{
			$end = strtotime($end_dt);
			if (timetostr('Y-m-d', $end) === timetostr('Y-m-d', $start))
			{
				$out .= ' – '.timetostr('H:i', $end);
			}
			else
			{
				$out .= ' → '.timetostr($mod->lang('j F Y H:i'), $end);
			}
		}
		return $out;
	}

	/**
	 * Les notifications que ce module envoie, pour les préférences de chaque membre (Notifications::types(),
	 * chantier A, étape A4).
	 *
	 * @return list<array<string, mixed>>
	 */
	public function types_de_notification(): array
	{
		return [
			['type' => 'calendar-reminder', 'titre' => (string) $this->lang('Le rappel d’un rendez-vous du calendrier'), 'ordre' => 42],
		];
	}
}
