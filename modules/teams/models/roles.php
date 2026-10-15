<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Teams\Models;

use NF\NeoFrag\Loadables\Model;

class Roles extends Model
{
	public function get_roles()
	{
		// `order` est un mot réservé : le constructeur ne l'entoure de quotes que NU, pas préfixé
		// d'un alias (`escape_keywords`). Préfixer cassait la requête, et la page des équipes rendait
		// 500 (2026-10-15). Aucune des deux tables jointes ne porte `order` : le nom nu suffit.
		return $this->lu()->order_by('order', 'role_id')->get();
	}

	/**
	 * La requête de base : le rôle AVEC son nom dans la meilleure langue.
	 *
	 * Le nom vit dans `nf_teams_roles_lang` depuis m32 (2026-10-15).
	 */
	private function lu()
	{
		return $this->db	->select('r.role_id', 'rl.title')
							->from('nf_teams_roles r')
							->join_lang('nf_teams_roles_lang rl', 'role_id', 'r.role_id', NULL, 'LEFT');
	}

	/** Le nom TEL QU'IL EST ÉCRIT dans une langue, sans repli : c'est ce que l'administration édite. */
	public function titre_dans(int $role_id, string $langue): ?string
	{
		$titre = $this->db	->select('title')
							->from('nf_teams_roles_lang')
							->where('role_id', $role_id)
							->where('lang', $langue)
							->row();

		return is_string($titre) ? $titre : NULL;
	}

	public function check_role($role_id, $title)
	{
		$role = $this->lu()->where('r.role_id', $role_id)->row();

		if ($role && $title == url_title($role['title']))
		{
			return $role;
		}
		else
		{
			return FALSE;
		}
	}

	public function add_role($title)
	{
		// Un INSERT sans colonne n'est pas du SQL valide : le rôle n'a plus que son ordre à poser.
		$role_id = (int) $this->db->insert('nf_teams_roles', ['order' => 0]);

		$this->ecrire_titre($role_id, (string) $title);

		return $role_id;
	}

	public function edit_role($role_id, $title)
	{
		$this->ecrire_titre((int) $role_id, (string) $title);
	}

	/** Écrit le nom dans la langue où l'on travaille ; `ecrire_lang` CRÉE la ligne qui manque (m30). */
	public function ecrire_titre(int $role_id, string $titre, ?string $langue = NULL): void
	{
		$this->db->ecrire_lang('nf_teams_roles_lang',
			['role_id' => $role_id, 'lang' => (string) ($langue ?: $this->config->lang->info()->name)],
			['title' => $titre]);
	}

	public function delete_role($role_id)
	{
		$this->db	->where('role_id', $role_id)
					->delete('nf_teams_roles');
	}
}
