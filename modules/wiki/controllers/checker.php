<?php
declare(strict_types=1);
namespace NF\Modules\Wiki\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		// Le titre et le texte d'une page se traduisent (m32, 2026-10-15) : la meilleure langue.
		$pages = NeoFrag()->db	->select('p.id', 'p.slug', 'wl.title', 'p.parent_id')
								->from('nf_wiki_pages p')
								->join_lang('nf_wiki_pages_lang wl', 'page_id', 'p.id', NULL, 'LEFT')
								->where('p.published', '1')
								->order_by('p.parent_id ASC, p.sort_order ASC, wl.title ASC')
								->get();
		return [$pages];
	}

	public function _page($slug)
	{
		$page = NeoFrag()->db	->select('p.*', 'wl.title', 'wl.content', 'u.id AS author_id', 'u.username', 'UNIX_TIMESTAMP(p.updated_at) AS updated_ts')
								->from('nf_wiki_pages p')
								->join_lang('nf_wiki_pages_lang wl', 'page_id', 'p.id', NULL, 'LEFT')
								->join('nf_user u', 'p.user_id = u.id', 'LEFT')
								->where('p.slug', $slug)
								->where('p.published', '1')
								->row();

		if (empty($page)) return;

		if (count_view('wiki', $page['id']))
		{
			// updated_at = updated_at : la colonne se met à jour d'elle-même (ON UPDATE), et la page
			// affichait « modifiée le » à l'heure de la dernière visite.
			NeoFrag()->db->execute('UPDATE nf_wiki_pages SET views = views + 1, updated_at = updated_at WHERE id = '.(int)$page['id']);
		}

		return [$page];
	}

	/*
	 * L'historique, une révision et la comparaison ne s'ouvrent que sur une page PUBLIÉE, comme la page elle-même : ils
	 * rendaient le texte d'un brouillon à n'importe quel visiteur — chaque modification écrit une révision, et leurs
	 * numéros se suivent (audit du 2026-10-09). Un brouillon se relit dans l'administration.
	 */
	public function _history($slug)
	{
		$page = NeoFrag()->db->select('p.id', 'p.slug', 'wl.title')->from('nf_wiki_pages p')->join_lang('nf_wiki_pages_lang wl', 'page_id', 'p.id', NULL, 'LEFT')->where('p.slug', $slug)->where('p.published', '1')->row();
		if (empty($page)) return;

		// L'historique d'une langue : ses révisions, et celles d'avant que le wiki ait des langues (lang NULL).
		$langue    = (string) $this->config->lang->info()->name;
		$revisions = NeoFrag()->db	->select('r.id', 'r.title', 'r.comment', 'UNIX_TIMESTAMP(r.created_at) AS ts', 'u.id AS user_id', 'u.username')
									->from('nf_wiki_revisions r')
									->join('nf_user u', 'r.user_id = u.id', 'LEFT')
									->where('r.page_id', $page['id'])
									->where('r.lang', NULL, 'OR', 'r.lang', $langue)
									->order_by('r.created_at DESC')
									->get();

		return [$page, $revisions];
	}

	public function _revision($id)
	{
		$rev = NeoFrag()->db	->select('r.*', 'p.slug AS page_slug', 'u.id AS user_id', 'u.username', 'UNIX_TIMESTAMP(r.created_at) AS ts')
								->from('nf_wiki_revisions r')
								->join('nf_wiki_pages p', 'r.page_id = p.id')
								->join('nf_user u', 'r.user_id = u.id', 'LEFT')
								->where('r.id', $id)
								->where('p.published', '1')
								->row();
		return $rev ? [$rev] : NULL;
	}

	// diff/{id} : révision vs version actuelle ; diff/{id}/{id} : deux révisions (même page).
	public function _diff($id, $id2 = NULL)
	{
		$revision = function($rid){
			return NeoFrag()->db	->select('r.id', 'r.page_id', 'r.lang', 'r.title', 'r.content', 'r.comment', 'UNIX_TIMESTAMP(r.created_at) AS ts', 'u.id AS user_id', 'u.username')
									->from('nf_wiki_revisions r')
									->join('nf_user u', 'r.user_id = u.id', 'LEFT')
									->where('r.id', (int)$rid)
									->row();
		};

		$a = $revision($id);

		if (empty($a))
		{
			return;
		}

		// La version actuelle DANS LA LANGUE de la révision comparée (la meilleure, pour une révision sans langue).
		$page = NeoFrag()->db	->select('p.id', 'p.slug', 'wl.title', 'wl.content', 'UNIX_TIMESTAMP(p.updated_at) AS ts', 'u.id AS author_id', 'u.username')
								->from('nf_wiki_pages p')
								->join_lang('nf_wiki_pages_lang wl', 'page_id', 'p.id', ($a['lang'] ?? NULL) ?: NULL, 'LEFT')
								->join('nf_user u', 'p.user_id = u.id', 'LEFT')
								->where('p.id', (int)$a['page_id'])
								->where('p.published', '1')
								->row();

		if (empty($page))
		{
			return;
		}

		if ($id2 !== NULL)
		{
			$b = $revision($id2);

			if (empty($b) || (int)$b['page_id'] !== (int)$a['page_id'])
			{
				return;
			}
		}
		else
		{
			// Pseudo-révision « version actuelle » dérivée de la page vive.
			$b = [
				'id'       => 0,
				'page_id'  => $page['id'],
				'title'    => $page['title'],
				'content'  => $page['content'],
				'comment'  => NULL,
				'ts'       => $page['ts'],
				'user_id'  => $page['author_id'],
				'username' => $page['username'],
				'current'  => TRUE
			];
		}

		// Ordonne ancien -> récent pour une lecture naturelle.
		if ($a['ts'] > $b['ts'])
		{
			[$a, $b] = [$b, $a];
		}

		return [$a, $b, $page];
	}
}
