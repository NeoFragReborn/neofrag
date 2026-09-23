<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: NeoFrag Reborn
 */

namespace NF\Modules\Calendar\Models;

use NF\NeoFrag\Loadables\Model;

/**
 * Rappels des événements du calendrier.
 *
 * Le module `events` rappelle ses événements à leurs **participants** ; le calendrier générique n'en
 * a pas. Ce qui manquait n'était donc pas le rappel mais le **suivi** — et le produit en avait déjà
 * un : le mécanisme d'abonnement de `notifications`, employé par les actualités et les articles. Le
 * calendrier déclare son type de contenu comme abonnable, et les abonnés deviennent les
 * destinataires.
 *
 * La mécanique d'envoi est calquée sur `Events::send_due_reminders()`, délibérément : deux rappels
 * qui se comporteraient différemment seraient deux choses à comprendre au lieu d'une.
 */
class Calendar extends Model
{
	/** Fenêtre par défaut, en heures, quand le réglage est absent. */
	public const RAPPEL_HEURES_DEFAUT = 24;

	/**
	 * Envoie les rappels dus, et rend le nombre d'événements rappelés.
	 *
	 * Idempotent : `reminder_sent_at` est posé **avant** l'envoi, et seul le passage qui réussit à le
	 * poser notifie. Deux exécutions du cron qui se chevauchent n'enverront donc pas deux fois — ce
	 * qui arrive pour de vrai quand une exécution traîne et que la suivante démarre.
	 *
	 * @param int $limite plafond par passage, pour ne pas faire traîner le cron
	 */
	public function send_due_reminders(int $limite = 50): int
	{
		$brut   = $this->config->calendar_reminder_hours;
		$heures = ($brut === FALSE || $brut === NULL || $brut === '') ? self::RAPPEL_HEURES_DEFAUT : (int) $brut;

		if ($heures <= 0)
		{
			return 0; // 0 = rappels désactivés par l'exploitant
		}

		$maintenant = date('Y-m-d H:i:s');
		$echeance   = date('Y-m-d H:i:s', strtotime('+'.$heures.' hours'));
		$comptes    = 0;

		foreach ($this->db	->select('id', 'title')
							->from('nf_calendar_events')
							->where('published', '1')
							->where('reminder_sent_at', NULL)
							->where('start_at >', $maintenant)
							->where('start_at <=', $echeance)
							->order_by('start_at ASC')
							->limit($limite)
							->get(FALSE) as $event)
		{
			$pris = (int) $this->db	->where('id', (int) $event['id'])
									->where('reminder_sent_at', NULL)
									->update('nf_calendar_events', ['reminder_sent_at' => $maintenant]);

			if ($pris < 1)
			{
				continue; // un autre passage l'a pris entre-temps
			}

			$this->_notifier_abonnes((int) $event['id'], (string) $event['title']);
			$comptes++;
		}

		return $comptes;
	}

	/**
	 * Une notification par abonné.
	 *
	 * `push_unique` plutôt que `push` : si un rappel a déjà été posé et non lu, on ne l'empile pas.
	 */
	private function _notifier_abonnes(int $event_id, string $titre): void
	{
		/** @var \NF\Modules\Notifications\Notifications|null $notifications */
		$notifications = $this->module('notifications');

		if (!$notifications)
		{
			return; // module absent d'une installation allégée : pas une erreur
		}

		$url = 'calendar/'.$event_id.'/'.url_title($titre);

		foreach ($notifications->subscribers('calendar-event', $event_id) as $user_id)
		{
			$notifications->push_unique(
				(int) $user_id,
				'calendar-reminder',
				$this->lang('Rappel : « %s » commence bientôt', $titre),
				$url,
				NULL
			);
		}
	}
}
