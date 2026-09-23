<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Addons;

use NF\NeoFrag\Loadables\Addon;

abstract class Module extends Addon
{
	/**
	 * Descripteurs de contenu — COLLECTES AUPRES DES MODULES (inversion du 2026-09-15).
	 *
	 * Avant, la meme connaissance (« quel type de contenu vit dans quelle table, sous quelle cle,
	 * avec quelle colonne auteur ») etait recopiee CINQ fois, dans des modules qui n'ont aucune
	 * raison de se connaitre : Notifications::OWNER_MAP, Notifications::SUB_TYPES, le
	 * $content_tables de Notifications::content_url(), les trois constantes de Gamification
	 * (OWNER_MAP, REACTION_SOURCES, CONTENT_SOURCES) et Reactions::ALLOWED_TYPES. Trois modules du
	 * coeur nommaient donc en dur les tables de news, articles et forum — ce qui interdisait
	 * d'alleger le paquet : ils les auraient reclames.
	 *
	 * Desormais chaque module de contenu declare `declare_content_types()` et le coeur collecte.
	 * Un module absent ne declare rien : son type disparait, sans qu'aucun consommateur ne le sache.
	 *
	 * Forme d'un descripteur :
	 *   'news' => [
	 *       'table'        => 'nf_news',   // table portant le proprietaire
	 *       'pk'           => 'news_id',   // clef primaire
	 *       'author'       => 'user_id',   // colonne auteur (defaut : user_id)
	 *       'reactable'    => TRUE,        // accepte les reactions / compte pour le karma
	 *       'revisable'    => TRUE,        // porte un historique de revisions
	 *       'subscribable' => TRUE,        // on peut s'y abonner (notifications)
	 *       'aliases'      => ['article'], // autres cles designant le meme contenu
	 *   ]
	 *
	 * Le module declarant peut aussi implementer `content_url($type, $id)` pour batir l'URL
	 * publique du contenu ; sans elle, l'URL est simplement vide.
	 */
	static public function content_types()
	{
		static $types = NULL;

		if ($types !== NULL)
		{
			return $types;
		}

		$types = [];

		foreach (NeoFrag()->model2('addon')->get('module') as $module)
		{
			if (!method_exists($module, 'declare_content_types'))
			{
				continue;
			}

			foreach ($module->declare_content_types() as $key => $desc)
			{
				$desc += [
					'module'       => $module->info()->name,
					'author'       => 'user_id',
					'reactable'    => FALSE,
					'revisable'    => FALSE,
					'subscribable' => FALSE,
					'aliases'      => [],
				];

				$types[$key] = $desc;

				// Les alias designent le MEME contenu (« article » et « articles » coexistent
				// historiquement selon l'emetteur) : meme descripteur, cle differente.
				foreach ($desc['aliases'] as $alias)
				{
					$types[$alias] = $desc;
				}
			}
		}

		return $types;
	}

	/**
	 * URL publique d'un contenu, DELEGUEE au module qui le declare. Evite que les consommateurs
	 * (notifications, widgets, gamification) ne portent la logique d'URL des autres modules.
	 * Rend une chaine vide si le type est inconnu, sa table absente, ou le module sans `content_url()`.
	 */
	static public function content_url_of($type, $id)
	{
		$types = self::content_types();

		if (!isset($types[$type]) || empty($types[$type]['table']))
		{
			return '';
		}

		$desc = $types[$type];

		// Module non installe : sa table n'existe pas. On rend un lien vide plutot que de fataliser.
		if (!NeoFrag()->db->table_exists($desc['table']))
		{
			return '';
		}

		$module = NeoFrag()->module($desc['module']);

		return $module && method_exists($module, 'content_url')
			? (string) $module->content_url($type, (int) $id)
			: '';
	}

	static public function __class($name)
	{
		return 'Modules\\'.$name.'\\'.$name;
	}

	public function __toString()
	{
		return (string)$this->output->data->get('module', 'content');
	}

	public function __init()
	{

	}

	public function get_method(&$args, $ignore_ajax = FALSE)
	{
		$url = '';

		if ($this->url->admin)
		{
			$url .= 'admin';
		}

		if ($this->url->ajax && !$ignore_ajax)
		{
			$url .= '/ajax';
		}

		$url = ltrim($url, '/');

		$routes = $this->info()->routes;

		if ($url)
		{
			foreach (array_keys($routes) as $route)
			{
				if (!preg_match('#^'.$url.'#', $route))
				{
					unset($routes[$route]);
				}
			}

			$url .= '/';
		}

		$url .= implode('/', $args);

		$method = NULL;

		foreach ($routes as $route => $function)
		{
			if (preg_match('#^'.str_replace(array_map(function($a){ return '{'.$a.'}'; }, array_keys(self::$route_patterns)) + ['#'], array_values(self::$route_patterns) + ['\#'], $route).'$#', $url, $matches))
			{
				$args = [];

				if (in_string('{url_title*}', $route))
				{
					foreach (array_offset_left($matches) as $arg)
					{
						$args = array_merge($args, explode('/', trim($arg, '/')));
					}
				}
				else
				{
					$args = array_offset_left($matches);
				}

				$args = array_map(function($a){return trim($a, '/');}, $args);

				$method = $function;
				break;
			}
		}

		return $method;
	}

	public function get_permissions($type = NULL)
	{
		if (method_exists($this, 'permissions'))
		{
			$permissions = $this->permissions();

			if ($type === NULL)
			{
				return $permissions;
			}
			else if (isset($permissions[$type]))
			{
				return $permissions[$type];
			}
		}

		return [];
	}

	public function is_administrable(&$category = NULL)
	{
		if (property_exists($info = $this->info(), 'admin'))
		{
			$category = $info->admin;

			if (is_bool($category))
			{
				$category = $category ? 'default' : 'none';
			}

			return TRUE;
		}

		return FALSE;
	}

	public function is_authorized()
	{
		static $allowed = [];

		if (!array_key_exists($hash = spl_object_hash($this), $allowed))
		{
			$allowed[$hash] = FALSE;

			if ($this->is_administrable())
			{
				if ($this->access->effective_admin())
				{
					$allowed[$hash] = TRUE;
				}
				else if (($all_permissions = $this->get_permissions('default')))
				{
					foreach ($all_permissions['access'] as $a)
					{
						foreach ($a['access'] as $action => $access)
						{
							if (!empty($access['admin']) && $this->access($this->info()->name, $action))
							{
								$allowed[$hash] = TRUE;
								break 2;
							}
						}
					}
				}
			}
		}

		return $allowed[$hash];
	}

	public function title($title)
	{
		$this->output->data->set('module', 'title', $title);
		return $this;
	}

	public function subtitle($subtitle)
	{
		$this->output->data->set('module', 'subtitle', $subtitle);
		return $this;
	}

	// Description SEO de la page courante (meta description + og/twitter). Tronquée à ~160 car.
	public function meta_description($description)
	{
		$description = trim(preg_replace('/\s+/', ' ', strip_tags((string)$description)));

		if (mb_strlen($description) > 160)
		{
			$description = mb_substr($description, 0, 157).'…';
		}

		$this->output->data->set('module', 'description', $description);
		return $this;
	}

	public function icon($icon)
	{
		$this->output->data->set('module', 'icon', $icon);
		return $this;
	}

	public function add_action($url, $title = '', $icon = '')
	{
		if (!is_a($url, 'NF\NeoFrag\Libraries\Html'))
		{
			$url = $this->button($title, $icon, 'primary', $url);
		}

		$this->output->data->append('module', 'actions', $url);
		return $this;
	}

	public function ajax()
	{
		$this->output->data->set('module', 'ajax', TRUE);
		return $this;
	}

	public function api()
	{
		if ($controller = $this->controller('api'))
		{
			return NeoFrag()->___load('', 'api', [$controller]);
		}
	}
}
