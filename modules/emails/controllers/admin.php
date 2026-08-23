<?php
/**
 * https://neofr.ag
 * NeoFrag — Module emails — Controller admin (R2.0, 2026-05-06)
 *
 * Réutilise strictement les classes natives du theme admin :
 *   .settings-hub-grid, .settings-hub-card--<color>, .settings-hub-icon, .settings-hub-text/title/desc
 *   .settings-section-card, .settings-section-header, .settings-section-icon, .settings-section-meta,
 *     .settings-section-title, .settings-section-subtitle, .settings-section-body, .settings-section-row
 *   .settings-section-back (bouton retour stylé)
 *
 * Classes locales additionnelles : tabs langue + barre placeholders + viewer email — minimum nécessaire.
 */

namespace NF\Modules\Emails\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($templates)
	{
		$this->title($this->lang('Templates emails'));
		$this->icon('fas fa-envelope-open-text');

		// Mapping module → icon + couleur (modifier BEM .settings-hub-card--<color>)
		$module_meta = [
			'user'        => ['icon' => 'fas fa-user',           'color' => 'accent',  'label' => $this->lang('Comptes utilisateurs')],
			'forum'       => ['icon' => 'fas fa-comments',       'color' => 'info',    'label' => 'Forum'],
			'talks'       => ['icon' => 'fas fa-envelope',       'color' => 'success', 'label' => 'Talks / Messagerie'],
			'moderation'  => ['icon' => 'fas fa-shield-alt',     'color' => 'warning', 'label' => $this->lang('Modération')],
			'newsletter'  => ['icon' => 'fas fa-paper-plane',    'color' => 'danger',  'label' => 'Newsletter'],
			'core'        => ['icon' => 'fas fa-cube',           'color' => 'accent',  'label' => $this->lang('Système')]
		];

		$by_module = [];
		foreach ($templates as $t)
		{
			$mod = $t['module'] ?: 'core';
			$by_module[$mod][] = $t;
		}

		uksort($by_module, function($a, $b) use ($module_meta){
			$keys = array_keys($module_meta);
			$pa = array_search($a, $keys, TRUE);
			$pb = array_search($b, $keys, TRUE);
			if ($pa === FALSE) $pa = 999;
			if ($pb === FALSE) $pb = 999;
			return $pa <=> $pb;
		});

		$html = '<div class="settings-hub">';

		foreach ($by_module as $mod => $list)
		{
			$meta  = $module_meta[$mod] ?? ['icon' => 'fas fa-cube', 'color' => 'accent', 'label' => ucfirst($mod)];
			$count = count($list);

			// Section card avec header (titre groupe + compteur) et body (grid de templates)
			$html .= '<div class="settings-section-card">';
			$html .= '<div class="settings-section-header">';
			$html .= '<div class="settings-section-icon"><i class="'.$meta['icon'].'"></i></div>';
			$html .= '<div class="settings-section-meta">';
			$html .= '<div class="settings-section-title">'.htmlspecialchars($meta['label']).'</div>';
			$html .= '<div class="settings-section-subtitle">'.$count.' '.($count > 1 ? $this->lang('templates') : $this->lang('template')).'</div>';
			$html .= '</div>';
			$html .= '</div>';

			$html .= '<div class="settings-section-body">';
			$html .= '<div class="settings-hub-grid">';

			foreach ($list as $t)
			{
				$slug    = url_title($t['title'] ?: $t['key']);
				$enabled = !empty($t['enabled']);
				$subject = $t['current_subject'] ?: '';

				$html .= '<div class="settings-hub-card settings-hub-card--'.$meta['color'].'">';

				// Icône colorée
				$html .= '<div class="settings-hub-icon"><i class="'.$meta['icon'].'"></i></div>';

				// Texte (titre + key + desc + sujet preview + statut + actions)
				$html .= '<div class="settings-hub-text">';
				$html .= '<div class="settings-hub-title">'.htmlspecialchars($t['title']).'</div>';
				$html .= '<div class="settings-hub-desc"><code style="font-size:11px;">'.htmlspecialchars($t['key']).'</code></div>';

				if (!empty($t['description']))
				{
					$html .= '<div class="settings-hub-desc" style="margin-top:6px;">'.htmlspecialchars($t['description']).'</div>';
				}

				if ($subject)
				{
					$html .= '<div class="settings-hub-desc" style="margin-top:8px;font-style:italic;">'.icon('fas fa-at').' '.htmlspecialchars($subject).'</div>';
				}

				// Pied : badges + actions
				$html .= '<div style="display:flex;align-items:center;gap:6px;margin-top:10px;flex-wrap:wrap;">';
				if ($enabled)
				{
					$html .= '<span class="badge text-bg-success">'.$this->lang('Actif').'</span>';
				}
				else
				{
					$html .= '<span class="badge text-bg-secondary">'.$this->lang('Désactivé').'</span>';
				}
				$html .= '<span class="badge text-bg-light">'.icon('fas fa-language').' '.(int)$t['lang_count'].'</span>';
				$html .= '<div style="margin-left:auto;display:flex;gap:4px;">';
				$html .= '<a class="btn btn-sm btn-primary" href="'.url('admin/emails/edit/'.$t['template_id'].'/'.$slug).'" title="'.$this->lang('Éditer').'">'.icon('fas fa-edit').'</a>';
				$html .= '<a class="btn btn-sm '.($enabled ? 'btn-outline-warning' : 'btn-outline-success').'" href="'.$this->csrf_url('admin/emails/toggle/'.$t['template_id'].'/'.$slug).'" title="'.$this->lang($enabled ? 'Désactiver' : 'Activer').'">'.icon($enabled ? 'fas fa-toggle-off' : 'fas fa-toggle-on').'</a>';
				$html .= '<a class="btn btn-sm btn-outline-info" href="'.url('admin/emails/test/'.$t['template_id'].'/'.$slug).'" title="'.$this->lang('Envoi de test').'">'.icon('fas fa-paper-plane').'</a>';
				$html .= '</div>';
				$html .= '</div>';

				$html .= '</div>'; // .settings-hub-text
				$html .= '</div>'; // .settings-hub-card
			}

			$html .= '</div>'; // .settings-hub-grid
			$html .= '</div>'; // .settings-section-body
			$html .= '</div>'; // .settings-section-card
		}

		$html .= '</div>'; // .settings-hub

		return $html;
	}

	public function _edit($template)
	{
		$this->title($this->lang('Éditer le template').' — '.$template['title']);
		$this->icon('fas fa-edit');

		$lang_default = 'fr';
		$lang         = isset($_GET['lang']) ? (string)$_GET['lang'] : $lang_default;

		$site_langs = [];
		if (isset($this->config->langs) && is_iterable($this->config->langs))
		{
			foreach ($this->config->langs as $l)
			{
				if (method_exists($l, 'info'))
				{
					$site_langs[] = $l->info()->name;
				}
			}
		}
		$site_langs = $site_langs ?: ['fr'];

		if (!in_array($lang, $site_langs, TRUE))
		{
			$lang = $lang_default;
		}

		$existing = $template['translations'][$lang] ?? ['subject' => '', 'body' => ''];

		$placeholders = [];
		if (!empty($template['placeholders']))
		{
			$decoded = json_decode($template['placeholders'], TRUE);
			if (is_array($decoded)) $placeholders = $decoded;
		}

		$slug = url_title($template['title'] ?: $template['key']);

		// Form2
		$form = $this->form2()
			->rule($this->form_hidden('lang', $lang))
			->rule($this->form_text('subject')
						->title($this->lang('Sujet'))
						->value($existing['subject'])
						->required())
			->rule($this->form_textarea('body')
						->title($this->lang('Corps HTML'))
						->value($existing['body'])
						->rows(16)
						->editor()
						->required())
			->success(function($data) use ($template, $slug){
				$this->model()->save_translation(
					(int)$template['template_id'],
					(string)$data['lang'],
					(string)$data['subject'],
					(string)$data['body']
				);
				notify($this->lang('Modifications enregistrées'));
				redirect('admin/emails/edit/'.$template['template_id'].'/'.$slug.'?lang='.urlencode($data['lang']));
			})
			->submit($this->lang('Enregistrer'));

		// CSS local minimal (uniquement ce qui n'existe pas dans le theme)
		$style = '<style>'
			.'.emails-lang-tabs{display:flex;gap:4px;margin-bottom:14px;border-bottom:2px solid var(--nf-border);}'
			.'.emails-lang-tab{padding:7px 14px;background:transparent;font-weight:600;font-size:12px;color:var(--nf-muted);border-bottom:2px solid transparent;margin-bottom:-2px;text-transform:uppercase;letter-spacing:.5px;text-decoration:none;border-radius:var(--nf-radius-sm) var(--nf-radius-sm) 0 0;transition:color 120ms,background 120ms;}'
			.'.emails-lang-tab:hover{color:var(--nf-text);background:var(--nf-bg);text-decoration:none;}'
			.'.emails-lang-tab.active{color:var(--nf-accent);border-bottom-color:var(--nf-accent);}'
			.'.emails-lang-tab .miss{color:var(--nf-warning);margin-left:4px;font-size:10px;}'
			.'.emails-ph-bar{display:flex;flex-wrap:wrap;gap:6px;align-items:center;padding:10px 14px;background:var(--nf-bg);border:1px dashed var(--nf-border);border-radius:var(--nf-radius-sm);margin-bottom:14px;font-size:12px;}'
			.'.emails-ph-bar .label{color:var(--nf-muted);font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.4px;}'
			.'.emails-ph{cursor:pointer;font-family:Menlo,Monaco,monospace;font-size:11px;background:var(--nf-surface);padding:3px 8px;border-radius:3px;border:1px solid var(--nf-border);color:var(--nf-accent);transition:all 120ms;}'
			.'.emails-ph:hover{background:var(--nf-accent);color:#fff;border-color:var(--nf-accent);}'
			.'.emails-ph.copied{background:var(--nf-success) !important;color:#fff !important;border-color:var(--nf-success) !important;}'
			.'.emails-preview-frame{background:var(--nf-bg);padding:16px;margin:-14px -16px;}'
			.'.emails-preview-mail{max-width:520px;margin:0 auto;background:#fff;border:1px solid #e0e4e8;border-radius:6px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06);}'
			.'.emails-preview-mail-head{padding:14px 18px;border-bottom:1px solid #e0e4e8;background:#fafbfc;}'
			.'.emails-preview-mail-subject{font-weight:600;font-size:15px;color:#212529;margin-bottom:8px;line-height:1.3;}'
			.'.emails-preview-mail-meta{font-size:12px;color:#6c757d;}'
			.'.emails-preview-mail-meta strong{color:#42526e;}'
			.'.emails-preview-mail-body{padding:18px;color:#212529;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;font-size:14px;line-height:1.55;}'
			.'.emails-preview-mail-body p{margin:0 0 10px;}'
			.'</style>';

		// Bouton retour (classe native settings-section-back)
		$back = '<a href="'.url('admin/emails').'" class="settings-section-back">'
			.icon('fas fa-arrow-left').' '.$this->lang('Retour à la liste')
			.'</a>';

		// Bandeau meta (titre + key + module + langues + bouton test)
		$meta  = '<div class="settings-section-card">';
		$meta .= '<div class="settings-section-header">';
		$meta .= '<div class="settings-section-icon"><i class="fas fa-envelope-open-text"></i></div>';
		$meta .= '<div class="settings-section-meta">';
		$meta .= '<div class="settings-section-title">'.htmlspecialchars($template['title']).'</div>';
		$meta .= '<div class="settings-section-subtitle">';
		$meta .= '<code>'.htmlspecialchars($template['key']).'</code>';
		if (!empty($template['module']))
		{
			$meta .= ' &mdash; '.icon('fas fa-cube').' '.htmlspecialchars($template['module']);
		}
		$meta .= ' &mdash; '.icon('fas fa-language').' '.count($template['translations']).' / '.count($site_langs).' '.$this->lang('langues');
		$meta .= '</div>';
		$meta .= '</div>';
		$meta .= '<a class="btn btn-sm btn-outline-info" href="'.url('admin/emails/test/'.$template['template_id'].'/'.$slug).'" style="margin-left:auto;">'.icon('fas fa-paper-plane').' '.$this->lang('Envoi de test').'</a>';
		$meta .= '</div>';
		$meta .= '</div>';

		// Tabs langue
		$lang_tabs = '<div class="emails-lang-tabs">';
		foreach ($site_langs as $l)
		{
			$active = ($l === $lang) ? ' active' : '';
			$miss   = isset($template['translations'][$l]) ? '' : '<i class="fas fa-exclamation-circle miss"></i>';
			$lang_tabs .= '<a class="emails-lang-tab'.$active.'" href="'.url('admin/emails/edit/'.$template['template_id'].'/'.$slug).'?lang='.urlencode($l).'">'.strtoupper(htmlspecialchars($l)).$miss.'</a>';
		}
		$lang_tabs .= '</div>';

		// Barre placeholders (au-dessus du form, compacte)
		$ph_bar = '';
		if ($placeholders)
		{
			$ph_bar  = '<div class="emails-ph-bar">';
			$ph_bar .= '<span class="label">'.icon('fas fa-magic').' '.$this->lang('Placeholders').'</span>';
			foreach ($placeholders as $ph)
			{
				$ph_bar .= '<code class="emails-ph" data-ph="'.htmlspecialchars($ph).'">'.htmlspecialchars($ph).'</code>';
			}
			$ph_bar .= '</div>';
		}

		// Card form (gauche)
		$form_card  = '<div class="settings-section-card">';
		$form_card .= '<div class="settings-section-header">';
		$form_card .= '<div class="settings-section-icon"><i class="fas fa-edit"></i></div>';
		$form_card .= '<div class="settings-section-meta">';
		$form_card .= '<div class="settings-section-title">'.$this->lang('Édition').' &mdash; '.strtoupper(htmlspecialchars($lang)).'</div>';
		$form_card .= '</div>';
		$form_card .= '</div>';
		$form_card .= '<div class="settings-section-body">';
		$form_card .= $ph_bar;
		$form_card .= (string)$form;
		$form_card .= '</div>';
		$form_card .= '</div>';

		// Card preview (droite)
		$preview_card = $this->_render_preview_card($template, $lang);

		// Script copy placeholders
		$script = '<script>(function(){'
			.'document.querySelectorAll(".emails-ph").forEach(function(e){'
				.'e.addEventListener("click", function(){'
					.'var t = this.dataset.ph;'
					.'if (navigator.clipboard) navigator.clipboard.writeText(t);'
					.'this.classList.add("copied");'
					.'setTimeout(()=>{ this.classList.remove("copied"); }, 800);'
				.'});'
			.'});'
			.'})();</script>';

		// Layout split natif via .settings-section-row (responsive built-in : passe en 1 col < 992px)
		return $style
			.$back
			.$meta
			.$lang_tabs
			.'<div class="settings-section-row">'
				.'<div class="settings-section-main">'.$form_card.'</div>'
				.'<div class="settings-section-aside">'.$preview_card.'</div>'
			.'</div>'
			.$script;
	}

	/**
	 * Card aperçu — injectée à droite de l'editor dans _edit.
	 */
	private function _render_preview_card($template, $lang)
	{
		$translation = $template['translations'][$lang] ?? NULL;

		$card  = '<div class="settings-section-card">';
		$card .= '<div class="settings-section-header">';
		$card .= '<div class="settings-section-icon"><i class="far fa-eye"></i></div>';
		$card .= '<div class="settings-section-meta">';
		$card .= '<div class="settings-section-title">'.$this->lang('Aperçu').'</div>';
		$card .= '<div class="settings-section-subtitle">'.$this->lang('Valeurs factices, version sauvegardée').'</div>';
		$card .= '</div>';
		$card .= '</div>';
		$card .= '<div class="settings-section-body">';

		if (!$translation)
		{
			$card .= '<div class="text-muted text-center" style="padding:30px 0;"><em>'.$this->lang('Pas de template pour cette langue').'</em></div>';
			$card .= '</div></div>';
			return $card;
		}

		$card .= '<div class="alert alert-warning" style="font-size:12px;margin-bottom:12px;">'
			.icon('fas fa-info-circle').' '
			.$this->lang('Enregistre tes modifs pour rafraîchir cet aperçu.')
			.'</div>';

		// Valeurs d'exemple réalistes
		$site_name = (string)$this->config->nf_name ?: 'Mon Site';
		$site_url  = rtrim((isset($this->url->host) ? (($this->url->https ? 'https' : 'http').'://'.$this->url->host) : 'https://example.com'), '/');

		$samples = [
			'username'        => 'Jean Dupont',
			'site_name'       => $site_name,
			'validation_url'  => $site_url.'/user/validation/abc123def456',
			'reset_url'       => $site_url.'/user/lost-password/abc123def456',
			'topic_title'     => 'Question sur la dernière màj',
			'topic_url'       => $site_url.'/forum/topic/42/question-sur-la-derniere-maj',
			'mentioner'       => 'Alice',
			'author'          => 'Bob',
			'talk_name'       => 'Discussion privée',
			'talk_url'        => $site_url.'/talks/17/discussion-privee',
			'sanction_type'   => 'Avertissement',
			'reason'          => 'Comportement non conforme à la charte',
			'duration'        => '7 jours',
			'confirm_url'     => $site_url.'/newsletter/confirm/abc123def456'
		];

		$placeholders = [];
		if (!empty($template['placeholders']))
		{
			$decoded = json_decode($template['placeholders'], TRUE);
			if (is_array($decoded))
			{
				foreach ($decoded as $ph)
				{
					$key = trim($ph, '{}');
					$placeholders[$key] = $samples[$key] ?? ('['.$key.']');
				}
			}
		}
		$placeholders['site_name'] = $samples['site_name'];

		$model = $this->model();
		$rendered_subject = $model::render_placeholders($translation['subject'], $placeholders);
		$rendered_body    = $model::render_placeholders($translation['body'], $placeholders);

		$card .= '<div class="emails-preview-frame">';
		$card .= '<div class="emails-preview-mail">';
		$card .= '<div class="emails-preview-mail-head">';
		$card .= '<div class="emails-preview-mail-subject">'.htmlspecialchars($rendered_subject).'</div>';
		$card .= '<div class="emails-preview-mail-meta">';
		$card .= '<div><strong>'.$this->lang('De').' :</strong> '.htmlspecialchars($site_name).' &lt;'.htmlspecialchars($this->config->nf_contact ?: 'noreply@example.com').'&gt;</div>';
		$card .= '<div><strong>'.$this->lang('À').' :</strong> jean.dupont@example.com</div>';
		$card .= '</div>';
		$card .= '</div>';
		$card .= '<div class="emails-preview-mail-body">'.$rendered_body.'</div>';
		$card .= '</div>';
		$card .= '</div>';

		$card .= '</div>'; // .settings-section-body
		$card .= '</div>'; // .settings-section-card

		return $card;
	}

	/**
	 * Vue _preview obsolète (preview est maintenant intégré dans _edit en colonne droite).
	 * On redirige pour ne pas casser les liens existants (la liste pointe directement vers _edit désormais).
	 */
	public function _preview($template)
	{
		$lang = (isset($this->config->lang) && method_exists($this->config->lang, 'info'))
			? $this->config->lang->info()->name
			: 'fr';

		$slug = url_title($template['title'] ?: $template['key']);

		redirect('admin/emails/edit/'.$template['template_id'].'/'.$slug.'?lang='.urlencode($lang));
	}

	public function _toggle($template)
	{
		$this->check_csrf('admin/emails');

		$enable = empty($template['enabled']);
		$this->model()->set_enabled($template['template_id'], $enable);
		notify($this->lang($enable ? 'Template activé' : 'Template désactivé'));
		redirect('admin/emails');
	}

	public function _test($template)
	{
		$this->title($this->lang('Envoi de test').' — '.$template['title']);
		$this->icon('fas fa-paper-plane');

		$default_email = $this->user() ? $this->user->email : '';
		$slug          = url_title($template['title'] ?: $template['key']);

		$form = $this->form2()
			->rule($this->form_email('email')
						->title($this->lang('Adresse email de test'))
						->value($default_email)
						->required())
			->success(function($data) use ($template){
				$placeholders = [];
				if (!empty($template['placeholders']))
				{
					$decoded = json_decode($template['placeholders'], TRUE);
					if (is_array($decoded))
					{
						foreach ($decoded as $ph)
						{
							$key = trim($ph, '{}');
							$placeholders[$key] = '['.$key.']';
						}
					}
				}

				$ok = $this->email
							->template($template['key'], $placeholders)
							->to($data['email'])
							->send();

				if ($ok)
				{
					notify($this->lang('Email de test envoyé à %s (via %s)', $data['email'], $this->email->last_transport()));
				}
				else
				{
					$err = $this->email->last_error();
					notify($this->lang('Échec de l\'envoi').($err ? ' — '.htmlspecialchars($err) : ''), 'danger');
				}

				redirect('admin/emails');
			})
			->submit($this->lang('Envoyer le test'));

		// Bouton retour + bouton éditer
		$back = '<a href="'.url('admin/emails').'" class="settings-section-back">'
			.icon('fas fa-arrow-left').' '.$this->lang('Retour à la liste')
			.'</a>';

		// Card meta du template
		$info_card  = '<div class="settings-section-card">';
		$info_card .= '<div class="settings-section-header">';
		$info_card .= '<div class="settings-section-icon"><i class="fas fa-paper-plane"></i></div>';
		$info_card .= '<div class="settings-section-meta">';
		$info_card .= '<div class="settings-section-title">'.htmlspecialchars($template['title']).'</div>';
		$info_card .= '<div class="settings-section-subtitle"><code>'.htmlspecialchars($template['key']).'</code>';
		if (!empty($template['module']))
		{
			$info_card .= ' &mdash; '.htmlspecialchars($template['module']);
		}
		$info_card .= '</div>';
		$info_card .= '</div>';
		$info_card .= '<a class="btn btn-sm btn-outline-primary" href="'.url('admin/emails/edit/'.$template['template_id'].'/'.$slug).'" style="margin-left:auto;">'.icon('fas fa-edit').' '.$this->lang('Éditer').'</a>';
		$info_card .= '</div>';
		$info_card .= '<div class="settings-section-body">';
		$info_card .= '<div class="alert alert-info" style="margin-bottom:0;font-size:13px;">';
		$info_card .= icon('fas fa-info-circle').' ';
		$info_card .= $this->lang('Le template sera envoyé avec des valeurs de placeholder factices.');
		$info_card .= ' <strong>'.$this->lang('Seule l\'adresse saisie ci-dessous recevra le message.').'</strong>';
		$info_card .= '</div>';
		$info_card .= '</div>';
		$info_card .= '</div>';

		// Card form
		$form_card  = '<div class="settings-section-card">';
		$form_card .= '<div class="settings-section-header">';
		$form_card .= '<div class="settings-section-icon"><i class="fas fa-at"></i></div>';
		$form_card .= '<div class="settings-section-meta">';
		$form_card .= '<div class="settings-section-title">'.$this->lang('Destinataire').'</div>';
		$form_card .= '</div>';
		$form_card .= '</div>';
		$form_card .= '<div class="settings-section-body">';
		$form_card .= (string)$form;
		$form_card .= '</div>';
		$form_card .= '</div>';

		// Layout centré : col-lg-7 offset-lg-2 (largeur raisonnable, pas étiré)
		return $back
			.'<div class="row">'
				.'<div class="col-12 col-lg-8 offset-lg-2">'
					.$info_card
					.$form_card
				.'</div>'
			.'</div>';
	}
}
