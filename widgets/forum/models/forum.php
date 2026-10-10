<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Forum\Models;

use NF\NeoFrag\Loadables\Model;

class Forum extends Model
{
	public function get_last_messages()
	{
		$forums = $this->_get_forum();

		// Ni les messages d'un membre sous shadow ban (audit du 2026-10-09).
		if ($sans_masques = $this->moderation->condition_sans_masques('m.user_id'))
		{
			$this->db->where($sans_masques);
		}

		return $this->_noms_externes($this->db->select('m.message_id', 'm.topic_id', 'm.message', 'm.date', 't.title as topic_title', 'u.id as user_id', 'u.username', 'm.identity_id', 'up.avatar', 'up.sex')
						->from('nf_forum_messages m')
						->join('nf_forum_topics t',  'm.topic_id = t.topic_id')
						->join('nf_user u',          'u.id       = m.user_id AND u.deleted = "0"')
						->join('nf_user_profile up', 'up.id      = m.user_id')
						->where('t.forum_id', $forums)
						// Un message supprimé par la modération n'a plus de texte : il s'affichait en
						// ligne vide, menant vers un message disparu.
						->where('m.deleted_at', NULL)
						->order_by('m.date DESC')
						->limit(3)
						->get());
	}

	public function get_last_topics()
	{
		$forums = $this->_get_forum();

		// Ni les messages d'un membre sous shadow ban (audit du 2026-10-09).
		if ($sans_masques = $this->moderation->condition_sans_masques('m.user_id'))
		{
			$this->db->where($sans_masques);
		}

		return $this->_noms_externes($this->db->select('t.topic_id', 't.title', 'm.message_id', 'u.id as user_id', 'm.date', 'u.username', 'm.identity_id', 'up.avatar', 'up.sex', 't.count_messages')
						->from('nf_forum_messages m')
						->join('nf_forum_topics t',  'm.topic_id = t.topic_id')
						->join('nf_user u',          'u.id       = m.user_id AND u.deleted = "0"')
						->join('nf_user_profile up', 'up.id      = m.user_id')
						->where('t.forum_id', $forums)
						->group_by('t.topic_id')
						->order_by('m.date DESC')
						->limit(3)
						->get());
	}

	/**
	 * Les chiffres du forum que le visiteur peut lire (2026-10-01) : sujets, réponses
	 * encore en ligne, annonces, membres qui y ont écrit — pour le widget « Statistiques » comme pour
	 * l'accueil de la vitrine. Une catégorie réservée à l'équipe n'y entre pas — en
	 * public, on n'annonce que ce que le visiteur peut aller voir.
	 *
	 * @return array{sujets: int, reponses: int, annonces: int, membres: int}
	 */
	public function statistiques_publiques(): array
	{
		if (!$forums = $this->_get_forum())
		{
			return ['sujets' => 0, 'reponses' => 0, 'annonces' => 0, 'membres' => 0];
		}

		$sujets   = (int) $this->db->select('COUNT(*)')->from('nf_forum_topics')->where('forum_id', $forums)->row();
		$annonces = (int) $this->db->select('COUNT(*)')->from('nf_forum_topics')->where('forum_id', $forums)->where('status', ['-2', '1'])->row();
		$messages = (int) $this->db->select('COUNT(*)')->from('nf_forum_messages m')->join('nf_forum_topics t', 't.topic_id = m.topic_id', 'INNER')->where('t.forum_id', $forums)->where('m.deleted_at', NULL)->row();
		$membres  = (int) $this->db->select('COUNT(DISTINCT m.user_id)')->from('nf_forum_messages m')->join('nf_forum_topics t', 't.topic_id = m.topic_id', 'INNER')->where('t.forum_id', $forums)->where('m.deleted_at', NULL)->row();

		return ['sujets' => $sujets, 'reponses' => max(0, $messages - $sujets), 'annonces' => $annonces, 'membres' => $membres];
	}

	/**
	 * Un auteur venu de Discord sans compte lié : son nom d'identité à la place du pseudo
	 * de membre qu'il n'a pas — sans quoi l'accueil de la vitrine l'affichait « Visiteur ».
	 *
	 * @param array<int, array<string, mixed>> $lignes
	 * @return array<int, array<string, mixed>>
	 */
	private function _noms_externes($lignes): array
	{
		$lignes = array_values((array) $lignes);
		$ids    = array_filter(array_map(static fn (array $l) => empty($l['user_id']) ? (int) ($l['identity_id'] ?? 0) : 0, $lignes));
		$forum  = $ids ? \NeoFrag()->module('forum') : NULL;
		$modele = $forum ? $forum->model('forum') : NULL;

		if (!$modele instanceof \NF\Modules\Forum\Models\Forum)
		{
			return $lignes;
		}

		$identites = $modele->identites($ids);

		foreach ($lignes as &$l)
		{
			if (empty($l['user_id']) && isset($identites[(int) ($l['identity_id'] ?? 0)]))
			{
				$l['username'] = $identites[(int) $l['identity_id']]['nom'];
			}
		}

		return $lignes;
	}

	public function _get_forum()
	{
		// Les forums que le visiteur peut lire : la règle — le droit de lecture de la catégorie, et la réserve du
		// VIP, que le widget ignorait jusqu'au 2026-10-04 — vit dans le module, Forum::forums_lisibles(), que la
		// frise de la saison emploie aussi (2026-10-06).
		$forum  = \NeoFrag()->module('forum');
		$modele = $forum ? $forum->model('forum') : NULL;

		return $modele instanceof \NF\Modules\Forum\Models\Forum ? $modele->forums_lisibles() : [];
	}
}
