<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * NeoFrag — Model emails (R2.0, 2026-05-06)
 */

namespace NF\Modules\Emails\Models;

use NF\NeoFrag\Loadables\Model;

class Emails extends Model
{
	/**
	 * Liste tous les templates avec compteur de traductions et trad pour la langue courante (fallback fr).
	 */
	public function get_templates($current_lang = 'fr')
	{
		$rows = $this->db	->select('t.*',
									'(SELECT COUNT(*) FROM nf_email_template_translations WHERE template_id = t.template_id) AS lang_count')
							->from('nf_email_templates t')
							->order_by('t.module', 't.title')
							->get(FALSE);

		if (!$rows)
		{
			return [];
		}

		// Récupérer subject/body de la langue courante (ou fallback fr)
		$ids = array_column($rows, 'template_id');
		$trans_current = [];
		$trans_fr      = [];

		foreach ($this->db	->select('template_id', 'subject')
							->from('nf_email_template_translations')
							->where('template_id', $ids)
							->where('lang', $current_lang)
							->get(FALSE) as $t)
		{
			$trans_current[(int)$t['template_id']] = $t['subject'];
		}

		foreach ($this->db	->select('template_id', 'subject')
							->from('nf_email_template_translations')
							->where('template_id', $ids)
							->where('lang', 'fr')
							->get(FALSE) as $t)
		{
			$trans_fr[(int)$t['template_id']] = $t['subject'];
		}

		foreach ($rows as &$r)
		{
			$id = (int)$r['template_id'];
			$r['current_subject'] = $trans_current[$id] ?? ($trans_fr[$id] ?? '');
		}
		unset($r);

		return $rows;
	}

	public function get_template($id)
	{
		$id = (int)$id;
		if ($id <= 0) return NULL;

		$row = $this->db	->select('*')
							->from('nf_email_templates')
							->where('template_id', $id)
							->row(FALSE);

		if (!$row) return NULL;

		$row['translations'] = [];
		foreach ($this->db	->select('lang', 'subject', 'body', 'updated_at')
							->from('nf_email_template_translations')
							->where('template_id', $id)
							->get(FALSE) as $t)
		{
			$row['translations'][$t['lang']] = $t;
		}

		return $row;
	}

	/**
	 * Récupère un template par sa clé fonctionnelle (ex: 'user.registration').
	 * Retourne NULL si pas trouvé OU si désactivé.
	 */
	public function get_by_key($key)
	{
		$row = $this->db	->select('*')
							->from('nf_email_templates')
							->where('`key`', $key)
							->where('enabled', '1')
							->row(FALSE);

		return $row ?: NULL;
	}

	public function get_translation($template_id, $lang, $fallback_lang = 'fr')
	{
		$template_id = (int)$template_id;

		$row = $this->db	->select('subject', 'body')
							->from('nf_email_template_translations')
							->where('template_id', $template_id)
							->where('lang', $lang)
							->row(FALSE);

		if ($row)
		{
			return $row;
		}

		// Fallback langue par défaut
		if ($lang !== $fallback_lang)
		{
			$row = $this->db	->select('subject', 'body')
								->from('nf_email_template_translations')
								->where('template_id', $template_id)
								->where('lang', $fallback_lang)
								->row(FALSE);

			if ($row)
			{
				return $row;
			}
		}

		return NULL;
	}

	public function save_translation($template_id, $lang, $subject, $body)
	{
		$template_id = (int)$template_id;
		$lang        = (string)$lang;
		$subject     = (string)$subject;
		$body        = (string)$body;

		$existing = $this->db	->select('lang')
								->from('nf_email_template_translations')
								->where('template_id', $template_id)
								->where('lang', $lang)
								->row(FALSE);

		if ($existing)
		{
			$this->db	->where('template_id', $template_id)
						->where('lang', $lang)
						->update('nf_email_template_translations', [
							'subject' => $subject,
							'body'    => $body
						]);
		}
		else
		{
			$this->db	->insert('nf_email_template_translations', [
							'template_id' => $template_id,
							'lang'        => $lang,
							'subject'     => $subject,
							'body'        => $body
						]);
		}

		// Bump updated_at sur le parent
		$this->db	->where('template_id', $template_id)
					->update('nf_email_templates', [
						'updated_at' => date('Y-m-d H:i:s')
					]);
	}

	public function set_enabled($template_id, $enabled)
	{
		$this->db	->where('template_id', (int)$template_id)
					->update('nf_email_templates', [
						'enabled' => $enabled ? '1' : '0'
					]);
	}

	/**
	 * Render un texte (subject ou body) avec les placeholders fournis.
	 * Format placeholders : {{key}} → str_replace.
	 * Les placeholders inconnus laissés tels quels (visible pour debug).
	 */
	public static function render_placeholders($text, array $placeholders)
	{
		if ($text === '' || !$placeholders) return $text;

		$search  = [];
		$replace = [];

		foreach ($placeholders as $key => $value)
		{
			$search[]  = '{{'.$key.'}}';
			$replace[] = (string)$value;
		}

		return str_replace($search, $replace, $text);
	}
}
