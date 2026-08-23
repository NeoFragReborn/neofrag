<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Themes\Admin;

use NF\NeoFrag\Addons\Theme;

class Admin extends Theme
{
	public $data;

	protected function __info()
	{
		return [
			'title'       => 'Administration',
			'description' => $this->lang('Administration panel'),
			'language'    => 'en',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'zones'       => [$this->lang('Content'), $this->lang('pre_content'), $this->lang('post_content'), $this->lang('header'), $this->lang('Top'), $this->lang('footer')]
		];
	}

	public function __init()
	{
		if ($this->config->nf_update_callback)
		{
			$this->config('nf_update_callback', '');

			if ($patch = @NeoFrag()->install($this->config->nf_update_callback))
			{
				if (method_exists($patch, 'post'))
				{
					$patch->post();
				}

				unlink('neofrag/install/'.$this->config->nf_update_callback.'.php');
			}

			refresh();
		}

		$this	->css('bootstrap.min')->css('nf-bs5-bridge')
				->css('fonts/open-sans')
				->css('fonts/titillium-web')
				->css('icons/Pe-icon-7-stroke')
				->css('icons/fontawesome.min')
				->css('style')
				->js('bootstrap.bundle.min')
				->js('modal')
				->js('notify')
				->js('confirm')
				->js('sidebar')
				->js('theme')
				->js('settings');

		$this->data = $this->array;

		// ============ Mapping module → section ============
		// Chaque module administrable est rangé dans une catégorie claire ; plus de
		// fourre-tout « Autres modules » (les non-mappés restent en 'autres', rare).
		$module_to_section = [
			// Contenu (publication)
			'pages'       => 'contenu',
			'articles'    => 'contenu',
			'news'        => 'contenu',
			'newsletter'  => 'contenu',
			'feeds'        => 'contenu',
			// Communauté & échanges
			'forum'       => 'communaute',
			'comments'    => 'communaute',
			'talks'        => 'communaute',
			'guestbook'   => 'communaute',
			'surveys'     => 'communaute',
			'bugtracker'  => 'communaute',
			'contact'     => 'communaute',
			'moderation'   => 'communaute',
			// Base de connaissances
			'wiki'        => 'connaissance',
			'faq'         => 'connaissance',
			'downloads'   => 'connaissance',
			'links'       => 'connaissance',
			// Médias
			'media'       => 'medias',
			'gallery'     => 'medias',
			'calendar'    => 'medias',
			// Gaming / eSport
			'events'      => 'gaming',
			'games'       => 'gaming',
			'recruits'    => 'gaming',
			'teams'       => 'gaming',
			'partners'    => 'gaming',
			'awards'      => 'gaming',
			// Monétisation & engagement
			'shop'         => 'monetisation',
			'donations'    => 'monetisation',
			'payments'     => 'monetisation',
			'ads'          => 'monetisation',
			'gamification' => 'monetisation',
			'classifieds'  => 'monetisation',
		];

		$module_items = [
			'contenu'      => [],
			'communaute'   => [],
			'connaissance' => [],
			'medias'       => [],
			'gaming'       => [],
			'monetisation' => [],
			'autres'       => []
		];

		// Modules à exclure du listing (déjà rendus dans des sections spéciales : Système, Monitoring)
		$excluded_modules = ['monitoring', 'admin', 'addons', 'settings', 'user', 'access', 'live_editor', 'statistics', 'search', 'tools', 'members', 'trash'];

		foreach (NeoFrag()->model2('addon')->get('module') as $module)
		{
			if ($module->is_enabled() && $module->is_administrable($category) && $category != 'none' && $module->is_authorized())
			{
				$mname = $module->info()->name;
				if (in_array($mname, $excluded_modules, TRUE)) continue;
				$section = isset($module_to_section[$mname]) ? $module_to_section[$mname] : 'autres';
				$module_items[$section][] = [
					'title' => (string)$module->info()->title,
					'icon'  => $module->info()->icon,
					'url'   => 'admin/'.$mname,
					'name'  => $mname
				];
			}
		}

		array_walk($module_items, function(&$a){
			array_natsort($a, function($a){ return $a['title']; });
		});

		// ============ Apparence (theme custom) ============
		$customize = $this->array();
		$theme     = NeoFrag()->model2('addon')->get('theme', $this->config->nf_default_theme, FALSE);

		if (@$theme->addon()->controller('admin'))
		{
			$customize	->set('title',  $this->lang('Appearance'))
						->set('icon',   'fas fa-paint-brush')
						->set('access', $this->access->effective_admin())
						->set('url',   'admin/addons/customize/'.$theme->url());
		}

		// ============ Sidebar sections ============
		$sections = [];

		// Épinglés
		$sections[] = [
			'id'    => 'pinned',
			'icon'  => 'fas fa-th-large',
			'title' => $this->lang('Pinned'),
			'items' => array_values(array_filter([
				['title' => $this->lang('Dashboard'), 'icon' => 'fas fa-th-large', 'url' => 'admin', 'name' => 'dashboard']
			]))
		];

		// Catégories de contenu (chacune masquée si vide — utile après le découplage
		// marketplace). Ordre = ordre d'affichage dans la sidebar.
		$labels = [
			'contenu'      => ['title' => $this->lang('Content'),       'icon' => 'fas fa-bullhorn'],
			'communaute'   => ['title' => $this->lang('Community'),     'icon' => 'fas fa-users'],
			'connaissance' => ['title' => $this->lang('Knowledge'),     'icon' => 'fas fa-book'],
			'medias'       => ['title' => $this->lang('Media'),         'icon' => 'fas fa-photo-video'],
			'gaming'       => ['title' => $this->lang('Gaming'),        'icon' => 'fas fa-gamepad'],
			'monetisation' => ['title' => $this->lang('Monetization'),  'icon' => 'fas fa-coins'],
			'autres'       => ['title' => $this->lang('Other modules'), 'icon' => 'fas fa-ellipsis-h'],
		];
		foreach ($labels as $key => $meta)
		{
			if (!empty($module_items[$key]))
			{
				$sections[] = [
					'id'    => $key,
					'icon'  => $meta['icon'],
					'title' => $meta['title'],
					'items' => $module_items[$key]
				];
			}
		}

		// Système (admin technique)
		$systeme_items = array_values(array_filter([
			['title' => $this->lang('Settings'),        'icon' => 'fas fa-cogs',         'url' => 'admin/settings',      'access' => $this->access->effective_admin(), 'name' => 'settings'],
			['title' => $this->lang('Users'),           'icon' => 'fas fa-user',         'url' => 'admin/user',          'access' => $this->access->effective_admin(), 'name' => 'user'],
			['title' => $this->lang('Permissions (matrice)'), 'icon' => 'fas fa-th',          'url' => 'admin/access/matrix',       'access' => $this->access->effective_admin(), 'name' => 'access-matrix'],
			['title' => $this->lang('Rôles'),                 'icon' => 'fas fa-user-shield', 'url' => 'admin/access/roles',        'access' => $this->access->effective_admin(), 'name' => 'access-roles'],
			['title' => $this->lang('Assigner aux users'),    'icon' => 'fas fa-users-cog',   'url' => 'admin/access/users-roles',  'access' => $this->access->effective_admin(), 'name' => 'access-users-roles'],
			['title' => $this->lang('Assigner aux groupes'),  'icon' => 'fas fa-layer-group', 'url' => 'admin/access/groups-roles', 'access' => $this->access->effective_admin(), 'name' => 'access-groups-roles'],
			$customize->__toArray() ?: NULL,
			['title' => $this->lang('Themes & addons'), 'icon' => 'fas fa-puzzle-piece', 'url' => 'admin/addons',        'access' => $this->access->effective_admin(), 'name' => 'addons'],
			['title' => $this->lang('Live Editor'),     'icon' => 'fas fa-desktop',      'url' => 'admin/live-editor',   'access' => $this->access->effective_admin(), 'name' => 'live-editor'],
			['title' => $this->lang('Statistics'),      'icon' => 'far fa-chart-bar',    'url' => 'admin/statistics',    'access' => $this->access->effective_admin(), 'name' => 'statistics'],
			['title' => (string)$this->lang('Corbeille'), 'icon' => 'fas fa-trash-restore', 'url' => 'admin/trash',       'access' => $this->access->effective_admin(), 'name' => 'trash'],
			['title' => $this->lang('Monitoring'), 'icon' => 'fas fa-heartbeat', 'url' => 'admin/monitoring', 'access' => $this->access->effective_admin(), 'name' => 'monitoring']
		]));
		// Add 'name' fallback for customize
		foreach ($systeme_items as &$it) { if (empty($it['name']) && !empty($it['url'])) $it['name'] = preg_replace('#^admin/#', '', is_string($it['url']) ? $it['url'] : ''); }
		unset($it);

		$sections[] = [
			'id'    => 'systeme',
			'icon'  => 'fas fa-cogs',
			'title' => $this->lang('System'),
			'items' => $systeme_items
		];

		$this->data->set('sidebar', [
			'sections' => $sections
		]);
	}

	public function styles_row()
	{
		//Nothing to do
	}

	public function styles_widget()
	{
		//Nothing to do
	}

	public function update()
	{
		if (file_exists($file = 'cache/monitoring/version.json'))
		{
			$version = json_decode(file_get_contents($file))->neofrag;

			if (version_compare(version_format($version->version), version_format(NEOFRAG_VERSION), '>'))
			{
				return $version;
			}
		}
	}
}
