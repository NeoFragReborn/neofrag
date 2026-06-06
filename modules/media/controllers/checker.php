<?php
namespace NF\Modules\Media\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		// Public : seulement les médias images/PDF (pas les autres types perso)
		$medias = NeoFrag()->db	->select('m.*', 'u.username', 'UNIX_TIMESTAMP(m.created_at) AS ts')
								->from('nf_media m')
								->join('nf_user u', 'm.user_id = u.id', 'LEFT')
								->where('m.mime_type LIKE', 'image/%')
								->order_by('m.created_at DESC')
								->limit(60)
								->get();
		return [$medias];
	}
}
