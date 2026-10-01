<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Core;

use NF\NeoFrag\Core;

class Db extends Core
{
	static protected $_drivers  = [];
	static protected $_requests = [];
	static protected $_config = [];

	protected $_request = [];

	public function __construct($config)
	{
		foreach ($config as $c)
		{
			if (!isset($c['hostname'], $c['username'], $c['password'], $c['database']))
			{
				continue;
			}

			if (!isset($c['type']))
			{
				$c['type'] = 'default';
			}

			if (isset(self::$_drivers[$c['type']]))
			{
				continue;
			}

			if (!isset($c['driver']))
			{
				$c['driver'] = 'mysqli';
			}

			static::$_config[$c['type']][] = $c;
		}

		$this->debug->bar('database', function(&$label){
			$total_time   = 0;
			$total_errors = 0;

			$result = '<table class="table table-striped">';

			foreach (self::$_requests as $i => $request)
			{
				$result .= '	<tr>
									<td><b>'.($i + 1).'</b><div class="float-end"><span class="badge '.badge_class(!empty($request->error) ? 'danger' : 'success').'">'.round($request->time * 1000, 3).' ms</span></div></td>
									<td>'.$request->debug().'</td>
									<td class="text-end">'.(isset($request->file) ? $request->file.' <code>'.$request->line : '').'</code></td>
								</tr>';

				$total_time   += $request->time;
				$total_errors += (int)!empty($request->error);
			}

			if (!empty(self::$_requests))
			{
				$result .= '	<tr>
									<td><b>Total</b><div class="float-end"><span class="badge text-bg-success">'.round($total_time * 1000, 3).' ms</span></div></td>
									<td colspan="2"></td>
								</tr>';
			}

			$result .= '</table>';

			$label = '<span class="badge '.badge_class($total_errors > 0 ? 'danger' : 'success').'">'.($total_errors ?: $i + 1).'</span>';

			return $result;
		});
	}

	public function __invoke($type = NULL)
	{
		$db = clone $this;

		if ($type)
		{
			$db->_request['type'] = $type;
		}

		return $db;
	}

	public function __call($name, $args)
	{
		//TODO 5.6 compatibility
		if ($name == 'array')
		{
			return parent::__call('array', $this->get(...$args));
		}
		else if ($name == 'empty')
		{
			return !$this->select('1')->row();
		}

		return parent::__call($name, $args);
	}

	public function __debugInfo()
	{
		return static::$_requests;
	}

	public function get_info($var = NULL)
	{
		static $info;

		if ($info === NULL)
		{
			$info = array_merge($this->_driver('get_info'), [
				'driver' => preg_replace('/.+\\\(.+?)$/', '\1', strtolower(get_class($this->driver())))
			]);
		}

		if ($var !== NULL && isset($info[$var]))
		{
			return $info[$var];
		}

		return $info;
	}

	public function get_size()
	{
		static $size;

		if ($size === NULL)
		{
			$size = $this->_driver('get_size');
		}

		return $size;
	}

	public function escape_string($string)
	{
		return $this->_driver('escape_string', $string);
	}

	public function select()
	{
		if ($args = func_get_args())
		{
			$this->_request['select'] = $args;
			return $this;
		}
		else if (isset($this->_request['select']))
		{
			return $this->_request['select'];
		}
	}

	public function from($from)
	{
		$this->_request['from'] = $from;
		return $this;
	}

	public function where($name, $value = NULL, $operator = 'AND')
	{
		if (func_num_args() > 3 && in_array(func_num_args() % 3, [0, 2]))
		{
			$args = [];

			foreach (func_get_args() as $i => $arg)
			{
				if ($i % 3 == 0)
				{
					$args[] = [$arg];
				}
				else
				{
					$args[array_last_key($args)][] = $arg;
				}
			}

			$this->_request['where'][] = array_map(function($a){
				return (object)[
					'name'     => $a[0],
					'value'    => $a[1],
					'operator' => !empty($a[2]) ? $a[2] : 'AND'
				];
			}, $args);
		}
		else
		{
			$where = (object)[
				'name'     => $name,
				'value'    => $value,
				'operator' => $operator
			];

			if (func_num_args() == 1)
			{
				unset($where->value);
			}

			$this->_request['where'][] = $where;
		}

		return $this;
	}

	public function join($table, $on, $type = '')
	{
		$join = '';

		if ($on == 'NATURAL')
		{
			$join .= 'NATURAL ';
		}
		else if (!$type)
		{
			$type = 'LEFT';
		}

		$join .= $type.' JOIN '.$table;

		if ($on != 'NATURAL')
		{
			$join .= ' ON '.$on;
		}

		$this->_request['join'][] = $join;

		return $this;
	}

	/*
	 * Joint la MEILLEURE traduction d'un objet : celle de la langue demandée, sinon le français,
	 * sinon la première qui existe. Toutes les colonnes viennent ainsi d'une même ligne.
	 *
	 * L'écriture habituelle — `join('nf_teams_lang tl', …)->where('tl.lang', $langue)` — rend la
	 * jointure interne : une équipe, un jeu ou une catégorie saisis dans une seule langue
	 * DISPARAISSAIENT des cinq autres, et un groupe d'équipe avec ses droits (relevé le 2026-10-01 :
	 * trois équipes sur la démo en français, aucune en anglais). L'administration n'enregistre le
	 * titre que dans la langue où l'on écrit : ce cas est le cas normal, pas une exception.
	 *
	 *   ->join_lang('nf_teams_lang tl', 'team_id', 't.team_id')
	 *
	 * Le texte d'un CONTENU (actualité, article, page) ne passe pas par ici : une liste de contenus
	 * est dans la langue demandée, et un contenu monolingue est servi seul, dans sa langue.
	 */
	public function join_lang(string $table, string $cle, string $parent, ?string $langue = NULL, string $type = '')
	{
		[$nom, $alias] = array_pad(preg_split('/\s+/', trim($table)), 2, NULL);
		$alias = $alias ?: $nom;
		$autre = $alias.'_choix';

		if ($langue === NULL)
		{
			$courante = NeoFrag()->config->lang;
			$langue   = is_object($courante) ? (string) $courante->info()->name : '';
		}

		$ordre = [];

		foreach (array_unique([$langue, 'fr']) as $code)
		{
			if (preg_match('/^[a-z]{2}$/', $code))
			{
				$ordre[] = $autre.'.lang = "'.$code.'" DESC';
			}
		}

		$ordre[] = $autre.'.lang';

		return $this->join($table, $alias.'.'.$cle.' = '.$parent.' AND '.$alias.'.lang = (SELECT '.$autre.'.lang FROM '.$nom.' '.$autre.' WHERE '.$autre.'.'.$cle.' = '.$parent.' ORDER BY '.implode(', ', $ordre).' LIMIT 1)', $type);
	}

	public function group_by()
	{
		$this->_request['group_by'] = func_get_args();
		return $this;
	}

	public function having()
	{
		$this->_request['having'] = func_get_args();
		return $this;
	}

	public function order_by()
	{
		$this->_request['order_by'] = func_get_args();
		return $this;
	}

	public function limit()
	{
		$this->_request['limit'] = implode(', ', func_get_args());
		return $this;
	}

	public function ignore_foreign_keys()
	{
		$this->_request['ignore_foreign_keys'] = TRUE;
		return $this;
	}

	public function insert($table, $data)
	{
		$this->_request['insert'] = $table;
		$this->_request['values'] = $data;

		return $this->_exec('last_id');
	}

	public function replace($table, $data)
	{
		$this->_request['replace'] = $table;
		$this->_request['values']  = $data;

		return $this->_exec('last_id');
	}

	public function update($table, $data)
	{
		$this->_request['update'] = $table;
		$this->_request['set']    = $data;

		return $this->_exec('affected_rows');
	}

	public function delete($table, $multi_tables = '')
	{
		$this->_request['delete'] = $table;

		if ($multi_tables)
		{
			$this->_request['multi_tables'] = $multi_tables;
		}

		return $this->_exec('affected_rows');
	}

	public function get($cast = TRUE)
	{
		$get = $this->_exec('get');

		if ($cast && !empty($get) && count($get[0]) == 1)
		{
			foreach ($get as &$row)
			{
				$row = current($row);
			}
		}

		return $get;
	}

	public function index()
	{
		$list = [];
		$n = 0;

		foreach ($this->get(FALSE) as $row)
		{
			$id = array_shift($row);

			if (!$n)
			{
				$n = count($row);
			}

			$list[$id] = $n == 1 ? array_shift($row) : array_values($row);
		}

		return $list;
	}

	public function count()
	{
		return $this->select('COUNT(*)')->order_by()->row();
	}

	public function row($cast = TRUE)
	{
		$row = $this->limit(1)->_exec('row');

		if ($cast && count($row) == 1)
		{
			return current($row);
		}

		return $row;
	}

	public function results()
	{
		return $this->_exec('results');
	}

	public function fetch($results)
	{
		return $this->_driver('fetch', $results);
	}

	public function free($results)
	{
		return $this->_driver('free', $results);
	}

	public function lock($tables)
	{
		$this->_driver('lock', $tables);
		return $this;
	}

	public function unlock($tables)
	{
		$this->_driver('unlock', $tables);
		return $this;
	}

	// Via l'API du driver, jamais via le pipeline prepared-statement :
	// MySQL 8 refuse PREPARE 'START TRANSACTION' (erreur 1295).
	// Un BEGIN, un COMMIT ou un ROLLBACK que le pilote refuse ne doit jamais passer en silence : la suite
	// s'exécuterait hors transaction en croyant être protégée. Repéré en comparant avec HiddenCMS
	// (2026-09-17), dont les variantes lèvent ; rare avec MariaDB, mais un silence ici est un mensonge.
	public function transaction()
	{
		if ($this->_driver('transaction') === FALSE)
		{
			throw new \RuntimeException('Impossible d\'ouvrir la transaction SQL.');
		}

		return $this;
	}

	public function commit()
	{
		if ($this->_driver('commit') === FALSE)
		{
			throw new \RuntimeException('Impossible de valider la transaction SQL.');
		}

		return $this;
	}

	public function rollback()
	{
		if ($this->_driver('rollback') === FALSE)
		{
			throw new \RuntimeException('Impossible d\'annuler la transaction SQL.');
		}

		return $this;
	}

	public function tables()
	{
		return $this->_driver('tables');
	}

	// Vrai si la table existe. Permet au code cœur de tolérer l'absence de la table d'un module
	// optionnel non installé (modèle « tout bundlé, activé à la carte ») au lieu de fataliser sur
	// « table doesn't exist ». Résultat mis en cache pour la requête courante.
	public function table_exists($table)
	{
		static $tables = NULL;

		if ($tables === NULL)
		{
			$tables = array_flip($this->tables());
		}

		return isset($tables[$table]);
	}

	public function table_create($table)
	{
		return $this->_driver('table_create', $table);
	}

	public function table_columns($table)
	{
		return $this->_driver('table_columns', $table);
	}

	public function execute($query)
	{
		$this->query($query);
		$this->_exec();
		return $this;
	}

	// Exécute un SQL multi-statements (DDL d'install/désinstall d'addon) hors du
	// pipeline prepared-statements (le DDL ne se prépare pas). Retour : TRUE ou message d'erreur.
	public function import($sql)
	{
		return $this->_driver('import', $sql);
	}

	public function query($query)
	{
		$this->_request['query'] = $query;
		return $this;
	}

	public function driver()
	{
		return self::$_drivers[$this->_driver()];
	}

	protected function _driver()
	{
		if (!($args = func_get_args()))
		{
			$type = isset($this->_request['type']) ? $this->_request['type'] : 'default';

			if (!isset(self::$_drivers[$type]) && isset(self::$_config[$type]))
			{
				array_walk(self::$_config[$type], $connect = function($config) use (&$connect){
					if ($driver = NeoFrag()->___load('drivers', $config['driver'], [$config['hostname'], $config['username'], $config['password'], $config['database'], $config['port'] ?? 3306]))
					{
						if ($connection = $driver->connect())
						{
							if (is_string($connection))
							{
								$config['driver'] = $connection;
								$connect($config);
								return;
							}
							else
							{
								if (isset($config['init']) && is_a($config['init'], 'closure'))
								{
									call_user_func($config['init'], $connection);
								}

								self::$_drivers[$config['type']] = $driver;

								if (NEOFRAG_DEBUG_BAR || NEOFRAG_LOGS)
								{
									$this->debug('DB', 'Connection established '.$config['type'].' / '.$config['hostname'].' / '.$config['database'].' ('.$config['driver'].')');
								}
							}
						}
					}
				});

				if (!isset(self::$_drivers[$type]))
				{
					header('HTTP/1.0 503 Service Unavailable');
					exit('Database error check config/db.php');
				}

				unset(self::$_config[$type]);
			}

			return $type;
		}

		return call_user_func_array([$this->driver(), array_shift($args)], $args);
	}

	/**
	 * Exécute une requête SANS abîmer celle qu'on est peut-être en train de construire.
	 *
	 * Ce constructeur est PARTAGÉ et il accumule : `select()`, `from()`, `where()` empilent dans le
	 * même panier, que `get()` vide en l'exécutant. Une méthode qui interroge la base au milieu
	 * d'une chaîne détruit donc silencieusement la chaîne de son appelant — la requête part avec les
	 * morceaux des deux, et le résultat est vide. Mesuré le 2026-09-21 : une page d'équipe et une
	 * page d'article rendaient 404 dans leur propre langue, pour cette seule raison.
	 *
	 * On met le panier de côté, on laisse la requête se faire dans un panier neuf, et on rend
	 * l'ancien — quoi qu'il arrive, `finally` compris.
	 *
	 *   $langues = $this->db->standalone(function($db) use ($table, $id){
	 *       return $db->select('lang')->from($table)->where('id', $id)->get();
	 *   });
	 */
	public function standalone(callable $query)
	{
		$pending        = $this->_request;
		$this->_request = [];

		try
		{
			return $query($this);
		}
		finally
		{
			$this->_request = $pending;
		}
	}

	protected function _exec($callback = NULL)
	{
		$request = $this->_driver('query', $this->_request);

		$this->_request = [];

		if (NEOFRAG_DEBUG_BAR || NEOFRAG_LOGS)
		{
			self::$_requests[] = $request;
		}

		if (empty($request->error) && $callback)
		{
			return $request->$callback();
		}
	}
}
