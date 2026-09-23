<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Addons\Controllers\Addons;

use NF\NeoFrag\Loadables\Controller;

class Theme extends Controller
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

		$this->__label = [$this->lang('Thèmes'), $this->lang('Thème'), 'fas fa-tint', 'success'];
	}

	public function __actions()
	{
		return $this->array
					->set('enable',    [$this->lang('Activer'), 'fas fa-check', 'success', TRUE, function($addon){
						return $addon->info()->name != 'admin' && !$addon->is_enabled();
					}])
					->set('customize', [$this->lang('Personnaliser'), 'fas fa-paint-brush', 'info', FALSE, function($addon){
						return $addon->info()->name != 'admin' && @$addon->controller('admin');
					}])
					->set('reset',     [$this->lang('Réinstaller par défaut'), 'fas fa-sync', 'warning', TRUE, function($addon){
						return $addon->info()->name != 'admin';
					}])
					->set('delete',    [$this->lang('Supprimer'), 'fas fa-trash', 'danger', TRUE, function($addon){
						// is_removable() plutot qu'un nom code en dur : protege le back-office `admin`,
						// mais AUSSI `nebula` (seul theme public livre) et `vitrine` (notre propre site),
						// que l'ancien test laissait supprimables des lors qu'ils n'etaient pas actifs.
						return $addon->is_removable() && !$addon->is_enabled();
					}]);
	}

	public function enable($addon)
	{
		$this->config('nf_default_theme', $addon->info()->name);

		// Changer le thème par défaut invalide les préférences visiteur (cookie nf_theme) POSÉES AVANT ce
		// changement : on incrémente une « époque ». Le sélecteur de thème capture l'époque courante au
		// moment du choix ; à la résolution (output.php), un cookie d'époque périmée est ignoré et effacé
		// → le visiteur qui n'a pas re-choisi depuis suit le nouveau défaut. C'est l'admin qui décide.
		$this->config('nf_theme_epoch', (int) $this->config->nf_theme_epoch + 1);

		// Un thème fraîchement activé n'a AUCUNE mise en page : l'installation initiale (install_complete)
		// se contente d'enregistrer l'addon, elle ne lance pas son install(). Sans ce filet, activer un
		// thème afficherait un site VIDE (zones sans widgets) jusqu'à un « Réinstaller par défaut » manuel.
		// On applique donc sa disposition par défaut (son install()) — mais UNIQUEMENT s'il n'en a aucune,
		// pour ne jamais écraser une personnalisation existante (Live Editor).
		if (!$this->db->select('theme')->from('nf_dispositions')->where('theme', $addon->info()->name)->get())
		{
			$addon->install();
		}

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
					->submit($this->lang('Réinstaller'), 'warning')
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
					->submit($this->lang('Supprimer'), 'danger')
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
