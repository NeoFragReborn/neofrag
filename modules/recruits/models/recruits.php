<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Recruits\Models;

use NF\NeoFrag\Loadables\Model;

class Recruits extends Model
{
	public function get_recruits()
	{
		$this->db	->select('r.*', 'rl.title', 'rl.introduction', 'rl.description', 'rl.requierments', 'u.id as user_id', 'u.username', 'up.avatar', 'up.sex', 'COUNT(DISTINCT rc.candidacy_id) as candidacies', 'COUNT(DISTINCT CASE WHEN rc.status = \'1\' THEN rc.candidacy_id END) as candidacies_pending', 'COUNT(DISTINCT CASE WHEN rc.status = \'2\' THEN rc.candidacy_id END) as candidacies_accepted', 'COUNT(DISTINCT CASE WHEN rc.status = \'3\' THEN rc.candidacy_id END) as candidacies_declined', 'tl.title as team_name')
					->from('nf_recruits r')
					->join_lang('nf_recruits_lang rl', 'recruit_id', 'r.recruit_id', NULL, 'LEFT')
					->join('nf_user u',                  'r.user_id     = u.id')
					->join('nf_user_profile up',         'up.id         = u.id')
					// LEFT : ne sert qu'a COMPTER les candidatures. En stricte, un recrutement SANS
					// candidature disparaissait — c'est-a-dire tout recrutement qui vient d'etre ouvert.
					->join('nf_recruits_candidacies rc', 'rc.recruit_id = r.recruit_id', 'LEFT')
					->join_lang('nf_teams_lang tl', 'team_id', 'r.team_id')
					->group_by('r.recruit_id')
					->order_by('r.date DESC');

		if (!$this->url->admin)
		{
			$this->db->where('r.closed', FALSE);
		}

		return $this->db->get();
	}

	public function check_recruit($recruit_id, $title)
	{
		$this->db	->select('r.*', 'rl.title', 'rl.introduction', 'rl.description', 'rl.requierments', 'u.id as user_id', 'u.username', 'up.avatar', 'up.sex', 'COUNT(DISTINCT rc.candidacy_id) as candidacies', 'COUNT(DISTINCT CASE WHEN rc.status = \'1\' THEN rc.candidacy_id END) as candidacies_pending', 'COUNT(DISTINCT CASE WHEN rc.status = \'2\' THEN rc.candidacy_id END) as candidacies_accepted', 'COUNT(DISTINCT CASE WHEN rc.status = \'3\' THEN rc.candidacy_id END) as candidacies_declined', 'tl.title as team_name')
					->from('nf_recruits r')
					->join_lang('nf_recruits_lang rl', 'recruit_id', 'r.recruit_id', NULL, 'LEFT')
					->join('nf_user u',                  'r.user_id     = u.id')
					->join('nf_user_profile up',         'up.id         = u.id')
					// LEFT : ne sert qu'a COMPTER les candidatures. En stricte, un recrutement SANS
					// candidature disparaissait — c'est-a-dire tout recrutement qui vient d'etre ouvert.
					->join('nf_recruits_candidacies rc', 'rc.recruit_id = r.recruit_id', 'LEFT')
					->join_lang('nf_teams_lang tl', 'team_id', 'r.team_id')
					->group_by('r.recruit_id')
					->where('r.recruit_id', $recruit_id);

		if (!$this->url->admin)
		{
			$this->db->where('r.closed', FALSE);
		}

		$recruit = $this->db->row();

		if ($recruit && url_title($recruit['title']) == $title)
		{
			return $recruit;
		}
		else
		{
			return FALSE;
		}
	}

	public function add_recruits($title, $introduction, $description, $requierments, $size, $role, $icon, $date_end, $closed, $team_id, $image_id)
	{
		$recruit_id = $this->db->insert('nf_recruits', [
			'user_id'      => $this->user->id,
			'size'         => $size,
			'role'         => $role,
			'icon'         => $icon,
			'date_end'     => $date_end ?: NULL,
			'closed'       => $closed,
			'team_id'      => $team_id ?: NULL,
			'image_id'     => $image_id ?: NULL
		]);

		$this->ecrire_texte((int) $recruit_id, $title, $introduction, $description, $requierments);

		$this->access->init('recruits', 'recruit', $recruit_id);
	}

	/*
	 * Le titre, l'accroche, la description et les prérequis d'une offre se traduisent (m32, 2026-10-15). Les lectures
	 * servent la meilleure traduction (la langue demandée, sinon le français, sinon la première écrite) ; l'écran
	 * d'édition ouvre le texte TEL QU'IL EST ÉCRIT dans la langue de l'administration, vide si rien n'y est écrit.
	 */
	public function texte_dans(int $recruit_id, string $langue): array
	{
		$ecrit = $this->db	->select('title', 'introduction', 'description', 'requierments')
							->from('nf_recruits_lang')
							->where('recruit_id', $recruit_id)
							->where('lang', $langue)
							->row();

		return [
			'title'        => is_array($ecrit) ? (string) ($ecrit['title'] ?? '') : '',
			'introduction' => is_array($ecrit) ? (string) ($ecrit['introduction'] ?? '') : '',
			'description'  => is_array($ecrit) ? (string) ($ecrit['description'] ?? '') : '',
			'requierments' => is_array($ecrit) ? (string) ($ecrit['requierments'] ?? '') : ''
		];
	}

	/** `ecrire_lang` CRÉE la ligne quand elle manque (leçon de m30). */
	public function ecrire_texte(int $recruit_id, $title, $introduction, $description, $requierments, ?string $langue = NULL): void
	{
		$this->db->ecrire_lang('nf_recruits_lang',
			['recruit_id' => $recruit_id, 'lang' => (string) ($langue ?: $this->config->lang->info()->name)], [
				'title'        => (string) $title,
				'introduction' => (string) $introduction,
				'description'  => (string) $description,
				'requierments' => (string) $requierments
			]);
	}

	public function get_teams_list()
	{
		$list = [];

		foreach ($this->db	->select('t.team_id', 't.name', 'tl.title')
							->from('nf_teams t')
							->join_lang('nf_teams_lang tl', 'team_id', 't.team_id')
							->order_by('tl.title')
							->get() as $team)
		{
			$list[$team['team_id']] = $team['title'];
		}

		natsort($list);

		return $list;
	}

	public function edit_recruits($recruit_id, $title, $introduction, $description, $requierments, $size, $role, $icon, $date_end, $closed, $team_id, $image_id)
	{
		$this->db	->where('recruit_id', $recruit_id)
					->update('nf_recruits', [
						'size'         => $size,
						'role'         => $role,
						'icon'         => $icon,
						'date_end'     => $date_end ?: NULL,
						'closed'       => $closed,
						'team_id'      => $team_id ?: NULL,
						'image_id'     => $image_id ?: NULL
					]);

		$this->ecrire_texte((int) $recruit_id, $title, $introduction, $description, $requierments);
	}

	public function delete_recruit($recruit_id)
	{
		NeoFrag()->model2('file', $this->db->select('image_id')->from('nf_recruits')->where('recruit_id', $recruit_id)->row())->delete();

		$this->db	->where('recruit_id', $recruit_id)
					->delete('nf_recruits');
	}

	public function get_candidacies($recruit_id = '', $status = '')
	{
		$this->db	->select('rc.*', 'u.id as user_id', 'u.username', 'up.avatar', 'up.sex', 'rl.title')
					->from('nf_recruits_candidacies rc')
					->join('nf_recruits r',      'rc.recruit_id = r.recruit_id')
					->join_lang('nf_recruits_lang rl', 'recruit_id', 'r.recruit_id', NULL, 'LEFT')
					->join('nf_user u',          'rc.user_id    = u.id')
					->join('nf_user_profile up', 'up.id         = u.id')
					->order_by('rc.date DESC');

		if ($recruit_id)
		{
			$this->db->where('rc.recruit_id', $recruit_id);
		}

		if ($status)
		{
			$this->db->where('rc.status', $status);
		}

		return $this->db->get();
	}

	public function send_candidacy($recruit_id, $user_id, $pseudo, $email, $date_of_birth, $presentation, $motivations, $experiences, $custom = NULL)
	{
		$candidacy_id = $this->db->insert('nf_recruits_candidacies', [
			'recruit_id' => $recruit_id,
			'user_id' => $user_id,
			'pseudo' => $pseudo,
			'email' => $email,
			'date_of_birth' => $date_of_birth,
			'presentation' => $presentation,
			'motivations' => $motivations,
			'experiences' => $experiences,
			'custom' => $custom
		]);

		return $candidacy_id;
	}

	// ---- Personnalisation du formulaire : champs custom par offre ----

	// Le libellé d'une question se traduit (m32, 2026-10-15) ; son type, son obligation et son ordre sont communs. Les
	// RÉPONSES d'un candidat gardent le libellé qu'il a lu : elles n'ont jamais eu de langue.
	public function get_fields($recruit_id)
	{
		return $this->db	->select('f.field_id', 'f.recruit_id', 'fl.label', 'f.type', 'f.required', 'f.sort_order')
							->from('nf_recruits_fields f')
							->join_lang('nf_recruits_fields_lang fl', 'field_id', 'f.field_id', NULL, 'LEFT')
							->where('f.recruit_id', (int)$recruit_id)
							->order_by('f.sort_order ASC, f.field_id ASC')
							->get();
	}

	public function get_field($field_id)
	{
		return $this->db	->select('f.field_id', 'f.recruit_id', 'fl.label')
							->from('nf_recruits_fields f')
							->join_lang('nf_recruits_fields_lang fl', 'field_id', 'f.field_id', NULL, 'LEFT')
							->where('f.field_id', (int)$field_id)
							->row();
	}

	/** Une question, son libellé TEL QU'IL EST ÉCRIT dans une langue (vide si rien n'y est écrit) : ce qu'on édite. */
	public function champ_dans(int $field_id, string $langue): array
	{
		$champ = $this->db	->select('type', 'required')
							->from('nf_recruits_fields')
							->where('field_id', $field_id)
							->row();

		$label = $this->db	->select('label')
							->from('nf_recruits_fields_lang')
							->where('field_id', $field_id)
							->where('lang', $langue)
							->row();

		return [
			'type'     => is_array($champ) ? (string) $champ['type'] : 'text',
			'required' => is_array($champ) && !empty($champ['required']),
			'label'    => is_string($label) ? $label : ''
		];
	}

	public function edit_field(int $field_id, string $label, string $type, bool $required, ?string $langue = NULL): void
	{
		$this->db	->where('field_id', $field_id)
					->update('nf_recruits_fields', [
						'type'     => in_array($type, ['text', 'textarea'], TRUE) ? $type : 'text',
						'required' => $required ? 1 : 0
					]);

		$this->db->ecrire_lang('nf_recruits_fields_lang',
			['field_id' => $field_id, 'lang' => (string) ($langue ?: $this->config->lang->info()->name)],
			['label' => $label]);
	}

	public function add_field($recruit_id, $label, $type, $required)
	{
		$row = $this->db	->select('IFNULL(MAX(sort_order), 0) AS m')
							->from('nf_recruits_fields')
							->where('recruit_id', (int)$recruit_id)
							->row();

		$field_id = (int) $this->db->insert('nf_recruits_fields', [
			'recruit_id' => (int)$recruit_id,
			'type'       => in_array($type, ['text', 'textarea'], TRUE) ? $type : 'text',
			'required'   => $required ? 1 : 0,
			'sort_order' => (int)$row['m'] + 1
		]);

		$this->db->ecrire_lang('nf_recruits_fields_lang',
			['field_id' => $field_id, 'lang' => (string) $this->config->lang->info()->name],
			['label' => $label]);

		return $field_id;
	}

	public function delete_field($field_id, $recruit_id)
	{
		$this->db	->where('field_id', (int)$field_id)
					->where('recruit_id', (int)$recruit_id)
					->delete('nf_recruits_fields');
	}

	public function get_candidacy_custom($candidacy_id)
	{
		// Une requête à UNE colonne rend la valeur, pas la ligne (`Db::row()`) : lire `['custom']` sur le
		// JSON lui-même levait une TypeError sous PHP 8 dès qu'une candidature portait des champs
		// personnalisés.
		$custom = $this->db->select('custom')->from('nf_recruits_candidacies')->where('candidacy_id', (int)$candidacy_id)->row();
		$data   = is_string($custom) && $custom !== '' ? json_decode($custom, TRUE) : NULL;

		// La vue attend une liste `['label' => …, 'value' => …]` — la forme qu'écrit le formulaire de
		// candidature. Une réponse enregistrée autrement (le semoir de démonstration écrivait
		// `libellé => valeur`) est ramenée à cette forme au lieu de faire planter la page.
		$reponses = [];

		foreach (is_array($data) ? $data : [] as $cle => $valeur)
		{
			if (is_array($valeur) && isset($valeur['label']))
			{
				$reponses[] = ['label' => (string)$valeur['label'], 'value' => (string)($valeur['value'] ?? '')];
			}
			else if (is_string($cle) && is_scalar($valeur))
			{
				$reponses[] = ['label' => $cle, 'value' => (string)$valeur];
			}
		}

		return $reponses;
	}

	public function check_candidacy($candidacy_id, $title)
	{
		$candidacy = $this->db	->select('rc.*', 'r.recruit_id', 'rl.title', 'r.icon', 'r.role', 'r.team_id', 'tl.title as team_name', 'u.username', 'up.avatar', 'up.sex')
								->from('nf_recruits_candidacies rc')
								->join('nf_recruits r',        'rc.recruit_id = r.recruit_id')
								->join_lang('nf_recruits_lang rl', 'recruit_id', 'r.recruit_id', NULL, 'LEFT')
								->join_lang('nf_teams_lang tl', 'team_id', 'r.team_id')
								->join('nf_user u',            'rc.user_id    = u.id')
								->join('nf_user_profile up',   'up.id         = u.id')
								->where('rc.candidacy_id', $candidacy_id)
								->row();

		if ($candidacy && url_title($candidacy['title']) == $title)
		{
			return $candidacy;
		}

		return FALSE;
	}

	public function update_candidacy($candidacy_id, $reply, $status)
	{
		$this->db	->where('candidacy_id', $candidacy_id)
					->update('nf_recruits_candidacies', [
						'reply'  => $reply,
						'status' => $status
					]);
	}

	public function delete_candidacy($candidacy_id)
	{
		$this->db	->where('candidacy_id', $candidacy_id)
					->delete('nf_recruits_candidacies');
	}

	public function postulated($user_id, $recruit_id, $title)
	{
		$candidacy = $this->db	->select('rc.candidacy_id', 'rc.recruit_id', 'rc.date', 'rc.user_id', 'rc.status', 'rl.title')
								->from('nf_recruits_candidacies rc')
								->join('nf_recruits r', 'rc.recruit_id = r.recruit_id')
								->join_lang('nf_recruits_lang rl', 'recruit_id', 'r.recruit_id', NULL, 'LEFT')
								->where('rc.user_id', $user_id)
								->where('rc.recruit_id', $recruit_id)
								->where('rl.title', $title);

		$candidacy = $this->db->row();

		if ($candidacy && $candidacy['title'] == $title)
		{
			return $candidacy;
		}

		return FALSE;
	}

	public function get_votes($candidacy_id)
	{
		return $this->db->select('rcv.*', 'u.username', 'up.avatar', 'up.sex')
						->from('nf_recruits_candidacies_votes rcv')
						->join('nf_user u',            'u.id   = rcv.user_id')
						->join('nf_user_profile up',   'up.id  = u.id')
						->where('rcv.candidacy_id', $candidacy_id)
						->get();
	}

	public function send_vote($candidacy_id, $vote, $comment)
	{
		$this->db->insert('nf_recruits_candidacies_votes', [
			'candidacy_id' => $candidacy_id,
			'user_id'      => $this->user->id,
			'vote'         => $vote,
			'comment'      => $comment
		]);
	}

	public function update_vote($user_id, $candidacy_id, $vote, $comment)
	{
		$this->db	->where('candidacy_id', $candidacy_id)
					->where('user_id', $user_id)
					->update('nf_recruits_candidacies_votes', [
						'vote'    => $vote,
						'comment' => $comment
					]);
	}

	public function check_team($team_id, $name)
	{
		return $this->db->select('t.team_id', 't.name', 'tl.title', 't.image_id', 't.icon_id', 'tl.description', 't.game_id', 'gl.title as game', 'g.icon_id as game_icon')
						->from('nf_teams t')
						->join_lang('nf_teams_lang tl', 'team_id', 't.team_id')
						->join('nf_games g',       'g.game_id = t.game_id')
						->join_lang('nf_games_lang gl', 'game_id', 'g.game_id')
						->where('t.team_id', $team_id)
						->where('t.name', $name)
						->row();
	}

	public function check_role($title)
	{
		// Le nom d'un rôle vit dans `nf_teams_roles_lang` depuis m32 : on le cherche dans TOUTES les
		// langues, sinon une candidature ouverte en anglais ne retrouverait pas son rôle.
		$role = $this->db	->select('r.role_id', 'rl.title')
							->from('nf_teams_roles r')
							->join('nf_teams_roles_lang rl', 'rl.role_id = r.role_id')
							->where('rl.title', $title)
							->group_by('r.role_id')
							->row();

		if ($role && $title == $role['title'])
		{
			return $role;
		}

		return FALSE;
	}
}
