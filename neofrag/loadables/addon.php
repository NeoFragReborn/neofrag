<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Loadables;

use NF\NeoFrag\NeoFrag;

/**
 * @property mixed $__addon La ligne `nf_addon` de cet addon (id, name, type, data…), posée par le
 *                          constructeur. `mixed` et non `Models\Addon` : un Model2 résout ses colonnes
 *                          par méthode magique, et PHPStan verrait trente accès inconnus.
 */
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

	/**
	 * Un addon qu'on ne peut pas eteindre est forcement actif. Sinon, son reglage fait foi.
	 *
	 * Auparavant cette methode s'appuyait sur is_removable(), ce qui produisait une incoherence :
	 * l'administration proposait « Desactiver » pour comments/pages/search alors que is_enabled()
	 * repondait TRUE quoi qu'il arrive ; et a l'inverse un module d'infrastructure non desactivable
	 * pouvait rester eteint sans aucun moyen de le rallumer.
	 */
	public function is_enabled()
	{
		return !$this->is_deactivatable() || !empty($this->settings()->enabled);
	}

	/**
	 * Peut-on l'eteindre depuis l'administration ? Declare par l'addon lui-meme
	 * ('deactivatable' => FALSE sur l'infrastructure sans laquelle le site ne tourne pas).
	 * Defaut : oui.
	 */
	public function is_deactivatable()
	{
		$info = $this->info();

		return !isset($info->deactivatable) || (bool) $info->deactivatable;
	}

	/**
	 * Peut-on le desinstaller ? Non s'il appartient au coeur (livre toujours), non plus s'il n'est
	 * pas diffusable — cas du theme `vitrine`, qui est notre propre site.
	 *
	 * Remplace trois tableaux statiques (Module::$core, Widget::$core, Theme::$core) et une
	 * surcharge (Widget::is_removable()) qui se contredisaient et avaient tous derive : celle des
	 * widgets protegeait sept noms qui ne sont pas des widgets, et celle des themes protegeait un
	 * theme « default » inexistant tout en laissant `nebula` — le seul theme public livre —
	 * supprimable. La source de verite est desormais la declaration de l'addon.
	 */
	public function is_removable()
	{
		$info = $this->info();

		return empty($info->core) && (!isset($info->distributed) || (bool) $info->distributed);
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

		// Migrations par-addon : BASELINE — marque les migrations présentes comme appliquées SANS les
		// exécuter (install.sql porte déjà le schéma à jour). Les deltas postérieurs seront joués par update().
		return $this->migrate('baseline');
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

	/**
	 * Mise à jour d'un addon DÉJÀ installé (après remplacement de ses fichiers par une nouvelle version) :
	 * réimporte install.sql (idempotent — crée d'éventuelles nouvelles tables, préserve l'existant) PUIS
	 * applique les migrations de schéma en attente (ALTER/rename/données). À utiliser à la place de reset()
	 * pour une MAJ : reset() BASELINE les migrations (les marque sans les jouer), update() les EXÉCUTE.
	 */
	public function update()
	{
		if (nf_demo())
		{
			return $this;
		}

		$file = NEOFRAG_CMS.'/'.$this->__addon->type->name.'s/'.$this->__info['name'].'/install/install.sql';

		if (is_file($file) && ($sql = file_get_contents($file)) !== FALSE && trim($sql) !== '')
		{
			if (($error = $this->db->import($sql)) !== TRUE)
			{
				error_log('[addon.update] '.$this->__addon->type->name.'/'.$this->__info['name'].': '.$error);
			}
		}

		return $this->migrate('up');
	}

	/**
	 * Runner de migrations PAR ADDON : <type>s/<name>/install/migrations/*.up.sql, suivies dans
	 * nf_addon_migrations (clé unique type+name+migration). Deux modes :
	 *  - 'baseline' : marque toutes les migrations présentes comme appliquées SANS les exécuter
	 *    (install neuf — install.sql porte déjà le schéma à jour).
	 *  - 'up' : exécute, dans l'ordre (préfixe daté), les migrations pas encore enregistrées (MAJ).
	 * Stoppe à la première erreur (ne marque ni la migration fautive ni les suivantes).
	 */
	public function migrate($mode = 'up')
	{
		if (nf_demo())
		{
			return $this;
		}

		$type = $this->__addon->type->name;
		$name = $this->__info['name'];
		$dir  = NEOFRAG_CMS.'/'.$type.'s/'.$name.'/install/migrations';

		if (!($files = glob($dir.'/*.up.sql')))
		{
			return $this;
		}

		$migrations = array_map(function($file){ return basename($file, '.up.sql'); }, $files);
		sort($migrations);

		$this->_ensure_addon_migrations_table();

		$applied   = [];
		$max_batch = 0;

		foreach ($this->db->select('migration', 'batch')->from('nf_addon_migrations')->where('type', $type)->where('name', $name)->get(FALSE) as $row)
		{
			$applied[$row['migration']] = TRUE;
			$max_batch = max($max_batch, (int)$row['batch']);
		}

		$batch = $max_batch + 1;

		foreach ($migrations as $migration)
		{
			if (isset($applied[$migration]))
			{
				continue;
			}

			if ($mode === 'up')
			{
				$sql = file_get_contents($dir.'/'.$migration.'.up.sql');

				if ($sql !== FALSE && trim($sql) !== '' && ($error = $this->db->import($sql)) !== TRUE)
				{
					error_log('[addon.migrate] '.$type.'/'.$name.' '.$migration.': '.$error);
					break;
				}
			}

			$this->db->insert('nf_addon_migrations', [
				'type'      => $type,
				'name'      => $name,
				'migration' => $migration,
				'batch'     => $mode === 'up' ? $batch : 0,
			]);
		}

		return $this;
	}

	private function _ensure_addon_migrations_table()
	{
		$this->db->query(
			'CREATE TABLE IF NOT EXISTS `nf_addon_migrations` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`type` varchar(32) NOT NULL,
				`name` varchar(100) NOT NULL,
				`migration` varchar(191) NOT NULL,
				`batch` int(10) unsigned NOT NULL DEFAULT 0,
				`applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
				PRIMARY KEY (`id`),
				UNIQUE KEY `uniq_addon_migration` (`type`,`name`,`migration`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);
	}
}
