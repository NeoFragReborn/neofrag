<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Games\Models;

use NF\NeoFrag\Loadables\Model;

class Maps extends Model
{
	public function get_maps($game_id = NULL)
	{
		if ($game_id)
		{
			$this->db->where('m.game_id', $game_id);
		}

		// Le nom d'une carte vit dans `nf_games_maps_lang` depuis m32 (2026-10-15).
		return $this->db->select('m.*', 'ml.title', 'g.name', 'g.icon_id', 'gl.title as game_title')
						->from('nf_games_maps m')
						->join_lang('nf_games_maps_lang ml', 'map_id', 'm.map_id', NULL, 'LEFT')
						->join('nf_games g', 'g.game_id = m.game_id')
						->join_lang('nf_games_lang gl', 'game_id', 'm.game_id')
						->order_by('gl.title', 'ml.title')
						->get();
	}

	public function check_map($map_id, $title)
	{
		$map = $this->db->select('m.*', 'ml.title', 'g.name')
						->from('nf_games_maps m')
						->join_lang('nf_games_maps_lang ml', 'map_id', 'm.map_id', NULL, 'LEFT')
						->join('nf_games g', 'g.game_id = m.game_id')
						->where('m.map_id', $map_id)
						->row();

		if ($map && url_title($map['title']) == $title)
		{
			return $map;
		}
		else
		{
			return FALSE;
		}
	}

	public function add_map($game_id, $title, $image)
	{
		$map_id = (int) $this->db->insert('nf_games_maps', [
			'game_id'  => $game_id,
			'image_id' => $image
		]);

		$this->ecrire_titre($map_id, (string) $title);

		return $map_id;
	}

	public function edit_map($map_id, $game_id, $title, $image)
	{
		$this->db	->where('map_id', $map_id)
					->update('nf_games_maps', [
						'game_id'  => $game_id,
						'image_id' => $image
					]);

		$this->ecrire_titre((int) $map_id, (string) $title);
	}

	/** Le nom TEL QU'IL EST ÉCRIT dans une langue, sans repli : c'est ce que l'administration édite. */
	public function titre_dans(int $map_id, string $langue): ?string
	{
		$titre = $this->db	->select('title')
							->from('nf_games_maps_lang')
							->where('map_id', $map_id)
							->where('lang', $langue)
							->row();

		return is_string($titre) ? $titre : NULL;
	}

	/** Écrit le nom dans une langue ; `ecrire_lang` CRÉE la ligne quand elle manque (leçon de m30). */
	public function ecrire_titre(int $map_id, string $titre, ?string $langue = NULL): void
	{
		$this->db->ecrire_lang('nf_games_maps_lang',
			['map_id' => $map_id, 'lang' => (string) ($langue ?: $this->config->lang->info()->name)],
			['title' => $titre]);
	}

	public function delete_map($map_id)
	{
		NeoFrag()->model2('file', $this->db->select('image_id')->from('nf_games_maps')->where('map_id', $map_id)->row())->delete();

		$this->db	->where('map_id', $map_id)
					->delete('nf_games_maps');
	}
}
