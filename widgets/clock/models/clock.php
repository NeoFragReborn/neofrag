<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Clock\Models;

use NF\NeoFrag\Loadables\Model;

class Clock extends Model
{
	public function get_birthdays()
	{
		return $this->db	->select('up.id AS user_id', 'up.date_of_birth', 'u.username')
							->from('nf_user_profile up')
							->join('nf_user u', 'up.id = u.id AND u.deleted = "0"', 'INNER')
							->where('up.date_of_birth IS NOT NULL')
							->where('DATE_FORMAT(up.date_of_birth, "%m-%d") = DATE_FORMAT(NOW(), "%m-%d")')
							->get();
	}
}
