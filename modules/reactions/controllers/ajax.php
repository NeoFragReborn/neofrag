<?php
/**
 * https://neofr.ag
 * Endpoint AJAX : /ajax/reactions/toggle/{type}/{id} — bascule le "j'aime" de l'utilisateur courant.
 */

namespace NF\Modules\Reactions\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Reactions\Reactions;

class Ajax extends Controller_Module
{
	public function _toggle($type, $id)
	{
		header('Content-Type: application/json');

		if (!$this->user() || !Reactions::is_allowed($type))
		{
			echo json_encode(['ok' => FALSE]);
			exit;
		}

		$id      = (int)$id;
		$user_id = (int)$this->user->id;

		$existing = $this->db	->select('id')
								->from('nf_reactions')
								->where('user_id', $user_id)
								->where('content_type', $type)
								->where('content_id', $id)
								->row();

		if ($existing)
		{
			$this->db->where('id', $existing)->delete('nf_reactions');
			$reacted = FALSE;
		}
		else
		{
			$this->db->insert('nf_reactions', [
				'user_id'      => $user_id,
				'content_type' => $type,
				'content_id'   => $id
			]);
			$reacted = TRUE;
		}

		$count = (int)$this->db	->select('COUNT(*)')
								->from('nf_reactions')
								->where('content_type', $type)
								->where('content_id', $id)
								->row();

		if ($reacted && ($notif = $this->module('notifications')))
		{
			$label = $type === 'comment'
				? $this->lang('%s a aimé votre commentaire', $this->user->username)
				: $this->lang('%s a aimé votre publication', $this->user->username);
			$notif->push_to_content_owner($type, $id, 'reaction', $label, $user_id);
		}

		// Karma + points : recalcule la réputation du propriétaire (toggle) ; crédite les points
		// (réaction donnée + reçue) uniquement à l'ajout d'une réaction.
		if ($gam = $this->module('gamification'))
		{
			$gam->on_reaction($type, $id);

			if ($reacted)
			{
				$gam->earn($user_id, 'reaction_given');

				if ($owner = $gam->content_owner($type, $id))
				{
					$gam->earn($owner, 'reaction_received');
				}
			}
		}

		echo json_encode(['ok' => TRUE, 'reacted' => $reacted, 'count' => $count]);
		exit;
	}
}
