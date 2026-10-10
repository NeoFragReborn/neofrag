<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Awards;

use NF\NeoFrag\Addons\Widget;

class Awards extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Palmarès'),
			'description' => $this->lang('Le palmarès en bref, au choix : les derniers résultats des équipes, l\'équipe la plus récompensée ou le jeu le plus récompensé.'),
			'icon'        => 'fas fa-trophy',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => ['awards'],
			'version'     => '1.0',
			// SANS cette ligne, l'installation par depot d'archive passait son chemin EN SILENCE :
			// l'installeur exige une version ET une dependance au coeur pour reconnaitre l'addon.
			// C'etait le seul des 61 addons distribuables a ne pas la declarer (2026-09-22).
			'depends'     => ['neofrag' => '0.2.0'],
			'types'       => [
				'index'     => $this->lang('Derniers palmarès'),
				'best_team' => $this->lang('Équipe la plus récompensée'),
				'best_game' => $this->lang('Jeu le plus récompensé')
			]
		];
	}
}
