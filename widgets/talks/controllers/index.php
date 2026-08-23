<?php
/**
 * https://neofr.ag
 *
 * Widget Talks (Phase Option 3 — refonte 2026-05-04)
 * Le widget devient un BADGE de notifications cliquable qui pointe vers /talks.
 * Plus de duplication avec la page module /talks.
 */

namespace NF\Widgets\Talks\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($settings = [])
	{
		if (!$this->user())
		{
			// Visiteur anonyme : invitation à se connecter
			return $this	->panel()
							->heading($this->lang('Discussions'), 'far fa-comments')
							->body('<div class="text-center">'
								. '<small>'.$this->lang('Connecte-toi pour accéder aux discussions.').'</small>'
								. '</div>');
		}

		// Compter les unread totaux et conversations actives
		$rows = $this->db->select(	'COUNT(*) as nb_conversations',
									'COALESCE(SUM(unread), 0) as total_unread'
								)
						 ->from('(SELECT t.talk_id, '
								. '(SELECT COUNT(*) FROM nf_talks_messages m WHERE m.talk_id = t.talk_id AND m.deleted_at IS NULL AND (p.last_read_at IS NULL OR m.date > p.last_read_at)) as unread '
								. 'FROM nf_talks t '
								. 'JOIN nf_talks_participants p ON p.talk_id = t.talk_id '
								. 'WHERE p.user_id = '.(int)$this->user->id.' '
								. 'AND p.archived_at IS NULL '
								. 'AND t.deleted_at IS NULL'
								. ') sub')
						 ->row();

		$nb_conversations = is_array($rows) ? (int)$rows['nb_conversations'] : 0;
		$total_unread     = is_array($rows) ? (int)$rows['total_unread']     : 0;

		// Public channels accessibles (pour proposer de les rejoindre)
		$public_count = $this->db	->select('COUNT(*)')
									->from('nf_talks t')
									->where('t.type', 'public')
									->where('t.deleted_at', NULL)
									->where('t.talk_id NOT IN (SELECT talk_id FROM nf_talks_participants WHERE user_id = '.(int)$this->user->id.' AND archived_at IS NULL)')
									->row();
		$public_count = is_array($public_count) ? 0 : (int)$public_count;

		$body = '<div class="text-center">';

		if ($total_unread > 0)
		{
			$body .= '<a href="'.url('talks').'" class="btn btn-primary btn-block">'
				   . icon('fas fa-bell').' '
				   . $this->lang('%d message non lu|%d messages non lus', $total_unread, $total_unread)
				   . ' <span class="badge text-bg-light ms-2">'.$total_unread.'</span>'
				   . '</a>';
		}
		else if ($nb_conversations > 0)
		{
			$body .= '<a href="'.url('talks').'" class="btn btn-light btn-block">'
				   . icon('far fa-comments').' '
				   . $this->lang('%d conversation|%d conversations', $nb_conversations, $nb_conversations)
				   . '</a>';
		}
		else
		{
			$body .= '<a href="'.url('talks').'" class="btn btn-light btn-block">'
				   . icon('far fa-comment-dots').' '
				   . $this->lang('Mes discussions')
				   . '</a>';
		}

		if ($public_count > 0)
		{
			$body .= '<small class="text-muted d-block mt-2">'
				   . $this->lang('%d salon public à découvrir|%d salons publics à découvrir', $public_count, $public_count)
				   . '</small>';
		}

		$body .= '<a href="'.url('talks/new').'" class="btn btn-sm btn-outline-primary mt-2">'
			   . icon('fas fa-plus').' '.$this->lang('Nouvelle discussion')
			   . '</a>';

		$body .= '</div>';

		return $this	->panel()
						->heading($this->lang('Discussions'), 'far fa-comments')
						->body($body);
	}
}
