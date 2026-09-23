<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: NeoFrag Reborn
 */

namespace NF\Modules\User\Models;

use NF\NeoFrag\Loadables\Model;

/**
 * Champs de profil définis par l'administrateur.
 *
 * Le profil livré est fixe : seize colonnes de `nf_user_profile`, de l'état civil aux réseaux
 * sociaux. Ce modèle permet d'en ajouter d'autres — pseudo en jeu, plateforme, rang — sans livrer
 * une migration à chaque besoin d'une communauté.
 *
 * Deux règles tiennent tout le reste :
 *
 *  - le **nom technique** d'un champ est immuable après création. C'est la clé du formulaire et de
 *    l'export ; le renommer perdrait le lien avec les valeurs déjà saisies. Seul le libellé change.
 *  - un champ est **privé par défaut**. Rendre public se demande explicitement, parce qu'une donnée
 *    publiée par inadvertance ne se dépublie pas de la mémoire de ceux qui l'ont lue.
 */
class Fields extends Model
{
	/** Les types qu'un administrateur peut choisir, et leur libellé de formulaire. */
	public const TYPES = ['text', 'textarea', 'select', 'radio', 'checkbox', 'url', 'number', 'date'];

	/** Les deux types dont la liste d'options a un sens. */
	public const TYPES_A_OPTIONS = ['select', 'radio'];

	/**
	 * Les définitions, dans l'ordre d'affichage.
	 *
	 * @param bool $publics_seulement ne rendre que ce qui s'affiche sur la fiche publique
	 * @return list<array<string, mixed>>
	 */
	public function get_fields(bool $publics_seulement = FALSE): array
	{
		$requete = $this->db->select('field_id', 'name', 'label', 'description', 'type', 'options', 'required', 'public', 'sort_order')
							->from('nf_user_fields');

		if ($publics_seulement)
		{
			$requete->where('public', TRUE);
		}

		return $requete->order_by('sort_order ASC, field_id ASC')->get();
	}

	/** @return array<string, mixed>|null */
	public function get_field($field_id)
	{
		return $this->db->select('field_id', 'name', 'label', 'description', 'type', 'options', 'required', 'public', 'sort_order')
						->from('nf_user_fields')
						->where('field_id', (int) $field_id)
						->row();
	}

	/**
	 * Le nom technique, dérivé du libellé et rendu unique.
	 *
	 * `url_title()` donne `pseudo-en-jeu` ; on préfère `pseudo_en_jeu`, qui peut servir de clé de
	 * formulaire et de colonne d'export sans échappement. Un suffixe numérique règle les collisions
	 * plutôt que de refuser la création : l'administrateur a choisi son libellé, pas son identifiant.
	 */
	public function nom_disponible(string $label): string
	{
		$base = trim(str_replace('-', '_', url_title($label)), '_') ?: 'champ';
		$base = substr($base, 0, 56);
		$nom  = $base;
		$i    = 2;

		while ($this->db->select('field_id')->from('nf_user_fields')->where('name', $nom)->row())
		{
			$nom = $base.'_'.$i++;
		}

		return $nom;
	}

	/**
	 * Les options d'un `select` ou d'un `radio`, une par ligne à la saisie.
	 *
	 * Rendues telles quelles pour le stockage (une chaîne), et en tableau pour le formulaire. Les
	 * lignes vides sont écartées : une option sans libellé ne se choisit pas.
	 *
	 * @return list<string>
	 */
	public static function options_en_tableau($options): array
	{
		$lignes = preg_split('/\R/', (string) $options) ?: [];

		return array_values(array_filter(array_map('trim', $lignes), 'strlen'));
	}

	/**
	 * Le rang le plus élevé déjà attribué, ou 0.
	 *
	 * `row()` ne rend PAS toujours un tableau : sur une sélection d'une seule colonne calculée, il
	 * rend la valeur elle-même (ici la chaîne « 0 »). Mesuré le 2026-09-20 ; le modèle `recruits`
	 * fait la même requête et lit `$row['m']`, ce qui ne tient que tant que le constructeur se
	 * comporte autrement avec un `where`. On ne parie pas là-dessus : on accepte les deux formes.
	 */
	private function dernier_ordre(): int
	{
		$row = $this->db->select('IFNULL(MAX(sort_order), 0) AS m')->from('nf_user_fields')->row();

		return (int) (is_array($row) ? ($row['m'] ?? 0) : $row);
	}

	public function add_field(string $label, string $type, $options, bool $required, bool $public, string $description = ''): int
	{
		// Les deux lectures se font AVANT l'insertion, et non dans ses arguments.
		//
		// Le constructeur de requêtes est un objet mutable partagé : commencer un `insert()` puis
		// lancer d'autres requêtes pendant l'évaluation de ses arguments — ce que faisait
		// `nom_disponible()` appelé en ligne — mélange les deux états et produit une requête invalide.
		// Symptôme : « Call to a member function fetch_field() on false », dans le pilote, sans que
		// rien ne désigne l'imbrication.
		$nom   = $this->nom_disponible($label);
		$ordre = $this->dernier_ordre();

		return (int) $this->db->insert('nf_user_fields', [
			'name'        => $nom,
			'label'       => $label,
			'description' => $description,
			'type'        => in_array($type, self::TYPES, TRUE) ? $type : 'text',
			'options'     => in_array($type, self::TYPES_A_OPTIONS, TRUE) ? implode("\n", self::options_en_tableau($options)) : NULL,
			'required'    => $required ? 1 : 0,
			'public'      => $public ? 1 : 0,
			'sort_order'  => $ordre + 1,
		]);
	}

	/** Le nom technique n'est jamais touché : il relie la définition aux valeurs déjà saisies. */
	public function edit_field($field_id, string $label, string $type, $options, bool $required, bool $public, string $description = ''): void
	{
		$this->db	->where('field_id', (int) $field_id)
					->update('nf_user_fields', [
						'label'       => $label,
						'description' => $description,
						'type'        => in_array($type, self::TYPES, TRUE) ? $type : 'text',
						'options'     => in_array($type, self::TYPES_A_OPTIONS, TRUE) ? implode("\n", self::options_en_tableau($options)) : NULL,
						'required'    => $required ? 1 : 0,
						'public'      => $public ? 1 : 0,
					]);
	}

	/** Les valeurs partent avec, par la contrainte de clé étrangère. */
	public function delete_field($field_id): void
	{
		$this->db->where('field_id', (int) $field_id)->delete('nf_user_fields');
	}

	public function sort_fields(array $ordre): void
	{
		foreach (array_values($ordre) as $rang => $field_id)
		{
			$this->db	->where('field_id', (int) $field_id)
						->update('nf_user_fields', ['sort_order' => $rang + 1]);
		}
	}

	/**
	 * Les valeurs d'un membre, indexées par nom technique.
	 *
	 * @return array<string, string>
	 */
	public function get_values($user_id): array
	{
		$valeurs = [];

		foreach ($this->db	->select('f.name', 'v.value')
							->from('nf_user_fields_values v')
							->join('nf_user_fields f', 'f.field_id = v.field_id')
							->where('v.user_id', (int) $user_id)
							->get() as $ligne)
		{
			$valeurs[$ligne['name']] = $ligne['value'];
		}

		return $valeurs;
	}

	/**
	 * Les valeurs PUBLIQUES d'un membre, avec leur libellé — ce que la fiche publique affiche.
	 *
	 * @return list<array{label: string, type: string, value: string}>
	 */
	public function get_public_values($user_id): array
	{
		$lignes = $this->db	->select('f.label', 'f.type', 'v.value')
							->from('nf_user_fields_values v')
							->join('nf_user_fields f', 'f.field_id = v.field_id')
							->where('v.user_id', (int) $user_id)
							->where('f.public', TRUE)
							->order_by('f.sort_order ASC, f.field_id ASC')
							->get();

		return array_values(array_filter($lignes, static fn (array $l): bool => trim((string) $l['value']) !== ''));
	}

	/**
	 * Enregistre ce qu'un membre a saisi.
	 *
	 * Une valeur vide EFFACE la ligne plutôt que d'enregistrer une chaîne vide : sans cela, un champ
	 * rendu public plus tard exposerait des lignes vides, et l'export RGPD annoncerait des données
	 * que le membre n'a jamais fournies.
	 *
	 * @param array<string, mixed> $saisies indexées par nom technique
	 */
	public function set_values($user_id, array $saisies): void
	{
		foreach ($this->get_fields() as $champ)
		{
			if (!array_key_exists($champ['name'], $saisies))
			{
				continue;
			}

			$valeur = trim((string) $saisies[$champ['name']]);

			$this->db	->where('field_id', (int) $champ['field_id'])
						->where('user_id', (int) $user_id)
						->delete('nf_user_fields_values');

			if ($valeur !== '')
			{
				$this->db->insert('nf_user_fields_values', [
					'field_id' => (int) $champ['field_id'],
					'user_id'  => (int) $user_id,
					'value'    => $valeur,
				]);
			}
		}
	}
}
