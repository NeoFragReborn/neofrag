<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Events\Models;

use NF\NeoFrag\Loadables\Model;

class Types extends Model
{
	public function check_type($type_id, $title)
	{
		$type = $this->lu()->where('t.type_id', $type_id)->row();

		if ($type && $title == url_title($type['title']))
		{
			return $type;
		}
	}

	/**
	 * La requête de base : le type AVEC son nom dans la meilleure langue.
	 *
	 * Le nom vit dans `nf_events_types_lang` depuis m32 (2026-10-15) : le lire dans `nf_events_types`
	 * rendrait NULL partout, sans un mot.
	 */
	private function lu()
	{
		return $this->db	->select('t.*', 'tl.title')
							->from('nf_events_types t')
							->join_lang('nf_events_types_lang tl', 'type_id', 't.type_id', NULL, 'LEFT');
	}

	/** Le nom TEL QU'IL EST ÉCRIT dans une langue, sans repli : c'est ce que l'administration édite. */
	public function titre_dans(int $type_id, string $langue): ?string
	{
		$titre = $this->db	->select('title')
							->from('nf_events_types_lang')
							->where('type_id', $type_id)
							->where('lang', $langue)
							->row();

		return is_string($titre) ? $titre : NULL;
	}

	/** Écrit le nom dans une langue ; `ecrire_lang` CRÉE la ligne quand elle manque (leçon de m30). */
	public function ecrire_titre(int $type_id, string $titre, ?string $langue = NULL): void
	{
		$this->db->ecrire_lang('nf_events_types_lang',
			['type_id' => $type_id, 'lang' => (string) ($langue ?: $this->config->lang->info()->name)],
			['title' => $titre]);
	}

	public function get_types()
	{
		static $types;

		if ($types === NULL)
		{
			$types = [];

			foreach ($this->lu()->order_by('tl.title')->get() as $type)
			{
				if ($this->access('events', 'access_events_type', $type['type_id']))
				{
					$types[$type['type_id']] = $type;
				}
			}
		}

		return $types;
	}

	public function get_types_list()
	{
		// Sans les modules Jeux et Équipes, pas de match à créer (m17).
		return \NF\Modules\Events\Models\Matches::possibles() ? [$this->lang('Standard'), $this->lang('Match')] : [$this->lang('Standard')];
	}

	public function add($type, $title, $color, $icon)
	{
		$type_id = $this->db->insert('nf_events_types', [
			'type'  => $type,
			'color' => $color,
			'icon'  => $icon
		]);

		$this->ecrire_titre((int) $type_id, (string) $title);

		$this->access->init('events', 'type', $type_id);

		return $type_id;
	}

	public function edit($type_id, $type, $title, $color, $icon)
	{
		$this->db	->where('type_id', $type_id)
					->update('nf_events_types', [
						'type'  => $type,
						'color' => $color,
						'icon'  => $icon
					]);

		$this->ecrire_titre((int) $type_id, (string) $title);
	}

	public function delete($type_id)
	{
		$this->db	->where('type_id', $type_id)
					->delete('nf_events_types');
	}
}
