<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Addons\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		$addons = array_filter(NeoFrag()->collection('addon')->get(), function($addon){
			$object = $addon->addon();

			if ($object && ($controller = $addon->controller()))
			{
				$actions = $object->__actions = $controller->__actions()->filter(function($action) use ($object){
					return !isset($action[4]) || $action[4]($object);
				});

				return !$actions->empty();
			}
		});

		$types = array_count_values(array_map(function($a){
			return $a->type->id;
		}, $addons));

		usort($addons, function($a, $b) use ($types){
			if ($types[$a->type->id] > $types[$b->type->id])
			{
				return 1;
			}
			else if ($types[$a->type->id] < $types[$b->type->id])
			{
				return -1;
			}
			else
			{
				return str_nat($a, $b, function($a){
					return $a->type->name.$a->addon()->info()->title;
				});
			}
		});

		$this->add_action($this->button($this->lang('Scanner le disque'), 'fas fa-sync', 'secondary')->modal_ajax('admin/ajax/addons/scan'));
		$this->add_action($this->button($this->lang('Mises à jour'), 'fas fa-arrow-up', 'secondary')->modal_ajax('admin/ajax/addons/updates'));
		$this->add_action($this->button($this->lang('Marketplace'), 'fas fa-store', 'secondary')->modal_ajax('admin/ajax/addons/marketplace'));
		$this->add_action($this->button($this->lang('Ajouter'), 'fas fa-plus', 'primary')->modal_ajax('admin/ajax/addons/install'));

		return $this->module('settings')->controller('admin')->_layout(function($col) use ($addons){
			$col->append($this	->js('mixitup.min')
								->js('addons')
								->css('addons')
								->view('admin', [
									'addons' => $addons,
									'csrf'   => $this->csrf_token()
								])
			);
		});
	}

	public function help($controller, $method)
	{
		return $this->modal('Aide', 'far fa-life-ring')
					->large()
					->body(call_user_func([$controller, $method]))
					->close();
	}

	public function _action($addon, $controller, $action)
	{
		// Les actions qui mutent (activer/désactiver, ordre, reset/suppression de thème)
		// exigent le jeton porté par les liens du panneau Addons.
		//
		// Pas l'envoi qui SUIT le lien de trois d'entre elles : la confirmation de « Réinstaller par défaut » et de
		// « Supprimer », le glisser-déposer d'« Ordre ». Il part en AJAX vers l'adresse nue, sans le jeton du lien. La
		// fenêtre de confirmation porte le sien (form2, champ `_`), qu'elle vérifie avant d'agir ; le glisser-déposer,
		// l'en-tête X-Requested-With, qu'une page d'un autre site ne peut pas poser. Leur demander ici le jeton du lien
		// les refusait tous — « jeton de sécurité invalide » : un thème n'était jamais réinstallé ni supprimé depuis ce
		// panneau, l'ordre des langues et des authentificateurs jamais enregistré (relevé sur l'atelier le 2026-10-08).
		$suite = in_array($action, ['order', 'reset', 'delete'], TRUE)
			&& $this->url->ajax_header
			&& strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';

		if (in_array($action, ['enable', 'disable', 'order', 'reset', 'delete'], TRUE) && !$suite)
		{
			$this->check_csrf('admin/addons');
		}

		return $controller->$action($addon, $this);
	}
}
