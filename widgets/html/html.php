<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Html;

use NF\NeoFrag\Addons\Widget;

class Html extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Contenu libre / Code HTML'),
			'description' => $this->lang('Bloc HTML libre pour insérer du contenu personnalisé.'),
			'icon'        => 'fas fa-code',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'types'       => [
				'index' => $this->lang('Contenu libre'),
				'html'  => $this->lang('Code HTML')
			]
		];
	}
}
