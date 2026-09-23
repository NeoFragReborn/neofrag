<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: HiddenCMS — porté sur NeoFrag Reborn
 */

namespace NF\Modules\Files\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	private function normalize_db_path($path)
	{
		$path = trim(str_replace('\\', '/', (string)$path));

		if (strpos($path, './') === 0)
		{
			$path = substr($path, 2);
		}

		return trim($path, '/');
	}

	public function _file($slug)
	{
		$slug = trim((string)$slug);

		if ($slug === '')
		{
			return;
		}

		foreach ($this->db->select('id', 'path')->from('nf_file')->get(FALSE) as $row)
		{
			$path = $this->normalize_db_path($row['path']);

			if (strpos($path, 'upload/files/') !== 0)
			{
				continue;
			}

			if (pathinfo(basename($path), PATHINFO_FILENAME) === $slug && $this->access('files', 'read_file', (int)$row['id']) && ($file = NeoFrag()->model2('file', (int)$row['id'])) && $file())
			{
				return [$file];
			}
		}
	}
}
