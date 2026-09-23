<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\User\Models\User;

class Delete extends \NF\NeoFrag\Actions\Delete
{
	protected function check($user)
	{
		return !$user->deleted;
	}
}
