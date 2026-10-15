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
					}])
					// Décidé le 2026-10-15 : un module se vide ou se retire depuis sa carte, une sauvegarde
					// prise juste avant. Pas l'infrastructure (is_deactivatable()), ni le cœur pour la désinstallation.
					->set('reset', [$this->lang('Remettre à zéro'), 'fas fa-eraser', 'warning', TRUE, function($addon){
						return $addon->is_deactivatable() && $addon->tables_propres();
					}])
					->set('delete', [$this->lang('Désinstaller'), 'fas fa-trash', 'danger', TRUE, function($addon){
						return $addon->is_removable();
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

	/** Le nombre de lignes de ses tables : ce que la confirmation annonce comme perdu. */
	private function _lignes($addon): int
	{
		$total = 0;

		foreach ($addon->tables_propres() as $table)
		{
			if ($this->db->table_exists($table))
			{
				$total += (int) $this->db->from($table)->count();
			}
		}

		return $total;
	}

	public function reset($addon)
	{
		return $this->modal($this->lang('Remettre à zéro'), 'fas fa-eraser')
					->body($this->lang('Remettre le module <b>%s</b> à zéro ?<br />Ses données sont effacées (%d ligne(s)) et il repart comme au jour de son installation. Ses réglages, ses permissions et les liens qui y mènent restent.<br />Une sauvegarde de ses tables est prise juste avant, dans le dossier backups/addons du serveur.', $addon->info()->title, $this->_lignes($addon)))
					->submit($this->lang('Remettre à zéro'), 'warning')
					->cancel()
					->callback(function() use ($addon){
						if (($sauvegarde = $addon->sauvegarder(FALSE)) === NULL)
						{
							notify($this->lang('La sauvegarde n’a pas pu être écrite dans le dossier %s : vérifiez ses droits d’écriture et l’espace disque.', 'backups'), 'danger');
							return refresh();
						}

						$addon->remettre_a_zero();

						notify($this->lang('<b>%s</b> remis à zéro. Sauvegarde : %s', $addon->info()->title, basename($sauvegarde)));
						refresh();
					});
	}

	public function delete($addon)
	{
		$modal = $this->modal($this->lang('Désinstaller'), 'fas fa-trash');

		// Un module dont un autre a besoin ne part pas : les pages de l'autre tomberaient.
		if ($dependants = $addon->dependants())
		{
			return $modal	->body($this->lang('Le module <b>%s</b> ne peut pas être désinstallé : %s en ont besoin. Désinstallez-les d’abord.', $addon->info()->title, implode(', ', array_map('nf_texte', $dependants))))
							->cancel();
		}

		return $modal	->body($this->lang('Désinstaller le module <b>%s</b> ?<br />Ses données (%d ligne(s)) et ses fichiers sont retirés. Les liens de menu qui y mènent restent : retirez-les dans Menus.<br />Une sauvegarde de ses tables et de ses fichiers est prise juste avant, dans le dossier backups/addons du serveur.', $addon->info()->title, $this->_lignes($addon)))
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
}
