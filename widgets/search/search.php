<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Search;

use NF\NeoFrag\Addons\Widget;

class Search extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Rechercher'),
			'description' => $this->lang('Champ de recherche compact pour la sidebar.'),
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
		];
	}
}
