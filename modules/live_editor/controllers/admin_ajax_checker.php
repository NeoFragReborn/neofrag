<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Live_Editor\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Ajax_Checker extends Module_Checker
{
	public function zone_fork()
	{
		return $this->_check_disposition('disposition_id', 'url');
	}

	public function row_add()
	{
		return $this->_check_disposition('disposition_id');
	}

	public function row_move()
	{
		return $this->_check_disposition('disposition_id', 'row_id', 'position');
	}

	public function row_style()
	{
		return $this->_check_disposition('disposition_id', 'row_id', 'style');
	}

	public function row_delete()
	{
		return $this->_check_disposition('disposition_id', 'row_id');
	}

	public function col_add()
	{
		return $this->_check_disposition('disposition_id', 'row_id');
	}

	public function col_move()
	{
		return $this->_check_disposition('disposition_id', 'row_id', 'col_id', 'position');
	}

	public function col_size()
	{
		return $this->_check_disposition('disposition_id', 'row_id', 'col_id', 'size');
	}

	public function col_delete()
	{
		return $this->_check_disposition('disposition_id', 'row_id', 'col_id');
	}

	public function widget_add()
	{
		if (($args = $this->_check_disposition('disposition_id', 'row_id', 'col_id', 'title', 'widget', 'type', 'settings?')) && (list(,,,,, $widget_name, $type) = $args))
		{
			$this->model()->get_widgets($widgets, $types);

			if (isset($widgets[$widget_name]) && (isset($types[$widget_name][$type]) || $type == 'index'))
			{
				return $args;
			}
		}
	}

	public function widget_move()
	{
		return $this->_check_disposition('disposition_id', 'row_id', 'col_id', 'widget_id', 'position');
	}

	public function widget_style()
	{
		return $this->_check_disposition('disposition_id', 'row_id', 'col_id', 'widget_id', 'style');
	}

	public function widget_admin()
	{
		if ($this->access->effective_admin())
		{
			$post = post();

			if (!empty($post['widget_id']) && $widget = $this->db	->select('widget', 'type', 'settings')
																	->from('nf_widgets')
																	->where('widget_id', $post['widget_id'])
																	->row())
			{
				return [$widget['widget'], $widget['type'], $widget['settings'] ? \NF\NeoFrag\Fields\Json::decode($widget['settings']) : NULL];
			}
			else if (!empty($post['widget']) && isset($post['type']))
			{
				return [$post['widget'], $post['type'] ?: 'index'];
			}
		}
	}

	public function widget_settings()
	{
		if (($args = $this->_lire_disposition('disposition_id', 'row_id', 'col_id', 'widget_id')) && (list($disposition_id, $disposition, $row_id, $col_id, $widget_id) = $args))
		{
			if ($widget_id == -1)
			{
				return [];
			}
			else if ($widget = $this->model()->check_widget($disposition->get($row_id, $col_id, $widget_id)->widget_id()))
			{
				return $widget;
			}
		}
	}

	public function widget_update()
	{
		if (($args = $this->_check_disposition('disposition_id', 'row_id', 'col_id', 'widget_id', 'title', 'widget', 'type', 'settings?')) &&
			(list($disposition_id, $disposition, $row_id, $col_id, $widget_id, $title, $widget_name, $type, $settings) = $args) &&
			($widget = $this->model()->check_widget($disposition->get($row_id, $col_id, $widget_id)->widget_id())))
		{
			$this->model()->get_widgets($widgets, $types);

			if (isset($widgets[$widget_name]) && (isset($types[$widget_name][$type]) || $type == 'index'))
			{
				$widget['title']    = $title;
				$widget['widget']   = $widget_name;
				$widget['type']     = $type;
				$widget['settings'] = $settings;

				return array_merge([$disposition_id, $disposition, $row_id, $col_id, $widget_id], array_values($widget));
			}
		}
	}

	public function widget_delete()
	{
		return $this->_check_disposition('disposition_id', 'row_id', 'col_id', 'widget_id');
	}

	/**
	 * Une ÉCRITURE dans une disposition. Toutes les modifications de l'éditeur en direct passent par
	 * ici, et aucune n'est permise sur la démonstration : le module y est verrouillé
	 * (NF_DEMO_MODULES_VERROUILLES), le filet d'Output les refuse avant ce checker, et ce refus-ci
	 * reste en second rideau. Un widget HTML rend son contenu tel quel : écrit par un visiteur, son
	 * script s'exécuterait chez tous les autres.
	 */
	private function _check_disposition()
	{
		if (nf_demo())
		{
			return FALSE;
		}

		return $this->_lire_disposition(...func_get_args());
	}

	/**
	 * Une LECTURE dans une disposition (les réglages d'un widget, pour les montrer) : permise partout.
	 * Rend la disposition et les champs demandés, ou NULL si la requête ne les porte pas.
	 */
	private function _lire_disposition(string ...$champs)
	{
		if ($this->access->effective_admin() && $check = post_check($champs))
		{
			array_splice($check, 1, 0, [$this->model()->get_disposition($check['disposition_id'], $theme, $page, $zone)]);

			$check[] = $theme;
			$check[] = $page;
			$check[] = $zone;

			return array_values($check);
		}
	}
}
