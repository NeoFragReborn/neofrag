<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Loadables;

use NF\NeoFrag\NeoFrag;

abstract class Addon extends NeoFrag implements \NF\NeoFrag\Loadable
{
	static protected $_objects = [];

	abstract protected function __info();
	//abstract public function paths();

	static public function __load($caller, $args = [])
	{
		$name  = array_shift($args);
		$addon = array_shift($args);

		if (!isset(static::$_objects[$class = get_called_class()][$name]))
		{
			$dir = ($type = strtolower(preg_replace('/.+Addons\\\/', '', $class))).'s';

			if (!$addon)
			{
				$addon = NeoFrag()->model2('addon')->get($type, $name, FALSE);
			}

			if (!$addon || !$addon())
			{
				static::$_objects[$class][$name] = NULL;
			}
			else if (static::$_objects[$class][$name] = $addon = $caller->___load('addons', static::__class($name), [$addon]))
			{
				$addon->__path(function($caller, $type, $file) use ($dir){
					$file = [$file];

					if (!in_array($type, ['addons', 'assets']) && $type)
					{
						array_unshift($file, $type);
					}

					$file = $dir.'/'.$caller->info()->name.'/'.implode('/', $file);

					if (!NEOFRAG_SAFE_MODE)
					{
						yield 'overrides/'.$file;

						if ($caller->output && ($theme = $caller->output->theme()))
						{
							yield 'themes/'.$theme->info()->name.'/overrides/'.$file;
						}
					}

					yield $file;
				});
			}
		}

		return static::$_objects[$class][$name];
	}

	static public function __class($name)
	{
		return 'Addons\\'.$name.'\\'.$name;
	}

	protected $__info     = [];
	protected $__settings = [];

	public function __construct($addon)
	{
		$this->__info     = [
			'name' => $addon->name
		];

		$this->__settings = (object)$addon->data;

		$this->__addon    = $addon;
	}

	public function info()
	{
		static $info = [];

		if (!array_key_exists($id = spl_object_hash($this), $info))
		{
			$info[$id] = (object)array_merge($this->__info(), $this->__info);
		}

		return $info[$id];
	}

	public function settings()
	{
		return $this->__settings;
	}

	public function is_enabled()
	{
		return !$this->is_removable() || !empty($this->settings()->enabled);
	}

	public function is_deactivatable()
	{
		return !isset(static::$core) || !array_key_exists($this->__info['name'], static::$core) || static::$core[$this->__info['name']];
	}

	public function is_removable()
	{
		return !isset(static::$core) || empty(static::$core[$this->__info['name']]);
	}

	public function install()
	{
		// Site de démo : pas de mutation d'addon (l'admin est en lecture seule ; les addons sont
		// figés par l'install initiale, non rejoués au reset). Filet de sécurité côté modèle —
		// couvre aussi reset() et les chemins hors form2 (réinstaller/supprimer un thème).
		if (nf_demo())
		{
			return $this;
		}

		$file = NEOFRAG_CMS.'/'.$this->__addon->type->name.'s/'.$this->__info['name'].'/install/install.sql';

		if (is_file($file) && ($sql = file_get_contents($file)) !== FALSE && trim($sql) !== '')
		{
			if (($error = $this->db->import($sql)) !== TRUE)
			{
				error_log('[addon.install] '.$this->__addon->type->name.'/'.$this->__info['name'].': '.$error);
			}
		}

		return $this;
	}

	public function uninstall($remove = TRUE)
	{
		if (nf_demo())
		{
			return $this;
		}

		if ($remove)
		{
			$file = NEOFRAG_CMS.'/'.$this->__addon->type->name.'s/'.$this->__info['name'].'/install/uninstall.sql';

			if (is_file($file) && ($sql = file_get_contents($file)) !== FALSE && trim($sql) !== '')
			{
				if (($error = $this->db->import($sql)) !== TRUE)
				{
					error_log('[addon.uninstall] '.$this->__addon->type->name.'/'.$this->__info['name'].': '.$error);
				}
			}

			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('addon.uninstalled', [
				'target_type' => $this->__addon->type->name,
				'target_id'   => $this->__info['name'],
			]);

			$this->__addon->delete();
			dir_remove($this->__addon->type->name.'s/'.$this->__info['name']);
		}

		return $this;
	}

	public function reset()
	{
		return $this->uninstall(FALSE)
					->install();
	}
}
