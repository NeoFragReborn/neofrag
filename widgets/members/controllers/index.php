<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Members\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($config = [])
	{
		$members = $this->db->select('id as user_id', 'username', 'registration_date')
							->from('nf_user')
							->where('deleted', FALSE)
							->where('id !=', nf_compte_masque())
							->order_by('registration_date DESC')
							->limit(5)
							->get();

		if (!empty($members))
		{
			// Le widget lit les comptes du cœur et vit sans le module Membres ; seul ce lien mène à sa liste.
			return $this->panel()
						->heading($this->lang('Derniers membres'))
						->body($this->view('index', [
							'members'  => $members
						]), FALSE)
						->footer_if(($liste = $this->module('members')) && $liste->is_enabled(), '<a href="'.url('members').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Liste des membres').'</a>', 'right');
		}
		else
		{
			return $this->panel()
						->heading($this->lang('Derniers membres'))
						->body($this->lang('Aucun membre pour le moment'));
		}
	}

	public function online($config = [])
	{
		list($admins, $members) = $this->_presents();
		$nb_admins  = count($admins);
		$nb_members = count($members);

		$output = $this->array();

		$output->append(
			$this	->panel()
					->heading($this->lang('Qui est en ligne ?'))
					->body($this->view('online', [
						'administrators' => $admins,
						'members'        => $members,
						'nb_admins'      => $nb_admins,
						'nb_members'     => $nb_members,
						'nb_visitors'    => $this->session->current_sessions()->count() - $nb_admins - $nb_members
					]))
		);

		if ($nb_admins)
		{
			$output->append(
				$this->view('online_modal', [
					'name'  => 'administrators',
					'title' => $this->lang('Administrateurs en ligne'),
					'users' => $admins
				])
			);
		}

		if ($nb_members)
		{
			$output->append(
				$this->view('online_modal', [
					'name'  => 'members',
					'title' => $this->lang('Membres en ligne'),
					'users' => $members
				])
			);
		}

		return $output;
	}

	/**
	 * « Qui est en ligne ? (liste) » : les mêmes nombres, puis les présents eux-mêmes, avec leur avatar — les
	 * administrateurs d'abord, puis les membres (douze au plus ; au-delà, « et N autres »). Écrit pour le panneau
	 * de droite d'Extend 2.0.0 (le « Lanceur »), où la liste reste ouverte sur toutes les pages, et utilisable
	 * partout : sa feuille (css/en-ligne.css) ne suppose aucun thème.
	 */
	public function en_ligne($config = [])
	{
		$this->css('en-ligne');

		list($admins, $members) = $this->_presents();
		$nb_admins  = count($admins);
		$nb_members = count($members);

		return $this->panel()
					->heading($this->lang('En ligne'), 'fas fa-circle')
					->body($this->view('en_ligne', [
						'administrators' => $admins,
						'members'        => array_slice($members, 0, 12),
						'autres'         => max(0, $nb_members - 12),
						'nb_admins'      => $nb_admins,
						'nb_members'     => $nb_members,
						'nb_visitors'    => max(0, $this->session->current_sessions()->count() - $nb_admins - $nb_members)
					]), FALSE);
	}

	/**
	 * Les membres connectés depuis cinq minutes, rangés en deux listes : administrateurs, membres.
	 */
	private function _presents(): array
	{
		$admins = $members = [];

		foreach ($this->db	->select('u.id as user_id', 'u.username', 'u.admin', 'up.avatar', 'up.sex', 'MAX(s.last_activity) AS last_activity')
							->from('nf_session s')
							->join('nf_user         u',  'u.id = s.user_id AND u.deleted = "0"', 'INNER')
							->join('nf_user_profile up', 'u.id = up.id')
							->where('s.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)')
							->where('u.id !=', nf_compte_masque())
							// Un membre qui cache sa présence n'apparaît pas parmi ceux en ligne (chantier A, étape A2).
							->where('IFNULL(up.montrer_statut, 1) = 1')
							->group_by('u.id')
							->order_by('u.username')
							->get() as $user)
		{
			if ($user['admin'])
			{
				$admins[] = $user;
			}
			else
			{
				$members[] = $user;
			}
		}

		return [$admins, $members];
	}

	public function online_mini($config = [])
	{
		return $this->view('online_mini', [
			'members' => $this->session->current_sessions()->count(),
			'align'   => !empty($config['align']) ? $config['align'] : 'float-end'
		]);
	}
}
