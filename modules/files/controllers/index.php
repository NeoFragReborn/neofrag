<?php
/**
 * https://neofr.ag
 * @author: HiddenCMS — porté sur NeoFrag Reborn
 */

namespace NF\Modules\Files\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function _file($file)
	{
		$path = trim(str_replace('\\', '/', (string)$file->path), '/');

		if (strpos($path, './') === 0)
		{
			$path = substr($path, 2);
		}

		dir_create(NEOFRAG_CMS.'/upload/files');

		$full = str_replace('\\', '/', NEOFRAG_CMS.'/'.$path);
		$root = str_replace('\\', '/', realpath(NEOFRAG_CMS.'/upload/files'));
		$check = file_exists($full) ? str_replace('\\', '/', realpath($full)) : NULL;

		if (!$root || !$check || stripos($check, $root) !== 0)
		{
			$this->error();
		}

		$extension = extension($file->name);
		$mime = in_array($extension, ['bmp', 'css', 'eot', 'gif', 'jpeg', 'jpg', 'js', 'json', 'html', 'otf', 'png', 'svg', 'swf', 'ttf', 'woff', 'woff2', 'zip'], TRUE) ? get_mime_by_extension($extension) : 'application/octet-stream';

		header('Content-Type: '.$mime);
		header('Content-Length: '.filesize($full));
		header('Content-Disposition: inline; filename="'.basename($file->name).'"');
		exit(file_get_contents($full));
	}
}
