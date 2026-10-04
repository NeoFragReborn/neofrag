<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Recruits\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($settings = [])
	{
		// Le réglage « Masquer les offres indisponibles » du module vaut aussi ici : le widget les
		// listait toujours, badge « Clôturée » à l'appui, quand la page des offres les cachait.
		$modele = $this->model('recruits');

		if (!$modele instanceof \NF\Widgets\Recruits\Models\Recruits)
		{
			return;
		}

		$recruits = $this->config->recruits_hide_unavailable
			? array_slice(array_values(array_filter((array) $modele->get_recruits(), static fn ($r): bool => !self::indisponible($r))), 0, 5)
			: $modele->get_last_recruits();

		if (!empty($recruits))
		{
			return $this->panel()
						->heading('Offres de recrutement')
						->body($this->view('index', [
							'recruits' => $recruits
						]), FALSE)
						->footer('<a href="'.url('recruits').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Voir toutes les annonces').'</a>');
		}
		else
		{
			return $this->panel()
						->heading('Recrutement')
						->body($this->lang('Aucune offre pour le moment'));
		}
	}

	/**
	 * Une offre qu'on ne peut plus pourvoir : close, complète, ou passée sa date limite. Même
	 * condition que le module (Recruits\Controllers\Checker::index).
	 *
	 * @param array<string, mixed> $recruit
	 */
	public static function indisponible(array $recruit, ?int $maintenant = NULL): bool
	{
		return !empty($recruit['closed'])
			|| (int) $recruit['candidacies_accepted'] >= (int) $recruit['size']
			|| (!empty($recruit['date_end']) && strtotime((string) $recruit['date_end']) < ($maintenant ?? time()));
	}

	public function recruit($settings = [])
	{
		// Sans réglages — posé par un thème, ou d'une disposition ancienne —, le checker refuse de
		// désigner un élément au hasard : c'est au rendu de s'en tirer (check-widget-contract).
		$recruit = $this->model()->get_recruit($settings['recruit_id'] ?? 0);

		if (!empty($recruit))
		{
			if (!$recruit['closed'] && ($recruit['candidacies_accepted'] < $recruit['size']) && (!$recruit['date_end'] || strtotime($recruit['date_end']) > time()))
			{
				return $this->panel()
							->heading('Recrutement')
							->body($this->view('recruit', [
								'recruit_id'   => $recruit['recruit_id'],
								'title'        => $recruit['title'],
								'introduction' => bbcode(str_shortener($recruit['introduction'], 190)),
								'date'         => $recruit['date'],
								'size'         => $recruit['size'] - $recruit['candidacies_accepted'],
								'role'         => $recruit['role'],
								'icon'         => $recruit['icon'],
								'date_end'     => $recruit['date_end'],
								'team_id'      => $recruit['team_id'],
								'team_name'    => $recruit['team_name'],
								'image_id'     => $recruit['image_id']
							]), FALSE)
							->footer('<a href="'.url('recruits/'.$recruit['recruit_id'].'/'.url_title($recruit['title'])).'">'.icon('far fa-eye').' '.$this->lang('Découvrir l\'offre').'</a>');
			}
		}
		else
		{
			return $this->panel()
						->heading('Recrutement')
						->body($this->lang('Aucune offre pour le moment'));
		}
	}
}
