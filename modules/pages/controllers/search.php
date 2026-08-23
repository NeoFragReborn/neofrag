<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Pages\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Search extends Controller_Module
{
	public function index($result, $keywords)
	{
		$result['content'] = highlight($result['content'], $keywords);
		return $this->view('search/index', $result);
	}

	public function detail($result, $keywords)
	{
		$result['content'] = highlight($result['content'], $keywords, 1024);
		return $this->view('search/index', $result);
	}

	public function search()
	{
		// ACL par page (access_page) : ne pas exposer titre/URL des pages restreintes dans la recherche
		// (le contenu reste protégé par le checker, mais titre+URL fuiteraient sinon). Cf. index.php.
		$pages = array_filter($this->db->select('page_id')->from('nf_pages')->where('published', TRUE)->get(), function($page_id){
			return $this->access('pages', 'access_page', $page_id);
		});

		$this->db	->select('p.page_id', 'p.name', 'pl.title', 'pl.subtitle', 'pl.content')
					->from('nf_pages p')
					->join('nf_pages_lang pl', 'p.page_id = pl.page_id')
					->where('p.page_id', $pages)
					->where('pl.lang', $this->config->lang->info()->name);

		return ['pl.title', 'pl.subtitle', 'pl.content'];
	}

	/** Suggestion typeahead (titre + lien) à partir d'une ligne de résultat. */
	public function suggest($result)
	{
		return [
			'title' => $result['title'],
			'url'   => url(url_title($result['name']))
		];
	}
}
