<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Le site en chiffres : trois ou quatre nombres qui disent la vie du site — ses membres, ses discussions, ses
 * actualités, ses rendez-vous à venir, ses photos (2026-10-06, pour le thème Pulse ; il sert à tout thème). LGPLv3.
 *
 * couplage(forum): les discussions et les messages ne se comptent que si le module Forum est installé, dans les forums que le visiteur peut lire.
 * couplage(news): les actualités, de même : seulement si le module Actualités est installé.
 * couplage(calendar): les rendez-vous à venir, de même : seulement si le module Calendrier est installé.
 * couplage(gallery): les photos, de même : seulement si le module Galerie est installé, dans les albums que le visiteur peut voir.
 */

namespace NF\Widgets\Chiffres;

use NF\NeoFrag\Addons\Widget;

class Chiffres extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Le site en chiffres'),
			'description' => $this->lang('Trois ou quatre nombres qui disent la vie du site, au choix : ses membres, les discussions et les messages du forum, les actualités, les rendez-vous à venir, les photos — chacun si son module est installé, et seulement ce que le visiteur peut voir.'),
			'icon'        => 'fas fa-chart-simple',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['communaute', 'association', 'gaming'],
			'requires'    => [],
			'version'     => '1.0.0',
			'depends'     => [
				'neofrag' => '1.2.38'
			],
			'types'       => ['index' => $this->lang('Le site en chiffres')]
		];
	}
}
