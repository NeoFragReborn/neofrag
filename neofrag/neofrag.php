<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag;

/**
 * Cœur + service-locator. Les services sont résolus dynamiquement via __get() ; ces annotations les
 * rendent visibles de l'IDE et de PHPStan sans changer le runtime (cf. les notes du mainteneur, chunk 1).
 *
 * On ne type ici QUE les services dont la classe expose une vraie API déclarée. Volontairement
 * absents : $user (\NF\NeoFrag\Models\User, Model2) et $lang (\NF\NeoFrag\Libraries\Lang), qui
 * exposent leurs membres par magie (__get sur colonnes SQL, info()…) : les typer ferait cascader
 * ~330 faux positifs « property/method not found ». Les laisser en `mixed` est ici le bon choix.
 *
 * @property \NF\NeoFrag\Core\Db            $db
 * @property \NF\NeoFrag\Core\Config        $config
 * @property \NF\NeoFrag\Core\Session       $session
 * @property \NF\NeoFrag\Core\Url           $url
 * @property \NF\NeoFrag\Core\Output        $output
 * @property \NF\NeoFrag\Core\Access        $access
 * @property \NF\NeoFrag\Core\Groups        $groups
 * @property \NF\NeoFrag\Core\Events        $events
 * @property \NF\NeoFrag\Core\Input         $input
 * @property \NF\NeoFrag\Core\Debug         $debug
 * @property \NF\NeoFrag\Libraries\Crypt      $crypt
 * @property \NF\NeoFrag\Libraries\Moderation $moderation
 * @property \NF\NeoFrag\Libraries\Password   $password
 * @property \NF\NeoFrag\Libraries\Network    $network
 * @property \NF\NeoFrag\Libraries\Rate_Limit $rate_limit
 * @property \NF\NeoFrag\Libraries\Captcha    $captcha
 * @property \NF\NeoFrag\Libraries\Anti_Flood $anti_flood
 *
 * @method string                           lang(string $key, mixed ...$args)
 * @method void                             debug(mixed ...$args)
 * @method \NF\NeoFrag\Loadables\Model      model(string $name, mixed ...$args)
 * @method \NF\NeoFrag\Loadables\Model2     model2(string $name, mixed ...$args)
 * @method \NF\NeoFrag\Addons\Module        module(string $name)
 * @method \NF\NeoFrag\Libraries\Collection collection(?string $name = null, mixed ...$args)
 * @method \NF\NeoFrag\Libraries\Form       form(mixed ...$args)
 * @method \NF\NeoFrag\Libraries\Panel      panel(mixed ...$args)
 * @method \NF\NeoFrag\Libraries\Date       date(mixed ...$args)
 * @method \NF\NeoFrag\Libraries\Table      table(mixed ...$args)
 * @method \NF\NeoFrag\Libraries\Breadcrumb breadcrumb(mixed ...$args)
 * @method \NF\NeoFrag\Libraries\Pagination pagination(mixed ...$args)
 * @method \NF\NeoFrag\Libraries\Email      email(mixed ...$args)
 * @method \NF\NeoFrag\Displayables\Col     col(mixed ...$args)
 * @method \NF\NeoFrag\Displayables\Row     row(mixed ...$args)
 * @method \NF\NeoFrag\Displayables\Widget  widget(mixed ...$args)
 * @method \NF\NeoFrag\Libraries\View       view(string $view = '', array $data = [])
 * @method \NF\NeoFrag\Libraries\Form       form2(mixed ...$args)
 * @method bool                             access(string $module, string $action, int $id = 0, ?int $group_id = null, ?int $user_id = null)
 * @method \NF\NeoFrag\Libraries\Button     button(mixed ...$args)
 * @method \NF\NeoFrag\Libraries\Html       html(mixed ...$args)
 * @method \NF\NeoFrag\Libraries\No_Translate no_translate(mixed $value)
 * @method \NF\NeoFrag\Libraries\Error      error(mixed ...$args)
 * @property \NF\NeoFrag\Libraries\Error    $error
 *
 * Les méthodes ci-dessous passent par __call vers une bibliothèque ou le cœur : l'analyse statique ne
 * pouvait pas les voir, et la liste d'exceptions en portait des centaines d'occurrences (2026-09-23).
 * Leur type de retour dépend de l'appelant : `mixed`, ce qui est la vérité.
 *
 * @method mixed                            css(mixed ...$args)
 * @method mixed                            js(mixed ...$args)
 * @method mixed                            user(mixed ...$args)
 * @property mixed                          $user
 */
#[\AllowDynamicProperties]
class NeoFrag
{
	//TODO
	static public $route_patterns = [
		'id'         => '([0-9]+?)',
		'key_id'     => '([a-z0-9]+?)',
		'url_title'  => '([a-z0-9-]+?)',
		'url_title*' => '([a-z0-9-/]+?)',
		'page'       => '((?:/?page/[0-9]+?)?)',
		'pages'      => '((?:/?(?:all|page/[0-9]+?(?:/(?:10|25|50|100))?))?)'
	];

	public function __path($type, $file = '', &$dir = [], $callback = 'check_file')
	{
		static $paths = [];

		if (is_a($type, 'closure'))
		{
			$paths[get_called_class()] = $type;
			return $this;
		}

		foreach ($paths[get_called_class()]($this, $type, $file) as $path)
		{
			if ($callback($path))
			{
				if ((NEOFRAG_DEBUG_BAR || NEOFRAG_LOGS) && isset($this->debug) && !is_a($path, 'NF\NeoFrag\Libraries\Date'))
				{
					$this->debug(strtoupper($type), get_class($this), is_object($path) ? get_class($path) : $path);
				}

				return $path;
			}

			$dir[] = $path;
		}

		if ($this != ($NeoFrag = NeoFrag()))
		{
			return $NeoFrag->__path($type, $file, $dir, $callback);
		}
	}

	public function ___load($type, $name, $args = [])
	{
		$paths = [];

		$callback = function(&$path) use ($args){
			if (class_exists($path = class_name('NF\\'.str_replace('/', '\\', $path))))
			{
				$path = NeoFrag($path, $args);
				return TRUE;
			}
		};

		if ($object = $this->__path($type, $name, $paths, $callback))
		{
			return $object;
		}

		trigger_error('Unfound '.$type.': '.$name.' in paths ['.implode(';', $paths).']', E_USER_WARNING);
	}

	public function __isset($name)
	{
		return isset(NeoFrag()->$name);
	}

	public function __get($name)
	{
		if (isset(NeoFrag()->$name))
		{
			return NeoFrag()->$name;
		}
		else
		{
			if (!defined('NEOFRAG_CORE') && substr($name, 0, 5) == 'core_')
			{
				$type = 'core';
				$name = substr($name, 5);
				$args = [];
			}
			else
			{
				$type = 'libraries';
				$args = [property_exists($this, '__caller') ? $this->__caller : (is_a($this, 'NF\NeoFrag\Loadables\Addon') ? $this : NeoFrag())];
			}

			$$name = NULL;

			if (file_exists('config/'.$name.'.php') && (@include 'config/'.$name.'.php') && $$name)
			{
				$args[] = $$name;
			}

			if (!is_null($name) && !($object = @NeoFrag()->___load($type, $name, $args)))
			{
				$class = explode('_', $name, 2);

				if (array_key_exists(1, $class))
				{
					$class[0] .= 's';
				}

				$object = @NeoFrag()->___load($type, implode('\\', $class), $args);
			}

			if ($type == 'core')
			{
				$this->$name = $object;
			}

			if(isset($object))
			 	return $object;
			 
			return false;
			
		}

		trigger_error('Undefined property: '.get_class($this).'::$'.$name, E_USER_WARNING);
	}

	public function __call($name, $args)
	{
		if ($name == 'clone')
		{
			return clone $this;
		}

		$callback = NULL;

		if (preg_match('/^(?:static_)?(.+?)_if$/', $name, $match))
		{
			if (!$contition = $args[0])
			{
				return method_exists($this, '__extends') ? $this->__extends() : $this;
			}

			$args = array_slice($args, 1);

			if (($name = $match[1]) != 'exec')
			{
				array_walk($args, function(&$a) use ($contition){
					if (is_a($a, 'closure'))
					{
						$a = $a($contition, $this);
					}
				});
			}

			$callback = [$this, $name];
		}

		if (preg_match('/^static_(.+)/', $name, $match))
		{
			return forward_static_call_array($callback ?: [$this, $match[1]], $args);
		}
		else if (is_a($class = 'NF\NeoFrag\Displayables\\'.$name, 'NF\NeoFrag\Displayable', TRUE))
		{
			$callback = NeoFrag($class);
		}
		else if (is_a($class = 'NF\NeoFrag\Addons\\'.$name, 'NF\NeoFrag\Loadable', TRUE))
		{
			return forward_static_call_array($class.'::__load', [NeoFrag(), $args]);
		}
		else if (is_a($class = 'NF\NeoFrag\Loadables\\'.$name, 'NF\NeoFrag\Loadable', TRUE))
		{
			return forward_static_call_array($class.'::__load', [property_exists($this, '__caller') ? $this->__caller : (is_a($this, 'NF\NeoFrag\Loadables\Addon') ? $this : NeoFrag()), $args]);
		}
		else if (!$callback && is_callable($library = $this->$name))
		{
			$callback = $library;
		}

		if ($callback)
		{
			return call_user_func_array($callback, $args);
		}

		trigger_error('Call to undefined method '.get_class($this).'::'.$name.'()', E_USER_WARNING);
	}

	public function exec($callback)
	{
		$callback($this);
		return $this;
	}

	public function __debugInfo()
	{
		$properties = [];

		foreach (get_object_vars($this) as $key => $value)
		{
			$properties[$key] = $key == '__caller' ? get_class($value) : $value;
		}

		return $properties;
	}
}
