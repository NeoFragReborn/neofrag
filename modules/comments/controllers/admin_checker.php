<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Comments\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index($page = '')
	{
		return [$this->_collection()->paginate($page)];
	}

	/**
	 * Les commentaires d'un contenu précis : `admin/comments/<module>/<id>`.
	 *
	 * C'est la cible de la colonne « Commentaires » des listes d'administration. Le nom du module
	 * est validé contre ce qui existe RÉELLEMENT en base plutôt que contre une liste écrite en dur :
	 * n'importe quel module peut brancher les commentaires, la liste serait donc toujours en retard.
	 */
	public function _module($module, $module_id, $page = '')
	{
		$module = (string) $module;

		if (!preg_match('/^[a-z0-9_]+$/', $module) || ($module_id = (int) $module_id) <= 0)
		{
			return;
		}

		return [
			$this->_collection()->where('module', $module)->where('module_id', $module_id)->paginate($page),
			$module,
			$module_id
		];
	}

	/** Le filtre de recherche, partagé par les deux écrans. */
	private function _collection()
	{
		return $this->collection('comment')
					->filters(
						$this->form2()
							 ->rule($this->form_text('content')
										 ->title('Contenu')
										 ->filter('_.content LIKE')
							 )
					);
	}
}
