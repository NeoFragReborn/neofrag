<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : l'accueil du forum, ses forums et leurs sujets —
 * le plan d'avant n'annonçait que `/forum`. Seulement ce qu'un VISITEUR lit : les catégories que son rôle
 * peut lire (`forum.category_read`, jugé pour le groupe des visiteurs, pas pour qui demande le plan), hors
 * catégories réservées aux VIP, et hors forums de redirection, qui ne sont qu'un lien vers ailleurs.
 *
 * Le forum n'a pas de repli de langue : un forum se lit partout, sous son titre traduit quand il l'est.
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Forum\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$lisibles = [];

		foreach ($this->db->select('category_id', 'vip_only')->from('nf_forum_categories')->get() as $categorie)
		{
			if (!$categorie['vip_only'] && $this->access('forum', 'category_read', (int) $categorie['category_id'], 'visitors'))
			{
				$lisibles[(int) $categorie['category_id']] = TRUE;
			}
		}

		$redirections = [];

		foreach ($this->db->select('forum_id', 'url')->from('nf_forum_url')->get() as $redirection)
		{
			if (trim((string) $redirection['url']) !== '')
			{
				$redirections[(int) $redirection['forum_id']] = TRUE;
			}
		}

		$traductions = [];

		foreach ($this->db->select('forum_id', 'title')->from('nf_forum_lang')->where('lang', $this->config->lang->info()->name)->get() as $traduction)
		{
			if (trim((string) $traduction['title']) !== '')
			{
				$traductions[(int) $traduction['forum_id']] = (string) $traduction['title'];
			}
		}

		$forums = [];

		foreach ($this->db	->select('f.forum_id', 'f.parent_id', 'f.is_subforum', 'f.title', 'm.date')
							->from('nf_forum f')
							->join('nf_forum_messages m', 'm.message_id = f.last_message_id', 'LEFT')
							->get() as $forum)
		{
			$forums[(int) $forum['forum_id']] = $forum;
		}

		$adresses = [['adresse' => 'forum']];
		$visibles = [];

		foreach ($forums as $id => $forum)
		{
			// Un sous-forum appartient à la catégorie de son forum parent.
			$categorie = $forum['is_subforum'] ? (int) ($forums[(int) $forum['parent_id']]['parent_id'] ?? 0) : (int) $forum['parent_id'];

			if (isset($lisibles[$categorie]) && !isset($redirections[$id]))
			{
				$visibles[$id] = TRUE;
				$adresses[]    = ['adresse' => 'forum/'.$id.'/'.url_title($traductions[$id] ?? (string) $forum['title']), 'date' => $forum['date']];
			}
		}

		if ($visibles)
		{
			foreach ($this->db	->select('t.topic_id', 't.title', 'IFNULL(m2.date, m1.date) AS date')
								->from('nf_forum_topics t')
								->join('nf_forum_messages m1', 'm1.message_id = t.message_id')
								->join('nf_forum_messages m2', 'm2.message_id = t.last_message_id', 'LEFT')
								->where('t.forum_id', array_keys($visibles))
								->where('m1.deleted_at', NULL)
								->order_by('date DESC')
								->get() as $sujet)
			{
				$adresses[] = ['adresse' => 'forum/topic/'.$sujet['topic_id'].'/'.url_title($sujet['title']), 'date' => $sujet['date']];
			}
		}

		$adresses[0]['date'] = max(array_map(static fn (array $a): string => (string) ($a['date'] ?? ''), $adresses));

		return $adresses;
	}
}
