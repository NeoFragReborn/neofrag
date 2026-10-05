<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\User\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	/**
	 * Nav locale pour les sous-pages user (Membres / Sessions / Audit log).
	 * Affichée en haut des 3 pages user pour navigation rapide entre elles.
	 */
	private function _user_subnav($active)
	{
		$tabs = [
			['key' => 'index',     'url' => 'admin/user',           'icon' => 'fas fa-users',          'title' => $this->lang('Membres')],
			['key' => 'sessions',  'url' => 'admin/user/sessions',  'icon' => 'fas fa-globe',          'title' => $this->lang('Sessions')],
			['key' => 'audit-log', 'url' => 'admin/user/audit-log', 'icon' => 'fas fa-clipboard-list', 'title' => $this->lang('Journal d\'audit')],
			['key' => 'fields',    'url' => 'admin/user/fields',    'icon' => 'fas fa-list-ul',        'title' => $this->lang('Champs de profil')]
		];

		$html = '<div class="nf-local-nav">';
		foreach ($tabs as $t)
		{
			$is_active = ($t['key'] === $active);
			$html .= '<a class="nf-local-tab'.($is_active ? ' active' : '').'" href="'.url($t['url']).'">';
			$html .= '<i class="'.$t['icon'].'"></i> '.nf_texte($t['title']);
			$html .= '</a>';
		}
		$html .= '</div>';
		return $html;
	}

	public function index($members, $recherche = '')
	{
		$this	->title($this->lang('Membres'))
				->icon('fas fa-users');

		// === Liste groupes en HTML compact ===
		$groups_data = $this->groups();
		$groups_html = '<ul class="nf-groups-list">';
		$groups_count = 0;
		foreach ($groups_data as $group_id => $g)
		{
			// Skip groupes "users" sans users actifs (NULL = pas un vrai groupe)
			if (isset($g['users']) && $g['users'] === NULL) continue;
			$groups_count++;

			$is_auto = !empty($g['auto']);
			$slug    = !empty($g['url']) ? $g['url'] : url_title((string)$group_id);

			$groups_html .= '<li class="nf-group-item">';
			if (!$is_auto)
			{
				$groups_html .= '<button type="button" class="nf-group-drag" data-sort-id="'.nf_texte($group_id).'" data-sort-url="admin/ajax/user/groups/sort" title="'.$this->lang('Glisser pour réordonner').'"><i class="fas fa-grip-vertical"></i></button>';
			}
			else
			{
				$groups_html .= '<span class="nf-group-drag nf-group-drag-disabled"><i class="fas fa-lock"></i></span>';
			}
			$groups_html .= '<span class="nf-group-label">'.NeoFrag()->groups->display($group_id, TRUE, FALSE).'</span>';
			if (!empty($g['hidden']))
			{
				$groups_html .= '<span class="nf-group-hidden" title="'.$this->lang('Groupe caché').'"><i class="far fa-eye-slash"></i></span>';
			}
			$groups_html .= '<span class="nf-group-actions">';
			$groups_html .= '<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/user/groups/edit/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
			if (!$is_auto)
			{
				$groups_html .= '<a class="btn btn-sm btn-outline-danger" href="'.url('admin/user/groups/delete/'.$slug).'" data-confirm="'.nf_texte($this->lang('Supprimer ce groupe ?')).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
			}
			$groups_html .= '</span>';
			$groups_html .= '</li>';
		}
		$groups_html .= '</ul>';

		if ($groups_count === 0)
		{
			$groups_html = $this->admin_empty('fas fa-users-cog', $this->lang('Aucun groupe'));
		}

		// === Cards membres en grid ===
		$members_html = '<div class="nf-members-grid">';
		$count = 0;

		foreach ($members->get() as $user)
		{
			$count++;
			$is_online = $user->is_online();

			// Groupes : primary (premier) + secondaires (le reste)
			$user_group_ids = NeoFrag()->groups($user->id);
			$primary_group  = !empty($user_group_ids) ? $user_group_ids[0] : NULL;
			$secondary_ids  = array_slice($user_group_ids, 1);

			$reg_date = $user->registration_date ? timetostr($this->lang('d/m/Y'), $user->registration_date) : '—';
			$last_act = $user->last_activity_date ? timetostr($this->lang('d/m/Y H:i'), $user->last_activity_date) : '—';

			$members_html .= '<div class="nf-member-card">';

			// Header : avatar + identity + groupe principal
			$members_html .= '<div class="nf-member-card-head">';
			$members_html .= '<div class="nf-member-avatar-wrap">'.$user->avatar().'<span class="nf-member-status '.($is_online ? 'online' : 'offline').'" title="'.($is_online ? $this->lang('En ligne') : $this->lang('Hors ligne')).'"></span></div>';
			$members_html .= '<div class="nf-member-identity">';
			$members_html .= '<div class="nf-member-name">'.$user->link().'</div>';
			$members_html .= '<a class="nf-member-email" href="mailto:'.nf_texte($user->email).'">'.nf_texte($user->email).'</a>';
			$members_html .= '</div>';
			$members_html .= '</div>';

			// Groupe principal + chip "+N" pour les secondaires
			if ($primary_group)
			{
				$members_html .= '<div class="nf-member-groups">';
				$members_html .= NeoFrag()->groups->display($primary_group, TRUE, FALSE);

				if (!empty($secondary_ids))
				{
					$tooltip_lines = [];
					foreach ($secondary_ids as $sid)
					{
						$tooltip_lines[] = strip_tags((string)NeoFrag()->groups->display($sid, TRUE, FALSE));
					}
					$tooltip_text = implode(' • ', $tooltip_lines);
					$members_html .= '<span class="nf-member-groups-more" data-bs-toggle="tooltip" title="'.nf_texte($tooltip_text).'">+'.count($secondary_ids).'</span>';
				}
				$members_html .= '</div>';
			}

			$members_html .= '<div class="nf-member-meta">';
			$members_html .= '<span title="'.$this->lang('Inscrit le').'"><i class="fas fa-calendar-plus"></i> '.$reg_date.'</span>';
			$members_html .= '<span title="'.$this->lang('Dernière activité').'"><i class="far fa-clock"></i> '.$last_act.'</span>';
			if ($user->totp_enabled)
			{
				$members_html .= '<span class="nf-member-2fa" title="'.$this->lang('2FA activé').'"><i class="fas fa-shield-alt"></i> 2FA</span>';
			}
			$members_html .= '</div>';

			// Footer : actions
			$members_html .= '<div class="nf-member-card-foot">';
			$members_html .= '<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/user/'.$user->id.'/'.url_title($user->username)).'" title="'.$this->lang('Modifier').'"><i class="fas fa-edit"></i></a>';
			if ($user->totp_enabled)
			{
				$members_html .= '<a class="btn btn-sm btn-outline-warning" href="'.url('admin/user/totp-reset/'.$user->id.'/'.url_title($user->username)).'" title="'.$this->lang('Réinitialiser le 2FA').'"><i class="fas fa-shield-alt"></i></a>';
			}
			$members_html .= '<a class="btn btn-sm btn-outline-danger" href="'.url('admin/user/delete/'.$user->id.'/'.url_title($user->username)).'" data-confirm="'.nf_texte($this->lang('Supprimer %s ?', $user->username)).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
			$members_html .= '</div>';

			$members_html .= '</div>';
		}

		$members_html .= '</div>';

		// Les liens des pages, après get() qui a posé la limite ; le total vient de la pagination, $count
		// ne compte que la page affichée (la liste s'arrêtait aux 20 premiers, sans lien vers la suite).
		$pagination = (string) $members->pagination->get_pagination();
		$total      = $pagination !== '' ? (int) $members->pagination->count() : $count;

		if ($count === 0)
		{
			$members_html = $recherche !== ''
				? $this->admin_empty('fas fa-search', $this->lang('Aucun membre ne correspond à « %s ».', $recherche))
				: $this->admin_empty('fas fa-users', $this->lang('Aucun membre'));
		}

		$recherche_html = '<form method="get" action="'.url($members->pagination->get_url()).'" class="d-flex gap-2 flex-wrap align-items-center mb-3">'
			.'<input type="search" name="q" value="'.nf_texte($recherche).'" class="form-control form-control-sm" style="max-width:280px;" placeholder="'.nf_texte($this->lang('Rechercher un pseudo ou un e-mail…')).'">'
			.'<button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-search"></i> '.$this->lang('Rechercher').'</button>'
			.($recherche !== '' ? '<a href="'.url('admin/user').'" class="btn btn-sm btn-light"><i class="fas fa-times"></i> '.$this->lang('Réinitialiser').'</a>' : '')
			.'</form>';

		$members_html = $recherche_html.$members_html.($pagination !== '' ? '<div class="d-flex justify-content-center mt-3">'.$pagination.'</div>' : '');

		// Card Groupes (aside)
		$groups_actions = (string)$this->button_create('admin/user/groups/add', $this->lang('Ajouter un groupe'));
		$groups_subtitle = $groups_count.' '.$this->lang('groupe|groupes', $groups_count);
		$groups_card    = $this->admin_card('fas fa-users-cog', $this->lang('Groupes'), $groups_html, $groups_subtitle, $groups_actions);

		// Card Membres (main)
		$count_label  = $total === 1 ? $this->lang('%s membre', $total) : $this->lang('%s membres', $total);
		$members_actions = $this->access->effective_admin()
			? '<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/user/export/csv').'" title="'.$this->lang('Exporter les membres (RGPD)').'"><i class="fas fa-file-csv"></i> CSV</a> '
			 .'<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/user/export/json').'" title="'.$this->lang('Exporter les membres (RGPD)').'"><i class="fas fa-file-code"></i> JSON</a>'
			: '';
		$members_card = $this->admin_card('fas fa-users', $this->lang('Membres'), $members_html, $count_label, $members_actions);

		return $this->_user_subnav('index')
			.'<div class="nf-list-layout">'
				.'<div class="nf-list-aside">'.$groups_card.'</div>'
				.'<div class="nf-list-main">'.$members_card.'</div>'
			.'</div>';
	}

	public function _export($format)
	{
		$members = $this->db	->select('id', 'username', 'email', 'registration_date', 'last_activity_date', 'admin')
								->from('nf_user')
								->where('deleted', '0')
								->where('id !=', nf_compte_masque())
								->order_by('id')
								->get(FALSE);

		$filename = 'neofrag-membres-'.date('Ymd-His').'.'.$format;

		if ($format === 'json')
		{
			$body = json_encode([
				'export_meta' => [
					'generated_at' => date('c'),
					'site'         => $this->config->nf_name,
					'count'        => count($members),
					// lang() rend un objet : json_encode() en écrirait `{}` au lieu du texte.
					'rgpd_notice'  => (string) $this->lang('Export des données membres (RGPD, article 15).'),
				],
				'members' => $members,
			], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

			$mime = 'application/json; charset=utf-8';
		}
		else
		{
			$columns = ['id', 'username', 'email', 'registration_date', 'last_activity_date', 'admin'];

			$out = fopen('php://temp', 'r+');
			// Séparateur, guillemet et échappement explicites : PHP 8.4 déprécie l'échappement implicite.
			fputcsv($out, $columns, ',', '"', '\\');

			foreach ($members as $m)
			{
				fputcsv($out, array_map(static fn($c) => $m[$c] ?? '', $columns), ',', '"', '\\');
			}

			rewind($out);
			$body = stream_get_contents($out);
			fclose($out);

			$mime = 'text/csv; charset=utf-8';
		}

		header('Content-Type: '.$mime);
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Length: '.strlen($body));
		echo $body;
		exit;
	}

	/**
	 * Champs de profil définis par l'administrateur.
	 *
	 * Le profil livré couvre l'état civil et les réseaux ; ces champs-ci laissent une communauté
	 * ajouter ce qui lui est propre sans qu'on livre une migration à chaque fois.
	 */
	public function _fields()
	{
		$this	->title($this->lang('Membres'))
				->subtitle($this->lang('Champs de profil'))
				->icon('fas fa-list-ul');

		$types = [
			'text'     => $this->lang('Texte court'),
			'textarea' => $this->lang('Texte long'),
			'select'   => $this->lang('Liste déroulante'),
			'radio'    => $this->lang('Choix unique'),
			'checkbox' => $this->lang('Case à cocher'),
			'url'      => $this->lang('Adresse web'),
			'number'   => $this->lang('Nombre'),
			'date'     => $this->lang('Date'),
		];

		/** @var \NF\Modules\User\Models\Fields $fields */
		$fields = $this->model('fields');
		$champs = $fields->get_fields();
		$lignes = '';

		foreach ($champs as $champ)
		{
			$lignes .= '<tr>'
				.'<td><b>'.nf_texte($champ['label']).'</b>'
				.'<br /><small class="text-muted"><code>'.nf_texte($champ['name']).'</code></small></td>'
				.'<td>'.nf_texte($types[$champ['type']] ?? $champ['type']).'</td>'
				.'<td>'.($champ['required'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-minus text-muted"></i>').'</td>'
				.'<td>'.($champ['public']
					? '<i class="fas fa-eye text-success" title="'.nf_texte($this->lang('Visible sur la fiche publique')).'"></i>'
					: '<i class="fas fa-eye-slash text-muted" title="'.nf_texte($this->lang('Privé')).'"></i>').'</td>'
				.'<td class="text-end">'
				.'<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/user/fields/edit/'.$champ['field_id'].'/'.url_title($champ['label'])).'"><i class="fas fa-pen"></i></a> '
				.'<a class="btn btn-sm btn-outline-danger" href="'.url('admin/user/fields/delete/'.$champ['field_id'].'/'.url_title($champ['label'])).'"><i class="far fa-trash-alt"></i></a>'
				.'</td></tr>';
		}

		$table = $champs
			? '<table class="table table-striped align-middle"><thead><tr>'
				.'<th>'.$this->lang('Libellé').'</th><th>'.$this->lang('Type').'</th>'
				.'<th>'.$this->lang('Obligatoire').'</th><th>'.$this->lang('Public').'</th><th></th>'
				.'</tr></thead><tbody>'.$lignes.'</tbody></table>'
			: '<p class="text-muted mb-0">'.$this->lang('Aucun champ supplémentaire. Le profil livré reste disponible tel quel.').'</p>';

		$ajouter = '<a class="btn btn-sm btn-primary" href="'.url('admin/user/fields/add').'">'.icon('fas fa-plus').' '.$this->lang('Ajouter un champ').'</a>';

		return $this->_user_subnav('fields')
			.$this->admin_card('fas fa-list-ul', $this->lang('Champs de profil'), $table, '', $ajouter);
	}

	public function _fields_add()
	{
		$this	->title($this->lang('Champs de profil'))
				->subtitle($this->lang('Ajouter'))
				->form()
				->add_rules('field')
				->add_back('admin/user/fields')
				->add_submit($this->lang('Ajouter'), 'fas fa-plus');

		if ($this->form()->is_valid($post))
		{
			/** @var \NF\Modules\User\Models\Fields $fields */
			$fields = $this->model('fields');
			$fields->add_field(
				$post['label'],
				$post['type'],
				$post['options'],
				in_array('on', (array) $post['required']),
				in_array('on', (array) $post['public']),
				(string) $post['description']
			);

			notify($this->lang('Champ ajouté'));

			redirect('admin/user/fields');
		}

		return $this->panel()
					->heading($this->lang('Ajouter un champ'), 'fas fa-plus')
					->body($this->form()->display())
					->size('col-12');
	}

	public function _fields_edit($field_id, $name, $label, $description, $type, $options, $required, $public)
	{
		$this	->title($this->lang('Champs de profil'))
				->subtitle($label)
				->form()
				->add_rules('field', [
					'label'       => $label,
					'description' => $description,
					'type'        => $type,
					'options'     => $options,
					'required'    => $required,
					'public'      => $public,
				])
				->add_back('admin/user/fields')
				// Sans libellé, `add_submit()` levait une ArgumentCountError : l'écran d'édition d'un
				// champ de profil plantait à chaque ouverture (trouvé par check-liens, 2026-09-22).
				->add_submit($this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			/** @var \NF\Modules\User\Models\Fields $fields */
			$fields = $this->model('fields');
			$fields->edit_field(
				$field_id,
				$post['label'],
				$post['type'],
				$post['options'],
				in_array('on', (array) $post['required']),
				in_array('on', (array) $post['public']),
				(string) $post['description']
			);

			notify($this->lang('Champ modifié'));

			redirect('admin/user/fields');
		}

		// Le nom technique est rappelé mais jamais modifiable : il relie la définition aux valeurs
		// que les membres ont déjà saisies.
		$rappel = '<p class="text-muted"><small>'
			.$this->lang('Nom technique : %s — fixé à la création, il ne change pas.', '<code>'.nf_texte($name).'</code>')
			.'</small></p>';

		return $this->panel()
					->heading($this->lang('Modifier un champ'), 'fas fa-pen')
					->body($rappel.$this->form()->display())
					->size('col-12');
	}

	public function _fields_delete($field_id, $label)
	{
		$this	->title($this->lang('Confirmation de suppression'))
				->form()
				->confirm_deletion(
					$this->lang('Confirmation de suppression'),
					$this->lang('Supprimer le champ <b>%s</b> effacera aussi ce que les membres y ont saisi. Continuer ?', nf_texte($label))
				);

		if ($this->form()->is_valid())
		{
			/** @var \NF\Modules\User\Models\Fields $fields */
			$fields = $this->model('fields');
			$fields->delete_field($field_id);

			notify($this->lang('Champ supprimé'));

			redirect('admin/user/fields');
		}

		return $this->form()->display();
	}

	public function _fields_sort()
	{
		if ($ordre = post('ordre'))
		{
			/** @var \NF\Modules\User\Models\Fields $fields */
			$fields = $this->model('fields');
			$fields->sort_fields((array) $ordre);
		}

		return TRUE;
	}

	public function _groups_add()
	{
		$this	->title($this->lang('Groupes'))
				->subtitle($this->lang('Ajouter'))
				->form()
				->add_rules('groups')
				->add_back('admin/user')
				->add_submit($this->lang('Ajouter'), 'fas fa-plus');

		if ($this->form()->is_valid($post))
		{
			$this->model('groups')->add_group(
				$post['title'],
				$post['color'],
				$post['icon'],
				in_array('on', $post['hidden']),
				$this->config->lang->info()->name
			);

			notify($this->lang('Groupe ajouté'));

			redirect_back('admin/user');
		}

		return $this->panel()
					->heading($this->lang('Ajouter un groupe'), 'fas fa-users')
					->body($this->form()->display())
					->size('col-12');
	}

	public function _groups_edit($group_id, $name, $title, $color, $icon, $hidden, $auto)
	{
		$this	->title($this->lang('Groupes'))
				->subtitle($this->lang('Éditer'))
				->form()
				->add_rules('groups', [
					'title'  => $title,
					'color'  => $color,
					'icon'   => $icon,
					'hidden' => $hidden,
					'auto'   => $auto
				])
				->add_back('admin/user')
				->add_submit($this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			if ($group_id)
			{
				$this->model('groups')->edit_group(
					$group_id,
					!$auto ? $post['title'] : NULL,
					$post['color'],
					$post['icon'],
					in_array('on', $post['hidden']),
					$this->config->lang->info()->name,
					$auto
				);
			}
			else
			{
				$this->db->insert('nf_groups', [
					'name'  => $name,
					'color' => $post['color'],
					'icon'  => $post['icon'],
					'auto'  => TRUE
				]);
			}

			notify($this->lang('Groupe modifié'));

			redirect_back('admin/user');
		}

		return $this->panel()
					->heading($this->lang('Éditer un groupe'), 'fas fa-users')
					->body($this->form()->display())
					->size('col-12');
	}

	public function _groups_delete($group_id, $title)
	{
		$this	->title($this->lang('Confirmation de suppression'))
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer le groupe <b>%s</b> ?', $title));

		if ($this->form()->is_valid())
		{
			$this->db	->where('group_id', $group_id)
						->delete('nf_groups');

			$this->access->revoke($group_id);

			return 'OK';
		}

		return $this->form()->display();
	}

	public function _sessions($sessions)
	{
		$this->title($this->lang('Sessions'))->icon('fas fa-globe');

		$table = (string)$this->table2('session', $sessions, $this->lang('Aucune session active'));

		return $this->_user_subnav('sessions')
			.$this->admin_card('fas fa-bars', $this->lang('Liste des sessions actives'), $table, '', '');
	}

	public function _sessions_delete($session_id, $username)
	{
		$this	->title($this->lang('Confirmation de suppression'))
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer la session de l\'utilisateur <b>%s</b> ?', $username));

		if ($this->form()->is_valid())
		{
			$this->db	->where('id', $session_id)
						->delete('nf_session');

			return 'OK';
		}

		return $this->form()->display();
	}

	public function _audit_log($rows, $total = 0)
	{
		$this->title($this->lang('Journal d\'audit'))
			 ->icon('fas fa-clipboard-list')
			 ->breadcrumb();

		$body = '<table class="table table-hover"><thead><tr>'
			.'<th>'.$this->lang('Date').'</th><th>'.$this->lang('Utilisateur').'</th><th>'.$this->lang('Action').'</th><th>'.$this->lang('Cible').'</th><th>'.$this->lang('IP').'</th><th>'.$this->lang('Statut').'</th><th>'.$this->lang('Détails').'</th>'
			.'</tr></thead><tbody>';

		if (empty($rows))
		{
			$body .= '<tr><td colspan="7" class="text-center text-muted">'.$this->lang('Aucune entrée pour le moment.').'</td></tr>';
		}
		else
		{
			foreach ($rows as $row)
			{
				$badge = $row['success']
					? '<span class="badge text-bg-success">OK</span>'
					: '<span class="badge text-bg-danger">FAIL</span>';
				$user_disp = $row['username']
					? nf_texte($row['username']).' (#'.($row['user_id'] ?: '?').')'
					: '<i class="text-muted">'.$this->lang('Anonyme').'</i>';
				$target = $row['target_type']
					? nf_texte($row['target_type']).':'.nf_texte($row['target_id'] ?? '')
					: '<i class="text-muted">-</i>';
				$details = $row['details']
					? '<code style="font-size:11px">'.nf_texte(substr($row['details'], 0, 80)).(strlen($row['details']) > 80 ? '…' : '').'</code>'
					: '<i class="text-muted">-</i>';

				$body .= '<tr>'
					.'<td class="text-nowrap">'.nf_date_heure($row['created_ts']).'</td>'
					.'<td>'.$user_disp.'</td>'
					.'<td>'.nf_texte((new \NF\NeoFrag\Libraries\Audit_Log($this))->libelle((string) $row['action'])).'<br><code class="small text-body-secondary">'.nf_texte($row['action']).'</code></td>'
					.'<td>'.$target.'</td>'
					.'<td><small>'.nf_texte($row['ip_address'] ?? '-').'</small></td>'
					.'<td>'.$badge.'</td>'
					.'<td>'.$details.'</td>'
					.'</tr>';
			}
		}

		$body .= '</tbody></table>';

		if ($pagination = (string) $this->module->pagination->get_pagination())
		{
			$body .= '<div class="d-flex justify-content-center mt-3">'.$pagination.'</div>';
		}

		$subtitle = $this->lang('%d entrée, conservée 365 jours|%d entrées, conservées 365 jours', $total, $total);

		return $this->_user_subnav('audit-log')
			.$this->admin_card('fas fa-clipboard-list', $this->lang('Journal d\'audit'), $body, $subtitle, '');
	}

	/**
	 * Reset 2FA pour un user (admin only) — utile si user a perdu téléphone + recovery codes.
	 * Désactive totp_enabled, supprime totp_secret et les recovery codes, log audit.
	 */
	public function _totp_reset($user)
	{
		$this	->title($this->lang('Reset 2FA'))
				->form()
				->confirm_deletion($this->lang('Reset 2FA'), $this->lang('Réinitialiser le 2FA de <b>%s</b> ? Le user devra le reconfigurer s\'il veut le réactiver. Cette action est tracée dans l\'audit log.', nf_texte($user['username'])));

		if ($this->form()->is_valid())
		{
			$user_id = (int)$user['id'];

			$this->db	->where('id', $user_id)
						->update('nf_user', [
							'totp_secret'  => NULL,
							'totp_enabled' => 0
						]);

			$this->db	->where('user_id', $user_id)
						->delete('nf_user_totp_recovery');

			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('totp.admin_reset', [
				'target_user_id' => $user_id,
				'target_username' => $user['username']
			]);

			notify($this->lang('2FA réinitialisé pour %s.', $user['username']));

			return 'OK';
		}

		return $this->form()->display();
	}

	public function _delete($user)
	{
		$this	->title($this->lang('Supprimer l\'utilisateur'))
				->form()
				->confirm_deletion($this->lang('Supprimer l\'utilisateur'), $this->lang('Supprimer le compte de <b>%s</b> ? Le compte sera anonymisé (soft-delete RGPD) et ses sessions fermées. Action tracée dans l\'audit log.', nf_texte($user['username'])));

		if ($this->form()->is_valid())
		{
			$user_id = (int)$user['id'];

			$this->db	->where('id', $user_id)
						->update('nf_user', [
							'deleted'      => 1,
							'email'        => 'deleted-'.$user_id.'@deleted.local',
							'totp_secret'  => NULL,
							'totp_enabled' => 0
						]);

			$this->db->where('user_id', $user_id)->delete('nf_session');
			$this->db->where('user_id', $user_id)->delete('nf_user_totp_recovery');

			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('user.admin_deleted', [
				'target_user_id'  => $user_id,
				'target_username' => $user['username']
			]);

			notify($this->lang('Utilisateur %s supprimé.', $user['username']));

			return 'OK';
		}

		return $this->form()->display();
	}

	public function _edit($user)
	{
		$uid = (int)$user['id'];

		$this	->title($this->lang('Utilisateurs'))
				->subtitle($this->lang('Éditer : %s', $user['username']))
				->form()
				->add_rules([
					'username' => ['label' => $this->lang('Identifiant'), 'type' => 'text', 'value' => $user['username'], 'rules' => 'required'],
					'email'    => ['label' => $this->lang('Email'), 'type' => 'email', 'value' => $user['email'], 'rules' => 'required'],
					'admin'    => ['label' => $this->lang('Rôle'), 'type' => 'checkbox', 'values' => ['1' => $this->lang('Administrateur (accès complet)')], 'checked' => ['1' => !empty($user['admin'])]]
				])
				->add_back('admin/user')
				->add_submit($this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			if (!NeoFrag()->db->from('nf_user')->where('username', $post['username'])->where('deleted', FALSE)->where('id <>', $uid)->empty())
			{
				$this->form()->error($this->lang('Identifiant déjà pris'));
			}
			else if (!NeoFrag()->db->from('nf_user')->where('email', $post['email'])->where('deleted', FALSE)->where('id <>', $uid)->empty())
			{
				$this->form()->error($this->lang('Adresse email déjà utilisée'));
			}
			else
			{
				$new_admin = in_array('1', $post['admin'] ?? []) ? '1' : '0';

				// Garde-fou : ne pas se retirer l'admin ni démettre le super-admin (id=1).
				if ($new_admin === '0' && ($uid === (int)$this->user->id || $uid === 1))
				{
					$new_admin = '1';
				}

				NeoFrag()->db	->where('id', $uid)
								->update('nf_user', [
									'username' => $post['username'],
									'email'    => $post['email'],
									'admin'    => $new_admin
								]);

				(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('user.admin_edited', [
					'target_user_id'  => $uid,
					'target_username' => $post['username']
				]);

				notify($this->lang('Utilisateur modifié.'));
				redirect_back('admin/user');
			}
		}

		return $this->panel()
					->heading($this->lang('Éditer : %s', $user['username']), 'fas fa-user-edit')
					->body($this->form()->display())
					->size('col-12');
	}
}
