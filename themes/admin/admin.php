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
			'description' => $this->lang('Panneau d\'administration'),
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			// Infrastructure : le site ne tourne pas sans lui, l'administration ne propose donc
			// pas de l'eteindre. Reprend a l'identique l'ancien Module/Widget/Theme::$core.
			'deactivatable' => FALSE,
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'zones'       => [$this->lang('Contenu'), $this->lang('Avant le contenu'), $this->lang('Après le contenu'), $this->lang('En-tête'), $this->lang('Haut'), $this->lang('Pied de page')]
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
				// APRES la feuille du theme, et jamais avant : elle retablit ce que le theme
				// ecrase sans le vouloir — cadre des boutons « contour », coins des cartes.
				// Voir css/nf-apres-theme.css.
				->css('nf-apres-theme')
				->js('bootstrap.bundle.min')
				->js('modal')
				->js('notify')
				->js('confirm')
				->js('sidebar')
				->js('theme')
				->js('settings');

		$this->data = $this->array;

		// ============ Rubriques ============
		// Une seule liste, nf_rubriques_admin() (helpers/theme.php), que suit aussi le menu « Navigation » de l'éditeur en direct. Un module
		// qu'elle ne nomme pas — une extension de la place de marché — va dans « Autres modules ».
		$rubriques         = nf_rubriques_admin();
		$module_to_section = [];
		$module_items      = array_fill_keys(array_keys($rubriques), []);

		foreach ($rubriques as $cle => $rubrique)
		{
			foreach ($rubrique['modules'] as $nom)
			{
				$module_to_section[$nom] = $cle;
			}
		}

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
			$customize	->set('title',  $this->lang('Apparence'))
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
			'title' => $this->lang('Épinglé'),
			'items' => array_values(array_filter([
				['title' => $this->lang('Tableau de bord'), 'icon' => 'fas fa-th-large', 'url' => 'admin', 'name' => 'dashboard']
			]))
		];

		// Les rubriques, chacune masquée si vide (une installation n'a pas tous les modules), dans
		// l'ordre de nf_rubriques_admin().
		foreach ($rubriques as $key => $meta)
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
			['title' => $this->lang('Paramètres'),        'icon' => 'fas fa-cogs',         'url' => 'admin/settings',      'access' => $this->access->effective_admin(), 'name' => 'settings'],
			['title' => $this->lang('Utilisateurs'),           'icon' => 'fas fa-user',         'url' => 'admin/user',          'access' => $this->access->effective_admin(), 'name' => 'user'],
			['title' => $this->lang('Permissions (matrice)'), 'icon' => 'fas fa-th',          'url' => 'admin/access/matrix',       'access' => $this->access->effective_admin(), 'name' => 'access-matrix'],
			['title' => $this->lang('Rôles'),                 'icon' => 'fas fa-user-shield', 'url' => 'admin/access/roles',        'access' => $this->access->effective_admin(), 'name' => 'access-roles'],
			['title' => $this->lang('Assigner aux users'),    'icon' => 'fas fa-users-cog',   'url' => 'admin/access/users-roles',  'access' => $this->access->effective_admin(), 'name' => 'access-users-roles'],
			['title' => $this->lang('Assigner aux groupes'),  'icon' => 'fas fa-layer-group', 'url' => 'admin/access/groups-roles', 'access' => $this->access->effective_admin(), 'name' => 'access-groups-roles'],
			$customize->__toArray() ?: NULL,
			['title' => $this->lang('Thèmes & addons'), 'icon' => 'fas fa-puzzle-piece', 'url' => 'admin/addons',        'access' => $this->access->effective_admin(), 'name' => 'addons'],
			['title' => $this->lang('Éditeur en direct'),     'icon' => 'fas fa-desktop',      'url' => 'admin/live-editor',   'access' => $this->access->effective_admin(), 'name' => 'live-editor'],
			['title' => $this->lang('Statistiques'),      'icon' => 'far fa-chart-bar',    'url' => 'admin/statistics',    'access' => $this->access->effective_admin(), 'name' => 'statistics'],
			['title' => (string)$this->lang('Corbeille'), 'icon' => 'fas fa-trash-restore', 'url' => 'admin/trash',       'access' => $this->access->effective_admin(), 'name' => 'trash'],
			['title' => $this->lang('Monitoring'), 'icon' => 'fas fa-heartbeat', 'url' => 'admin/monitoring', 'access' => $this->access->effective_admin(), 'name' => 'monitoring']
		]));
		// Add 'name' fallback for customize
		foreach ($systeme_items as &$it) { if (empty($it['name']) && !empty($it['url'])) $it['name'] = preg_replace('#^admin/#', '', is_string($it['url']) ? $it['url'] : ''); }
		unset($it);

		$sections[] = [
			'id'    => 'systeme',
			'icon'  => 'fas fa-cogs',
			'title' => $this->lang('Système'),
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

	/**
	 * Version publiee, si elle est plus recente que celle installee. NULL sinon.
	 *
	 * Le cache peut contenir n'importe quoi : un manifeste d'une version anterieure du format, ou
	 * le corps qu'un routeur a rendu a la place du fichier. On verifie donc la forme avant de lire
	 * — sans quoi ->neofrag sur un objet absent fait tomber TOUTE l'administration, pas seulement
	 * le bouton de mise a jour.
	 */
	public function update()
	{
		if (!file_exists($file = 'cache/monitoring/version.json'))
		{
			return NULL;
		}

		$manifeste = json_decode((string)@file_get_contents($file));

		if (!is_object($manifeste) || !isset($manifeste->neofrag->version) || !is_string($manifeste->neofrag->version))
		{
			return NULL;
		}

		$version = $manifeste->neofrag;

		return version_compare(version_format($version->version), version_format(NEOFRAG_VERSION), '>') ? $version : NULL;
	}
}
