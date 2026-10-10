<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Partners\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function _partner($partner_id, $name)
	{
		if ($partner = $this->model()->check_partner($partner_id, $name))
		{
			// Pas de site (website vide) → ne PAS émettre header('Location: ') vide (= rechargement
			// de la même URL = boucle infinie, cf. bug forum). Retour à la liste des partenaires.
			// Une adresse au schéma refusé (`javascript:`…) n'est pas suivie non plus : la page des
			// partenaires ne l'affiche pas en lien (nf_url_sure), la visite ne la sert pas davantage.
			if (empty($partner['website']) || !nf_url_sure((string) $partner['website']))
			{
				redirect('partners');
			}

			// Compté par la base, pas relu puis réécrit : deux visites simultanées comptent deux.
			$this->db->execute('UPDATE nf_partners SET `count` = `count` + 1 WHERE partner_id = '.(int) $partner_id);

			nf_quitter_le_site((string) $partner['website'], 'partners');
		}
	}
}
