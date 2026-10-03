<?php
declare(strict_types=1);
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

		$pages = $this->db	->select('p.page_id', 'p.name', 'pl.title', 'pl.subtitle')
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
			// L'adresse d'une page est son NOM, à la racine du site (`/fr/a-propos`) : le contrôleur refuse
			// `pages/…`, et le titre n'est pas le nom. Chaque lien de cette liste rendait 404 (2026-10-03).
			$url = url($p['name']);
			$html .= '<li class="list-group-item">'
				   . '<a href="'.$url.'">'.icon('far fa-file-alt').' '.htmlspecialchars((string) ($p['title'])).'</a>'
				   . (!empty($p['subtitle']) ? ' <small class="text-muted">— '.htmlspecialchars((string) ($p['subtitle'])).'</small>' : '')
				   . '</li>';
		}
		$html .= '</ul>';
		return $html;
	}

	public function _index($page_id, $title, $subtitle, $content, $layout = 'default')
	{
		$this	->title($title)
				->meta_description(!empty($subtitle) ? $subtitle : preg_replace('/\[block:[a-z0-9._-]+[^\]]*\]/i', '', $content))
				->breadcrumb($this->lang('Pages'), 'pages')
				->breadcrumb($title);

		// Contenu libre (+ shortcodes [block:]) PUIS les blocs de module ordonnés (Palier 1).
		// render_instances renvoie '' s'il n'y en a pas → la page rend exactement comme avant.
		$body = $this->_render_content($content).$this->model()->render_instances($page_id);

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

		// [block:clé] ou [block:clé p=v …] — la clé s'arrête à un espace/`]`, les paramètres
		// (jusqu'au `]`) sont validés contre les `fields` du bloc par le modèle (render_block).
		return preg_replace_callback('#<p>\s*\[block:([a-z0-9._-]+)([^\]]*)\]\s*</p>|\[block:([a-z0-9._-]+)([^\]]*)\]#i', function($m){
			return $m[1] !== '' ? $this->model()->render_block($m[1], $m[2]) : $this->model()->render_block($m[3], $m[4]);
		}, $html);
	}
}
