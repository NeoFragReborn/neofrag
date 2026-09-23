<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Donations;

use NF\NeoFrag\Addons\Widget;

class Donations extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Campagne de dons'),
			'description' => $this->lang('Affiche la progression d\'une campagne de dons : barre de progression, montant collecté, top donateurs et bouton "Faire un don".'),
			'icon'        => 'fas fa-hand-holding-heart',
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'types'       => [
				'progress' => $this->lang('Barre de progression'),
				'top'      => $this->lang('Top donateurs')
			]
		];
	}
}
