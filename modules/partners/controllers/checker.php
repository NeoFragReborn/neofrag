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
		// L'adresse porte le nom court du partenaire, refait quand son titre change : vérifiée avec le VRAI, une ancienne
		// adresse mène à la nouvelle au lieu de répondre 404 (m06).
		$vrai = nf_titre_lu($this->db->select('name')->from('nf_partners')->where('partner_id', (int) $partner_id)->row());

		if ($vrai !== '' && ($partner = $this->model()->check_partner($partner_id, $vrai)))
		{
			nf_bon_titre((string) $name, $vrai, 'partners/'.(int) $partner_id);

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
