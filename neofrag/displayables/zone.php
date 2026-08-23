<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Displayables;

use NF\NeoFrag\Displayable;

class Zone extends Displayable
{
	public function display($disposition)
	{
		$output = NeoFrag()->disposition->decode($disposition['disposition']);

		if ($live_editor = NeoFrag()->output->live_editor())
		{
			$i = 0;

			$output->each(function($row) use (&$i){
				return $row->id($i++);
			});

			if ($live_editor & \NF\NeoFrag\Core\Output::ZONES)
			{
				$zone_id    = $disposition['zone'];
				$theme      = $this->theme($disposition['theme']);
				$zone_label = !empty($theme->info()->zones[$zone_id]) ? $theme->info()->zones[$zone_id] : NeoFrag()->lang('Zone #%d', $zone_id);

				$is_common  = $disposition['page'] == '*';
				$fork_label = $is_common ? NeoFrag()->lang('Disposition commune') : NeoFrag()->lang('Disposition spécifique à la page');
				$fork_icon  = $is_common ? 'fas fa-toggle-off'                    : 'fas fa-toggle-on';

				$header = '<header class="nf-le-zone-header">'.
					'<span class="nf-le-zone-title">'.$zone_label.'</span>'.
					'<button type="button" class="nf-le-btn nf-le-btn-add live-editor-add-row" title="'.NeoFrag()->lang('Ajouter une ligne').'" aria-label="'.NeoFrag()->lang('Ajouter une ligne').'">'.icon('fas fa-plus').'</button>'.
					'<span class="nf-le-spacer"></span>'.
					'<button type="button" class="nf-le-fork live-editor-fork" data-enabled="'.($is_common ? '0' : '1').'" title="'.$fork_label.'">'.icon($fork_icon).'<span>'.$fork_label.'</span></button>'.
				'</header>';

				$output = $header.$output;
			}

			$output = '<section'.($live_editor & \NF\NeoFrag\Core\Output::ZONES ? ' class="live-editor-zone nf-le-zone"' : '').' data-disposition-id="'.$disposition['disposition_id'].'">'.$output.'</section>';
		}

		return $output;
	}
}
