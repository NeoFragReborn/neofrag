<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Addons\Controllers\Addons;

use NF\NeoFrag\Loadables\Controller;

class Widget extends Controller
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

		$this->__label = [$this->lang('Widgets'), $this->lang('Widget'), 'fas fa-cube', 'warning'];
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
					->set('delete', [$this->lang('Désinstaller'), 'fas fa-trash', 'danger', TRUE, function($addon){
						return $addon->is_removable();
					}]);
	}

	public function delete($addon)
	{
		return $this->modal($this->lang('Désinstaller'), 'fas fa-trash')
					->body($this->lang('Désinstaller le widget <b>%s</b> ?<br />Ses fichiers sont retirés ; une copie est gardée dans le dossier backups/addons du serveur. Là où il est placé, il ne s’affiche plus — et revient si vous le réinstallez.', $addon->info()->title))
					->submit($this->lang('Désinstaller'), 'danger')
					->cancel()
					->callback(function() use ($addon){
						$titre = $addon->info()->title;

						if (($sauvegarde = $addon->sauvegarder(TRUE)) === NULL)
						{
							notify($this->lang('La sauvegarde n’a pas pu être écrite dans le dossier %s : vérifiez ses droits d’écriture et l’espace disque.', 'backups'), 'danger');
							return refresh();
						}

						$addon->uninstall();

						notify($this->lang('<b>%s</b> désinstallé. Sauvegarde : %s', $titre, basename($sauvegarde)));
						refresh();
					});
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
}
