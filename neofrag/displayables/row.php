<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Displayables;

use NF\NeoFrag\Displayable;

class Row extends Displayable
{
	protected $_style;

	public function __sleep()
	{
		return array_merge(parent::__sleep(), ['_style']);
	}

	public function style($style = NULL)
	{
		if (func_num_args())
		{
			$this->_style = $style;
			return $this;
		}

		return $this->_style;
	}

	public function __toString()
	{
		$output = '';

		$live_editor = FALSE;

		if ($this->_id !== NULL)
		{
			foreach ($this->_array as $i => $child)
			{
				$child->id($i);
			}

			if ($live_editor = NeoFrag()->output->live_editor() & \NF\NeoFrag\Core\Output::ROWS)
			{
				$output .= '<header class="live-editor-row-header nf-le-row-header">'.
					'<span class="nf-le-row-handle" title="'.NeoFrag()->lang('Glisser').'">'.icon('fas fa-grip-lines').'</span>'.
					'<span class="nf-le-row-title">'.NeoFrag()->lang('Row').'</span>'.
					'<button type="button" class="nf-le-btn nf-le-btn-add live-editor-add-col" title="'.NeoFrag()->lang('Ajouter une colonne').'" aria-label="'.NeoFrag()->lang('Ajouter une colonne').'">'.icon('fas fa-plus').'</button>'.
					'<span class="nf-le-spacer"></span>'.
					'<div class="nf-le-toolbar" role="toolbar">'.
						'<button type="button" class="nf-le-btn live-editor-style"  title="'.NeoFrag()->lang('Apparence').'" aria-label="'.NeoFrag()->lang('Apparence').'">'.icon('fas fa-paint-brush').'</button>'.
						'<button type="button" class="nf-le-btn nf-le-btn-danger live-editor-delete" title="'.NeoFrag()->lang('Supprimer').'" aria-label="'.NeoFrag()->lang('Supprimer').'">'.icon('far fa-trash-alt').'</button>'.
					'</div>'.
				'</header>';
			}
		}

		// `nf-row` : la ligne d'une disposition. Le socle lui donne un espace entre colonnes EMPILÉES
		// (css/nf-bs5-bridge.css) — sans lui, sur un téléphone, le premier bloc de la colonne latérale
		// touchait le dernier du contenu (signalé le 2026-09-23).
		$output .= '<div class="row nf-row'.(!empty($this->_style) ? ' '.$this->_style.($live_editor ? '" data-original-style="'.$this->_style : '') : '').'"'.($this->_id !== NULL ? ' data-row-id="'.$this->_id.'"' : '').'>
						'.parent::__toString().'
					</div>';

		return $live_editor ? '<div class="live-editor-row nf-le-row">'.$output.'</div>' : $output;
	}
}
