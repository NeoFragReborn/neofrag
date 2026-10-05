<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

// Le fuseau horaire du membre, sur la page « Mon compte » depuis le chantier A (étape A1) : il était au
// milieu du formulaire du profil public, qui ne le montre pas.
$this	->rule($this->form_select('timezone')
					->title($this->lang('Fuseau horaire'))
					// Les fuseaux groupés par région, les villes dans la langue du site (nf_fuseaux_liste()) ;
					// la liste brute des 419 identifiants anglais était illisible. Vide : le fuseau du navigateur.
					->data((function(){
						$fuseaux = ['' => [$this->lang('Automatique : celui de votre navigateur')]];

						foreach (array_values(nf_fuseaux_liste()) as $groupe => $liste)
						{
							foreach ($liste as $nom => $libelle)
							{
								$fuseaux[$nom] = [$libelle, $groupe];
							}
						}

						return $fuseaux;
					})())
					->optgroup(1, array_keys(nf_fuseaux_liste()))
		)
		// Sans fonction de succès, un formulaire v2 n'enregistre rien (Form2::check()).
		->success(function($profile){
			$profile->commit();
			notify($this->lang('Fuseau horaire enregistré'));
			refresh();
		});
