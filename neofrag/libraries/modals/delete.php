<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries\Modals;

use NF\NeoFrag\Libraries\Modal;

class Delete extends Modal
{
	public function __invoke($title = '', $icon = '')
	{
		return parent	::__invoke($title ?: NeoFrag()->lang('Confirmation de suppression'), ($icon ?: 'fas fa-trash-alt').' text-danger')
						->submit(NeoFrag()->lang('Supprimer'), 'danger')
						->cancel();
	}
}
