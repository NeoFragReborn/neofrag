<?php
namespace NF\Modules\Media\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		$filters = [
			'q'    => isset($_GET['q']) ? trim((string)$_GET['q']) : '',
			'type' => isset($_GET['type']) && in_array($_GET['type'], ['image', 'video', 'audio', 'pdf'], TRUE) ? $_GET['type'] : ''
		];

		$type_like = [
			'image' => 'image/%',
			'video' => 'video/%',
			'audio' => 'audio/%',
			'pdf'   => 'application/pdf'
		];

		$db = NeoFrag()->db	->select('m.*', 'u.username', 'UNIX_TIMESTAMP(m.created_at) AS ts')
							->from('nf_media m')
							->join('nf_user u', 'm.user_id = u.id', 'LEFT');

		if ($filters['q'] !== '')
		{
			$like = '%'.$filters['q'].'%';
			$db->where('m.original_name LIKE', $like, 'OR', 'm.title LIKE', $like);
		}

		if ($filters['type'] !== '')
		{
			$db->where('m.mime_type LIKE', $type_like[$filters['type']]);
		}

		$medias = $db->order_by('m.created_at DESC')->limit(self::SEARCH_CAP)->get();

		// Stats globales (toute la bibliothèque), indépendantes du filtre courant.
		$total_size  = (int)NeoFrag()->db->select('SUM(size_bytes)')->from('nf_media')->row();
		$total_count = (int)NeoFrag()->db->select('COUNT(*)')->from('nf_media')->row();

		$filters['matched'] = count($medias);
		$filters['capped']  = $filters['matched'] >= self::SEARCH_CAP;
		$filters['active']  = $filters['q'] !== '' || $filters['type'] !== '';

		$filters['sort_cols'] = [
			'date'  => $this->lang('Date'),
			'title' => $this->lang('Nom'),
			'size'  => $this->lang('Taille')
		];
		list($medias, $filters['sort']) = $this->sort_items($medias, [
			'date'  => 'ts',
			'title' => 'original_name',
			'size'  => 'size_bytes'
		], 'date', 'desc');

		return [
			$this->module->pagination->fix_items_per_page(24)->get_data($medias, $page),
			$total_count,
			$total_size,
			$filters
		];
	}

	public function _upload()
	{
		return [];
	}

	public function _edit($id)
	{
		$m = NeoFrag()->db->select('*')->from('nf_media')->where('id', $id)->row();
		return $m ? [$m] : NULL;
	}

	public function _delete($id)
	{
		$m = NeoFrag()->db->select('id', 'filename', 'original_name')->from('nf_media')->where('id', $id)->row();
		return $m ? [$m] : NULL;
	}
}
