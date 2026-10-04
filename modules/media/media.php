<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Media — bibliothèque centralisée des uploads (images, PDF, vidéos, etc.).
 */

namespace NF\Modules\Media;

use NF\NeoFrag\Addons\Module;

class Media extends Module
{
	const UPLOAD_DIR    = 'upload/media';
	const MAX_SIZE      = 33554432; // 32 Mo
	const ALLOWED_MIMES = [
		// image/svg+xml volontairement EXCLU : un SVG peut embarquer du JS inline
		// (XSS stocké s'il est servi inline). À réintroduire seulement avec sanitisation.
		'image/jpeg', 'image/png', 'image/gif', 'image/webp',
		'application/pdf', 'text/plain', 'text/markdown',
		'video/mp4', 'video/webm',
		'audio/mpeg', 'audio/ogg',
		'application/zip', 'application/x-zip-compressed',
		'application/json'
	];

	protected function __info()
	{
		return [
			'title'       => $this->lang('Médias'),
			'description' => $this->lang('Bibliothèque média : upload d\'images, vidéos, PDF, audio avec validation MIME.'),
			'icon'        => 'fas fa-photo-video',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				''                       => 'index',
				'admin{pages}'           => 'index',
				'admin/upload'           => '_upload',
				'admin/edit/{id}'        => '_edit',
				'admin/delete/{id}'      => '_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Médias'),
						'icon'   => 'fas fa-photo-video',
						'access' => [
							'manage' => ['title' => $this->lang('Gérer les médias'), 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}

	public static function format_size($bytes)
	{
		if ($bytes <= 0) return '0 B';
		$units = ['B', 'KB', 'MB', 'GB'];
		$i = 0;
		while ($bytes >= 1024 && $i < count($units) - 1)
		{
			$bytes /= 1024;
			$i++;
		}
		return round($bytes, 2).' '.$units[$i];
	}

	public static function is_image($mime)
	{
		return strpos($mime, 'image/') === 0;
	}
}
