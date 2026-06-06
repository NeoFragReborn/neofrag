<?php
namespace NF\Modules\Wiki\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index($pages)
	{
		$this->title('Wiki')->icon('fas fa-book')->breadcrumb();

		// Regroupe par parent : les pages de 1er niveau sont les catégories.
		$by_parent = [];
		foreach ($pages as $p)
		{
			$by_parent[$p['parent_id'] ?? 0][] = $p;
		}

		if (empty($pages))
		{
			$body = '<div class="alert alert-info text-center">Aucune page wiki pour le moment.</div>';
		}
		else
		{
			$body = '<div class="wiki-toc">';
			foreach (($by_parent[0] ?? []) as $cat)
			{
				$children = $by_parent[$cat['id']] ?? [];
				$body .= '<section class="wiki-cat">'
					   .  '<a class="wiki-cat-head" href="'.url('wiki/'.$cat['slug']).'">'
					   .  '<span class="wiki-cat-ico"><i class="fas fa-folder-open"></i></span>'
					   .  '<span class="wiki-cat-title">'.htmlspecialchars($cat['title']).'</span>'
					   .  ($children ? '<span class="wiki-cat-count">'.count($children).'</span>' : '')
					   .  '</a>';
				if ($children)
				{
					$body .= '<ul class="wiki-cat-list">';
					foreach ($children as $p)
					{
						$body .= '<li><a href="'.url('wiki/'.$p['slug']).'"><i class="far fa-file-lines"></i><span>'.htmlspecialchars($p['title']).'</span><i class="fas fa-chevron-right wiki-go"></i></a></li>';
					}
					$body .= '</ul>';
				}
				$body .= '</section>';
			}
			$body .= '</div>';
		}

		return $this->css('wiki')->panel()->title('Wiki', 'fas fa-book')->body($body);
	}

	public function _page($page)
	{
		$this->title($page['title'])->icon('fas fa-book')->breadcrumb();

		// Navigation latérale : arbre des pages (page active surlignée).
		$all = NeoFrag()->db->select('id', 'slug', 'title', 'parent_id')->from('nf_wiki_pages')->where('published', '1')->order_by('parent_id ASC, sort_order ASC, id ASC')->get();
		$kids = [];
		foreach ($all as $p) { $kids[$p['parent_id'] ?? 0][] = $p; }

		$nav = '<nav class="wiki-nav"><a class="wiki-nav-head" href="'.url('wiki').'"><i class="fas fa-book"></i> '.$this->lang('Documentation').'</a>';
		foreach (($kids[0] ?? []) as $cat)
		{
			$nav .= '<div class="wiki-nav-cat">'.htmlspecialchars($cat['title']).'</div><ul>';
			foreach ($kids[$cat['id']] ?? [] as $c)
			{
				$nav .= '<li><a href="'.url('wiki/'.$c['slug']).'"'.($c['slug'] === $page['slug'] ? ' class="active"' : '').'>'.htmlspecialchars($c['title']).'</a></li>';
			}
			$nav .= '</ul>';
		}
		$nav .= '</nav>';

		$meta = '<div class="wiki-meta"><small>';
		$meta .= '<i class="far fa-eye"></i> '.(int)$page['views'].' vues';
		if ($page['author_id'])
		{
			$meta .= ' &middot; <i class="far fa-user"></i> '.$this->user->link($page['author_id'], $page['username']);
		}
		$meta .= ' &middot; <i class="far fa-clock"></i> '.date('Y-m-d H:i', $page['updated_ts']);
		$meta .= '</small><a class="btn btn-sm btn-outline-secondary" href="'.url('wiki/history/'.$page['slug']).'"><i class="fas fa-history"></i> Historique</a></div>';

		$main = $meta.'<div class="wiki-content">'.render_content($page['content']).'</div>';

		$body = '<div class="wiki-layout">'.$nav.'<div class="wiki-main">'.$main.'</div></div>';

		return $this->css('wiki')->panel()->title($page['title'], 'fas fa-book')->body($body);
	}

	public function _history($page, $revisions)
	{
		$this->title('Historique : '.$page['title'])->icon('fas fa-history')->breadcrumb();

		$body = '<a class="btn btn-secondary btn-sm mb-3" href="'.url('wiki/'.$page['slug']).'"><i class="fas fa-arrow-left"></i> Retour à la page</a>';

		if (empty($revisions))
		{
			$body .= '<div class="alert alert-info">Aucune révision archivée.</div>';
		}
		else
		{
			$body .= '<table class="table table-sm"><thead><tr><th>Date</th><th>Auteur</th><th>Titre</th><th>Commentaire</th><th></th></tr></thead><tbody>';
			$revs = array_values($revisions);
			foreach ($revs as $idx => $r)
			{
				$actions = '<a class="btn btn-sm btn-outline-info" href="'.url('wiki/revision/'.$r['id']).'"><i class="fas fa-eye"></i> Voir</a> '
					.'<a class="btn btn-sm btn-outline-secondary" href="'.url('wiki/diff/'.$r['id']).'" title="Comparer à la version actuelle"><i class="fas fa-exchange-alt"></i> Actuelle</a>';

				// Révision précédente (plus ancienne) = ligne suivante (tri DESC).
				if (isset($revs[$idx + 1]))
				{
					$actions .= ' <a class="btn btn-sm btn-outline-secondary" href="'.url('wiki/diff/'.$revs[$idx + 1]['id'].'/'.$r['id']).'" title="Comparer à la révision précédente"><i class="fas fa-exchange-alt"></i> Précédente</a>';
				}

				$body .= '<tr>'
					.'<td>'.date('Y-m-d H:i', $r['ts']).'</td>'
					.'<td>'.($r['user_id'] ? $this->user->link($r['user_id'], $r['username']) : 'Anonyme').'</td>'
					.'<td>'.htmlspecialchars($r['title']).'</td>'
					.'<td><small>'.htmlspecialchars($r['comment'] ?? '').'</small></td>'
					.'<td>'.$actions.'</td>'
					.'</tr>';
			}
			$body .= '</tbody></table>';
		}

		return $this->panel()->title('Historique', 'fas fa-history')->body($body);
	}

	public function _revision($rev)
	{
		$this->title('Révision archivée : '.$rev['title'])->icon('fas fa-history')->breadcrumb();

		$body = '<div class="alert alert-warning"><i class="fas fa-info-circle"></i> Tu visualises une <strong>ancienne version</strong> de cette page (révision du '.date('Y-m-d H:i', $rev['ts']).' par '.($rev['user_id'] ? $this->user->link($rev['user_id'], $rev['username']) : 'Anonyme').').';
		if (!empty($rev['comment']))
		{
			$body .= ' Commentaire : <em>'.htmlspecialchars($rev['comment']).'</em>';
		}
		$body .= '</div>';

		$body .= '<div class="wiki-content">'.render_content($rev['content']).'</div>';

		$body .= '<div class="mt-3">'
			.'<a class="btn btn-secondary btn-sm" href="'.url('wiki/history/'.$rev['page_slug']).'"><i class="fas fa-arrow-left"></i> Retour à l\'historique</a> '
			.'<a class="btn btn-primary btn-sm" href="'.url('wiki/'.$rev['page_slug']).'"><i class="fas fa-eye"></i> Voir la version actuelle</a>'
			.'</div>';

		return $this->panel()->title('Révision archivée', 'fas fa-history')->body($body);
	}

	public function _diff($from, $to, $page)
	{
		$this->title('Comparaison : '.$page['title'])->icon('fas fa-exchange-alt')->breadcrumb();

		$label = function($rev){
			$who  = $rev['user_id'] ? $this->user->link($rev['user_id'], $rev['username']) : 'Anonyme';
			$when = date('Y-m-d H:i', $rev['ts']);
			$name = !empty($rev['current']) ? $this->lang('Version actuelle') : $this->lang('Révision');
			return '<strong>'.$name.'</strong><br><small>'.$when.' · '.$who.'</small>';
		};

		$head = '<div class="wiki-diff-head">'
			.'<div class="wiki-diff-side del">'.$label($from).'</div>'
			.'<div class="wiki-diff-arrow"><i class="fas fa-arrow-right"></i></div>'
			.'<div class="wiki-diff-side add">'.$label($to).'</div>'
			.'</div>';

		$title_diff = '';
		if ($from['title'] !== $to['title'])
		{
			$title_diff = '<div class="wiki-diff-titlerow"><span class="wiki-diff-tag">'.$this->lang('Titre').'</span> '
				.'<span class="wiki-diff-inline del">'.htmlspecialchars($from['title']).'</span> '
				.'<i class="fas fa-arrow-right text-muted"></i> '
				.'<span class="wiki-diff-inline add">'.htmlspecialchars($to['title']).'</span></div>';
		}

		$ops     = $this->_diff_ops($this->_diff_lines($from['content']), $this->_diff_lines($to['content']));
		$changed = array_filter($ops, function($o){ return $o[0] !== '='; });

		if (!$changed)
		{
			$diff_body = '<div class="alert alert-info mb-0"><i class="fas fa-equals"></i> '.$this->lang('Aucune différence de contenu entre ces deux versions.').'</div>';
		}
		else
		{
			$diff_body = '<div class="wiki-diff">';
			foreach ($ops as $op)
			{
				$cls = $op[0] === '+' ? 'add' : ($op[0] === '-' ? 'del' : 'ctx');
				$sym = $op[0] === '+' ? '+' : ($op[0] === '-' ? '−' : '');
				$diff_body .= '<div class="wiki-diff-line '.$cls.'"><span class="wiki-diff-sym">'.$sym.'</span><span class="wiki-diff-txt">'.htmlspecialchars($op[1]).'</span></div>';
			}
			$diff_body .= '</div>';
		}

		$back = '<div class="mt-3">'
			.'<a class="btn btn-secondary btn-sm" href="'.url('wiki/history/'.$page['slug']).'"><i class="fas fa-arrow-left"></i> '.$this->lang('Retour à l\'historique').'</a> '
			.'<a class="btn btn-primary btn-sm" href="'.url('wiki/'.$page['slug']).'"><i class="fas fa-eye"></i> '.$this->lang('Voir la version actuelle').'</a>'
			.'</div>';

		return $this->css('wiki')->panel()->title($this->lang('Comparaison de versions'), 'fas fa-exchange-alt')->body($head.$title_diff.$diff_body.$back);
	}

	/** Contenu HTML -> lignes de texte lisibles (frontières de blocs = sauts de ligne). */
	private function _diff_lines($html)
	{
		$text = preg_replace('#</(p|li|h[1-6]|div|tr|blockquote|pre)>|<br\s*/?>#i', "\n", (string)$html);
		$text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

		$lines = [];
		foreach (preg_split('/\R/u', $text) as $line)
		{
			if (($line = trim(preg_replace('/[ \t]+/u', ' ', $line))) !== '')
			{
				$lines[] = $line;
			}
		}

		return $lines;
	}

	/** Diff ligne à ligne par plus longue sous-séquence commune. @return list<array{0:string,1:string}> */
	private function _diff_ops(array $a, array $b)
	{
		$n = count($a);
		$m = count($b);

		// Garde-fou anti-explosion mémoire (table LCS en O(n*m)) : diff grossier au-delà.
		if ($n * $m > 1000000)
		{
			$ops = [];
			foreach ($a as $l) { $ops[] = ['-', $l]; }
			foreach ($b as $l) { $ops[] = ['+', $l]; }
			return $ops;
		}

		$lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
		for ($i = $n - 1; $i >= 0; $i--)
		{
			for ($j = $m - 1; $j >= 0; $j--)
			{
				$lcs[$i][$j] = $a[$i] === $b[$j]
					? $lcs[$i + 1][$j + 1] + 1
					: max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
			}
		}

		$ops = [];
		$i = $j = 0;
		while ($i < $n && $j < $m)
		{
			if ($a[$i] === $b[$j])
			{
				$ops[] = ['=', $a[$i]]; $i++; $j++;
			}
			else if ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1])
			{
				$ops[] = ['-', $a[$i]]; $i++;
			}
			else
			{
				$ops[] = ['+', $b[$j]]; $j++;
			}
		}
		while ($i < $n) { $ops[] = ['-', $a[$i]]; $i++; }
		while ($j < $m) { $ops[] = ['+', $b[$j]]; $j++; }

		return $ops;
	}
}
