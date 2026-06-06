<?php
/**
 * https://neofr.ag
 * Widget Vidéo — lecteur HTML5 + playlist des vidéos de la bibliothèque média.
 */

namespace NF\Widgets\Video;

use NF\NeoFrag\Addons\Widget;

class Video extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Vidéos'),
			'description' => $this->lang('Lecteur des vidéos de la bibliothèque média (player + playlist).'),
			'icon'        => 'fas fa-film',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'depends'     => ['neofrag' => '1.0.0']
		];
	}
}
