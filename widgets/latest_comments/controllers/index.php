<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Latest_Comments\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($settings = [])
	{
		$count = max(1, min(20, (int)($settings['count'] ?? 5)));

		// Loader explicite : $this->module() depuis une classe addon mis-résout le type (cf comments/notifications).
		$notifications = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications']);

		// On récupère un peu plus que demandé pour pouvoir écarter les commentaires supprimés (contenu vide)
		// et ceux dont l'URL du contenu n'est pas résolvable, sans descendre sous $count.
		$rows = $this->db	->select('c.id', 'c.module', 'c.module_id', 'c.content', 'c.date', 'u.id AS user_id', 'u.username')
							->from('nf_comment c')
							->join('nf_user u', 'u.id = c.user_id')
								->where('c.deleted_at IS NULL')
							->order_by('c.date DESC')
							->limit($count + 15)
							->get();

		$comments = [];

		foreach ($rows as $row)
		{
			if (count($comments) >= $count)
			{
				break;
			}

			// Décode les entités puis strip_tags → texte brut propre (le contenu est du HTML assaini).
			$text = trim(strip_tags(utf8_html_entity_decode((string)$row['content'])));

			if ($text === '')
			{
				continue; // commentaire supprimé
			}

			$comments[] = [
				'user_id'  => (int)$row['user_id'],
				'username' => $row['username'],
				'snippet'  => str_shortener($text, 80, '…'),
				'date'     => $row['date'],
				'url'      => $notifications ? $notifications->content_url($row['module'], (int)$row['module_id']) : ''
			];
		}

		$view = $this->view('latest_comments', ['comments' => $comments]);

		if (($settings['display_panel'] ?? 'oui') === 'oui')
		{
			return $this	->panel()
							->heading($this->lang('Derniers commentaires'), 'far fa-comments')
							->body($view);
		}

		return $view;
	}
}
