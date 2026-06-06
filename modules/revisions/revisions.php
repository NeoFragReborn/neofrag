<?php
/**
 * https://neofr.ag
 * Module Révisions — historique de contenu générique. Un snapshot JSON par enregistrement,
 * polymorphe via (content_type, content_id, lang). Réutilisable par n'importe quel module :
 *   $this->module('revisions')->snapshot('news', $id, ['title' => ..., 'content' => ...], $lang, 'Édité');
 *   echo $this->module('revisions')->history_panel('news', $id, 'admin/news/revision/restore/'.$id);
 */

namespace NF\Modules\Revisions;

use NF\NeoFrag\Addons\Module;

class Revisions extends Module
{
	// Types de contenu suivis (whitelist : un type inconnu n'est jamais rendu/restauré).
	const ALLOWED_TYPES = ['news', 'article'];

	protected function __info()
	{
		return [
			'title'       => $this->lang('Révisions'),
			'description' => $this->lang('Historique des modifications de contenu (snapshots + restauration).'),
			'icon'        => 'fas fa-history',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'depends'     => ['neofrag' => '1.0.0']
		];
	}

	public static function is_allowed($type)
	{
		return in_array($type, self::ALLOWED_TYPES, TRUE);
	}

	/**
	 * Enregistre un snapshot. Les champs sont sérialisés en JSON. Ne crée pas de doublon si le
	 * contenu est identique au dernier snapshot de la même (cible, langue).
	 * @return int|null l'id de la révision créée, ou NULL si ignorée/refusée.
	 */
	public function snapshot($content_type, $content_id, array $fields, $lang = '', $summary = '')
	{
		if (!self::is_allowed($content_type))
		{
			return NULL;
		}

		$content_id = (int)$content_id;
		$data       = json_encode($fields, JSON_UNESCAPED_UNICODE);

		$last = $this->db	->select('data')
							->from('nf_revisions')
							->where('content_type', $content_type)
							->where('content_id', $content_id)
							->where('lang', (string)$lang)
							->order_by('id DESC')
							->row();

		if (is_string($last) && $last === $data)
		{
			return NULL;
		}

		return $this->db->insert('nf_revisions', [
			'content_type' => $content_type,
			'content_id'   => $content_id,
			'lang'         => (string)$lang,
			'user_id'      => $this->user() ? (int)$this->user->id : NULL,
			'summary'      => mb_substr((string)$summary, 0, 100),
			'data'         => $data
		]);
	}

	public function count($content_type, $content_id)
	{
		return (int)$this->db	->select('COUNT(*)')
								->from('nf_revisions')
								->where('content_type', $content_type)
								->where('content_id', (int)$content_id)
								->row();
	}

	/** Liste légère (sans le blob data) pour l'affichage de l'historique. */
	public function get_list($content_type, $content_id, $limit = 50)
	{
		return $this->db	->select('r.id', 'r.lang', 'r.summary', 'r.created_at', 'r.user_id', 'u.username')
							->from('nf_revisions r')
							->join('nf_user u', 'u.id = r.user_id', 'LEFT')
							->where('r.content_type', $content_type)
							->where('r.content_id', (int)$content_id)
							->order_by('r.id DESC')
							->limit((int)$limit)
							->get(FALSE);
	}

	/** Une révision avec ses champs décodés (clé 'fields'), ou NULL. */
	public function get($id)
	{
		$row = $this->db	->select('id', 'content_type', 'content_id', 'lang', 'summary', 'created_at', 'data')
							->from('nf_revisions')
							->where('id', (int)$id)
							->row(FALSE);

		if (!$row)
		{
			return NULL;
		}

		$row['fields'] = json_decode($row['data'], TRUE) ?: [];

		return $row;
	}

	/**
	 * Rend le panneau d'historique (admin). $restore_base = préfixe de route restore auquel on
	 * append "/{revision_id}" ; $can_restore conditionne l'affichage du bouton.
	 */
	public function history_panel($content_type, $content_id, $restore_base, $can_restore = TRUE)
	{
		if (!self::is_allowed($content_type))
		{
			return '';
		}

		$rows = $this->get_list($content_type, $content_id);

		if (!$rows)
		{
			return '<p class="text-muted">'.$this->lang('Aucune révision enregistrée pour le moment.').'</p>';
		}

		$out = '<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr>'
			.'<th>#</th><th>'.$this->lang('Date').'</th><th>'.$this->lang('Auteur').'</th>'
			.'<th>'.$this->lang('Action').'</th><th>'.$this->lang('Aperçu').'</th>'
			.($can_restore ? '<th></th>' : '').'</tr></thead><tbody>';

		foreach ($rows as $i => $row)
		{
			$rev      = $this->get($row['id']);
			$fields   = $rev ? $rev['fields'] : [];
			$title    = isset($fields['title']) ? (string)$fields['title'] : '';
			$body     = isset($fields['content']) ? (string)$fields['content'] : '';
			$author   = $row['username'] ? htmlspecialchars($row['username']) : '<i>'.$this->lang('Système').'</i>';
			$is_first = ($i === 0);

			$preview  = '<details><summary>'.htmlspecialchars(mb_strimwidth($title, 0, 60, '…')).'</summary>'
				.'<div style="max-height:240px;overflow:auto;border:1px solid var(--nf-border,#444);padding:8px;margin-top:6px;border-radius:4px;">'
				.'<strong>'.htmlspecialchars($title).'</strong>'
				.'<pre style="white-space:pre-wrap;word-break:break-word;margin:6px 0 0;">'.htmlspecialchars($body).'</pre>'
				.'</div></details>';

			$restore = '';
			if ($can_restore && !$is_first)
			{
				$restore = '<a class="btn btn-sm btn-outline-warning" href="'.url($restore_base.'/'.(int)$row['id']).'" '
					.'onclick="return confirm(\''.htmlspecialchars($this->lang('Restaurer cette version ? La version actuelle sera conservée dans l\'historique.'), ENT_QUOTES).'\');">'
					.icon('fas fa-undo').' '.$this->lang('Restaurer').'</a>';
			}
			else if ($is_first)
			{
				$restore = '<span class="badge badge-success">'.$this->lang('Actuelle').'</span>';
			}

			$out .= '<tr>'
				.'<td>'.(int)$row['id'].'</td>'
				.'<td><small>'.htmlspecialchars($row['created_at']).'</small></td>'
				.'<td>'.$author.'</td>'
				.'<td><small>'.htmlspecialchars($row['summary']).($row['lang'] ? ' ('.htmlspecialchars($row['lang']).')' : '').'</small></td>'
				.'<td>'.$preview.'</td>'
				.($can_restore ? '<td class="text-right">'.$restore.'</td>' : '')
				.'</tr>';
		}

		$out .= '</tbody></table></div>';

		return $out;
	}
}
