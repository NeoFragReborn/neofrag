<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Pages\Models;

use NF\NeoFrag\Loadables\Model;

class Pages extends Model
{
	public function get_pages()
	{
		return $this->db->select('p.page_id', 'p.name', 'p.published', 'p.date', 'pl.title', 'pl.subtitle')
						->from('nf_pages p')
						->join('nf_pages_lang pl', 'p.page_id = pl.page_id')
						->where('pl.lang', $this->config->lang->info()->name)
						->order_by('pl.title ASC')
						->get();
	}

	/**
	 * Carrefour « block » : chaque module installé peut exposer controllers/block.php → block()
	 * retournant ses blocs injectables ['clé' => ['title' => …, 'render' => callable→HTML]].
	 * Consommé par le shortcode [block:clé] dans le contenu d'une page (injection d'un module).
	 *
	 * @return array<string,array{title:string,render:callable}>
	 */
	public function block_registry()
	{
		static $registry;

		if ($registry === NULL)
		{
			$registry = [];

			foreach (NeoFrag()->model2('addon')->get('module') as $module)
			{
				if ($controller = @$module->controller('block'))
				{
					foreach ((array)$controller->block() as $key => $def)
					{
						$registry[strtolower($key)] = $def;
					}
				}
			}
		}

		return $registry;
	}

	/**
	 * Palier 0 page-builder — rend un bloc PARAMÉTRIQUE : résout la clé dans le registre,
	 * valide ses paramètres (`[block:clé p=v …]`) contre les `fields` déclarés via la lib
	 * locator-free Block_Settings, puis appelle `render($settings)`. Chaîne vide si la clé est
	 * inconnue ou le bloc non rendu. Le `render` reçoit des settings DÉJÀ validés/bornés.
	 *
	 * @param  string $key    clé du bloc (ex. 'news.category')
	 * @param  string $params ce qui suit la clé dans le shortcode (ex. ' id=3 count=5')
	 */
	public function render_block($key, $params = '', $registry = NULL)
	{
		$registry = $registry !== NULL ? $registry : $this->block_registry();
		$key      = strtolower($key);

		if (!isset($registry[$key]) || !is_callable($registry[$key]['render'] ?? NULL))
		{
			return '';
		}

		return (string) call_user_func($registry[$key]['render'], \NF\Modules\Pages\Lib\Block_Settings::parse($registry[$key], (string) $params));
	}

	// ================================================================
	// Palier 1 page-builder — blocs de module ordonnés/configurés par page (nf_pages_instances)
	// ================================================================

	/** Instances d'une page, ordonnées (settings décodés en array). */
	public function get_instances($page_id, $only_enabled = TRUE)
	{
		$this->db	->select('instance_id', 'block', 'settings', 'position', 'enabled')
					->from('nf_pages_instances')
					->where('page_id', (int) $page_id);

		if ($only_enabled)
		{
			$this->db->where('enabled', TRUE);
		}

		$rows = $this->db->order_by('position', 'instance_id')->get(FALSE);

		foreach ($rows as &$row)
		{
			$row['enabled']  = (bool) (int) $row['enabled'];
			$row['settings'] = \NF\NeoFrag\Fields\Json::decode($row['settings']);
		}

		return $rows;
	}

	/**
	 * Remplace les instances d'une page par la liste ordonnée fournie (composer). Chaque item
	 * = ['block' => clé, 'settings' => array]. Les blocs INCONNUS sont ignorés et les settings
	 * validés contre les `fields` déclarés (Block_Settings::from_array) → frontière sûre.
	 */
	public function set_instances($page_id, array $list)
	{
		$page_id  = (int) $page_id;
		$registry = $this->block_registry();

		$this->db->where('page_id', $page_id)->delete('nf_pages_instances');

		$position = 0;

		foreach ($list as $item)
		{
			$block = strtolower((string) ($item['block'] ?? ''));

			if (!isset($registry[$block]))
			{
				continue;
			}

			$this->db->insert('nf_pages_instances', [
				'page_id'  => $page_id,
				'block'    => $block,
				'settings' => \NF\NeoFrag\Fields\Json::encode(\NF\Modules\Pages\Lib\Block_Settings::from_array($registry[$block], (array) ($item['settings'] ?? []))),
				'position' => $position++,
				'enabled'  => (!array_key_exists('enabled', $item) || $item['enabled']) ? '1' : '0'
			]);
		}
	}

	/** HTML des instances actives d'une page (chaîne vide si aucune → la page rend normalement). */
	public function render_instances($page_id)
	{
		$registry = $this->block_registry();
		$html     = '';

		foreach ($this->get_instances($page_id) as $instance)
		{
			$block = $instance['block'];

			if (!isset($registry[$block]) || !is_callable($registry[$block]['render'] ?? NULL))
			{
				continue;
			}

			// Re-validation defense-in-depth : les settings stockés peuvent dater d'un `fields` antérieur.
			$settings = \NF\Modules\Pages\Lib\Block_Settings::from_array($registry[$block], (array) $instance['settings']);

			$html .= (string) call_user_func($registry[$block]['render'], $settings);
		}

		return $html !== '' ? '<div class="nf-page-blocks">'.$html.'</div>' : '';
	}

	/** Registre des blocs au format JS-friendly pour le composer admin : clé → {title, fields}. */
	public function blocks_for_composer()
	{
		$out = [];

		foreach ($this->block_registry() as $key => $def)
		{
			$out[$key] = [
				'title'  => (string) ($def['title'] ?? $key),
				'fields' => array_map(function($spec){
					$spec = (array) $spec;

					return [
						'type'       => $spec['type']       ?? 'string',
						'default'    => $spec['default']    ?? NULL,
						'min'        => $spec['min']        ?? NULL,
						'max'        => $spec['max']        ?? NULL,
						'max_length' => $spec['max_length'] ?? NULL
					];
				}, $def['fields'] ?? [])
			];
		}

		ksort($out);

		return $out;
	}

	/**
	 * Une page publique, par son nom, dans la bonne langue : celle du visiteur si la page y existe,
	 * sinon celle où elle est écrite (`langue_du_contenu()`, qui déclare aussi les `hreflang`).
	 *
	 * La jointure d'origine ne filtrait aucune langue : une page traduite était servie dans la
	 * première version que la base rendait, française ou anglaise, quelle que soit la langue du
	 * visiteur. Invisible tant que chaque page n'avait qu'une version (relevé le 2026-09-23, en
	 * donnant une version anglaise aux pages de la démonstration).
	 */
	public function page_publique(string $name)
	{
		$page_id = $this->db->select('page_id')->from('nf_pages')->where('name', $name)->where('published', TRUE)->row();

		if (!$page_id)
		{
			return NULL;
		}

		$langue = $this->langue_du_contenu('nf_pages_lang', 'page_id', $page_id);

		$this->db	->select('p.page_id', 'pl.title', 'pl.subtitle', 'pl.content', 'p.layout')
					->from('nf_pages p')
					->join('nf_pages_lang pl', 'p.page_id = pl.page_id')
					->where('p.page_id', $page_id);

		if ($langue !== '')
		{
			$this->db->where('pl.lang', $langue);
		}

		return $this->db->row() ?: NULL;
	}

	public function check_page($page_id, $title, $lang = 'default', $all = FALSE)
	{
		if ($lang == 'default')
		{
			$lang = $this->config->lang->info()->name;
		}

		$this->db	->select('p.*', 'pl.title', 'pl.subtitle', 'pl.content')
					->from('nf_pages p')
					->join('nf_pages_lang pl', 'p.page_id = pl.page_id')
					->where('p.page_id', $page_id);

		if (!$all)
		{
			// Publication programmée : une date future masque la page jusqu'à son heure.
			$this->db->where('p.published', TRUE)->where('p.date <=', date('Y-m-d H:i:s'));
		}

		$page = $this->db	->where('pl.lang', $lang)
							->row();

		if ($page && url_title($page['title']) == $title)
		{
			return $page;
		}
		else
		{
			return FALSE;
		}
	}

	public function add_page($name, $title, $published, $subtitle, $content, $layout = 'default', $date = '')
	{
		$page_id = $this->db->insert('nf_pages', array_merge([
			'name'           => $name ?: url_title($title),
			'published'      => $published,
			'layout'         => $layout === 'blank' ? 'blank' : 'default'
		], $date ? ['date' => $date] : []));

		$this->db->insert('nf_pages_lang', [
			'page_id'        => $page_id,
			'lang'           => $this->config->lang->info()->name,
			'title'          => $title,
			'subtitle'       => $subtitle,
			'content'        => $content
		]);

		$this->access->init('pages', 'page', $page_id);
	}

	public function edit_page($page_id, $name, $title, $published, $subtitle, $content, $lang, $layout = 'default', $date = '')
	{
		$layout = $layout === 'blank' ? 'blank' : 'default';

		$page_extra = $date ? ['date' => $date] : [];

		if (!$this->db	->from('nf_pages p')
						->join('nf_pages_lang l', 'p.page_id = l.page_id')
						->where('p.page_id', $page_id)
						->where('l.lang', $lang)
						->empty())
		{
			$this->db	->where('page_id', $page_id)
						->where('lang', $lang)
						->update('nf_pages_lang', [
							'title'    => $title,
							'subtitle' => $subtitle,
							'content'  => $content
						]);

			$this->db	->where('page_id', $page_id)
						->update('nf_pages', array_merge([
							'name'           => $name ?: url_title($title),
							'published'      => $published,
							'layout'         => $layout
						], $page_extra));
		}
		else
		{
			$this->db	->insert('nf_pages_lang', [
							'page_id'  => $page_id,
							'lang'     => $lang,
							'title'    => $title,
							'subtitle' => $subtitle,
							'content'  => $content
						]);

			$this->db	->where('page_id', $page_id)
						->update('nf_pages', array_merge([
							'name'           => $name ?: url_title($title),
							'published'      => $published,
							'layout'         => $layout
						], $page_extra));
		}
	}

	public function delete_page($page_id)
	{
		$this->db	->where('page_id', $page_id)
					->delete('nf_pages');

		$this->db	->where('page_id', (int) $page_id)
					->delete('nf_pages_instances');

		$this->access->delete('pages', $page_id);
	}
}
