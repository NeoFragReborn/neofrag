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

		return $this->db->select('m.message_id', 'm.topic_id', 'm.message', 'm.date', 't.title as topic_title', 'u.id as user_id', 'u.username', 'up.avatar', 'up.sex')
						->from('nf_forum_messages m')
						->join('nf_forum_topics t',  'm.topic_id = t.topic_id')
						->join('nf_user u',          'u.id       = m.user_id AND u.deleted = "0"')
						->join('nf_user_profile up', 'up.id      = m.user_id')
						->where('t.forum_id', $forums)
						->order_by('m.date DESC')
						->limit(3)
						->get();
	}

	public function get_last_topics()
	{
		$forums = $this->_get_forum();

		return $this->db->select('t.topic_id', 't.title', 'm.message_id', 'u.id as user_id', 'm.date', 'u.username', 'up.avatar', 'up.sex', 't.count_messages')
						->from('nf_forum_messages m')
						->join('nf_forum_topics t',  'm.topic_id = t.topic_id')
						->join('nf_user u',          'u.id       = m.user_id AND u.deleted = "0"')
						->join('nf_user_profile up', 'up.id      = m.user_id')
						->where('t.forum_id', $forums)
						->group_by('t.topic_id')
						->order_by('m.date DESC')
						->limit(3)
						->get();
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

	public function _get_forum()
	{
		$categories = [];

		// Une requête à UNE colonne rend des scalaires, pas des lignes (`Db::get()`). La version
		// précédente lisait `$category['category_id']` sur un entier : aucune catégorie n'était
		// retenue, et le widget n'affichait jamais aucun sujet.
		foreach ($this->db->select('category_id')->from('nf_forum_categories')->get() as $category_id)
		{
			if ($this->access('forum', 'category_read', $category_id))
			{
				$categories[] = $category_id;
			}
		}

		return $this->db->select('f.forum_id')
						->from('nf_forum f')
						->join('nf_forum f2', 'f2.forum_id = f.parent_id AND f.is_subforum = "1"')
						->where('IFNULL(f2.parent_id, f.parent_id)', $categories)
						->get();
	}
}
