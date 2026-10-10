<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function _forum($forum_id, $title, $page = '')
	{
		// Vérifié par son titre par défaut, que la vérification accepte toujours ; elle rend celui de la langue servie,
		// que portent ses liens. Une adresse au mauvais titre — un titre changé, une autre langue — mène à la bonne au
		// lieu de répondre 404 (m06).
		$demande = (string) $title;
		$defaut  = nf_titre_lu($this->db->select('title')->from('nf_forum')->where('forum_id', (int) $forum_id)->row());

		if ($defaut === '')
		{
			return;
		}

		$title = url_title($defaut);

		if (($forum = $this->model()->check_forum($forum_id, $title)) !== FALSE)
		{
			if ($this->access('forum', 'category_read', $forum['category_id']))
			{
				nf_bon_titre($demande, (string) $title, 'forum/'.(int) $forum_id, (string) $page);

				// Forum « lien de redirection » : seulement si une URL réelle est définie. Une chaîne
				// VIDE (cas par défaut, nf_forum_url.url = '') n'est PAS une redirection — sinon
				// header('Location: ') recharge la même URL → boucle infinie. (Le modèle teste déjà
				// la véracité ; le checker doit être cohérent.)
				if (!empty($forum['url']))
				{
					$this->model()->increment_redirect($forum_id);
					nf_quitter_le_site((string) $forum['url'], 'forum');
				}
				else
				{
					$announces = $messages = [];
					$prefixe   = max(0, (int) ($_GET['prefixe'] ?? 0));

					foreach ($this->model()->get_topics($forum_id, $prefixe) as $topic)
					{
						if ($topic['announce'])
						{
							$announces[] = $topic;
						}
						else
						{
							$messages[] = $topic;
						}
					}

					return [
						$forum_id,
						$title,
						$forum['category_id'],
						$forum['subforums'] ? $this->model()->get_forums($forum_id) : [],
						$announces,
						$this->module->pagination->fix_items_per_page($this->config->forum_topics_per_page)->get_data($messages, $page),
						$prefixe
					];
				}
			}
			else
			{
				$this->error->unauthorized();
			}
		}
	}

	public function _new($forum_id, $title)
	{
		if (($forum = $this->model()->check_forum($forum_id, $title)) !== FALSE && empty($forum['url']))
		{
			if ($this->access('forum', 'category_write', $forum['category_id']))
			{
				return [$forum_id, $title, $forum['category_id']];
			}
			else
			{
				$this->error->unauthorized();
			}
		}
	}

	/**
	 * `forum/piece-jointe/{id}` : une pièce jointe, servie par le site après les contrôles du sujet qui la porte — droit
	 * de lire la catégorie, réserve VIP, auteur sous shadow ban, message supprimé (audit du 2026-10-09). Le fichier se
	 * servait tel quel à quiconque avait son adresse, celui d'un forum réservé aussi.
	 */
	public function _piece_jointe($attachment_id)
	{
		$piece = $this->db	->select('a.mime_type', 'f.name', 'f.path', 'm.topic_id', 'm.user_id', 'm.deleted_at', 't.title')
							->from('nf_forum_attachments a')
							->join('nf_file f', 'f.id = a.file_id', 'INNER')
							->join('nf_forum_messages m', 'm.message_id = a.message_id', 'INNER')
							->join('nf_forum_topics t', 't.topic_id = m.topic_id', 'INNER')
							->where('a.attachment_id', (int) $attachment_id)
							->row(FALSE);

		$titre  = $piece ? url_title((string) $piece['title']) : '';
		$modele = $this->model('forum');

		if ($piece && $modele instanceof \NF\Modules\Forum\Models\Forum
			&& ($piece['deleted_at'] === NULL || $this->access->effective_admin())
			&& ($sujet = $modele->check_topic((int) $piece['topic_id'], $titre))
			&& $this->access('forum', 'category_read', $sujet['category_id'])
			&& !in_array((int) $piece['user_id'], $this->moderation->auteurs_masques(), TRUE))
		{
			return [(string) $piece['path'], (string) $piece['name'], (string) $piece['mime_type']];
		}
	}

	public function _topic($topic_id, $title, $page = '')
	{
		// Vérifié avec son VRAI titre ; l'adresse au mauvais titre — le titre changé depuis — mène à la bonne au lieu de
		// répondre 404 (m06).
		$demande = (string) $title;
		$vrai    = nf_titre_lu($this->db->select('title')->from('nf_forum_topics')->where('topic_id', (int) $topic_id)->row());

		if ($vrai === '')
		{
			return;
		}

		$title = url_title($vrai);

		if ($topic = $this->model()->check_topic($topic_id, $title))
		{
			if ($this->access('forum', 'category_read', $topic['category_id']))
			{
				nf_bon_titre($demande, $vrai, 'forum/topic/'.(int) $topic_id, (string) $page);

				return [
					$topic_id,
					$title,
					$topic['forum_id'],
					$topic['title'],
					$topic['category_id'],
					$topic['views'],
					count(array_unique(array_map(function($a){ return $a['user_id']; }, $messages = $this->model()->get_messages($topic_id, $topic['forum_id'])))),
					count($messages) - 1,
					$topic['announce'],
					$topic['locked'],
					array_shift($messages),
					$this->module->pagination->fix_items_per_page($this->config->forum_messages_per_page)->get_data($messages, $page),
					(int) $topic['prefix_id'],
					(int) $topic['solution_message_id'],
					(int) $topic['topic_user_id']
				];
			}
			else
			{
				$this->error->unauthorized();
			}
		}
	}

	/**
	 * Marquer — ou retirer — la réponse qui résout un sujet : son auteur, ou un modérateur de la
	 * catégorie. La première réponse d'un sujet est la question, elle ne peut pas être sa solution.
	 */
	public function _solution($message_id, $title)
	{
		if (($message = $this->_modele_forum()->check_message($message_id, $title)) && empty($message['is_topic']))
		{
			$auteur = (int) $this->db->select('m.user_id')->from('nf_forum_topics t')->join('nf_forum_messages m', 'm.message_id = t.message_id')->where('t.topic_id', $message['topic_id'])->row();

			if (($this->user() && $auteur === (int) $this->user->id) || $this->access('forum', 'category_modify', $message['category_id']))
			{
				return [$message];
			}

			$this->error->unauthorized();
		}
	}

	public function _topic_announce($topic_id, $title, $permission = 'category_announce')
	{
		if ($topic = $this->model()->check_topic($topic_id, $title))
		{
			if ($this->access('forum', $permission, $topic['category_id']))
			{
				return [$topic_id, $topic['topic_title'], $topic['announce'], $topic['locked']];
			}
			else
			{
				$this->error->unauthorized();
			}
		}
	}

	public function _topic_lock($topic_id, $title)
	{
		return $this->_topic_announce($topic_id, $title, 'category_lock');
	}

	public function _message_edit($message_id, $title)
	{
		if ($message = $this->model()->check_message($message_id, $title))
		{
			if ($this->access('forum', 'category_modify', $message['category_id']) || (!$message['locked'] && $this->user->id && $message['user_id'] == $this->user->id))
			{
				return $message;
			}
			else
			{
				$this->error->unauthorized();
			}
		}
	}

	public function _message_delete($message_id, $title)
	{
		$this->ajax();

		$message = $this->db	->select('m.user_id', 'f.forum_id', 'f.parent_id as category_id', 't.topic_id', 't.title', 't.message_id = m.message_id as is_topic')
								->from('nf_forum_messages m')
								->join('nf_forum_topics t', 'm.topic_id = t.topic_id')
								->join('nf_forum        f', 't.forum_id = f.forum_id')
								->where('m.message_id', (int)$message_id)
								->row();

		if ($message && $title == url_title($message['title']))
		{
			if ($this->access('forum', 'category_delete', $message['category_id']) || ($this->user() && $message['user_id'] == $this->user->id))
			{
				return [$message_id, $message['title'], $message['topic_id'], $message['forum_id'], $message['is_topic']];
			}

			$this->error->unauthorized();
		}
	}

	public function _subscribe($topic_id, $title)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}

		if ($topic = $this->model()->check_topic($topic_id, $title))
		{
			if ($this->access('forum', 'category_read', $topic['category_id']))
			{
				return [$topic_id, $topic['topic_title']];
			}
			else
			{
				$this->error->unauthorized();
			}
		}
	}

	public function _unsubscribe($topic_id, $title)
	{
		return $this->_subscribe($topic_id, $title);
	}

	public function _search()
	{
		// Settings : search publique ou réservée aux membres ?
		if (!empty($this->config->forum_search_members_only) && !$this->user())
		{
			$this->error->unauthorized();
			return;
		}

		return [];
	}

	public function _subscriptions($page = '')
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}

		$subscriptions = $this->model()->get_subscriptions($this->user->id);

		return [$this->module->pagination->fix_items_per_page(20)->get_data($subscriptions, $page)];
	}

	public function mark_all_as_read()
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
		}

		return [];
	}

	public function _mark_all_as_read($forum_id, $title)
	{
		if (($forum = $this->model()->check_forum($forum_id, $title)) !== FALSE && empty($forum['url']))
		{
			if ($this->user() && $this->access('forum', 'category_read', $forum['category_id']))
			{
				return [$forum_id, $title];
			}
			else
			{
				$this->error->unauthorized();
			}
		}
	}

	/** Le modèle du forum, typé : pour l'analyse statique, `$this->model()` rend un modèle générique. */
	private function _modele_forum(): \NF\Modules\Forum\Models\Forum
	{
		$modele = $this->model('forum');

		if (!$modele instanceof \NF\Modules\Forum\Models\Forum)
		{
			throw new \LogicException('modèle du forum introuvable');
		}

		return $modele;
	}
}
