<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Settings\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Ajax extends Controller_Module
{
	public function humans()
	{
		return $this->config->nf_humans_txt;
	}

	public function robots()
	{
		$content = $this->config->nf_robots_txt;
		$sitemap_line = "\nSitemap: ".url('sitemap.xml');

		if (stripos($content, 'sitemap:') === FALSE)
		{
			$content .= $sitemap_line;
		}

		return $content;
	}

	public function sitemap()
	{
		$urls = [];

		// Home
		$urls[] = ['loc' => url('//'), 'changefreq' => 'daily', 'priority' => '1.0'];

		// Pages CMS publiées
		$pages = $this->db	->select('page_id', 'name')
							->from('nf_pages')
							->where('published', '1')
							->get();
		foreach ($pages as $p)
		{
			$urls[] = ['loc' => url($p['name']), 'changefreq' => 'monthly', 'priority' => '0.5'];
		}

		// Articles publiés
		$articles = $this->db	->select('a.article_id', 'al.title', 'UNIX_TIMESTAMP(a.date) AS ts')
								->from('nf_articles a')
								->join('nf_articles_lang al', 'a.article_id = al.article_id')
								->where('a.published', '1')
								->where('al.lang', $this->config->lang->info()->name)
								->order_by('a.date DESC')
								->get();
		foreach ($articles as $a)
		{
			$urls[] = [
				'loc'        => url('articles/'.$a['article_id'].'/'.url_title($a['title'])),
				'lastmod'    => date('Y-m-d', $a['ts']),
				'changefreq' => 'weekly',
				'priority'   => '0.8'
			];
		}

		// News publiées
		$news = $this->db	->select('n.news_id', 'nl.title', 'UNIX_TIMESTAMP(n.date) AS ts')
							->from('nf_news n')
							->join('nf_news_lang nl', 'n.news_id = nl.news_id')
							->where('n.published', '1')
							->where('nl.lang', $this->config->lang->info()->name)
							->order_by('n.date DESC')
							->get();
		foreach ($news as $n)
		{
			$urls[] = [
				'loc'        => url('news/'.$n['news_id'].'/'.url_title($n['title'])),
				'lastmod'    => date('Y-m-d', $n['ts']),
				'changefreq' => 'weekly',
				'priority'   => '0.7'
			];
		}

		// FAQ questions
		$faq = $this->db	->select('q.id', 'q.question', 'UNIX_TIMESTAMP(q.updated_at) AS ts')
							->from('nf_faq_questions q')
							->where('q.published', '1')
							->get();
		foreach ($faq as $q)
		{
			$urls[] = [
				'loc'        => url('faq#faq-q-'.$q['id']),
				'lastmod'    => date('Y-m-d', $q['ts']),
				'changefreq' => 'monthly',
				'priority'   => '0.4'
			];
		}

		// Top-level routes des modules core (généralistes)
		foreach (['articles', 'news', 'forum', 'gallery', 'contact', 'members', 'search', 'faq', 'links', 'downloads', 'guestbook', 'newsletter'] as $route)
		{
			$urls[] = ['loc' => url($route), 'changefreq' => 'weekly', 'priority' => '0.6'];
		}

		$xml  = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

		foreach ($urls as $u)
		{
			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>".htmlspecialchars($u['loc'], ENT_XML1)."</loc>\n";
			if (isset($u['lastmod']))
			{
				$xml .= "\t\t<lastmod>".$u['lastmod']."</lastmod>\n";
			}
			$xml .= "\t\t<changefreq>".$u['changefreq']."</changefreq>\n";
			$xml .= "\t\t<priority>".$u['priority']."</priority>\n";
			$xml .= "\t</url>\n";
		}

		$xml .= '</urlset>'."\n";

		return $xml;
	}

	public function debug_bar()
	{
		if ($tab = post('tab'))
		{
			$this->session->set('debug', 'tab', $tab);
		}
		else if ($tab === '')
		{
			$this->session->destroy('debug', 'tab');
		}
		else if ($height = (int)post('height'))
		{
			$this->session->set('debug', 'height', $height);
		}
	}

	public function languages()
	{
		if (post())
		{
			foreach ($this->config->langs as $language)
			{
				if (($name = $language->info()->name) == post('language'))
				{
					if ($this->user->id)
					{
						$this->user->set('language', $language->__addon)->update();
					}
					else
					{
						$this->session->set('language', $language->info()->name);
					}

					if ($name != $this->config->lang->info()->name)
					{
						$this->url->redirect($this->url->base.$name.substr(post('url'), strlen($this->url->base.$this->config->lang->info()->name)));
					}

					break;
				}
			}
		}
		else
		{
			return $this->js('languages')
						->modal('Choisir ma langue', 'fas fa-globe')
						->body($this->view('languages'))
						->cancel();
		}
	}
}
