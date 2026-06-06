<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Addons\Controllers\Addons;

use NF\NeoFrag\Loadables\Controller;

class Theme extends Controller
{
	public $__label = ['Thèmes', 'Thème', 'fas fa-tint', 'success'];

	public function __actions()
	{
		return $this->array
					->set('enable',    ['Activer', 'fas fa-check', 'success', TRUE, function($addon){
						return $addon->info()->name != 'admin' && !$addon->is_enabled();
					}])
					->set('customize', ['Personaliser', 'fas fa-paint-brush', 'info', FALSE, function($addon){
						return $addon->info()->name != 'admin' && @$addon->controller('admin');
					}])
					->set('reset',     ['Réinstaller par défaut', 'fas fa-sync', 'warning', TRUE, function($addon){
						return $addon->info()->name != 'admin';
					}])
					->set('delete',    ['Supprimer', 'fas fa-trash', 'danger', TRUE, function($addon){
						return $addon->info()->name != 'admin' && !$addon->is_enabled();
					}]);
	}

	public function enable($addon)
	{
		$this->config('nf_default_theme', $addon->info()->name);

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('theme.enabled', [
			'target_type' => 'theme',
			'target_id'   => $addon->info()->name,
			'details'     => $addon->info()->title
		]);

		notify($this->lang('<b>%s</b> activé', $addon->info()->title));

		refresh();
	}

	public function customize($theme, $controller)
	{
		$controller	->title($theme->info()->title)
					->subtitle($this->lang('Personnalisation du thème'))
					->icon('fas fa-paint-brush')
					->add_action($this->button($this->lang('Réinstaller par défaut'), 'fas fa-sync', 'warning')->modal($this->reset($theme)));

		return $theme->controller('admin')->index();
	}

	public function reset($theme)
	{
		return $this->modal('Réinstaller par défaut', 'fas fa-sync')
					->body($this->lang('Êtes-vous sûr(e) de vouloir réinstaller le thème <b>%s</b> ?<br />Toutes les dispositions et configurations de widgets seront perdues.', $theme->info()->title))
					->submit('Réinstaller', 'warning')
					->cancel()
					->callback(function() use ($theme){
						$theme->reset();
						notify($this->lang('Thème %s réinstallé par défaut', $theme->info()->title));
						refresh();
					});
	}

	public function delete($theme)
	{
		return $this->modal('Supprimer le thème', 'fas fa-trash')
					->body($this->lang('Supprimer définitivement le thème <b>%s</b> ?<br />Ses dispositions, sa configuration et ses fichiers seront retirés. Cette action est irréversible.', $theme->info()->title))
					->submit('Supprimer', 'danger')
					->cancel()
					->callback(function() use ($theme){
						$title = $theme->info()->title;
						$name  = $theme->info()->name;

						$theme->uninstall();

						(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('theme.deleted', [
							'target_type' => 'theme',
							'target_id'   => $name,
							'details'     => $title
						]);

						notify($this->lang('Thème %s supprimé', $title));
						refresh();
					});
	}
}
