<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Forum\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($config = [])
	{
		$messages = $this->_modele()->get_last_messages();

		if (!empty($messages))
		{
			return $this->panel()
						->heading($this->lang('Derniers messages'))
						->body($this->view('index', [
							'messages' => $messages
						]))
						->footer('<a href="'.url('forum').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Accèder au forum').'</a>', 'right');
		}
		else
		{
			return $this->panel()
						->heading($this->lang('Derniers messages'))
						->body($this->lang('Aucun message pour le moment'));
		}
	}

	public function topics($config = [])
	{
		$topics = $this->_modele()->get_last_topics();

		if (!empty($topics))
		{
			return $this->panel()
						->heading($this->lang('Derniers sujets'))
						->body($this->view('topics', [
							'topics' => $topics
						]))
						->footer('<a href="'.url('forum').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Accèder au forum').'</a>', 'right');
		}
		else
		{
			return $this->panel()
						->heading($this->lang('Derniers sujets'))
						->body($this->lang('Aucun sujet pour le moment'));
		}
	}

	public function statistics($config = [])
	{
		// Les forums que le visiteur peut lire seulement : une catégorie réservée à
		// l'équipe ne gonfle pas les chiffres affichés en public.
		$chiffres = $this->_modele()->statistiques_publiques();

		return $this->panel()
					->heading($this->lang('Statistiques'), 'fas fa-signal')
					->body($this->view('statistics', [
						'topics'    => $chiffres['sujets'],
						'messages'  => $chiffres['reponses'],
						'announces' => $chiffres['annonces'],
						'users'     => $chiffres['membres']
					]), FALSE);
	}

	public function activity($config = [])
	{
		$users = $this->db->select('DISTINCT u.id as user_id', 'u.username')->from('nf_session s')->join('nf_user u', 'u.id = s.user_id AND u.deleted = "0"', 'INNER')->where('s.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)')->get();

		array_natsort($users, function($a){
			return $a['username'];
		});

		return $this->panel()
					->heading($this->lang('Activité du forum'), 'fas fa-globe')
					->body($this->view('activity', [
						'users'    => $users,
						'visitors' => $this->db->from('nf_session')->where('user_id', NULL)->where('last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)')->count()
					]));
	}

	/** Le modèle du widget, typé : pour l'analyse statique, `$this->model()` rend un modèle générique. */
	private function _modele(): \NF\Widgets\Forum\Models\Forum
	{
		$modele = $this->model('forum');

		if (!$modele instanceof \NF\Widgets\Forum\Models\Forum)
		{
			throw new \LogicException('modèle du widget forum introuvable');
		}

		return $modele;
	}
}
