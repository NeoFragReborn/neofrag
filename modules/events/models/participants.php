<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Events\Models;

use NF\NeoFrag\Loadables\Model;

class Participants extends Model
{
	public function count_participants($event_id)
	{
		return implode(' / ', array_unique($this->db->select('COALESCE(SUM(IF(status = 1, 1, 0)), 0) as participants', 'COUNT(*) as total')->from('nf_events_participants')->where('event_id', $event_id)->row()));
	}

	public function get_participants($event_id)
	{
		return $this->db->select('u.id as user_id', 'u.username', 'u.admin', 'up.avatar', 'up.sex', 'MAX(s.last_activity) > DATE_SUB(NOW(), INTERVAL 5 MINUTE) as online', 'ep.status')
						->from('nf_events_participants ep')
						->join('nf_user                u',  'ep.user_id = u.id AND u.deleted = "0"', 'INNER')
						->join('nf_user_profile        up', 'u.id       = up.id')
						// LEFT : la table des sessions ne sert qu'au temoin « en ligne ». En jointure
						// stricte, un participant qui n'a aucune session ouverte DISPARAISSAIT de la
						// liste des participants.
						->join('nf_session             s',  'u.id       = s.user_id', 'LEFT')
						->where('ep.event_id', $event_id)
						->group_by('u.username')
						->order_by('ep.status', 'u.username')
						->get();
	}

	/**
	 * Les statuts de participation : libellé, couleur, icône. Construits à l'appel, et non en valeur de
	 * propriété, pour que chaque libellé passe par lang() avec son texte littéral (relevé par check-langs).
	 */
	public function status()
	{
		return [
			[$this->lang('En attente'), 'info',    'fas fa-question'],
			[$this->lang('Présent'),    'success', 'fas fa-check'],
			[$this->lang('Absent'),     'danger',  'fas fa-times'],
			[$this->lang('Peut-être'),  'warning', 'fas fa-ellipsis-h']
		];
	}

	public function label_status($status)
	{
		list($title, $color, $icon) = $this->status()[$status];
		return $this->label($title, $icon, $color);
	}

	public function buttons_status($event_id, $event_title, $current_status)
	{
		$dropdown = [];

		$statuts = $this->status();

		foreach ($statuts as $i => $status)
		{
			if (in_array($i, [0, $current_status]))
			{
				continue;
			}

			list($title, , $icon) = $status;

			$dropdown[] = $this	->label()
								->title($title)
								->icon($icon)
								// Le jeton de session : un lien piégé changeait la disponibilité d'un membre (audit du 2026-10-09).
								->url('events/participant/'.$event_id.'/'.url_title($event_title).'/'.$i.'?_='.nf_jeton_csrf());
		}

		list($title, $color, $icon) = $statuts[$current_status];

		return $this->button_dropdown()
					->title($title)
					->icon($icon)
					->color($color)
					->compact()
					->dropdown($dropdown);
	}

	public function invite($event_id, $title, $users)
	{
		foreach ($users as $user_id)
		{
			$this->db->insert('nf_events_participants', [
				'event_id' => $event_id,
				'user_id'  => $user_id,
				'status'   => 0
			]);
		}

		$actor = $this->user() ? (int)$this->user->id : NULL;

		// Notification cloche in-site : chaque invité est prévenu (push() ignore l'auto-notif).
		if ($notifications = $this->module('notifications'))
		{
			$url = 'events/'.$event_id.'/'.url_title($title);

			foreach ($users as $user_id)
			{
				$notifications->push((int)$user_id, 'event-invite', $this->lang('Vous êtes invité à l\'événement : %s', $title), $url, $actor);
			}
		}

		// Migration MP → Talks : les invitations sont envoyées comme conversations direct
		// pilotées par l'inviteur (visible dans l'espace Messagerie de chaque invité).
		if ($this->config->events_alert_mp && ($users = array_diff($users, [$this->user->id])) && $this->user())
		{
			try
			{
				if ($talks = $this->module('talks'))
				{
					$inviter_id = (int)$this->user->id;
					$content    = '<div class="alert alert-info m-0"><b>'.$this->lang('Message automatique.').'</b><br />'
						.$this->lang('Vous êtes invité à participer à l\'événement <b>%s</b>.', nf_texte($title)).'<br /><br />'
						.$this->lang('Pour indiquer votre disponibilité, <a href="%s">cliquez ici</a>.', url('events/'.$event_id.'/'.url_title($title))).'</div>';

					foreach ($users as $user_id)
					{
						$talk_id = $talks->model()->create_conversation(
							$inviter_id,
							'direct',
							(string) $this->lang('Invitation à l\'événement : %s', $title),
							'',
							[(int)$user_id]
						);
						if ($talk_id)
						{
							$talks->model()->send_message($talk_id, $inviter_id, $content);
						}
					}
				}
			}
			catch (\Throwable $e) {}
		}
	}
}
