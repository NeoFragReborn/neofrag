<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Pages\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index()
	{
		$this->title($this->lang('Pages'));

		$pages = $this->db	->select('p.page_id', 'pl.title', 'pl.subtitle')
							->from('nf_pages p')
							->join('nf_pages_lang pl', 'p.page_id = pl.page_id')
							->where('pl.lang', $this->config->lang->info()->name)
							->where('p.published', '1')
							->order_by('pl.title')
							->get();

		// Filtre par ACL access_page si défini par page
		$pages = array_values(array_filter($pages, function($p){
			return $this->access('pages', 'access_page', $p['page_id']);
		}));

		return $this->panel()
					->heading($this->lang('Pages disponibles'), 'far fa-file-alt')
					->body($this->_render_pages_list($pages));
	}

	private function _render_pages_list($pages)
	{
		if (empty($pages))
		{
			return '<div class="alert alert-info text-center mb-0">'.icon('far fa-folder-open').' '.$this->lang('Aucune page publiée pour le moment.').'</div>';
		}

		$html = '<ul class="list-group list-group-flush">';
		foreach ($pages as $p)
		{
			$url = url('pages/'.url_title($p['title']));
			$html .= '<li class="list-group-item">'
				   . '<a href="'.$url.'">'.icon('far fa-file-alt').' '.htmlspecialchars($p['title']).'</a>'
				   . (!empty($p['subtitle']) ? ' <small class="text-muted">— '.htmlspecialchars($p['subtitle']).'</small>' : '')
				   . '</li>';
		}
		$html .= '</ul>';
		return $html;
	}

	public function _index($page_id, $title, $subtitle, $content, $layout = 'default')
	{
		$this	->title($title)
				->meta_description(!empty($subtitle) ? $subtitle : preg_replace('/\[block:[a-z0-9._-]+\]/i', '', $content))
				->breadcrumb($this->lang('Pages'), 'pages')
				->breadcrumb($title);

		$body = $this->_render_content($content);

		// Gabarit « page nue » : contenu pleine largeur, sans cadre ni titre.
		if ($layout === 'blank')
		{
			return $body;
		}

		return $this->panel()
					->heading($title.($subtitle ? ' <small>'.$subtitle.'</small>' : ''), 'far fa-file-alt')
					->body($body);
	}

	/**
	 * Rend le contenu BBCode puis injecte les blocs de module ([block:clé]).
	 * L'injection se fait APRÈS sanitize_html (le HTML d'un bloc est généré par le module = de confiance).
	 * Gère le cas où HTMLPurifier isole le shortcode dans son propre <p>.
	 */
	private function _render_content($content)
	{
		$html = bbcode($content);

		if (strpos($html, '[block:') === FALSE)
		{
			return $html;
		}

		$registry = $this->model()->block_registry();

		return preg_replace_callback('#<p>\s*\[block:([a-z0-9._-]+)\]\s*</p>|\[block:([a-z0-9._-]+)\]#i', function($m) use ($registry){
			$key = strtolower($m[1] !== '' ? $m[1] : $m[2]);

			return isset($registry[$key]) && is_callable($registry[$key]['render']) ? (string)call_user_func($registry[$key]['render']) : '';
		}, $html);
	}
}
