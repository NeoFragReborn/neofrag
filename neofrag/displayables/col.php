<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Displayables;

use NF\NeoFrag\Displayable;

class Col extends Displayable
{
	protected $_size;

	public function __sleep()
	{
		return array_merge(parent::__sleep(), ['_size']);
	}

	public function size($size = NULL)
	{
		if (func_num_args())
		{
			$this->_size = $size;
			return $this;
		}

		return $this->_size;
	}

	public function __toString()
	{
		$size = $this->_size;

		foreach ($this as $i => $child)
		{
			if (method_exists($child, 'size') && !$size)
			{
				$size = $child->size();
			}
		}

		if (!is_null($size) && preg_match('/^col-(\d+)$/', $size, $match) && $match[0] < 12)
		{
			$size = 'col-12 col-lg-'.$match[1];
		}

		if ($this->_id !== NULL)
		{
			foreach ($this as $i => $child)
			{
				$child->id($i);
			}
		}

		$output = parent::__toString();

		if ($this->_id !== NULL && NeoFrag()->output->live_editor() & \NF\NeoFrag\Core\Output::COLS)
		{
			$output = '<div class="live-editor-col nf-le-col">'.
				'<header class="nf-le-col-header">'.
					'<span class="nf-le-col-handle" title="'.NeoFrag()->lang('Glisser').'">'.icon('fas fa-grip-vertical').'</span>'.
					'<span class="nf-le-col-title">'.NeoFrag()->lang('Col').'</span>'.
					'<button type="button" class="nf-le-btn nf-le-btn-add live-editor-add-widget" title="'.NeoFrag()->lang('Ajouter un widget').'" aria-label="'.NeoFrag()->lang('Ajouter un widget').'">'.icon('fas fa-plus').'</button>'.
					'<span class="nf-le-spacer"></span>'.
					'<div class="nf-le-toolbar" role="toolbar">'.
						'<button type="button" class="nf-le-btn live-editor-size" data-size="-1" title="'.NeoFrag()->lang('Réduire').'"   aria-label="'.NeoFrag()->lang('Réduire').'">'.icon('fas fa-chevron-left').'</button>'.
						'<button type="button" class="nf-le-btn live-editor-size" data-size="1"  title="'.NeoFrag()->lang('Augmenter').'" aria-label="'.NeoFrag()->lang('Augmenter').'">'.icon('fas fa-chevron-right').'</button>'.
						'<button type="button" class="nf-le-btn nf-le-btn-danger live-editor-delete" title="'.NeoFrag()->lang('Supprimer').'" aria-label="'.NeoFrag()->lang('Supprimer').'">'.icon('far fa-trash-alt').'</button>'.
					'</div>'.
				'</header>'.
				$output.
			'</div>';
		}

		return '<div class="'.($size ?: 'col-12').'"'.($this->_id !== NULL ? ' data-col-id="'.$this->_id.'"' : '').'>'.$output.'</div>';
	}
}
