<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Games\Models;

use NF\NeoFrag\Loadables\Model;

class Modes extends Model
{
	public function get_modes($game_id = NULL)
	{
		if ($game_id)
		{
			$this->db->where('m.game_id', $game_id);
		}

		// Le nom d'un mode vit dans `nf_games_modes_lang` depuis m32 (2026-10-15).
		return $this->db->select('m.*', 'ml.title')
						->from('nf_games_modes m')
						->join_lang('nf_games_modes_lang ml', 'mode_id', 'm.mode_id', NULL, 'LEFT')
						->order_by('ml.title')
						->get();
	}

	public function get_modes_list()
	{
		$modes = [];

		foreach ($this->db	->select('m.mode_id', 'ml.title', 'gl.title as game_title', 'gl2.title as game_title2')
							->from('nf_games_modes m')
							->join_lang('nf_games_modes_lang ml', 'mode_id', 'm.mode_id', NULL, 'LEFT')
							->join('nf_games g',        'm.game_id = g.game_id')
							->join_lang('nf_games_lang gl', 'game_id', 'm.game_id')
							->join_lang('nf_games_lang gl2', 'game_id', 'g.parent_id')
							->order_by('If(g.parent_id IS NULL, gl.title, CONCAT(gl2.title, gl.title))', 'ml.title')
							->get() as $mode)
		{
			$modes[$mode['mode_id']] = $mode['title'].' ('.implode(' - ', array_filter([$mode['game_title2'], $mode['game_title']])).')';
		}

		return $modes;
	}

	public function check_mode($mode_id, $title)
	{
		// Le PERMALIEN du jeu (`g.name`), comme `check_map()` : `_modes_edit()` en fait son lien de
		// retour vers `admin/games/{id}/{url_title}`. Le titre traduit qu'on rendait ici
		// (« Counter-Strike 2 ») donnait une adresse avec des espaces, et un 404 (2026-09-22).
		$mode = $this->db	->select('m.mode_id', 'm.game_id', 'ml.title', 'g.name')
							->from('nf_games_modes m')
							->join_lang('nf_games_modes_lang ml', 'mode_id', 'm.mode_id', NULL, 'LEFT')
							->join('nf_games g',        'm.game_id = g.game_id')
							->where('m.mode_id', $mode_id)
							->row();

		if ($mode && url_title($mode['title']) == $title)
		{
			return $mode;
		}
		else
		{
			return FALSE;
		}
	}

	public function add_mode($game_id, $title)
	{
		$mode_id = (int) $this->db->insert('nf_games_modes', [
			'game_id' => $game_id
		]);

		$this->ecrire_titre($mode_id, (string) $title);

		return $mode_id;
	}

	public function edit_mode($mode_id, $title)
	{
		$this->ecrire_titre((int) $mode_id, (string) $title);
	}

	/** Le nom TEL QU'IL EST ÉCRIT dans une langue, sans repli : c'est ce que l'administration édite. */
	public function titre_dans(int $mode_id, string $langue): ?string
	{
		$titre = $this->db	->select('title')
							->from('nf_games_modes_lang')
							->where('mode_id', $mode_id)
							->where('lang', $langue)
							->row();

		return is_string($titre) ? $titre : NULL;
	}

	/** Écrit le nom dans une langue ; `ecrire_lang` CRÉE la ligne quand elle manque (leçon de m30). */
	public function ecrire_titre(int $mode_id, string $titre, ?string $langue = NULL): void
	{
		$this->db->ecrire_lang('nf_games_modes_lang',
			['mode_id' => $mode_id, 'lang' => (string) ($langue ?: $this->config->lang->info()->name)],
			['title' => $titre]);
	}

	public function delete_mode($mode_id)
	{
		$this->db	->where('mode_id', $mode_id)
					->delete('nf_games_modes');
	}
}
