<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index()
	{
		$panels = $this->array;

		foreach ($this->model()->get_categories() as $category)
		{
			$panels->append($this->panel()->body($this->view('index', $category), FALSE));
		}

		if ($panels->empty())
		{
			$panels->append($this	->panel()
									->heading($this->lang('Forum'), 'fas fa-comments')
									->body('<div class="text-center">'.$this->lang('Aucun forum').'</div>')
									->color('info'));
		}

		if ($this->user())
		{
			$actions = $this->panel()
							->body('<a class="btn btn-light" href="'.url('forum/mark-all-as-read').'" data-bs-toggle="tooltip" title="'.$this->lang('Marquer tous les messages comme étant lus').'">'.icon('far fa-eye').'</a>')
							->style('card-transparent text-end');

			$panels->prepend($actions)->append($actions);
		}

		return $panels;
	}

	public function _forum($forum_id, $title, $category_id, $subforums, $announces, $topics, $prefixe = 0)
	{
		$prefixes = $this->_modele_forum()->prefixes();
		$this	->title($title)
				->_breadcrumb($category_id, $forum_id);

		$panels = $this->array;

		if (!empty($subforums))
		{
			$panels->append($this	->panel()
									->body($this->view('index', [
										'title'  => $this->lang('Sous-catégories'),
										'forums' => $subforums
									]), FALSE));
		}

		// Les filtres portent sur les annonces ET sur les sujets : ils passent au-dessus des deux listes.
		if ($filtres = $this->_filtres_prefixes($prefixes, (int) $prefixe, $forum_id, $title))
		{
			$panels->append($this->panel()->body($filtres, FALSE)->style('card-transparent'));
		}

		if (!empty($announces))
		{
			$panels->append($this	->panel()
									->body($this->view('forum', [
										'title'    => $this->lang('Annonces'),
										'icon'     => 'fas fa-flag',
										'topics'   => $announces,
										'prefixes' => $prefixes
									]), FALSE));
		}

		$panels->append($this	->panel()
								->body($this->view('forum', [
									'title'    => $title,
									'icon'     => 'fas fa-bars',
									'topics'   => $topics,
									'prefixes' => $prefixes
								]), FALSE));

		$content = '<a class="btn btn-light float-start" href="'.url(($this->url->back() ?: 'forum')).'">'.$this->lang('Retour').'</a>';

		if ($pagination = $this->module->pagination->get_pagination())
		{
			$content .= '<div class="float-start ms-1">'.$pagination.'</div>';
		}

		if ($this->access('forum', 'category_write', $category_id))
		{
			$content .= '<a class="float-end btn btn-primary ms-1" href="'.url('forum/new/'.$forum_id.'/'.url_title($title)).'">'.$this->lang('Nouveau sujet').'</a>';
		}

		if ($this->user())
		{
			$content .= '<a class="float-end btn btn-light" href="'.url('forum/mark-all-as-read/'.$forum_id.'/'.url_title($title)).'" data-bs-toggle="tooltip" title="'.$this->lang('Marquer tous les messages comme étant lus').'">'.icon('far fa-eye').'</a>';
		}

		$actions = $this	->panel()
							->body($content)
							->style('card-transparent');

		$panels->prepend($actions)->append($actions);

		return $panels;
	}

	public function _new($forum_id, $title, $category_id)
	{
		$this	->title($this->lang('Nouveau sujet'))
				->_breadcrumb($category_id, $forum_id)
				->breadcrumb()
				->form()
				->add_rules([
					'title' => [
						'rules' => 'required'
					],
					'message' => [
						'type'  => 'editor',
						'rules' => 'required'
					],
					'attachment' => [
						'type' => 'file'
					],
					'prefix' => [
						'type'   => 'select',
						'values' => ['' => ''] + array_column($this->_modele_forum()->prefixes(), 'title', 'prefix_id')
					]
				]);

		if ($this->access('forum', 'category_announce', $category_id))
		{
			$this->form()->add_rules([
				'announce' => [
					'type' => 'checkbox'
				]
			]);
		}

		if ($this->form()->is_valid($post))
		{
			// Phase 4 modération — block si user mute sur forum (ou globally)
			if ($this->moderation->is_muted((int)$this->user->id, 'forum'))
			{
				notify($this->moderation->block_message_for_user((int)$this->user->id, 'forum') ?: $this->lang('Tu es actuellement muet sur le forum.'), 'danger');
				redirect('forum/'.$forum_id.'/'.url_title($title));
			}

			$topic_id = $this->model()->add_topic(	$forum_id,
													$post['title'],
													$post['message'],
													!empty($post['announce']) && in_array('on', $post['announce']));

			$this->_attach_to_last_message($topic_id, $post);

			$this->_modele_forum()->set_prefix((int) $topic_id, (int) ($post['prefix'] ?? 0) ?: NULL);

			notify($this->lang('Sujet ajouté'));

			redirect('forum/topic/'.$topic_id.'/'.url_title($post['title']));
		}

		$panels = $this->array;

		if ($errors = $this->form()->get_errors())
		{
			$panels->append($this	->panel()
									->heading($this->lang('Veuillez remplir tous les champs'), 'fas fa-exclamation-triangle')
									->color('danger'));
		}

		$panels->append($this	->panel()
								->heading($this->lang('Nouveau sujet'), 'fas fa-pencil-alt')
								->body($this->view('new', [
									'form_id'     => $this->form()->token(),
									'post'        => $post,
									'forum_id'    => $forum_id,
									'category_id' => $category_id,
									'title'       => $title,
									'prefixes'    => $this->_modele_forum()->prefixes()
								]), FALSE));

		return $panels;
	}

	public function _topic($topic_id, $title, $forum_id, $forum_title, $category_id, $views, $nb_users, $nb_messages, $is_announce, $is_locked, $topic, $messages, $prefix_id = 0, $solution_id = 0, $topic_user_id = 0)
	{
		$prefixes      = $this->_modele_forum()->prefixes();
		$peut_resoudre = ($this->user() && (int) $topic_user_id === (int) $this->user->id) || $this->access('forum', 'category_modify', $category_id);
		$solution      = NULL;

		foreach ($messages as $m)
		{
			if ((int) $m['message_id'] === (int) $solution_id && $m['message'] !== NULL)
			{
				$solution = $m;
			}
		}

		// La solution peut être sur une autre page que celle affichée : on va la chercher.
		if ($solution_id && !$solution)
		{
			$solution = $this->db->select('message_id', 'user_id', 'message')->from('nf_forum_messages')->where('message_id', (int) $solution_id)->where('deleted_at', NULL)->row() ?: NULL;
		}

		$this	->title($title)
				->_breadcrumb($category_id, $forum_id)
				->breadcrumb()
				->js('delete');

		$last_message_read = NULL;

		$is_last_page = $nb_messages <= $this->module->pagination->get_items_per_page() || $this->module->pagination->get_page() == ceil($nb_messages / $this->module->pagination->get_items_per_page());

		if ($this->user())
		{
			$last_message_date = $messages ? end($messages)['date'] : $topic['date'];
			$last_message_read = $this->db->select('UNIX_TIMESTAMP(date)')->from('nf_forum_topics_read')->where('user_id', $this->user->id)->where('topic_id', $topic_id)->row();

			$forum_read = $this->db	->select('MAX(UNIX_TIMESTAMP(date))')
									->from('nf_forum_read')
									->where('user_id', $this->user->id)
									->where('forum_id', [0, $forum_id])
									->row();

			if ($forum_read && $last_message_read)
			{
				$last_message_read = max($last_message_read, $forum_read);
			}
			else if ($forum_read)
			{
				$last_message_read = $forum_read;
			}

			if (empty($last_message_read) || $last_message_read < $last_message_date)
			{
				$this->db	->where('topic_id', $topic_id)
							->where('user_id', $this->user->id)
							->delete('nf_forum_topics_read');

				$this->db->insert('nf_forum_topics_read', [
					'user_id'  => $this->user->id,
					'topic_id' => $topic_id,
					'date'     => date('Y-m-d H:i:s', $last_message_date)
				]);

				if (count_view('forum_topic', $topic_id))
				{
					$this->db	->where('topic_id', $topic_id)
								->update('nf_forum_topics', 'views = views + 1');
				}

				if ($is_last_page)
				{
					$this->model()->get_topics($forum_id);
				}
			}
		}

		$content = '<a class="btn btn-light float-start" href="'.url($this->url->back() ?: 'forum/'.$forum_id.'/'.url_title($forum_title)).'">'.$this->lang('Retour').'</a>';

		if ($pagination = $this->module->pagination->get_pagination())
		{
			$content .= '<div class="float-start ms-1">'.$pagination.'</div>';
		}

		if (!$is_locked && $this->access('forum', 'category_write', $category_id))
		{
			$page = '';

			if ($nb_messages > $this->module->pagination->get_items_per_page() && $this->module->pagination->get_page() != ($last_page = ceil($nb_messages / $this->module->pagination->get_items_per_page())))
			{
				$page = url('forum/topic/'.$topic_id.'/'.url_title($title).'/page/'.$last_page);
			}

			$content .= '<a class="float-end btn btn-primary ms-1" href="'.$page.'#reply">'.$this->lang('Répondre').'</a>';
		}

		if (($this->user() && $topic['user_id'] == $this->user->id) || $this->access('forum', 'category_delete', $category_id))
		{
			$content .= '<a class="float-end btn btn-light delete ms-1" href="'.url('forum/message/delete/'.$topic['message_id'].'/'.url_title($title)).'" data-bs-toggle="tooltip" title="'.$this->lang('Supprimer le sujet').'">'.icon('fas fa-times').'</a>';
		}

		if ($this->access('forum', 'category_lock', $category_id))
		{
			if ($is_locked)
			{
				$content .= '<a class="float-end btn btn-light ms-1" href="'.url('forum/lock/'.$topic_id.'/'.url_title($title)).'" data-bs-toggle="tooltip" title="'.$this->lang('Déverrouiller le sujet').'">'.icon('fas fa-unlock').'</a>';
			}
			else
			{
				$content .= '<a class="float-end btn btn-light ms-1" href="'.url('forum/lock/'.$topic_id.'/'.url_title($title)).'" data-bs-toggle="tooltip" title="'.$this->lang('Verrouiller le sujet').'">'.icon('fas fa-lock').'</a>';
			}
		}

		if ($this->access('forum', 'category_announce', $category_id))
		{
			if ($is_announce)
			{
				$content .= '<a class="float-end btn btn-light ms-1" href="'.url('forum/announce/'.$topic_id.'/'.url_title($title)).'" data-bs-toggle="tooltip" title="'.$this->lang('Retirer des annonces').'">'.icon('far fa-flag').'</a>';
			}
			else
			{
				$content .= '<a class="float-end btn btn-light ms-1" href="'.url('forum/announce/'.$topic_id.'/'.url_title($title)).'" data-bs-toggle="tooltip" title="'.$this->lang('Mettre en annonce').'">'.icon('fas fa-flag').'</a>';
			}
		}

		if ($this->access('forum', 'category_move', $category_id))
		{
			$content .= '<span class="float-end btn btn-light topic-move" data-bs-toggle="tooltip" data-modal-ajax="'.url('ajax/forum/topic/move/'.$topic_id.'/'.url_title($title)).'" title="'.$this->lang('Déplacer le sujet').'">'.icon('fas fa-reply fa-flip-horizontal').'</span>';
		}

		// Phase 7-bis : Split / Merge (mod avancée)
		if ($this->access('forum', 'category_modify', $category_id))
		{
			$content .= '<a class="float-end btn btn-light ms-1" href="'.url('admin/forum/topic/split/'.$topic_id.'/'.url_title($title)).'" data-bs-toggle="tooltip" title="'.$this->lang('Scinder le sujet').'">'.icon('fas fa-cut').'</a>';
			$content .= '<a class="float-end btn btn-light ms-1" href="'.url('admin/forum/topic/merge/'.$topic_id.'/'.url_title($title)).'" data-bs-toggle="tooltip" title="'.$this->lang('Fusionner avec un autre sujet').'">'.icon('fas fa-compress-arrows-alt').'</a>';
		}

		$panels = $this->array;

		if ($is_locked)
		{
			$panels->append($this	->panel()
									->heading('<a name="reply"></a>'.$this->lang('Le sujet est verrouillé'), 'fas fa-exclamation-triangle')
									->color('danger'));
		}

		$panels->append($this	->panel()
								->body($this->view('topic', array_merge($topic, [
									'category_id'       => $category_id,
									'topic_id'          => $topic_id,
									'title'             => $title,
									'views'             => $views,
									'last_message_read' => $last_message_read,
									'is_subscribed'     => $this->user() ? $this->model()->is_subscribed($topic_id, $this->user->id) : FALSE,
									'prefixe'           => $prefixes[(int) $prefix_id] ?? NULL,
									'solution'          => $solution
								])), FALSE));

		$actions = $this->panel()
						->body($content)
						->style('card-transparent');

		if (!empty($messages))
		{
			$panels->append($actions);

			$panels->append($this	->panel()
									->body($this->view('messages', [
										'category_id'       => $category_id,
										'topic_id'          => $topic_id,
										'title'             => $title,
										'nb_users'          => $nb_users,
										'nb_messages'       => $nb_messages,
										'messages'          => $messages,
										'last_message_read' => $last_message_read,
										'is_locked'         => $is_locked,
										'solution_id'       => (int) $solution_id,
										'peut_resoudre'     => $peut_resoudre,
										'jeton'             => $peut_resoudre ? $this->csrf_token() : ''
									]), FALSE));
		}

		$panels->append($actions);

		if ($is_last_page && $this->access('forum', 'category_write', $category_id) && !$is_locked)
		{
			$this	->form()
					->add_rules([
						'message' => [
							'type'  => 'editor',
							'rules' => 'required'
						],
						'attachment' => [
							'type' => 'file'
						]
					])
					->add_submit($this->lang('Répondre'), 'fas fa-reply');

			if ($this->form()->is_valid($post))
			{
				// Phase 4 modération — block si user mute sur forum
				if ($this->moderation->is_muted((int)$this->user->id, 'forum'))
				{
					notify($this->moderation->block_message_for_user((int)$this->user->id, 'forum') ?: $this->lang('Tu es actuellement muet sur le forum.'), 'danger');
					redirect('forum/topic/'.$topic_id.'/'.url_title($title));
				}

				$reply_to = !empty($_GET['reply_to']) ? (int)$_GET['reply_to'] : NULL;
				$message_id = $this->model()->add_message($topic_id, $post['message'], $reply_to);

				$this->_attach_file_to_message($message_id, $post);

				//notify('success', $this->lang('Réponse ajoutée avec succès'));

				$page = '';

				if (++$nb_messages > $this->module->pagination->get_items_per_page())
				{
					$page = '/page/'.ceil($nb_messages / $this->module->pagination->get_items_per_page());
				}

				redirect('forum/topic/'.$topic_id.'/'.url_title($title).$page.'#'.$message_id);
			}

			if ($errors = $this->form()->get_errors())
			{
				$panels->append($this	->panel()
										->heading('<a name="reply"></a>'.$this->lang('Veuillez remplir un message'), 'fas fa-exclamation-triangle')
										->color('danger')
				);
			}

			$panels->append($this	->panel()
									->heading('<a name="reply"></a>'.$this->lang('Répondre au sujet'), 'far fa-file-alt')
									->body($this->view('new', [
										'form_id'  => $this->form()->token()
									]), FALSE));
		}

		return $panels;
	}

	public function _subscribe($topic_id, $title)
	{
		$this->model()->subscribe($topic_id, $this->user->id);
		notify($this->lang('Tu es maintenant abonné(e) à ce sujet'));
		redirect('forum/topic/'.$topic_id.'/'.url_title($title));
	}

	public function _unsubscribe($topic_id, $title)
	{
		$this->model()->unsubscribe($topic_id, $this->user->id);
		notify($this->lang('Tu es désabonné(e) de ce sujet'));
		redirect('forum/topic/'.$topic_id.'/'.url_title($title));
	}

	public function _search()
	{
		$query     = trim((string)($_GET['q']      ?? ''));
		$forum_id  = !empty($_GET['forum'])  ? (int)$_GET['forum']           : NULL;
		$author    = !empty($_GET['author']) ? trim((string)$_GET['author']) : NULL;
		$date_from = !empty($_GET['from'])   ? trim((string)$_GET['from'])   : NULL;
		$date_to   = !empty($_GET['to'])     ? trim((string)$_GET['to'])     : NULL;
		$sort      = in_array($_GET['sort'] ?? '', ['relevance', 'date_desc', 'date_asc']) ? $_GET['sort'] : 'relevance';

		$this	->title($this->lang('Recherche dans le forum'))
				->breadcrumb($this->lang('Forum'), 'forum')
				->breadcrumb($this->lang('Recherche'));

		$results = [];
		$too_short = FALSE;

		if ($query !== '')
		{
			if (strlen($query) < 3)
			{
				$too_short = TRUE;
			}
			else
			{
				$results = $this->model()->search_fulltext($query, $forum_id, $author, $date_from, $date_to, $sort);
			}
		}

		return $this->view('search/forum', [
			'query'     => $query,
			'forum_id'  => $forum_id,
			'author'    => $author,
			'date_from' => $date_from,
			'date_to'   => $date_to,
			'sort'      => $sort,
			'results'   => $results,
			'too_short' => $too_short,
			'forums'    => $this->model()->get_categories_list()
		]);
	}

	public function _subscriptions($subscriptions)
	{
		$this	->title($this->lang('Mes abonnements forum'))
				->breadcrumb($this->lang('Forum'), 'forum')
				->breadcrumb($this->lang('Mes abonnements'));

		// Découpée par pages de 20 (le checker) : les liens des pages suivent la liste.
		return $this->view('subscriptions', ['subscriptions' => $subscriptions]).$this->module->pagination->get_pagination();
	}

	private function _attach_to_last_message($topic_id, $post)
	{
		if (empty($post['attachment']))
		{
			return;
		}

		// Le starter post du topic
		$message_id = $this->db	->select('message_id')
								->from('nf_forum_topics')
								->where('topic_id', (int)$topic_id)
								->row();

		if ($message_id)
		{
			$this->_attach_file_to_message($message_id, $post);
		}
	}

	private function _attach_file_to_message($message_id, $post)
	{
		if (empty($post['attachment']) || !is_object($post['attachment']) || empty($post['attachment']->id))
		{
			return;
		}

		$file          = $post['attachment'];
		$allowed_mimes = $this->model()->get_allowed_mimes();
		$max_size      = $this->model()->get_max_size_bytes();

		$disk_path = $file->path;
		$size      = file_exists($disk_path) ? filesize($disk_path) : 0;
		$mime      = (file_exists($disk_path) && function_exists('mime_content_type'))
				? mime_content_type($disk_path)
				: 'application/octet-stream';

		if (!\NF\Modules\Forum\Lib\Forum_Attachment_Rules::is_allowed_mime($mime, $allowed_mimes))
		{
			$file->delete();
			notify($this->lang('Type de fichier non autorisé : %s', $mime), 'danger');
			return;
		}

		if (!\NF\Modules\Forum\Lib\Forum_Attachment_Rules::is_within_size($size, $max_size))
		{
			$file->delete();
			notify($this->lang('Fichier trop volumineux (%s, max %s)', human_size($size), human_size($max_size)), 'danger');
			return;
		}

		$this->model()->attach_file($message_id, (int)$file->id, $size, $mime);
	}

	public function _topic_announce($topic_id, $title, $is_announce, $is_locked)
	{
		$this->db	->where('topic_id', $topic_id)
					->update('nf_forum_topics', [
						'status' => (string)($is_announce ? ($is_locked ? -1 : 0) : ($is_locked ? -2 : 1))
					]);
		//notify('success', $this->lang('topic mis en annonce ou pas...'));
		redirect('forum/topic/'.$topic_id.'/'.url_title($title));
	}

	public function _topic_lock($topic_id, $title, $is_announce, $is_locked)
	{
		$this->db	->where('topic_id', $topic_id)
					->update('nf_forum_topics', [
						'status' => (string)($is_locked ? ($is_announce ? 1 : 0) : ($is_announce ? -2 : -1))
					]);
		//notify('success', $this->lang('topic verrouillé ou pas...'));
		redirect('forum/topic/'.$topic_id.'/'.url_title($title));
	}

	public function _message_edit($message_id, $topic_id, $title, $is_topic, $message, $category_id, $forum_id, $user_id, $locked)
	{
		$this	->title($this->lang($is_topic ? 'Édition du sujet' : 'Édition du message'))
				->_breadcrumb($category_id, $forum_id)
				->breadcrumb($title, 'forum/topic/'.$topic_id.'/'.url_title($title))
				->breadcrumb()
				->form()
				->add_rules([
					'message' => [
						'type'  => 'editor',
						'rules' => 'required'
					]
				]);

		if ($is_topic)
		{
			$this->form()->add_rules([
				'title' => [
					'rules' => 'required'
				],
				'prefix' => [
					'type'   => 'select',
					'values' => ['' => ''] + array_column($this->_modele_forum()->prefixes(), 'title', 'prefix_id')
				]
			]);
		}

		if ($this->form()->is_valid($post))
		{
			if ($is_topic && $title != $post['title'])
			{
				$this->db	->where('topic_id', $topic_id)
							->update('nf_forum_topics', [
								'title' => $post['title']
							]);
			}

			if ($is_topic)
			{
				$this->_modele_forum()->set_prefix((int) $topic_id, (int) ($post['prefix'] ?? 0) ?: NULL);
			}

			$this->db	->where('message_id', $message_id)
						->update('nf_forum_messages', [
							'message' => $post['message']
						]);

			// Re-record mentions (efface les anciennes, recrée les nouvelles)
			$mentioned_users = $this->model()->record_mentions($message_id, $this->user->id, $post['message']);

			$this->events->fire('forum.post.edited', [
				'message_id'      => (int)$message_id,
				'topic_id'        => (int)$topic_id,
				'forum_id'        => (int)$forum_id,
				'user_id'         => $this->user->id,
				'old_message'     => $message,
				'new_message'     => $post['message'],
				'is_topic'        => (bool)$is_topic,
				'mentioned_users' => $mentioned_users
			]);

			//notify('success', $this->lang('Message modifié avec succès'));

			redirect('forum/topic/'.$topic_id.'/'.url_title($is_topic ? $post['title'] : $title));
		}

		$panels = $this->array;

		if ($errors = $this->form()->get_errors())
		{
			$panels->append($this	->panel()
									->heading($this->lang($is_topic ? 'Veuillez remplir tous les champs' : 'Veuillez remplir un message'), 'fas fa-exclamation-triangle')
									->color('danger')
			);
		}

		$panels->append($this	->panel()
								->heading($this->lang($is_topic ? 'Édition du sujet' : 'Édition du message'), 'far fa-file-alt')
								->body($this->view('new', [
									'form_id'  => $this->form()->token(),
									'post'     => $post,
									'topic_id' => $topic_id,
									'is_topic' => $is_topic,
									'title'    => $title,
									'message'  => $message,
									'user_id'  => $user_id,
									'prefixes' => $is_topic ? $this->_modele_forum()->prefixes() : [],
									'prefix'   => $is_topic ? (int) $this->db->select('prefix_id')->from('nf_forum_topics')->where('topic_id', $topic_id)->row() : 0
								]), FALSE));

		return $panels;
	}

	public function _message_delete($message_id, $title, $topic_id, $forum_id, $is_topic)
	{
		$this	->title($this->lang($is_topic ? 'Suppression du topic' : 'Suppression du message'))
				->subtitle($title)
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $is_topic ? $this->lang('Êtes-vous sûr(e) de vouloir supprimer le sujet <b>%s</b> ?', $title) : $this->lang('Êtes-vous sûr(e) de vouloir supprimer ce message ?'));

		if ($this->form()->is_valid())
		{
			$delete = TRUE;
			$is_self_delete = $this->user() && (int)$this->db->select('user_id')->from('nf_forum_messages')->where('message_id', $message_id)->row() === (int)$this->user->id;
			$reason = $is_self_delete ? 'self' : 'moderation';

			if ($is_topic)
			{
				$count_messages = $this->model()->count_messages($topic_id);

				$this->db	->where('topic_id', $topic_id)
							->delete('nf_forum_topics');

				$this->db	->where('forum_id', $forum_id)
							->update('nf_forum', 'count_topics = count_topics - 1');

				$this->db	->where('forum_id', $forum_id)
							->update('nf_forum', 'count_messages = count_messages - '.$count_messages);
			}
			else if ($this->db->select('message_id')->from('nf_forum_messages')->where('topic_id', $topic_id)->order_by('message_id DESC')->row() == $message_id)
			{
				$this->db	->where('message_id', $message_id)
							->delete('nf_forum_messages');

				$this->db	->where('topic_id', $topic_id)
							->update('nf_forum_topics', 'count_messages = count_messages - 1');

				$this->db	->where('forum_id', $forum_id)
							->update('nf_forum', 'count_messages = count_messages - 1');

				if (($last_message_id = $this->db->select('message_id')->from('nf_forum_messages')->where('topic_id', $topic_id)->order_by('message_id DESC')->row()) &&
					 $last_message_id != $this->db->select('message_id')->from('nf_forum_topics')->where('topic_id', $topic_id)->row())
				{
					$this->db	->where('topic_id', $topic_id)
								->update('nf_forum_topics', [
									'last_message_id' => $last_message_id
								]);
				}
			}
			else
			{
				// Soft-delete : message=NULL + audit trail dans deleted_at/by/reason
				$this->db	->where('message_id', $message_id)
							->update('nf_forum_messages', 'message = NULL, deleted_at = CURRENT_TIMESTAMP, deleted_by = '.(int)($this->user() ? $this->user->id : 0).', deleted_reason = "'.$this->db->escape_string($reason).'"');

				$delete = FALSE;
			}

			$this->events->fire('forum.post.deleted', [
				'message_id'   => (int)$message_id,
				'topic_id'     => (int)$topic_id,
				'forum_id'     => (int)$forum_id,
				'is_topic'     => (bool)$is_topic,
				'hard_delete'  => $delete,
				'deleted_by'   => $this->user() ? $this->user->id : NULL,
				'reason'       => $reason
			]);

			if ($delete)
			{
				$last_message_id = $this->model()->get_last_message_id($forum_id);

				$this->db	->where('forum_id', $forum_id)
							->update('nf_forum', [
								'last_message_id' => $last_message_id
							]);
			}

			if ($is_topic)
			{
				redirect('forum/'.$forum_id.'/'.url_title($this->db()->select($this->_modele_forum()->titre_forum('f'))->from('nf_forum f')->where('f.forum_id', $forum_id)->row()));
			}
			else
			{
				$nb_messages = $this->db()->from('nf_forum_messages')->where('topic_id', $topic_id)->count() - 1;

				$page = '';

				if ($nb_messages > $this->config->forum_messages_per_page)
				{
					$page = '/page/'.ceil($nb_messages / $this->config->forum_messages_per_page);
				}

				$last_message_id = $this->db()	->select('message_id')
												->from('nf_forum_messages')
												->where('topic_id',     $topic_id)
												->where('message_id <', $message_id)
												->order_by('message_id DESC')
												->row();

				redirect('forum/topic/'.$topic_id.'/'.url_title($title).$page.'#'.$last_message_id);
			}

			return 'OK';
		}

		return $this->form()->display();
	}

	public function mark_all_as_read()
	{
		$this->model()->mark_all_as_read();
		//notify('success', $this->lang('Tous les messages sont désormais considéré comme étant lus'));
		redirect('forum');
	}

	public function _mark_all_as_read($forum_id, $title)
	{
		foreach (array_merge([$forum_id], $this->db->select('forum_id')->from('nf_forum')->where('parent_id', $forum_id)->where('is_subforum', TRUE)->get()) as $id)
		{
			$this->model()->mark_all_as_read($id);
		}

		//notify('success', $this->lang('Tous les messages du forum <b>%s</b> sont désormais considéré comme étant lus', $title));
		redirect('forum/'.$forum_id.'/'.url_title($title));
	}

	private function _breadcrumb($category_id, $forum_id)
	{
		if ($category = $this->db->select($this->_modele_forum()->titre_categorie('c'))->from('nf_forum_categories c')->where('c.category_id', $category_id)->row())
		{
			$this->breadcrumb($category, 'forum');
		}

		if (list($title, $parent_forum_id) = array_values($this->db->select($this->_modele_forum()->titre_forum('f'), 'IF(f.is_subforum = "1", f.parent_id, 0)')->from('nf_forum f')->where('f.forum_id', $forum_id)->row()))
		{
			if ($parent_forum_id && $parent_forum = $this->db->select($this->_modele_forum()->titre_forum('f'))->from('nf_forum f')->where('f.forum_id', $parent_forum_id)->row())
			{
				$this->breadcrumb($parent_forum, 'forum/'.$parent_forum_id.'/'.url_title($parent_forum));
			}

			$this->breadcrumb($title, 'forum/'.$forum_id.'/'.url_title($title));
		}

		return $this;
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

	/** Marque, ou retire, la réponse qui résout le sujet. */
	public function _solution($message)
	{
		$adresse = 'forum/topic/'.$message['topic_id'].'/'.url_title($message['title']);

		$this->check_csrf($adresse);

		$actuelle = (int) $this->db->select('solution_message_id')->from('nf_forum_topics')->where('topic_id', $message['topic_id'])->row();
		$nouvelle = $actuelle === (int) $message['message_id'] ? NULL : (int) $message['message_id'];

		if ($this->_modele_forum()->set_solution((int) $message['topic_id'], $nouvelle))
		{
			notify($nouvelle ? $this->lang('Réponse marquée comme solution.') : $this->lang('Solution retirée.'));
		}

		redirect($adresse.'#'.(int) $message['message_id']);
	}

	/** Les pastilles de préfixes au-dessus des listes d'un forum : « Tous », puis chaque préfixe. */
	private function _filtres_prefixes(array $prefixes, int $actif, $forum_id, $title): string
	{
		if (!$prefixes)
		{
			return '';
		}

		$adresse = url('forum/'.$forum_id.'/'.url_title($title));
		$html    = '<nav class="forum-filtres" aria-label="'.$this->lang('Préfixes').'"><a class="'.(!$actif ? 'actif' : '').'" href="'.$adresse.'">'.$this->lang('Tous').'</a>';

		foreach ($prefixes as $p)
		{
			$html .= '<a class="'.($actif === $p['prefix_id'] ? 'actif' : '').'" href="'.$adresse.'?prefixe='.$p['prefix_id'].'">'.htmlspecialchars($p['title']).'</a>';
		}

		return $html.'</nav>';
	}
}
