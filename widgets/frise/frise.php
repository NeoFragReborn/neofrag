<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * La frise de la saison : ce que le site vit, mois par mois, sur une ligne de temps — les rendez-vous à venir du
 * calendrier, les actualités, les discussions du forum et les albums photo (2026-10-06, pour le thème Chronique ;
 * il sert à tout thème). LGPLv3.
 *
 * couplage(calendar): les rendez-vous de la frise ne viennent du calendrier que si le module Calendrier est installé.
 * couplage(news): les actualités, de même : seulement si le module Actualités est installé.
 * couplage(forum): les discussions, de même : seulement si le module Forum est installé, et parmi les forums que le visiteur peut lire.
 * couplage(gallery): les albums photo, de même : seulement si le module Galerie est installé.
 */

namespace NF\Widgets\Frise;

use NF\NeoFrag\Addons\Widget;

class Frise extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Frise de la saison'),
			'description' => $this->lang('La saison du site sur une frise, mois par mois : les rendez-vous à venir du calendrier, les actualités, les discussions du forum et les albums photo, chacun si son module est installé ; seul ce que le visiteur peut lire y paraît.'),
			'icon'        => 'fas fa-stream',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['communaute', 'association', 'gaming'],
			'requires'    => [],
			'version'     => '1.0.0',
			'depends'     => [
				'neofrag' => '1.2.36'
			],
			'types'       => ['index' => $this->lang('La saison')]
		];
	}
}
