<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum\Models;

use NF\NeoFrag\Loadables\Model;

class Forum extends Model
{
	use Forum_Subscriptions; // abonnements aux sujets (forum_subscriptions.php)
	use Forum_Mentions;      // mentions @user (forum_mentions.php)
	use Forum_Moderation;    // split / merge / trash (forum_moderation.php)
	use Forum_Fulltext;      // recherche FULLTEXT + son administration (forum_fulltext.php)
	use Forum_Threading_Db;  // lecture et validation des réponses (forum_threading.php)
	use Forum_Attachments;   // pièces jointes + leur administration (forum_attachments.php)

	/*
	 * Les titres traduits (2026-10-01). Une catégorie et un forum gardent leur titre par défaut —
	 * `title`, `description` — et peuvent en recevoir un par langue (`nf_forum_lang`,
	 * `nf_forum_categories_lang`). L'affichage prend celui de la langue du visiteur, sinon le titre
	 * par défaut : un forum jamais traduit s'affiche comme avant, partout.
	 */

	/** L'expression SQL du titre (ou de la description) d'un forum, dans la langue affichée. */
	public function titre_forum(string $alias, string $colonne = 'title'): string
	{
		$colonne = $colonne === 'description' ? 'description' : 'title';

		return 'COALESCE((SELECT NULLIF(tf.'.$colonne.', "") FROM nf_forum_lang tf WHERE tf.forum_id = '.$alias.'.forum_id AND tf.lang = "'.$this->_langue_forum().'"), '.$alias.'.'.$colonne.')';
	}

	/** L'expression SQL du titre d'une catégorie du forum, dans la langue affichée. */
	public function titre_categorie(string $alias): string
	{
		return 'COALESCE((SELECT NULLIF(tc.title, "") FROM nf_forum_categories_lang tc WHERE tc.category_id = '.$alias.'.category_id AND tc.lang = "'.$this->_langue_forum().'"), '.$alias.'.title)';
	}

	/** Les traductions d'un forum ou d'une catégorie : [lang => ['title' => …, 'description' => …]]. */
	public function traductions(string $type, int $id): array
	{
		[$table, $cle, $colonnes] = match ($type) {
			'category' => ['nf_forum_categories_lang', 'category_id', ['lang', 'title']],
			'prefix'   => ['nf_forum_prefixes_lang', 'prefix_id', ['lang', 'title']],
			default    => ['nf_forum_lang', 'forum_id', ['lang', 'title', 'description']],
		};

		$traductions = [];

		foreach ($this->db->select(...$colonnes)->from($table)->where($cle, $id)->get() as $ligne)
		{
			$traductions[$ligne['lang']] = $ligne;
		}

		return $traductions;
	}

	/** Enregistre les traductions saisies : un champ vide retire la traduction de cette langue. */
	public function enregistrer_traductions(string $type, int $id, array $saisies): void
	{
		[$table, $cle] = match ($type) {
			'category' => ['nf_forum_categories_lang', 'category_id'],
			'prefix'   => ['nf_forum_prefixes_lang', 'prefix_id'],
			default    => ['nf_forum_lang', 'forum_id'],
		};

		foreach ($saisies as $langue => $valeurs)
		{
			if (!preg_match('/^[a-z]{2}$/', (string) $langue))
			{
				continue;
			}

			$this->db->where($cle, $id)->where('lang', $langue)->delete($table);

			$titre       = trim((string) ($valeurs['title'] ?? ''));
			$description = trim((string) ($valeurs['description'] ?? ''));

			if ($titre !== '' || $description !== '')
			{
				$this->db->insert($table, array_merge([$cle => $id, 'lang' => $langue, 'title' => $titre], $type === 'forum' ? ['description' => $description] : []));
			}
		}
	}

	/*
	 * Les préfixes d'un sujet (2026-10-01) : « Question », « Tutoriel », « Important »… Une
	 * liste commune au forum, réglée en administration, chaque préfixe avec sa couleur et ses
	 * traductions. L'auteur d'un sujet ou un modérateur le pose ; la liste d'un forum se filtre dessus.
	 * Les étiquettes des salons Forum de Discord s'y relieront.
	 */

	/** L'expression SQL du titre d'un préfixe, dans la langue affichée. */
	public function titre_prefixe(string $alias): string
	{
		return 'COALESCE((SELECT NULLIF(tp.title, "") FROM nf_forum_prefixes_lang tp WHERE tp.prefix_id = '.$alias.'.prefix_id AND tp.lang = "'.$this->_langue_forum().'"), '.$alias.'.title)';
	}

	/** @return array<int, array{prefix_id: int, title: string, color: string, order: int}> */
	public function prefixes(): array
	{
		$prefixes = [];

		foreach ($this->db->select('p.prefix_id', $this->titre_prefixe('p').' AS title', 'p.color', 'p.order')->from('nf_forum_prefixes p')->order_by('p.order', 'p.prefix_id')->get() as $p)
		{
			$prefixes[(int) $p['prefix_id']] = ['prefix_id' => (int) $p['prefix_id'], 'title' => (string) $p['title'], 'color' => (string) $p['color'], 'order' => (int) $p['order']];
		}

		return $prefixes;
	}

	/** La pastille d'un préfixe, ou une chaîne vide. */
	public static function pastille_prefixe(?array $prefixe): string
	{
		if (!$prefixe)
		{
			return '';
		}

		return '<span class="forum-prefixe badge '.badge_class((string) $prefixe['color']).'">'.htmlspecialchars((string) $prefixe['title']).'</span>';
	}

	public function add_prefix(string $title, string $color, int $order): int
	{
		return (int) $this->db->insert('nf_forum_prefixes', ['title' => $title, 'color' => $color, 'order' => $order]);
	}

	public function edit_prefix(int $prefix_id, string $title, string $color, int $order): void
	{
		$this->db->where('prefix_id', $prefix_id)->update('nf_forum_prefixes', ['title' => $title, 'color' => $color, 'order' => $order]);
	}

	/** Supprime un préfixe : les sujets qui le portaient n'en ont plus, ils ne disparaissent pas. */
	public function delete_prefix(int $prefix_id): void
	{
		$this->db->where('prefix_id', $prefix_id)->update('nf_forum_topics', ['prefix_id' => NULL]);
		$this->db->where('prefix_id', $prefix_id)->delete('nf_forum_prefixes');
	}

	/** Le préfixe d'un sujet : un identifiant inconnu vaut « aucun ». */
	public function set_prefix(int $topic_id, ?int $prefix_id): void
	{
		if ($prefix_id && !isset($this->prefixes()[$prefix_id]))
		{
			$prefix_id = NULL;
		}

		$this->db->where('topic_id', $topic_id)->update('nf_forum_topics', ['prefix_id' => $prefix_id ?: NULL]);
	}

	/**
	 * La réponse qui résout un sujet, ou NULL pour la retirer. Elle doit être une RÉPONSE de ce sujet,
	 * encore là : ni le premier message (la question), ni un message supprimé.
	 */
	public function set_solution(int $topic_id, ?int $message_id): bool
	{
		if ($message_id)
		{
			$valide = $this->db	->select('m.message_id')
								->from('nf_forum_messages m')
								->where('m.message_id', $message_id)
								->where('m.topic_id', $topic_id)
								->where('m.deleted_at', NULL)
								->row();

			if (!$valide || (int) $this->db->select('message_id')->from('nf_forum_topics')->where('topic_id', $topic_id)->row() === $message_id)
			{
				return FALSE;
			}
		}

		$this->db->where('topic_id', $topic_id)->update('nf_forum_topics', ['solution_message_id' => $message_id ?: NULL]);

		return TRUE;
	}

	/**
	 * La solution d'un sujet telle qu'on l'affiche : la réponse marquée, si elle est encore là et
	 * encore dans ce sujet. Une réponse supprimée, mise à la corbeille ou déplacée par une scission
	 * ne laisse pas un sujet « Résolu » ; restaurée, elle redevient la solution sans rien resynchroniser.
	 */
	public function solution_vivante(string $alias): string
	{
		return '(SELECT s.message_id FROM nf_forum_messages s WHERE s.message_id = '.$alias.'.solution_message_id AND s.topic_id = '.$alias.'.topic_id AND s.deleted_at IS NULL)';
	}

	/** Une adresse reste valable avec le titre par défaut ET avec chacune de ses traductions. */
	private function _titre_d_adresse(string $type, int $id, string $slug, string $titre_par_defaut): bool
	{
		if ($slug === url_title($titre_par_defaut))
		{
			return TRUE;
		}

		foreach ($this->traductions($type, $id) as $traduction)
		{
			if ($traduction['title'] !== '' && $slug === url_title($traduction['title']))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	private function _langue_forum(): string
	{
		// L'administration édite le titre PAR DÉFAUT ; les traductions ont leurs propres champs.
		if ($this->url->admin)
		{
			return 'xx';
		}

		$langue = $this->config->lang;
		$code   = is_object($langue) ? (string) $langue->info()->name : '';

		return preg_match('/^[a-z]{2}$/', $code) ? $code : 'xx';
	}

	public function get_categories_list($forum_id = NULL)
	{
		$categories = [];

		foreach ($this->db	->select('c.category_id', $this->titre_categorie('c').' AS title', 'f.forum_id', $this->titre_forum('f').' AS forum_title')
							->from('nf_forum_categories c')
							->join('nf_forum f', 'c.category_id = f.parent_id AND f.is_subforum = "0"')
							->order_by('c.order', 'f.order')
							->get() as $category)
		{
			if (!isset($categories[$category['category_id']]))
			{
				$categories[$category['category_id']] = $category['title'];
			}

			if ($category['forum_id'] && (!$forum_id || $category['forum_id'] != $forum_id))
			{
				$categories['f'.$category['forum_id']] = str_repeat('&nbsp;', 10).$category['forum_title'];
			}
		}

		return $categories;
	}

	public function get_categories()
	{
		$categories = [];
		$forums     = $this->get_forums();
		$count_read = $i = 0;

		foreach ($this->db	->select('c.category_id', $this->titre_categorie('c').' AS title', 'c.image_id')
							->from('nf_forum_categories c')
							->order_by('c.order', 'c.category_id')
							->get() as $category)
		{
			if ($this->access('forum', 'category_read', $category['category_id']) && !$this->_vip_locked($category['category_id']))
			{
				$category['forums'] = [];

				foreach ($forums as $forum)
				{
					if ($forum['parent_id'] == $category['category_id'])
					{
						$category['forums'][] = $forum;

						$count_read += !$forum['has_unread'];
						$i++;
					}
				}

				$categories[] = $category;
			}
		}

		if ($count_read == $i)
		{
			$this->mark_all_as_read();
		}

		return $categories;
	}

	public function get_forums_tree()
	{
		$tree = [];

		foreach ($this->db	->select('c.category_id', $this->titre_categorie('c').' AS title')
							->from('nf_forum_categories c')
							->order_by('c.order', 'c.category_id')
							->get() as $category)
		{
			if ($this->access('forum', 'category_read', $category['category_id']) && !$this->_vip_locked($category['category_id']))
			{
				$forums = [];

				foreach ($this->db	->select('f.forum_id', $this->titre_forum('f').' AS title')
									->from('nf_forum f')
									->join('nf_forum_url u', 'u.forum_id = f.forum_id')
									->where('f.parent_id', $category['category_id'])
									->where('f.is_subforum', FALSE)
									->where('u.forum_id', NULL)
									->order_by('f.order', 'f.forum_id')
									->get() as $forum)
				{
					$subforums = [];

					foreach ($this->db	->select('f.forum_id', $this->titre_forum('f').' AS title')
										->from('nf_forum f')
										->join('nf_forum_url u', 'u.forum_id = f.forum_id')
										->where('f.parent_id', $forum['forum_id'])
										->where('f.is_subforum', TRUE)
										->where('u.forum_id', NULL)
										->order_by('f.order', 'f.forum_id')
										->get() as $subforum)
					{
						$subforums[$subforum['forum_id']] = $subforum['title'];
					}

					$forums[$forum['forum_id']] = [
						'title'     => $forum['title'],
						'subforums' => $subforums
					];
				}

				if ($forums)
				{
					$tree[$category['category_id']] = [
						'title'  => $category['title'],
						'forums' => $forums
					];
				}
			}
		}

		return $tree;
	}

	public function get_forums($forum_id = NULL, $mini = FALSE)
	{
		if ($forum_id)
		{
			$this->db	->where('f.parent_id', $forum_id)
						->where('f.is_subforum', TRUE);
		}
		else
		{
			$this->db	->join('nf_forum f2', 'f2.parent_id = f.forum_id AND f2.is_subforum = "1"')
						->where('f.is_subforum', FALSE);
		}

		$forums = $this->db	->select(	'f.forum_id',
										'f.parent_id',
										$this->titre_forum('f').' AS title',
										$this->titre_forum('f', 'description').' AS description',
										'f.icon AS icon_class',
										!$forum_id ? 'f.count_messages + SUM(IFNULL(f2.count_messages, 0)) as count_messages' : 'f.count_messages',
										!$forum_id ? 'f.count_topics   + SUM(IFNULL(f2.count_topics, 0))   as count_topics'   : 'f.count_topics',
										'f.last_message_id',
										'u.id as user_id',
										'u.username',
										't.topic_id',
										't.title as last_title',
										'm.date as last_message_date',
										't.count_messages as last_count_messages',
										'u2.url',
										'u2.redirects',
										(!$forum_id ? 'COUNT(f2.forum_id)' : 0).' as subforums'
									)
									->from('nf_forum f')
									// LEFT sur les trois : elles ne servent qu'a afficher le DERNIER MESSAGE du
									// forum. En stricte, un forum qui n'a encore aucun message disparaissait de
									// la liste — un forum tout juste cree etait donc invisible.
									->join('nf_forum_messages m', 'm.message_id = f.last_message_id', 'LEFT')
									->join('nf_forum_topics t',   't.topic_id = m.topic_id', 'LEFT')
									->join('nf_user u',           'u.id = m.user_id AND u.deleted = "0"', 'LEFT')
									->join('nf_forum_url u2',     'u2.forum_id = f.forum_id')
									->group_by('f.forum_id')
									->order_by('f.order', 'f.forum_id')
									->get();

		foreach ($forums as &$forum)
		{
			$forum['has_unread'] = $forum['url'] ? FALSE : $this->_has_unread($forum);

			if ($forum['subforums'])
			{
				foreach ($forum['subforums'] = $this->get_forums($forum['forum_id'], TRUE) as $subforum)
				{
					if (!$forum['has_unread'] && $subforum['has_unread'])
					{
						$forum['has_unread'] = TRUE;
					}

					if ($subforum['last_message_id'] > $forum['last_message_id'])
					{
						foreach (['last_message_id', 'user_id', 'username', 'topic_id', 'last_title', 'last_message_date', 'last_count_messages'] as $var)
						{
							$forum[$var] = $subforum[$var];
						}
					}
				}
			}
			else
			{
				$forum['subforums'] = [];
			}

			// L'icône choisie en administration l'emporte, forum-lien compris ; à défaut, le globe pour un
			// lien, la bulle sinon. Un forum non lu se distingue par la couleur d'accent (classe
			// `forum-non-lu`), quelle que soit l'icône.
			$classe              = !empty($forum['icon_class']) ? (string) $forum['icon_class'] : ($forum['url'] ? 'fas fa-globe' : ($forum['has_unread'] ? 'fas fa-comments' : 'far fa-comments'));
			$forum['icon']       = '<span class="forum-icone'.($forum['has_unread'] ? ' forum-non-lu' : '').'">'.icon($classe.($mini ? '' : ' fa-2x')).'</span>';
		}

		return $forums;
	}

	public function get_topics($forum_id, int $prefixe = 0)
	{
		if ($prefixe)
		{
			$this->db->where('t.prefix_id', $prefixe);
		}

		$topics = $this->db->select('t.topic_id',
									't.prefix_id',
									$this->solution_vivante('t').' AS solution_message_id',
									't.title',
									't.views',
									't.count_messages',
									't.last_message_id',
									'u1.id as user_id',
									'u1.username',
									'm1.date',
									'u2.id as last_user_id',
									'u2.username as last_username',
									'm2.date as last_message_date',
									'm2.message',
									't.status IN ("-2", "1") as announce',
									't.status IN ("-2", "-1") as locked'
								)
						->from('nf_forum_topics   t')
						->join('nf_forum_messages m1', 't.message_id = m1.message_id')
						->join('nf_forum_messages m2', 't.last_message_id = m2.message_id')
						->join('nf_user u1',           'u1.id = m1.user_id AND u1.deleted = "0"')
						->join('nf_user u2',           'u2.id = m2.user_id AND u2.deleted = "0"')
						->where('t.forum_id', $forum_id)
						->order_by('IFNULL(m2.date, m1.date) DESC')
						->get();

		if ($this->user())
		{
			$forum_read = $this->db	->select('MAX(UNIX_TIMESTAMP(date))')
									->from('nf_forum_read')
									->where('user_id', $this->user->id)
									->where('forum_id', [0, $forum_id])
									->row();

			$topics_read = [];

			foreach ($this->db->select('t.topic_id', 'r.date')
									->from('nf_forum_topics_read r')
									->join('nf_forum_topics t', 't.topic_id = r.topic_id')
									->where('t.forum_id', $forum_id)
									->where('r.user_id', $this->user->id)
									->get() as $read)
			{
				$topics_read[$read['topic_id']] = strtotime($read['date']);
			}
		}

		$count_read = $i = 0;

		foreach ($topics as &$topic)
		{
			$last_message_date = strtotime($topic['last_message_date'] ?: $topic['date']);
			$unread = $this->user() && $forum_read < $last_message_date && (!isset($topics_read[$topic['topic_id']]) || $topics_read[$topic['topic_id']] < $last_message_date);
			$topic['icon'] = '	<span class="topic-icon">
								'.icon(($unread ? 'fas' : 'far').' fa-'.($topic['announce'] ? 'flag' : 'comments').' fa-2x').'
								'.($topic['locked'] ? icon('fas fa-lock fa-2x') : '').'
							</span>';

			if (!$unread)
			{
				$count_read++;
			}

			$i++;
		}

		// Une liste FILTRÉE par préfixe ne montre pas tous les sujets : elle ne peut pas conclure que
		// tout le forum est lu.
		if ($count_read == $i && !$prefixe)
		{
			$this->mark_all_as_read($forum_id);
		}

		return $topics;
	}

	public function get_messages($topic_id, $forum_id)
	{
		$messages = $this->db	->select('message_id', 'parent_id', 'user_id', 'message', 'UNIX_TIMESTAMP(date) as date')
								->from('nf_forum_messages')
								->where('topic_id', $topic_id)
								->order_by('message_id')
								->get();

		// Calcul de la profondeur pour rendu nested (Phase 6)
		$messages = \NF\Modules\Forum\Lib\Forum_Threading::assign_depths($messages);

		// Phase B — enrichir chaque message d'une référence au parent (username + excerpt)
		// pour rendre cliquable le bandeau "En réponse à" dans la vue.
		$by_id = [];
		foreach ($messages as $m) { $by_id[(int)$m['message_id']] = $m; }
		foreach ($messages as &$m)
		{
			$pid = (int)$m['parent_id'];
			$m['parent_username'] = '';
			$m['parent_excerpt']  = '';
			if ($pid && isset($by_id[$pid]))
			{
				$parent = $by_id[$pid];
				$parent_user = $this->db->select('username')->from('nf_user')->where('id', (int)$parent['user_id'])->row();
				$m['parent_username'] = is_array($parent_user) ? $parent_user['username'] : (string)$parent_user;
				$m['parent_excerpt']  = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', (string)$parent['message'])));
			}
		}
		unset($m);

		return $messages;
	}

	public function check_category($category_id, $title)
	{
		$category = $this->db	->select('c.category_id', 'c.title AS titre_par_defaut', $this->titre_categorie('c').' AS title')
								->from('nf_forum_categories c')
								->where('c.category_id', $category_id)
								->row();

		if ($category && $this->_titre_d_adresse('category', (int) $category_id, (string) $title, (string) $category['titre_par_defaut']))
		{
			return $category;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Catégorie verrouillée pour le membre courant ? (VIP requis et non satisfait).
	 * Les administrateurs effectifs voient tout.
	 */
	private function _vip_locked($category_id)
	{
		if ($this->access->effective_admin())
		{
			return FALSE;
		}

		if (!(int) $this->db->select('vip_only')->from('nf_forum_categories')->where('category_id', (int) $category_id)->row())
		{
			return FALSE;
		}

		$uid = $this->user() ? (int) $this->user->id : 0;
		$gam = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['gamification']);

		return !($uid && $gam && $gam->is_vip($uid));
	}

	public function check_forum($forum_id, &$title)
	{
		$forum = $this->db	->select('f.forum_id', 'f.title AS titre_par_defaut', $this->titre_forum('f').' AS title', $this->titre_forum('f', 'description').' AS description', 'f.parent_id', 'f.is_subforum', 'u.url', 'IFNULL(f3.parent_id, f.parent_id) as category_id', 'COUNT(f2.forum_id) as subforums')
							->from('nf_forum f')
							// LEFT sur les deux : `f2` ne sert qu'a COMPTER les sous-forums, et `f3` a
							// retrouver le parent QUAND il y en a un. En stricte, un forum sans sous-forum
							// — ou un forum de premier niveau — devenait introuvable.
							->join('nf_forum f2', 'f2.parent_id = f.forum_id  AND f2.is_subforum = "1"', 'LEFT')
							->join('nf_forum f3', 'f3.forum_id  = f.parent_id AND f.is_subforum  = "1"', 'LEFT')
							->join('nf_forum_url u', 'u.forum_id = f.forum_id')
							->where('f.forum_id', $forum_id)
							->row();

		if ($forum && $this->_titre_d_adresse('forum', (int) $forum_id, (string) $title, (string) $forum['titre_par_defaut']))
		{
			if ($this->_vip_locked($forum['category_id']))
			{
				return FALSE;
			}

			$title = $forum['title'];
			return $forum;
		}
		else
		{
			return FALSE;
		}
	}

	public function check_topic($topic_id, &$title)
	{
		$topic = $this->db	->select('t.title as topic_title', 't.forum_id', 't.message_id AS first_message_id', 't.prefix_id', $this->solution_vivante('t').' AS solution_message_id', 'mt.user_id AS topic_user_id', $this->titre_forum('f').' AS title', 'IFNULL(f2.parent_id, f.parent_id) as category_id', 't.views', 't.status IN ("-2", "1") as announce', 't.status IN ("-2", "-1") as locked')
							->from('nf_forum_topics t')
							->join('nf_forum        f',  't.forum_id  = f.forum_id')
							->join('nf_forum        f2', 'f2.forum_id = f.parent_id AND f.is_subforum = "1"')
							->join('nf_forum_messages mt', 'mt.message_id = t.message_id')
							->where('t.topic_id', $topic_id)
							->row();

		if ($topic && $title == url_title($topic['topic_title']))
		{
			if ($this->_vip_locked($topic['category_id']))
			{
				return FALSE;
			}

			$title = $topic['topic_title'];
			return $topic;
		}
		else
		{
			return FALSE;
		}
	}

	public function check_message($message_id, $title)
	{
		$message = $this->db	->select('m.message_id', 't.topic_id', 't.title', 't.message_id = m.message_id as is_topic', 'm.message', 'IFNULL(f2.parent_id, f.parent_id) as category_id', 't.forum_id', 'm.user_id', 't.status IN ("-2", "-1") as locked')
								->from('nf_forum_messages m')
								->join('nf_forum_topics   t',  'm.topic_id = t.topic_id')
								->join('nf_forum          f',  't.forum_id = f.forum_id')
								->join('nf_forum          f2', 'f2.forum_id = f.parent_id AND f.is_subforum = "1"')
								->where('m.message_id', $message_id)
								->row();

		if ($message && $title == url_title($message['title']))
		{
			if ($this->_vip_locked($message['category_id']))
			{
				return FALSE;
			}

			return $message;
		}
		else
		{
			return FALSE;
		}
	}

	public function add_topic($forum_id, $title, $message, $announce)
	{
		$this->db->transaction();

		try
		{
			$topic_id = $this->db	->ignore_foreign_keys()
									->insert('nf_forum_topics', [
										'forum_id' => (int)$forum_id,
										'title'    => $title,
										'status'   => $announce
									]);

			$message_id = $this->db	->insert('nf_forum_messages', [
										'topic_id' => $topic_id,
										'user_id'  => $this->user->id,
										'message'  => $message
									]);

			$count_topics = $this->db->select('count_topics')->from('nf_forum')->where('forum_id', $forum_id)->row();

			$this->db	->where('forum_id', $forum_id)
						->update('nf_forum', [
							'last_message_id' => $message_id,
							'count_topics'    => $count_topics + 1
						]);

			$this->db	->where('topic_id', $topic_id)
						->update('nf_forum_topics', [
							'message_id' => $message_id
						]);

			$this->db	->insert('nf_forum_topics_read', [
							'topic_id' => $topic_id,
							'user_id'  => $this->user->id
						]);

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		// Auto-subscribe le créateur du topic
		$this->subscribe($topic_id, $this->user->id);

		// Enregistrer les @mentions
		$mentioned_users = $this->record_mentions($message_id, $this->user->id, $message);

		$this->events->fire('forum.topic.created', [
			'topic_id'   => $topic_id,
			'message_id' => $message_id,
			'forum_id'   => (int)$forum_id,
			'user_id'    => $this->user->id,
			'title'      => $title,
			'message'    => $message
		]);

		$this->events->fire('forum.post.created', [
			'message_id'      => $message_id,
			'topic_id'        => $topic_id,
			'forum_id'        => (int)$forum_id,
			'user_id'         => $this->user->id,
			'message'         => $message,
			'is_starter'      => TRUE,
			'mentioned_users' => $mentioned_users
		]);

		if ($wh = $this->module('webhooks'))
		{
			$wh->trigger('forum.topic', [
				'topic_id' => $topic_id,
				'forum_id' => (int)$forum_id,
				'title'    => $title,
				'user_id'  => (int)$this->user->id,
				'url'      => url('forum/topic/'.$topic_id.'/'.url_title($title))
			]);
		}

		$this->get_topics($forum_id);

		return $topic_id;
	}

	public function add_message($topic_id, $message, $parent_id = NULL)
	{
		$topic = $this->db->select('count_messages', 'forum_id')->from('nf_forum_topics')->where('topic_id', $topic_id)->row();
		$count_messages = $this->db->select('count_messages')->from('nf_forum')->where('forum_id', $topic['forum_id'])->row();

		$parent_id = $this->_validate_parent($parent_id, $topic_id);

		$this->db->transaction();

		try
		{
			$insert = [
				'topic_id' => (int)$topic_id,
				'user_id'  => $this->user->id,
				'message'  => $message
			];

			if ($parent_id)
			{
				$insert['parent_id'] = $parent_id;
			}

			$message_id = $this->db->insert('nf_forum_messages', $insert);

			$this->db	->where('forum_id', $topic['forum_id'])
						->update('nf_forum', [
							'last_message_id' => $message_id,
							'count_messages' => $count_messages + 1
						]);

			$this->db	->where('topic_id', $topic_id)
						->update('nf_forum_topics', [
							'last_message_id' => $message_id,
							'count_messages'  => $topic['count_messages'] + 1
						]);

			$this->db	->where('user_id', $this->user->id)
						->where('topic_id', $topic_id)
						->delete('nf_forum_topics_read');

			$this->db	->insert('nf_forum_topics_read', [
							'topic_id' => $topic_id,
							'user_id'  => $this->user->id
						]);

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		// Enregistrer les @mentions
		$mentioned_users = $this->record_mentions($message_id, $this->user->id, $message);

		$this->events->fire('forum.post.created', [
			'message_id'      => $message_id,
			'topic_id'        => (int)$topic_id,
			'forum_id'        => (int)$topic['forum_id'],
			'user_id'         => $this->user->id,
			'message'         => $message,
			'is_starter'      => FALSE,
			'mentioned_users' => $mentioned_users
		]);

		$this->get_topics($topic['forum_id']);

		return $message_id;
	}

	public function add_category($title, $image_id = NULL, $vip_only = FALSE)
	{
		$category_id = $this->db->insert('nf_forum_categories', [
			'title'    => $title,
			'image_id' => $image_id ?: NULL,
			'vip_only' => $vip_only ? 1 : 0
		]);

		$this->access->init('forum', 'category', $category_id);

		return $category_id;
	}

	public function add_forum($title, $category_id, $description, $url)
	{
		$this->db->transaction();

		try
		{
			$forum_id = $this->db->insert('nf_forum', [
				'title'       => $title,
				'parent_id'   => $this->get_parent_id($category_id, $is_subforum),
				'is_subforum' => $is_subforum,
				'description' => $description
			]);

			if ($url)
			{
				$this->db->insert('nf_forum_url', [
					'forum_id' => $forum_id,
					'url'      => $url
				]);
			}

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		return $forum_id;
	}

	public function edit_category($category_id, $title, $image_id = NULL, $vip_only = FALSE)
	{
		$this->db	->where('category_id', $category_id)
					->update('nf_forum_categories', [
						'title'    => $title,
						'image_id' => $image_id ?: NULL,
						'vip_only' => $vip_only ? 1 : 0
					]);
	}

	public function delete_category($category_id, $skip_transaction = FALSE)
	{
		if (!$skip_transaction) $this->db->transaction();

		try
		{
			$this->db	->where('category_id', $category_id)
						->delete('nf_forum_categories');

			foreach ($this->db->select('forum_id')->from('nf_forum')->where('parent_id', $category_id)->where('is_subforum', FALSE)->get() as $forum_id)
			{
				$this->delete_forum($forum_id, TRUE);
			}

			$this->access->delete('forum', $category_id);

			if (!$skip_transaction) $this->db->commit();
		}
		catch (\Throwable $e)
		{
			if (!$skip_transaction) $this->db->rollback();
			throw $e;
		}
	}

	public function delete_forum($forum_id, $skip_transaction = FALSE)
	{
		if (!$skip_transaction) $this->db->transaction();

		try
		{
			foreach ($this->db->select('forum_id')->from('nf_forum')->where('parent_id', $forum_id)->where('is_subforum', TRUE)->get() as $subforum_id)
			{
				$this->delete_forum($subforum_id, TRUE);
			}

			$this->db	->where('forum_id', $forum_id)
						->delete('nf_forum');

			$this->db	->where('forum_id', $forum_id)
						->delete('nf_forum_read');

			if (!$skip_transaction) $this->db->commit();
		}
		catch (\Throwable $e)
		{
			if (!$skip_transaction) $this->db->rollback();
			throw $e;
		}
	}

	public function mark_all_as_read($forum_id = 0)
	{
		if (!$this->user())
		{
			return;
		}

		if ($forum_id)
		{
			$this->db	->where('r.user_id', $this->user->id)
						->where('t.topic_id = r.topic_id')
						->where('t.forum_id', $forum_id)
						->delete('r', 'nf_forum_topics_read as r, nf_forum_topics as t');

			$this->db	->where('user_id', $this->user->id)
						->where('forum_id', $forum_id)
						->delete('nf_forum_read');
		}
		else
		{
			$this->db	->where('user_id', $this->user->id)
						->delete('nf_forum_topics_read');

			$this->db	->where('user_id', $this->user->id)
						->delete('nf_forum_read');
		}

		$this->db->insert('nf_forum_read', [
			'user_id'  => $this->user->id,
			'forum_id' => $forum_id
		]);
	}

	public function increment_redirect($forum_id)
	{
		$this->db	->where('forum_id', $forum_id)
					->update('nf_forum_url', 'redirects = redirects + 1');
	}

	public function get_parent_id($parent_id, &$is_subforum)
	{
		$is_subforum = FALSE;

		if (strpos($parent_id, 'f') === 0)
		{
			$parent_id   = substr($parent_id, 1);
			$is_subforum = TRUE;
		}

		return $parent_id;
	}

	public function get_last_message_id($forum_id)
	{
		$message_id = $this->db	->select('m.message_id')
								->from('nf_forum_messages m')
								->join('nf_forum_topics t', 't.topic_id = m.topic_id')
								->where('t.forum_id', $forum_id)
								->order_by('message_id DESC')
								->row();

		return $message_id ?: NULL;
	}

	public function count_messages($topic_id)
	{
		return $this->db->from('nf_forum_messages')
						->where('topic_id', $topic_id)
						->count() - 1;
	}

	// =================================================================
	// Abonnements aux sujets → trait Forum_Subscriptions (forum_subscriptions.php)

	// =================================================================
	// Mentions @user → trait Forum_Mentions (forum_mentions.php).
	// render_mentions vit dans le module class (forum.php) pour être accessible
	// depuis les vues via $this->output->module()->render_mentions(...)

	// Modération avancée (split / merge / trash) → trait Forum_Moderation (forum_moderation.php)

	public function _has_unread($forum)
	{
		/*
		 * Sans message à lui, un forum n'a rien de non lu. Ses compteurs peuvent pourtant ajouter ceux
		 * de ses sous-forums : « Annonces de l'équipe », qui n'a de sujets que dans son sous-forum
		 * « Changelog », arrivait ici sans date de dernier message, et `strtotime(NULL)` faisait tomber
		 * la liste du forum pour tout membre connecté (2026-10-01). Le non-lu d'un sous-forum remonte
		 * à son parent dans `get_forums()`.
		 */
		if (!$forum['count_topics'] || empty($forum['last_message_date']) || !$this->user())
		{
			return FALSE;
		}

		static $forum_reads;

		if ($forum_reads === NULL)
		{
			$forum_reads = [];

			foreach ($this->db	->select('forum_id', 'date')
								->from('nf_forum_read')
								->where('user_id', $this->user->id)
								->get() as $read)
			{
				$forum_reads[$read['forum_id']] = strtotime($read['date']);
			}

			// `registration_date` est un objet `Date` : le mode strict refuse sa conversion
			// implicite pour une fonction interne.
			$registration_date = strtotime((string) $this->user->registration_date);
			if (!isset($forum_reads[0]) || $registration_date > $forum_reads[0])
			{
				$forum_reads[0] = $registration_date;
			}
		}

		$dates = [];

		if (isset($forum_reads[0]))
		{
			$dates[] = $forum_reads[0];
		}

		if (isset($forum_reads[$forum['forum_id']]))
		{
			$dates[] = $forum_reads[$forum['forum_id']];
		}

		$forum_read_date = $dates ? max($dates) : NULL;

		return empty($forum_read_date) || $forum_read_date < strtotime($forum['last_message_date']);
	}
}
