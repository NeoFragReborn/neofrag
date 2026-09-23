<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Displayables;

use NF\NeoFrag\Displayable;

class Widget extends Displayable
{
	protected $_id;
	protected $_widget;
	protected $_style;
	protected $_size;

	public function __invoke($widget = 0)
	{
		return is_int($widget) ? $this->widget_id($widget) : forward_static_call_array('NF\NeoFrag\Addons\Widget::__load', [NeoFrag(), func_get_args()]);
	}

	public function __sleep()
	{
		return ['_widget', '_style', '_size'];
	}

	public function id($id)
	{
		$this->_id = $id;
		return $this;
	}

	public function widget_id($widget = NULL)
	{
		if (func_num_args())
		{
			$this->_widget = $widget;
			return $this;
		}
		else
		{
			return $this->_widget;
		}
	}

	public function style($style = NULL)
	{
		if (func_num_args())
		{
			$this->_style = $style;
			return $this;
		}
		else
		{
			return $this->_style;
		}
	}

	public function size($size = '')
	{
		if (func_num_args())
		{
			$this->_size = $size;
			return $this;
		}
		else
		{
			return $this->_size;
		}
	}

	public function __toString()
	{
		// Garde-fou : un widget en erreur (ex. table d'un module à moitié installé) ne doit PAS faire
		// planter toute la page (le rendu est une concaténation de chaînes — une exception ici viderait
		// l'écran entier). On l'efface et on journalise ; le reste du site reste affiché.
		try
		{
			return $this->_render();
		}
		catch (\Throwable $e)
		{
			error_log('[widget] #'.$this->_widget.' : '.$e->getMessage());
			if (defined('NEOFRAG_DEBUG_BAR') && NEOFRAG_DEBUG_BAR)
			{
				throw $e; // en debug : on remonte l'erreur au lieu de la masquer
			}
			return '';
		}
	}

	private function _render(): string
	{
		$widget_data = NeoFrag()->db->from('nf_widgets')
									->where('widget_id', $this->_widget)
									->row();

		if ($widget_data && ($widget = NeoFrag()->widget($widget_data['widget'])) && $widget->is_enabled())
		{
			$widget->data = NeoFrag()->array;
			
			$output = $widget->output($widget_data['type'], \NF\NeoFrag\Fields\Json::decode($widget_data['settings']));

			$style = function($output) use ($widget_data){
				if (is_a($output, 'NF\NeoFrag\Libraries\Panel'))
				{
					if (!empty($widget_data['title']))
					{
						$output->title($widget_data['title']);
					}

					if (!empty($this->_style))
					{
						$output->style($this->_style);
					}
				}
			};

			if (is_array($output) || is_a($output, 'ArrayAccess'))
			{
				array_walk_recursive($output, $style);
			}
			else
			{
				$style($output);
			}

			if ($widget_data['widget'] == 'module')
			{
				// Le widget « contenu de la page » sans module à montrer : rien à afficher. Il lisait
				// info() sur NULL — dix lignes au journal de la production le 2026-09-22, en deux rafales
				// qui tombent pendant des déploiements, quand un module ne se charge pas le temps que ses
				// fichiers soient remplacés.
				if (!($module = NeoFrag()->output->module()))
				{
					return '';
				}

				$type = 'module';
				$name = $module->info()->name;
			}
			else
			{
				$type = 'widget';
				$name = $widget_data['widget'];
			}

			return '<div class="'.$type.' '.$type.'-'.$name.($this->_id !== NULL ? ' live-editor-widget" data-widget-id="'.$this->_id.'" data-widget-style="'.$this->_style.'" data-title="'.$$type->info()->title.'"' : '"').'>'.$output.'</div>';
		}

		return '';
	}
}
