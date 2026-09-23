<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Members\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

/**
 * Les membres dans la recherche globale et dans la suggestion instantanée.
 *
 * La recherche du site ne trouvait que du CONTENU — forum, actualités, pages. Chercher quelqu'un
 * obligeait à ouvrir l'annuaire et à le feuilleter page par page, alors que c'est l'une des choses
 * qu'on cherche le plus souvent sur un site de communauté.
 *
 * Ce qui est cherché, et pourquoi c'est exactement ça
 * ---------------------------------------------------
 * Pseudo, prénom et nom — et rien d'autre. Ce ne sont pas des champs choisis pour leur utilité mais
 * pour ce qu'ils exposent : **les trois sont déjà affichés publiquement** sur la fiche de profil
 * (`modules/user/views/profile.tpl.php`) et dans l'aperçu au survol. Les rendre cherchables ne
 * divulgue donc rien de neuf — cela rend seulement trouvable ce qui est déjà visible.
 *
 * Le filtre est le MÊME que celui de l'annuaire public (`deleted = FALSE`, cf.
 * `Members\Controllers\Checker::index`). C'est une règle, pas une coïncidence : une recherche qui
 * montrerait plus que la liste serait une fuite, et une recherche qui montrerait moins serait un
 * défaut. Si l'annuaire se restreint un jour, ce filtre doit se restreindre avec lui.
 */
class Search extends Controller_Module
{
	public function index($result, $keywords)
	{
		// L'URL se construit sur le pseudo BRUT : `highlight()` y insérerait du balisage, et
		// `url_title()` en ferait une adresse qui ne mène nulle part.
		$result['pseudo'] = $result['username'];
		$result['nom']    = trim($result['first_name'].' '.$result['last_name']);

		$result['username'] = highlight($result['username'], $keywords);
		$result['nom']      = $result['nom'] ? highlight($result['nom'], $keywords) : '';

		return $this->view('search/index', $result);
	}

	public function detail($result, $keywords)
	{
		return $this->index($result, $keywords);
	}

	public function search()
	{
		$this->db	->select('u.id', 'u.username', 'u.last_activity_date', 'p.first_name', 'p.last_name')
					->from('nf_user u')
					->join('nf_user_profile p', 'u.id = p.id', 'LEFT')
					->where('u.deleted', FALSE)
					->where('u.id !=', nf_compte_masque())
					->order_by('u.username');

		return ['u.username', 'p.first_name', 'p.last_name'];
	}

	/** Suggestion typeahead (pseudo + lien vers la fiche). */
	public function suggest($result)
	{
		return [
			'title' => $result['username'],
			'url'   => url('user/'.$result['id'].'/'.url_title($result['username']))
		];
	}
}
