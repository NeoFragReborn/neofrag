<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Addons\Controllers\Addons;

use NF\NeoFrag\Loadables\Controller;

class Language extends Controller
{
	/**
	 * Libellés du type (pluriel, singulier), icône et couleur : affichés sur les cartes de la page des
	 * addons. Posés au constructeur, parce qu'une valeur par défaut de propriété ne peut pas appeler
	 * lang() — écrits en dur, ils restaient en français sur un site dans une autre langue.
	 */
	public $__label;

	public function __construct($caller)
	{
		parent::__construct($caller);

		$this->__label = [$this->lang('Langues'), $this->lang('Langue'), 'far fa-flag', 'danger'];
	}

	public function __actions()
	{
		return $this->array
					->set('enable', [$this->lang('Activer'), 'fas fa-check', 'success', TRUE, function($addon){
						return !$addon->is_enabled();
					}])
					->set('disable', [$this->lang('Désactiver'), 'fas fa-times', 'muted', TRUE, function($addon){
						return count($this->config->langs) > 1 && $addon->is_enabled();
					}])
					->set('order', [$this->lang('Ordre'), 'fas fa-sort', 'info', TRUE]);
	}

	public function enable($addon)
	{
		$addon->__addon->set('data', $addon->__addon->data->set('enabled', TRUE))->update();

		notify($this->lang('<b>%s</b> activé', $addon->info()->title));

		refresh();
	}

	public function disable($addon)
	{
		$addon->__addon->set('data', $addon->__addon->data->set('enabled', FALSE))->update();

		notify($this->lang('<b>%s</b> désactivé', $addon->info()->title));

		refresh();
	}

	public function order()
	{
		$langs = $this	->array($this->config->langs)
						->sort(function($a, $b){
							// L'ordre d'une langue est un entier ; `strnatcmp()` attend des chaines.
							return strnatcmp((string) $a->settings()->order, (string) $b->settings()->order);
						});

		if (($post = post_check('id', 'position')) && (list($addon_id, $position) = array_values($post)))
		{
			foreach ($langs as $id => $lang)
			{
				if ($lang->__addon->id == $addon_id)
				{
					break;
				}
			}

			foreach ($langs->move($id, $position)->values() as $order => $addon)
			{
				$addon->__addon	->set('data', $addon->__addon->data->set('order', $order))
								->update();
			}

			return $this->output->json(['success' => 'refresh']);
		}

		return $this->modal('Préférence des langues', 'far fa-flag')
					->body($this->table2($langs)
								->compact(function($a){
									return $this->button_sort($a->__addon->id, 'admin/addons/order/'.$a->__addon->url());
								})
								->col(function($a){
									return $this->label($a->info()->title, $a->info()->icon);
								})
					)
					->close();
	}
}
