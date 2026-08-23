<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Addons;

use NF\NeoFrag\Loadables\Addon;

abstract class Widget extends Addon
{
	static public $core = [
		'breadcrumb' => TRUE,
		'html'       => TRUE,
		'members'    => TRUE,
		'module'     => FALSE,
		'navigation' => TRUE,
		'user'       => TRUE
	];

	static public function __class($name)
	{
		return 'Widgets\\'.$name.'\\'.$name;
	}

	public function is_removable()
	{
		return !in_array($this->name, ['access', 'addons', 'admin', 'comments', 'live_editor', 'members', 'pages', 'search', 'settings', 'user']);
	}

	// Widgets adossés à un module optionnel (homonyme, tables préfixées nf_<nom>). Si le module
	// n'est pas installé, le bloc se masque au lieu de fataliser (ex. module news désinstallé mais
	// widget news resté dans une disposition). Même réflexe que le widget slider, centralisé ici.
	static protected $_module_widgets = ['articles', 'awards', 'calendar', 'donations', 'downloads', 'events', 'forum', 'gallery', 'guestbook', 'links', 'news', 'newsletter', 'partners', 'surveys', 'teams'];

	public function output($type = 'index', $settings = [])
	{
		if (is_array($type))
		{
			$settings = $type;
			$type     = 'index';
		}

		if (in_array($name = $this->info()->name, self::$_module_widgets, TRUE) && !$this->_module_installed($name))
		{
			return;
		}

		if (($controller = $this->controller('index')) && $controller->has_method($type))
		{
			return call_user_func_array([$controller, $type], [$settings]);
		}
	}

	private function _module_installed($name)
	{
		static $tables;

		if ($tables === NULL)
		{
			$tables = $this->db->tables();
		}

		foreach ($tables as $table)
		{
			if ($table === 'nf_'.$name || strpos($table, 'nf_'.$name.'_') === 0)
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	public function get_admin($type, $settings = [])
	{
		if (($controller = @$this->controller('admin')) && $controller->has_method($type))
		{
			if (!is_array($output = call_user_func_array([$controller, $type], [$settings])))
			{
				$output = [$output];
			}

			return $output;
		}

		return [];
	}

	public function get_settings($type, $settings = [])
	{
		if (($controller = @$this->controller('checker')) && $controller->has_method($type))
		{
			return \NF\NeoFrag\Fields\Json::encode(call_user_func_array([$controller, $type], [$settings]));
		}
	}
}
