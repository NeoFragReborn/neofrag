<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Members\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index($page = '')
	{
		return [$this->module('user')->collection('user')->where('deleted', FALSE)->where('_.id !=', nf_compte_masque())->order_by('username')->paginate($page, 24)];
	}

	public function _group()
	{
		$args = func_get_args();
		$page = array_pop($args);

		// Un groupe caché ne se liste pas, sauf pour un administrateur : sa page donnait tous ses membres à quiconque en
		// connaissait l'adresse (audit du 2026-10-09).
		// Une adresse au mauvais nom — le groupe renommé depuis — mène à la bonne au lieu de répondre 404 (m06) ; pas pour un
		// groupe caché, dont le nom ne se donne qu'à un administrateur.
		if (!$this->groups->check_group($args) && ($adresse = $this->groups->adresse_du_groupe($args)) && (!$adresse[2] || $this->access->effective_admin()))
		{
			nf_bon_titre((string) end($args), $adresse[1], $adresse[0], (string) $page);
		}

		if (($group = $this->groups->check_group($args)) && $group['users'] && (empty($group['hidden']) || $this->access->effective_admin()))
		{
			return [$group['title'], $this->module('user')->collection('user')->where('id', $group['users'])->where('deleted', FALSE)->where('_.id !=', nf_compte_masque())->order_by('username')->paginate($page, 24)];
		}
	}
}
