<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Events\Models;

use NF\NeoFrag\Loadables\Model;

class Participants extends Model
{
	private $_status = [
		['En attente', 'info',    'fas fa-question'],
		['Présent',    'success', 'fas fa-check'],
		['Absent',     'danger',  'fas fa-times'],
		['Peut-être',  'warning', 'fas fa-ellipsis-h']
	];

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
						->join('nf_session             s',  'u.id       = s.user_id')
						->where('ep.event_id', $event_id)
						->group_by('u.username')
						->order_by('ep.status', 'u.username')
						->get();
	}

	public function status()
	{
		return $this->_status;
	}

	public function label_status($status)
	{
		list($title, $color, $icon) = $this->_status[$status];
		return $this->label($this->lang($title), $icon, $color);
	}

	public function buttons_status($event_id, $event_title, $current_status)
	{
		$dropdown = [];

		foreach ($this->_status as $i => $status)
		{
			if (in_array($i, [0, $current_status]))
			{
				continue;
			}

			list($title, , $icon) = $status;

			$dropdown[] = $this	->label()
								->title($this->lang($title))
								->icon($icon)
								->url('events/participant/'.$event_id.'/'.url_title($event_title).'/'.$i);
		}

		list($title, $color, $icon) = $this->_status[$current_status];

		return $this->button_dropdown()
					->title($this->lang($title))
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
					$content    = '<div class="alert alert-info m-0"><b>Message automatique.</b><br />Vous êtes invité à participer à l\'événement <b>'.htmlspecialchars($title).'</b>.<br /><br />Pour indiquer votre disponibilité, <a href="'.url('events/'.$event_id.'/'.url_title($title)).'">cliquer ici</a>.</div>';

					foreach ($users as $user_id)
					{
						$talk_id = $talks->model()->create_conversation(
							$inviter_id,
							'direct',
							'Invitation événement : '.$title,
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
