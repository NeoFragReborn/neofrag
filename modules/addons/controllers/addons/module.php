<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Addons\Controllers\Addons;

use NF\NeoFrag\Loadables\Controller;

class Module extends Controller
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

		$this->__label = [$this->lang('Modules'), $this->lang('Module'), 'far fa-sticky-note', 'primary'];
	}

	public function __actions()
	{
		return $this->array
					->set('enable', [$this->lang('Activer'), 'fas fa-check', 'success', TRUE, function($addon){
						return $addon->is_deactivatable() && !$addon->is_enabled();
					}])
					->set('disable', [$this->lang('Désactiver'), 'fas fa-times', 'muted', TRUE, function($addon){
						return $addon->is_deactivatable() && $addon->is_enabled();
					}])
					->set('settings', [$this->lang('Configuration'), 'fas fa-wrench', 'warning', TRUE, function($addon){
						return isset($addon->info()->settings);
					}])
					->set('access', [$this->lang('Permissions'), 'fas fa-unlock-alt', 'success', FALSE, function($addon){
						return $addon->get_permissions('default');
					}]);
	}

	public function enable($addon)
	{
		$addon->__addon->set('data', $addon->__addon->data->set('enabled', TRUE))->update();

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('module.enabled', [
			'target_type' => 'module',
			'target_id'   => $addon->info()->name,
			'details'     => $addon->info()->title
		]);

		notify($this->lang('<b>%s</b> activé', $addon->info()->title));

		refresh();
	}

	public function disable($addon)
	{
		$addon->__addon->set('data', $addon->__addon->data->set('enabled', FALSE))->update();

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('module.disabled', [
			'target_type' => 'module',
			'target_id'   => $addon->info()->name,
			'details'     => $addon->info()->title
		]);

		notify($this->lang('<b>%s</b> désactivé', $addon->info()->title));

		refresh();
	}

	public function settings($addon)
	{
		return call_user_func($addon->info()->settings)	->modal($addon->info()->title, 'fas fa-wrench')
														->cancel();
	}

	public function access($addon)
	{
		redirect('admin/access/matrix/'.$addon->info()->name);
	}
}
