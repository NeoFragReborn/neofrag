<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Talks\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [];
	}

	public function _new()
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [];
	}

	/**
	 * `talks/piece-jointe/{id}` : une pièce jointe, servie par le site à qui peut lire la conversation (audit du
	 * 2026-10-09) — le fichier d'une conversation privée se servait tel quel à quiconque avait son adresse.
	 */
	public function _piece_jointe($attachment_id)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}

		$piece = $this->db	->select('a.mime_type', 'f.name', 'f.path', 'm.talk_id', 'm.user_id', 'm.deleted_at')
							->from('nf_talks_attachments a')
							->join('nf_file f', 'f.id = a.file_id', 'INNER')
							->join('nf_talks_messages m', 'm.message_id = a.message_id', 'INNER')
							->where('a.attachment_id', (int) $attachment_id)
							->row(FALSE);

		$admin  = (bool) $this->access->effective_admin();
		$modele = $this->model('talks');

		if ($piece && $modele instanceof \NF\Modules\Talks\Models\Talks
			&& ($piece['deleted_at'] === NULL || $admin)
			&& $modele->user_can_access((int) $piece['talk_id'], (int) $this->user->id, $admin)
			&& !in_array((int) $piece['user_id'], $this->moderation->auteurs_masques(), TRUE))
		{
			return [(string) $piece['path'], (string) $piece['name'], (string) $piece['mime_type']];
		}
	}

	public function _view($talk_id, $title, $page = '')
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [$talk_id, $title, $page];
	}

	public function _invite($talk_id, $title)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [$talk_id, $title];
	}

	public function _leave($talk_id, $title)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [$talk_id, $title];
	}

	public function _archive($talk_id, $title)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [$talk_id, $title];
	}

	public function _unarchive($talk_id, $title)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [$talk_id, $title];
	}
}
