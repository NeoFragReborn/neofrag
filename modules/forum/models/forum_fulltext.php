<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum\Models;

/**
 * Recherche FULLTEXT du forum (MATCH ... AGAINST sur `ft_message` et `ft_title`) et son
 * administration — extrait du modèle Forum.
 *
 * La construction de la requête booléenne vit ici avec la requête elle-même : c'est le même
 * sujet, et les séparer ferait voyager une chaîne à demi échappée entre deux fichiers.
 */
trait Forum_Fulltext
{
	public function search_fulltext($query, $forum_id = NULL, $author = NULL, $date_from = NULL, $date_to = NULL, $sort = 'relevance')
	{
		$query = trim((string)$query);
		if ($query === '' || strlen($query) < 3)
		{
			return [];
		}

		$accessible_categories = array_filter(
			$this->db->select('category_id')->from('nf_forum_categories')->get(),
			function($a){
				return $this->access('forum', 'category_read', $a);
			}
		);

		if (empty($accessible_categories))
		{
			return [];
		}

		// MATCH AGAINST en mode boolean (supporte les opérateurs +/-/")
		$boolean = $this->_to_boolean_query($query);

		$relevance_select = 'MATCH(m.message) AGAINST ('.$this->_quote($boolean).' IN BOOLEAN MODE) + IFNULL(MATCH(t.title) AGAINST ('.$this->_quote($boolean).' IN BOOLEAN MODE), 0) AS relevance';

		$this->db	->select(	'm.message_id',
								'm.message',
								'UNIX_TIMESTAMP(m.date) as date',
								't.topic_id',
								't.title as topic_title',
								't.count_messages',
								'f.forum_id',
								'f.title as forum_title',
								'u.id as user_id',
								'u.username',
								$relevance_select
							)
					->from('nf_forum_messages m')
					->join('nf_forum_topics   t',  'm.topic_id = t.topic_id')
					->join('nf_forum          f',  't.forum_id = f.forum_id')
					->join('nf_forum          f2', 'f.parent_id = f2.forum_id AND f.is_subforum = "1"')
					->join('nf_user           u',  'm.user_id = u.id AND u.deleted = "0"')
					->where('IFNULL(f2.parent_id, f.parent_id)', $accessible_categories)
					->where('(MATCH(m.message) AGAINST ('.$this->_quote($boolean).' IN BOOLEAN MODE) > 0 OR MATCH(t.title) AGAINST ('.$this->_quote($boolean).' IN BOOLEAN MODE) > 0)');

		if ($forum_id)
		{
			$this->db->where('t.forum_id', (int)$forum_id);
		}

		if ($author)
		{
			$this->db->where('u.username', (string)$author);
		}

		if ($date_from)
		{
			$this->db->where('m.date >=', date('Y-m-d H:i:s', strtotime($date_from)));
		}

		if ($date_to)
		{
			$this->db->where('m.date <=', date('Y-m-d H:i:s', strtotime($date_to)));
		}

		// Exclure les messages soft-deleted
		$this->db->where('m.deleted_at', NULL);

		if ($sort === 'date_desc')
		{
			$this->db->order_by('m.date DESC');
		}
		else if ($sort === 'date_asc')
		{
			$this->db->order_by('m.date ASC');
		}
		else
		{
			$this->db->order_by('relevance DESC');
		}

		return $this->db->limit(200)->get();
	}

	private function _to_boolean_query($query)
	{
		return \NF\Modules\Forum\Lib\Forum_Search::to_boolean_query((string)$query);
	}

	private function _quote($s)
	{
		return \NF\Modules\Forum\Lib\Forum_Search::quote((string)$s);
	}

	public function reindex_fulltext()
	{
		// Force reindex via OPTIMIZE TABLE qui rebuild les FT indexes
		$this->db->execute('OPTIMIZE TABLE nf_forum_messages');
		$this->db->execute('OPTIMIZE TABLE nf_forum_topics');
		return TRUE;
	}

	public function get_search_stats()
	{
		$msg_count = $this->db->from('nf_forum_messages')->where('deleted_at IS NULL')->count();
		$topic_count = $this->db->from('nf_forum_topics')->count();

		return [
			'indexed_messages' => $msg_count,
			'indexed_topics'   => $topic_count
		];
	}
}
