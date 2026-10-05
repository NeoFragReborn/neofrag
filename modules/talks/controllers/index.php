<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Controller user-side du module talks (Phase 2 unifié).
 */

namespace NF\Modules\Talks\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\NeoFrag\Models\File;
use NF\Modules\Talks\Security;

require_once __DIR__ . '/../security.php';

class Index extends Controller_Module
{
	public function index()
	{
		// Filtre : private (DM + group) | public | all (default)
		$filter = (string)($_GET['type'] ?? 'all');
		if (!in_array($filter, ['all', 'private', 'public'], TRUE))
		{
			$filter = 'all';
		}

		$titles = [
			'all'     => $this->lang('Toutes mes discussions'),
			'private' => $this->lang('Messagerie privée'),
			'public'  => $this->lang('Salons publics')
		];
		$icons = [
			'all'     => 'far fa-comments',
			'private' => 'far fa-envelope',
			'public'  => 'fas fa-hashtag'
		];

		$this	->title($titles[$filter])
				->icon($icons[$filter]);

		$user_id  = $this->user->id;
		$is_admin = (bool)$this->access->effective_admin();

		$all_conversations = $this->model()->get_my_conversations($user_id);
		$publics_all       = $this->model()->get_public_channels($user_id, $is_admin);

		// Filtre des "private" = direct + group
		$private = array_values(array_filter($all_conversations, function($c){
			return in_array($c['type'], ['direct', 'group'], TRUE);
		}));

		// "public" filter = uniquement les salons publics rejoints
		$publics_joined = array_values(array_filter($all_conversations, function($c){
			return $c['type'] === 'public';
		}));

		// Décide ce qu'on montre selon le filtre
		switch ($filter)
		{
			case 'private':
				$conversations = $private;
				$publics       = []; // on cache la liste publics dans la vue private
				break;

			case 'public':
				$conversations = $publics_joined;
				$publics       = $publics_all;
				break;

			default: // all
				$conversations = $all_conversations;
				$publics       = $publics_all;
		}

		$this->add_action(
			$this->button($this->lang('Nouvelle conversation'), 'fas fa-plus', 'primary')
				->url('talks/new'.($filter === 'private' ? '?type=direct' : ''))
		);

		return $this->view('user/index', [
			'conversations' => $conversations,
			'publics'       => $publics,
			'user_id'       => $user_id,
			'filter'        => $filter,
			'counts'        => [
				'private' => count($private),
				'public'  => count($publics_joined),
				'all'     => count($all_conversations)
			]
		]);
	}

	public function _new()
	{
		$this	->title($this->lang('Nouvelle conversation'))
				->breadcrumb($this->lang('Mes conversations'), 'talks');

		// Pré-remplir destinataire si user passé en query (?user=alice)
		$prefill_user = !empty($_GET['user']) ? trim((string)$_GET['user']) : '';
		$prefill_type = !empty($_GET['type']) && in_array($_GET['type'], ['direct', 'group', 'public'], TRUE)
					? $_GET['type']
					: 'group';

		$prefill_user_id = NULL;
		if ($prefill_user)
		{
			$prefill_user_id = (int)$this->db	->select('id')
												->from('nf_user')
												->where('username', $prefill_user)
												->where('deleted', '0')
												->row();
		}

		// Liste des users disponibles pour invitation
		$users = $this->db	->select('id', 'username')
							->from('nf_user')
							->where('id !=', (int)$this->user->id)
							->where('id !=', nf_compte_masque())
							->where('deleted', '0')
							->order_by('username')
							->get();

		$user_options = [];
		foreach ($users as $u)
		{
			$user_options[(int)$u['id']] = $u['username'];
		}

		$default_name = $prefill_user
				? $this->lang('Discussion avec %s', $prefill_user)
				: '';

		$form = $this->form()
			->add_rules([
				'name' => [
					'label' => $this->lang('Nom de la conversation'),
					'value' => $default_name,
					'rules' => 'required',
					'description' => $this->lang('Visible par tous les participants. Ex : "Strats Inferno", "Coordination match Alpha"...')
				],
				'type' => [
					'label'  => $this->lang('Type'),
					'type'   => 'select',
					'value'  => $prefill_type,
					'values' => [
						'direct' => $this->lang('Direct (1-1, message privé)'),
						'group'  => $this->lang('Groupe privé (sur invitation, plusieurs personnes)'),
						'public' => $this->lang('Salon public (visible et accessible à tous les membres connectés)')
					]
				],
				'description' => [
					'label' => $this->lang('Description (optionnel)'),
					'type'  => 'textarea'
				],
				'participants' => [
					'label'    => $this->lang('Inviter des membres (optionnel pour public)'),
					'type'     => 'select',
					'multiple' => TRUE,
					'values'   => $user_options,
					'value'    => $prefill_user_id ? [$prefill_user_id] : []
				]
			])
			->add_submit($this->lang('Créer'), 'fas fa-plus')
			->save();

		if ($form->is_valid($post))
		{
			$participants = isset($post['participants']) ? (array)$post['participants'] : [];

			$talk_id = $this->model()->create_conversation(
				$this->user->id,
				$post['type'],
				$post['name'],
				$post['description'],
				$participants
			);

			notify($this->lang('Conversation créée !'));
			redirect('talks/'.$talk_id.'/'.\url_title($post['name']));
		}

		return $this->panel()
					->heading($this->lang('Nouvelle conversation'), 'fas fa-plus')
					->body($form->display());
	}

	public function _view($talk_id, $title, $page = '')
	{
		$talk = $this->model()->user_can_access($talk_id, $this->user->id, (bool)$this->access->effective_admin());
		if (!$talk)
		{
			$this->error->unauthorized();
			return;
		}

		// Si user pas encore participant et talk public → join auto
		if ($talk['type'] === 'public' && !$this->model()->is_participant($talk_id, $this->user->id))
		{
			$this->model()->add_participant($talk_id, $this->user->id, 'member');
		}

		$this	->title($talk['name'])
				->icon($talk['type'] === 'public' ? 'fas fa-hashtag' : ($talk['type'] === 'group' ? 'fas fa-users' : 'fas fa-user'))
				->breadcrumb($this->lang('Mes conversations'), 'talks');

		// Action submit message + upload optionnel
		if (!empty($_POST['talk_message']) || !empty($_FILES['talk_attachment']['name']))
		{
			// Phase 4 modération — block si user mute sur talks
			if ($this->moderation->is_muted((int)$this->user->id, 'talks'))
			{
				notify($this->moderation->block_message_for_user((int)$this->user->id, 'talks') ?: $this->lang('Tu es actuellement muet sur les talks.'), 'danger');
				redirect('talks/'.$talk_id.'/'.\url_title($title));
			}
			// Phase 4 modération — block upload si restrict_upload
			if (!empty($_FILES['talk_attachment']['name']) && !$this->moderation->can_upload_files((int)$this->user->id))
			{
				notify($this->lang('Upload de fichiers désactivé pour ton compte.'), 'danger');
				redirect('talks/'.$talk_id.'/'.\url_title($title));
			}

			// Rate-limit anti-spam : max 10 messages / 60s par user, lockout 5 min si dépassé
			$rl_key = 'talks:msg:user:'.(int)$this->user->id;
			$rl_check = $this->rate_limit->check($rl_key);
			if (!$rl_check['allowed'])
			{
				notify($this->lang('Trop de messages envoyés. Réessaye dans %d secondes.', $rl_check['retry_after']), 'danger');
				redirect('talks/'.$talk_id.'/'.\url_title($title));
			}
			$this->rate_limit->hit($rl_key, 10, 60, 300);

			$message = Security::sanitize_message_text($_POST['talk_message'] ?? '');
			$message_id = NULL;

			// Si pas de texte mais un fichier, on stocke un placeholder
			if ($message === '' && !empty($_FILES['talk_attachment']['name']))
			{
				$message = '[' . $this->lang('Pièce jointe') . ']';
			}

			if ($message !== '' && mb_strlen($message) <= 2000)
			{
				$message_id = $this->model()->send_message($talk_id, $this->user->id, $message);
			}

			// Traitement upload si présent
			if ($message_id && !empty($_FILES['talk_attachment']['name']) && empty($_FILES['talk_attachment']['error']))
			{
				$tmp_path = $_FILES['talk_attachment']['tmp_name'];
				$validation = Security::validate_file(
					$tmp_path,
					$this->model()->get_allowed_mimes(),
					$this->model()->get_max_size_bytes()
				);

				if ($validation === TRUE)
				{
					$file = File::uploaded_file($_FILES['talk_attachment'], 'talks');
					if ($file && !empty($file->id))
					{
						$mime = Security::detect_real_mime($file->path) ?: 'application/octet-stream';
						$size = (int)filesize($file->path);
						$this->model()->attach_file($message_id, $file->id, $size, $mime);
					}
				}
				else
				{
					notify($this->lang('Pièce jointe rejetée : %s', $validation), 'danger');
				}
			}

			redirect('talks/'.$talk_id.'/'.\url_title($title).'#bottom');
		}

		// Mark as read
		$this->model()->mark_read($talk_id, $this->user->id);

		$messages     = $this->model()->get_paginated_messages($talk_id, 50);
		$participants = $this->model()->get_participants($talk_id);

		// Récupérer attachments groupés par message_id
		$msg_ids = array_map(function($m){ return (int)$m['message_id']; }, $messages);
		$attachments_map = $this->model()->get_attachments_for_messages($msg_ids);

		// Actions footer
		$actions = [];
		if ($talk['creator_id'] == $this->user->id || $this->access('default', 'admin_moderate'))
		{
			$actions[] = '<a href="'.url('talks/'.$talk_id.'/'.\url_title($title).'/invite').'" class="btn btn-sm btn-outline-primary">'.\icon('fas fa-user-plus').' '.$this->lang('Inviter').'</a>';
		}
		// "Quitter" = sortir du groupe/salon (hard, perd l'accès) — uniquement pour group/public
		if (in_array($talk['type'], ['group', 'public'], TRUE))
		{
			$actions[] = '<a href="'.url('talks/'.$talk_id.'/'.\url_title($title).'/leave').'" class="btn btn-sm btn-outline-warning" data-confirm="'.nf_texte($this->lang('Quitter cette conversation ? Tu ne pourras plus la voir ni y répondre.')).'" data-confirm-style="warning">'.\icon('fas fa-sign-out-alt').' '.$this->lang('Quitter').'</a>';
		}
		// "Archiver" = soft hide, retrouvable dans /talks/archives, ne quitte pas
		$actions[] = '<a href="'.url('talks/'.$talk_id.'/'.\url_title($title).'/archive').'" class="btn btn-sm btn-outline-secondary">'.\icon('fas fa-archive').' '.$this->lang('Archiver').'</a>';
		// "Supprimer pour moi" = soft-delete user-side, conservé 14j dans /talks/trash, restaurable
		$actions[] = '<a href="'.url('talks/'.$talk_id.'/'.\url_title($title).'/delete').'" class="btn btn-sm btn-outline-danger" data-confirm="'.nf_texte($this->lang('Supprimer cette conversation pour toi ? Elle reste accessible aux autres participants. Tu peux la restaurer pendant 14 jours depuis la corbeille.')).'">'.\icon('far fa-trash-alt').' '.$this->lang('Supprimer').'</a>';

		return $this->view('user/view', [
			'talk'            => $talk,
			'talk_id'         => $talk_id,
			'title'           => $title,
			'messages'        => $messages,
			'attachments_map' => $attachments_map,
			'participants'    => $participants,
			'user_id'         => $this->user->id,
			'actions'         => $actions,
			'allowed_mimes'   => $this->model()->get_allowed_mimes(),
			'max_size_bytes'  => $this->model()->get_max_size_bytes()
		]);
	}

	public function _invite($talk_id, $title)
	{
		// Le même droit de lecture que la page de la conversation (ligne ~189) : sans `effective_admin()`,
		// un administrateur voyait une conversation réservée à l'équipe, son bouton, puis un refus (403,
		// trouvé par check-liens le 2026-09-22).
		$talk = $this->model()->user_can_access($talk_id, $this->user->id, (bool)$this->access->effective_admin());
		if (!$talk || ($talk['creator_id'] != $this->user->id && !$this->access('default', 'admin_moderate')))
		{
			$this->error->unauthorized();
			return;
		}

		$this	->title($this->lang('Inviter dans : %s', $talk['name']))
				->breadcrumb($this->lang('Mes conversations'), 'talks')
				->breadcrumb($talk['name'], 'talks/'.$talk_id.'/'.\url_title($title));

		// Users non-participants
		$existing_ids = array_map(function($p){ return (int)$p['user_id']; }, $this->model()->get_participants($talk_id, TRUE));
		$users = $this->db	->select('id', 'username')
							->from('nf_user')
							->where('deleted', '0');
		if (!empty($existing_ids))
		{
			$users->where('id NOT IN ('.implode(',', $existing_ids).')');
		}
		$users = $users->order_by('username')->get();

		$user_options = [];
		foreach ($users as $u)
		{
			$user_options[(int)$u['id']] = $u['username'];
		}

		$form = $this->form()
			->add_rules([
				'invitees' => [
					'label'    => $this->lang('Membres à inviter'),
					'type'     => 'select',
					'multiple' => TRUE,
					'values'   => $user_options,
					'rules'    => 'required'
				]
			])
			->add_submit($this->lang('Inviter'), 'fas fa-user-plus')
			->save();

		if ($form->is_valid($post))
		{
			$count = 0;
			foreach ((array)$post['invitees'] as $uid)
			{
				if ($this->model()->add_participant($talk_id, (int)$uid))
				{
					$count++;
				}
			}
			notify($this->lang('%d membre(s) invité(s)', $count));
			redirect('talks/'.$talk_id.'/'.\url_title($title));
		}

		return $this->panel()
					->heading($this->lang('Inviter des membres'), 'fas fa-user-plus')
					->body($form->display());
	}

	public function _leave($talk_id, $title)
	{
		$talk = $this->model()->get_conversation($talk_id);
		if (!$talk)
		{
			$this->error->unauthorized();
			return;
		}

		$this->model()->remove_participant($talk_id, $this->user->id);
		notify($this->lang('Tu as quitté la conversation'));
		redirect('talks');
	}

	public function _archive($talk_id, $title)
	{
		$talk = $this->model()->get_conversation($talk_id);
		if (!$talk)
		{
			$this->error->unauthorized();
			return;
		}

		$this->model()->archive_for_user($talk_id, $this->user->id);
		notify($this->lang('Conversation archivée'));
		redirect('talks');
	}

	public function _unarchive($talk_id, $title)
	{
		$this->model()->unarchive_for_user($talk_id, $this->user->id);
		notify($this->lang('Conversation désarchivée'));
		redirect('talks/'.$talk_id.'/'.\url_title($title));
	}

	public function _delete($talk_id, $title)
	{
		// Soft-delete user-side : la conversation reste pour les autres participants,
		// conservée 14j pour modération unilatérale, restaurable depuis /talks/trash.
		$is_participant = $this->db	->select('1')
									->from('nf_talks_participants')
									->where('talk_id', (int)$talk_id)
									->where('user_id', (int)$this->user->id)
									->row();
		if (!$is_participant)
		{
			$this->error->unauthorized();
			return;
		}

		$this->model()->delete_for_user($talk_id, $this->user->id);
		notify($this->lang('Conversation supprimée. Tu peux la restaurer pendant 14 jours depuis la corbeille.'));
		redirect('talks');
	}

	public function _restore($talk_id, $title)
	{
		// Restauration depuis la corbeille (soft-delete annulé) — accessible même si deleted_at NOT NULL.
		$is_participant = $this->db	->select('deleted_at')
									->from('nf_talks_participants')
									->where('talk_id', (int)$talk_id)
									->where('user_id', (int)$this->user->id)
									->row();
		if (!$is_participant)
		{
			$this->error->unauthorized();
			return;
		}

		$this->model()->restore_for_user($talk_id, $this->user->id);
		notify($this->lang('Conversation restaurée'));
		redirect('talks/'.$talk_id.'/'.\url_title($title));
	}

	public function _archives()
	{
		// Espace dédié archives (équivalent de l'ancien onglet "Archives" du module MP).
		$conversations = $this->model()->get_archived_conversations($this->user->id);

		$this	->title($this->lang('Conversations archivées'))
				->icon('fas fa-archive')
				->breadcrumb($this->lang('Mes conversations'), 'talks')
				->breadcrumb($this->lang('Archives'));

		$this->add_action(
			$this->button($this->lang('Mes conversations'), 'far fa-comments', 'secondary')->url('talks')
		);
		$this->add_action(
			$this->button($this->lang('Corbeille'), 'far fa-trash-alt', 'secondary')->url('talks/trash')
		);

		return $this->view('user/archives', [
			'conversations' => $conversations,
			'user_id'       => $this->user->id
		]);
	}

	public function _staff_chat_send()
	{
		// Endpoint POST dédié pour la chatbox staff du dashboard admin.
		// Évite la redirection vers /talks/{id}/{slug} après envoi : on send_message + redirect /admin.
		if (!$this->access->effective_admin())
		{
			redirect('admin');
		}

		$message = isset($_POST['talk_message']) ? trim((string)$_POST['talk_message']) : '';
		if ($message === '')
		{
			redirect('admin');
		}

		// Récupère le talk staff (audience='staff')
		$staff_talk = $this->db	->select('talk_id')
								->from('nf_talks')
								->where('type', 'public')
								->where('audience', 'staff')
								->where('deleted_at', NULL)
								->order_by('talk_id')
								->row(FALSE);
		// row(FALSE) : une colonne seule rendait sa valeur, `is_array()` répondait non, et le message
		// partait toujours sur « Aucun salon staff configuré » (relevé le 2026-10-03).
		if (!is_array($staff_talk) || empty($staff_talk['talk_id']))
		{
			notify($this->lang('Aucun salon staff configuré.'), 'danger');
			redirect('admin');
		}

		// Auto-join si pas déjà participant
		if (!$this->model()->is_participant((int)$staff_talk['talk_id'], (int)$this->user->id))
		{
			$this->model()->add_participant((int)$staff_talk['talk_id'], (int)$this->user->id, 'admin');
		}

		try
		{
			$this->model()->send_message((int)$staff_talk['talk_id'], (int)$this->user->id, $message);
		}
		catch (\Throwable $e)
		{
			notify($this->lang('Erreur lors de l\'envoi du message.'), 'danger');
		}

		redirect('admin');
	}

	public function _trash()
	{
		// Corbeille : conversations soft-deleted dans les 14 derniers jours, restaurables.
		$conversations = $this->model()->get_recently_deleted_conversations($this->user->id, 14);

		$this	->title($this->lang('Corbeille'))
				->icon('far fa-trash-alt')
				->breadcrumb($this->lang('Mes conversations'), 'talks')
				->breadcrumb($this->lang('Corbeille'));

		$this->add_action(
			$this->button($this->lang('Mes conversations'), 'far fa-comments', 'secondary')->url('talks')
		);
		$this->add_action(
			$this->button($this->lang('Archives'), 'fas fa-archive', 'secondary')->url('talks/archives')
		);

		return $this->view('user/trash', [
			'conversations'  => $conversations,
			'user_id'        => $this->user->id,
			'retention_days' => 14
		]);
	}

	public function _search()
	{
		$query    = trim((string)($_GET['q'] ?? ''));
		$talk_id  = !empty($_GET['talk_id']) ? (int)$_GET['talk_id'] : NULL;

		$this	->title($this->lang('Recherche dans les conversations'))
				->breadcrumb($this->lang('Mes conversations'), 'talks')
				->breadcrumb($this->lang('Recherche'));

		$results   = [];
		$too_short = FALSE;
		if ($query !== '')
		{
			if (strlen($query) < 3) $too_short = TRUE;
			else $results = $this->model()->search_messages($query, $this->user->id, $talk_id);
		}

		// Liste de mes conversations pour le filtre
		$my_convs = $this->model()->get_my_conversations($this->user->id);

		return $this->view('user/search', [
			'query'     => $query,
			'talk_id'   => $talk_id,
			'results'   => $results,
			'too_short' => $too_short,
			'my_convs'  => $my_convs
		]);
	}

	public function _report($talk_id, $title, $message_id)
	{
		// Le même droit de lecture que la page de la conversation (ligne ~189) : sans `effective_admin()`,
		// un administrateur voyait une conversation réservée à l'équipe, son bouton, puis un refus (403,
		// trouvé par check-liens le 2026-09-22).
		$talk = $this->model()->user_can_access($talk_id, $this->user->id, (bool)$this->access->effective_admin());
		if (!$talk)
		{
			$this->error->unauthorized();
			return;
		}

		// Rate-limit signalements (max 5 / heure / user pour éviter abus)
		$rl_key = 'talks:report:user:'.(int)$this->user->id;
		$rl_check = $this->rate_limit->check($rl_key);
		if (!$rl_check['allowed'])
		{
			notify($this->lang('Trop de signalements. Réessaye dans %d secondes.', $rl_check['retry_after']), 'danger');
			redirect('talks/'.$talk_id.'/'.\url_title($title));
		}
		$this->rate_limit->hit($rl_key, 5, 3600, 3600);

		// Log dans nf_audit_log (table existante du tier 1 sécurité)
		$this->db->insert('nf_audit_log', [
			'user_id'     => (int)$this->user->id,
			'username'    => $this->user->username,
			'action'      => 'talks.message.reported',
			'target_type' => 'talks_message',
			'target_id'   => (string)(int)$message_id,
			'details'     => json_encode([
				'talk_id'    => (int)$talk_id,
				'message_id' => (int)$message_id,
				'reporter'   => $this->user->username
			]),
			'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? NULL,
			'success'     => 1
		]);

		notify($this->lang('Message signalé aux administrateurs. Merci.'));
		redirect('talks/'.$talk_id.'/'.\url_title($title));
	}
}
