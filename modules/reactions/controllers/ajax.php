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

		$reaction = (string)($_POST['reaction'] ?? 'love');

		if (!$this->user() || !Reactions::is_allowed($type) || !isset(Reactions::REACTIONS[$reaction]))
		{
			echo json_encode(['ok' => FALSE]);
			exit;
		}

		$id      = (int)$id;
		$user_id = (int)$this->user->id;

		// Modèle Facebook : une réaction par (user, contenu). Même emoji re-cliqué → on retire ;
		// emoji différent → on bascule ; aucune → on ajoute.
		$existing = $this->db	->select('id', 'reaction')
								->from('nf_reactions')
								->where('user_id', $user_id)
								->where('content_type', $type)
								->where('content_id', $id)
								->row();

		$added = FALSE;
		$mine  = NULL;

		if ($existing && $existing['reaction'] === $reaction)
		{
			$this->db->where('id', $existing['id'])->delete('nf_reactions');
		}
		else if ($existing)
		{
			$this->db->where('id', $existing['id'])->update('nf_reactions', ['reaction' => $reaction]);
			$mine = $reaction;
		}
		else
		{
			$this->db->insert('nf_reactions', [
				'user_id'      => $user_id,
				'content_type' => $type,
				'content_id'   => $id,
				'reaction'     => $reaction
			]);
			$added = TRUE;
			$mine  = $reaction;
		}

		// Comptes par emoji + total.
		$counts = [];
		foreach ($this->db	->select('reaction', 'COUNT(*) AS n')
							->from('nf_reactions')
							->where('content_type', $type)
							->where('content_id', $id)
							->group_by('reaction')
							->get() as $row)
		{
			if (isset(Reactions::REACTIONS[$row['reaction']]))
			{
				$counts[$row['reaction']] = (int)$row['n'];
			}
		}

		// Notif : seulement à l'ajout d'une nouvelle réaction (pas au switch/retrait).
		if ($added && ($notif = $this->module('notifications')))
		{
			$label = $type === 'comment'
				? $this->lang('%s a aimé votre commentaire', $this->user->username)
				: $this->lang('%s a aimé votre publication', $this->user->username);
			$notif->push_to_content_owner($type, $id, 'reaction', $label, $user_id);
		}

		// Karma + points : recalcule la réputation du propriétaire à chaque changement ; crédite les
		// points (réaction donnée + reçue) uniquement à l'ajout d'une NOUVELLE réaction.
		if ($gam = $this->module('gamification'))
		{
			$gam->on_reaction($type, $id);

			if ($added)
			{
				$gam->earn($user_id, 'reaction_given');

				if ($owner = $gam->content_owner($type, $id))
				{
					$gam->earn($owner, 'reaction_received');
				}
			}
		}

		echo json_encode(['ok' => TRUE, 'mine' => $mine, 'counts' => $counts, 'total' => array_sum($counts)]);
		exit;
	}
}
