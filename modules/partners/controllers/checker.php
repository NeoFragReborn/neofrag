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
			if (empty($partner['website']))
			{
				redirect('partners');
			}

			$this->db	->where('partner_id', $partner_id)
						->update('nf_partners', [
							'count' => $partner['count'] + 1
						]);

			header('Location: '.$partner['website']);
			exit;
		}
	}
}
