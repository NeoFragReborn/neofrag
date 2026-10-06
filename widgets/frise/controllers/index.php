<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Chaque source de la frise est lue seulement si son module est installé et activé ($present) :
 * couplage(calendar): les rendez-vous ne viennent du calendrier que si le module Calendrier est là.
 * couplage(news): les actualités, de même : seulement si le module Actualités est là.
 * couplage(forum): les discussions, de même, et parmi les forums que le visiteur peut lire (Forum::forums_lisibles()).
 * couplage(gallery): les albums photo, de même : seulement si le module Galerie est là.
 */

namespace NF\Widgets\Frise\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	/**
	 * La frise : les entrées de chaque source présente, rangées de la plus lointaine à la plus ancienne, mois par
	 * mois. Les rendez-vous à venir (au plus quatre, les plus proches) passent devant ; le reste se remplit de ce que
	 * le site a vécu sur les derniers mois.
	 */
	public function index($settings = [])
	{
		$this->css('frise');

		$nombre = max(5, min(30, (int) ($settings['count'] ?? 12)));
		$mois   = max(1, min(12, (int) ($settings['mois'] ?? 3)));

		$fuseau     = nf_fuseau();
		$stockage   = nf_fuseau_stockage();
		$maintenant = new \DateTimeImmutable('now', $stockage);
		$depuis     = $maintenant->modify('-'.$mois.' months')->format('Y-m-d H:i:s');
		$present    = static fn (string $module): bool => ($addon = @NeoFrag()->module($module)) && $addon->is_enabled();
		$texte      = static fn ($html, int $longueur): string => nf_texte(str_shortener(trim(strip_tags(utf8_html_entity_decode((string) $html))), $longueur, '…'));

		$a_venir = [];
		$passees = [];

		// Les rendez-vous du calendrier : les prochains, puis ceux qui viennent de passer.
		if ($present('calendar'))
		{
			$local = static function (array $e) use ($fuseau, $stockage): \DateTimeImmutable {
				// Une journée entière est une date de calendrier, sans fuseau (Calendar::format_dt()).
				return !empty($e['all_day'])
					? new \DateTimeImmutable(substr((string) $e['start_at'], 0, 10), $fuseau)
					: (new \DateTimeImmutable((string) $e['start_at'], $stockage))->setTimezone($fuseau);
			};

			$entree = function (array $e, bool $avenir) use ($local, $texte): array {
				return [
					'genre'   => 'rendez-vous',
					'libelle' => $this->lang('Rendez-vous'),
					'moment'  => $local($e),
					'journee' => !empty($e['all_day']),
					'a_venir' => $avenir,
					'titre'   => nf_texte($e['title']),
					'url'     => url('calendar/'.$e['id'].'/'.url_title($e['title'])),
					'texte'   => $texte(trim(($e['location'] ? $e['location'].' — ' : '').strip_tags((string) $e['description']), ' —'), 140),
					'image'   => ''
				];
			};

			$champs = ['id', 'title', 'description', 'location', 'start_at', 'all_day'];

			foreach ($this->db	->select(...$champs)
								->from('nf_calendar_events')
								->where('published', '1')
								->where('start_at >=', $maintenant->setTimezone($fuseau)->setTime(0, 0)->setTimezone($stockage)->format('Y-m-d H:i:s'))
								->where('start_at <', $maintenant->modify('+2 months')->format('Y-m-d H:i:s'))
								->order_by('start_at ASC')
								->limit(min(4, $nombre))
								->get() as $e)
			{
				$a_venir[] = $entree($e, TRUE);
			}

			foreach ($this->db	->select(...$champs)
								->from('nf_calendar_events')
								->where('published', '1')
								->where('start_at >=', $depuis)
								->where('start_at <', $maintenant->setTimezone($fuseau)->setTime(0, 0)->setTimezone($stockage)->format('Y-m-d H:i:s'))
								->order_by('start_at DESC')
								->limit($nombre)
								->get() as $e)
			{
				$passees[] = $entree($e, FALSE);
			}
		}

		// Les actualités parues, dans la langue affichée (les règles de Models\News::get_news()).
		if ($present('news'))
		{
			foreach ($this->db	->select('n.news_id', 'n.date', 'IFNULL(n.image_id, c.image_id) AS image', 'nl.title', 'nl.introduction')
								->from('nf_news n')
								->join('nf_news_lang nl',      'n.news_id     = nl.news_id')
								->join('nf_news_categories c', 'n.category_id = c.category_id')
								->where('nl.lang', $this->config->lang->info()->name)
								->where('n.deleted_at', NULL)
								->where('n.published', TRUE)
								->where('n.date >=', $depuis)
								->where('n.date <=', $maintenant->format('Y-m-d H:i:s'))
								->order_by('n.date DESC')
								->limit($nombre)
								->get() as $n)
			{
				$passees[] = [
					'genre'   => 'actualite',
					'libelle' => $this->lang('Actualité'),
					'moment'  => (new \DateTimeImmutable((string) $n['date'], $stockage))->setTimezone($fuseau),
					'journee' => TRUE,
					'a_venir' => FALSE,
					// Le titre d'une actualité est rangé déjà codé : il s'écrit tel quel (comme dans ses pages).
					'titre'   => $n['title'],
					'url'     => url('news/'.$n['news_id'].'/'.url_title($n['title'])),
					'texte'   => $texte($n['introduction'], 160),
					'image'   => $n['image'] ? (string) NeoFrag()->model2('file', $n['image'])->path() : ''
				];
			}
		}

		// Les discussions ouvertes sur le forum, parmi les forums que le visiteur peut lire.
		if ($present('forum') && ($forum = NeoFrag()->module('forum')) && ($modele = $forum->model('forum')) instanceof \NF\Modules\Forum\Models\Forum
			&& ($forums = $modele->forums_lisibles()))
		{
			foreach ($this->db	->select('t.topic_id', 't.title', 't.count_messages', 'm.date', 'u.username')
								->from('nf_forum_topics t')
								->join('nf_forum_messages m', 'm.message_id = t.message_id', 'INNER')
								->join('nf_user u',           'u.id = m.user_id AND u.deleted = "0"')
								->where('t.forum_id', $forums)
								->where('m.deleted_at', NULL)
								->where('m.date >=', $depuis)
								->order_by('m.date DESC')
								->limit($nombre)
								->get() as $t)
			{
				$reponses = max(0, (int) $t['count_messages']);

				$passees[] = [
					'genre'   => 'discussion',
					'libelle' => $this->lang('Discussion'),
					'moment'  => (new \DateTimeImmutable((string) $t['date'], $stockage))->setTimezone($fuseau),
					'journee' => TRUE,
					'a_venir' => FALSE,
					// Le titre d'un sujet est rangé déjà codé, comme dans les pages du forum.
					'titre'   => $t['title'],
					'url'     => url('forum/topic/'.$t['topic_id'].'/'.url_title($t['title'])),
					'texte'   => ($t['username'] ? $this->lang('Par %s', nf_texte($t['username'])).' · ' : '').$this->lang('%d réponse|%d réponses', $reponses, $reponses),
					'image'   => ''
				];
			}
		}

		// Les albums photo publiés, que le visiteur a le droit de voir (`gallery.gallery_see`, album par album : un
		// album réservé à un groupe ne paraît que pour lui, comme dans la galerie).
		if ($present('gallery'))
		{
			foreach ($this->db	->select('g.gallery_id', 'g.name', 'g.date', 'g.image_id', 'gl.title', 'COUNT(DISTINCT gi.image_id) AS images')
								->from('nf_gallery g')
								->join('nf_gallery_lang gl',   'g.gallery_id = gl.gallery_id')
								->join('nf_gallery_images gi', 'g.gallery_id = gi.gallery_id', 'LEFT')
								->where('gl.lang', $this->config->lang->info()->name)
								->where('g.deleted_at', NULL)
								->where('g.published', TRUE)
								->where('g.date >=', $depuis)
								->where('g.date <=', $maintenant->format('Y-m-d H:i:s'))
								->group_by('g.gallery_id')
								->order_by('g.date DESC')
								->limit($nombre * 2)
								->get() as $g)
			{
				if (!$this->access('gallery', 'gallery_see', (int) $g['gallery_id']))
				{
					continue;
				}

				$images = (int) $g['images'];

				$passees[] = [
					'genre'   => 'photos',
					'libelle' => $this->lang('Photos'),
					'moment'  => (new \DateTimeImmutable((string) $g['date'], $stockage))->setTimezone($fuseau),
					'journee' => TRUE,
					'a_venir' => FALSE,
					'titre'   => $g['title'],
					'url'     => url('gallery/album/'.$g['gallery_id'].'/'.url_title($g['name'])),
					'texte'   => $this->lang('%d photo|%d photos', $images, $images),
					'image'   => $g['image_id'] ? (string) NeoFrag()->model2('file', $g['image_id'])->path() : ''
				];
			}
		}

		usort($passees, static fn (array $a, array $b): int => $b['moment'] <=> $a['moment']);

		// Les rendez-vous à venir en tête, du plus lointain au plus proche : la frise se lit vers le passé.
		$entrees = array_merge(array_reverse($a_venir), array_slice($passees, 0, max(0, $nombre - count($a_venir))));

		$view = $this->view('frise', [
			'entrees'      => $entrees,
			'aujourdhui'   => $maintenant->setTimezone($fuseau)->setTime(0, 0)
		]);

		if (($settings['display_panel'] ?? 'oui') === 'oui')
		{
			return $this	->panel()
							->heading($this->lang('La saison'), 'fas fa-stream')
							->body($view);
		}

		return $view;
	}
}
