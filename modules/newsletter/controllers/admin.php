<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Newsletter\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		$this	->title($this->lang('Newsletter'))
				->icon('far fa-envelope');

		$nb_subs = $nb_pending = $nb_camps = 0;
		try { $nb_subs    = (int)NeoFrag()->db->from('nf_newsletter_subscribers')->where('confirmed', 1)->count(); } catch (\Throwable $e) {}
		try { $nb_pending = (int)NeoFrag()->db->from('nf_newsletter_subscribers')->where('confirmed', 0)->count(); } catch (\Throwable $e) {}
		try { $nb_camps   = (int)NeoFrag()->db->from('nf_newsletter_campaigns')->count(); } catch (\Throwable $e) {}

		// Stat cards
		$stats = '<div class="nf-stats-grid">'
			.'<div class="nf-stat-card"><div class="nf-stat-label"><i class="fas fa-user-check"></i> '.$this->lang('Abonnés confirmés').'</div><div class="nf-stat-value">'.number_format($nb_subs, 0, ',', ' ').'</div></div>'
			.'<div class="nf-stat-card"><div class="nf-stat-label"><i class="fas fa-clock"></i> '.$this->lang('En attente').'</div><div class="nf-stat-value">'.number_format($nb_pending, 0, ',', ' ').'</div>'
			.($nb_pending > 0 ? '<div class="nf-stat-trend warn"><i class="fas fa-exclamation-circle"></i> '.$this->lang('double opt-in non confirmé').'</div>' : '')
			.'</div>'
			.'<div class="nf-stat-card"><div class="nf-stat-label"><i class="fas fa-paper-plane"></i> '.$this->lang('Campagnes envoyées').'</div><div class="nf-stat-value">'.number_format($nb_camps, 0, ',', ' ').'</div></div>'
			.'</div>';

		// Action card
		$header_left  = '<span><i class="far fa-envelope"></i> '.$this->lang('Newsletter').'</span>';
		$header_right = '<span style="display:flex;gap:6px;">'
			.'<a class="btn btn-secondary btn-sm" href="'.url('admin/newsletter/subscribers').'"><i class="fas fa-users"></i> '.$this->lang('Abonnés').'</a>'
			.'<a class="btn btn-secondary btn-sm" href="'.url('admin/newsletter/campaigns').'"><i class="fas fa-history"></i> '.$this->lang('Historique').'</a>'
			.'<a class="btn btn-secondary btn-sm" href="'.url('admin/newsletter/templates').'"><i class="far fa-file-alt"></i> '.$this->lang('Modèles').'</a>'
			.'<a class="btn btn-primary btn-sm" href="'.url('admin/newsletter/compose').'"><i class="fas fa-paper-plane"></i> '.$this->lang('Composer').'</a>'
			.'</span>';
		$body = '<div class="card-body" style="color:var(--nf-text-soft);font-size:13.5px;">'
			.$this->lang('Gère ta liste d\'abonnés et l\'envoi de campagnes. Le double opt-in est activé : les abonnés doivent confirmer via email avant d\'apparaître dans tes campagnes.')
			.'</div>';

		return $stats.'<div class="card"><div class="card-header">'.$header_left.$header_right.'</div>'.$body.'</div>';
	}

	public function _subscribers($subs)
	{
		$this->title($this->lang('Abonnés'))->icon('fas fa-users')->breadcrumb();

		$header_left  = '<span><i class="fas fa-users"></i> '.$this->lang('Abonnés newsletter').' <small class="text-muted" style="font-weight:400;font-size:12px;margin-left:8px;">'.count($subs).'</small></span>';
		$header_right = '';

		if (empty($subs)) {
			$body = '<div class="nf-empty"><i class="fas fa-users"></i>'.$this->lang('Aucun abonné.').'</div>';
		} else {
			$body = '<table class="table table-hover" style="margin:0;"><thead><tr>'
				.'<th style="width:1%;">#</th><th>'.$this->lang('Email').'</th><th>'.$this->lang('Statut').'</th><th>'.$this->lang('Date inscription').'</th><th class="text-end">'.$this->lang('Actions').'</th>'
				.'</tr></thead><tbody>';
			foreach ($subs as $s) {
				$status = $s['confirmed']
					? '<span class="badge text-bg-success"><span class="dot"></span> '.$this->lang('Confirmé').'</span>'
					: '<span class="badge text-bg-warning"><span class="dot"></span> '.$this->lang('En attente').'</span>';
				$body .= '<tr>'
					.'<td style="font-family:\'JetBrains Mono\',monospace;font-size:12px;color:var(--nf-text-muted);">#'.(int)$s['id'].'</td>'
					.'<td><strong>'.htmlspecialchars((string) ($s['email'])).'</strong></td>'
					.'<td>'.$status.'</td>'
					.'<td style="color:var(--nf-text-muted);font-feature-settings:\'tnum\';">'.nf_date_heure($s['ts']).'</td>'
					.'<td class="text-end"><a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/newsletter/subscribers/delete/'.$s['id']).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer cet abonné ?')), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a></td>'
					.'</tr>';
			}
			$body .= '</tbody></table>';
		}

		return '<div class="card"><div class="card-header">'.$header_left.$header_right.'</div>'.$body.'</div>';
	}

	public function _campaigns($campaigns)
	{
		$this->title($this->lang('Historique campagnes'))->icon('fas fa-history')->breadcrumb();

		$header_left  = '<span><i class="fas fa-history"></i> '.$this->lang('Historique des campagnes').' <small class="text-muted" style="font-weight:400;font-size:12px;margin-left:8px;">'.count($campaigns).'</small></span>';
		$header_right = '';

		if (empty($campaigns)) {
			$body = '<div class="nf-empty"><i class="fas fa-paper-plane"></i>'.$this->lang('Aucune campagne pour le moment.').'</div>';
		} else {
			$body = '<table class="table table-hover" style="margin:0;"><thead><tr>'
				.'<th style="width:1%;">#</th><th>'.$this->lang('Sujet').'</th><th>'.$this->lang('Statut').'</th><th class="text-end">'.$this->lang('Destinataires').'</th><th>'.$this->lang('Date').'</th><th class="text-end"></th>'
				.'</tr></thead><tbody>';
			foreach ($campaigns as $c) {
				$status      = (string)($c['status'] ?? 'sent');
				$total       = (int)$c['recipients_total'];
				$sent        = (int)$c['sent_to'];
				$failed      = (int)$c['failed_to'];
				$opened      = (int)($c['opened_to'] ?? 0);

				$seg_label = '';
				if (($c['segment'] ?? 'all') === 'members')    { $seg_label = ' <small class="text-muted">· <i class="fas fa-user-friends"></i> '.$this->lang('Membres').'</small>'; }
				else if (($c['segment'] ?? 'all') === 'group') { $seg_label = ' <small class="text-muted">· <i class="fas fa-users"></i> '.htmlspecialchars((string) ($c['segment_group_title'] ?: $this->lang('Groupe'))).'</small>'; }

				switch ($status) {
					case 'scheduled':
						$badge = '<span class="badge text-bg-warning"><i class="fas fa-clock"></i> '.$this->lang('Programmée').'</span>';
						$recip = $total > 0 ? '≈ '.$total : '—';
						$date  = $c['scheduled_at'] ? timetostr('j M Y H:i', $c['scheduled_at']) : '—';
						break;
					case 'sending':
						$badge = '<span class="badge text-bg-info"><i class="fas fa-paper-plane"></i> '.$this->lang('En cours').'</span>';
						$recip = $sent.' / '.$total;
						$date  = $c['scheduled_at'] ? timetostr('j M Y H:i', $c['scheduled_at']) : '—';
						break;
					case 'cancelled':
						$badge = '<span class="badge text-bg-secondary"><i class="fas fa-ban"></i> '.$this->lang('Annulée').'</span>';
						$recip = '—';
						$date  = $c['scheduled_at'] ? timetostr('j M Y H:i', $c['scheduled_at']) : '—';
						break;
					default:
						$badge = '<span class="badge text-bg-success"><i class="fas fa-check"></i> '.$this->lang('Envoyée').'</span>'
							.($failed > 0 ? ' <span class="badge text-bg-danger" title="'.htmlspecialchars((string) ($this->lang('Échecs d\'envoi')), ENT_QUOTES).'">'.$failed.' ⚠</span>' : '');
						$recip = $sent.($sent > 0 ? ' <small class="text-muted" title="'.htmlspecialchars((string) ($this->lang('Taux d\'ouverture')), ENT_QUOTES).'">· '.$opened.' '.$this->lang('ouv.').' ('.round($opened / $sent * 100).'%)</small>' : '');
						$date  = $c['sent_at'] ? timetostr('j M Y H:i', $c['sent_at']) : '—';
				}

				$actions = '';
				if ($status === 'scheduled') {
					$actions = '<a class="btn btn-sm btn-outline-primary" href="'.$this->csrf_url('admin/newsletter/campaigns/send/'.$c['id']).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Envoyer cette campagne maintenant ?')), ENT_QUOTES).'" title="'.$this->lang('Envoyer maintenant').'"><i class="fas fa-paper-plane"></i></a> '
						.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/newsletter/campaigns/cancel/'.$c['id']).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Annuler cette campagne programmée ?')), ENT_QUOTES).'" title="'.$this->lang('Annuler').'"><i class="fas fa-ban"></i></a>';
				}

				$body .= '<tr>'
					.'<td style="font-family:\'JetBrains Mono\',monospace;font-size:12px;color:var(--nf-text-muted);">#'.(int)$c['id'].'</td>'
					.'<td><strong>'.htmlspecialchars((string) ($c['subject'])).'</strong>'.$seg_label.'<br><small class="text-muted">'.htmlspecialchars((string) ($c['username'] ?? '—')).'</small></td>'
					.'<td>'.$badge.'</td>'
					.'<td class="text-end" style="font-feature-settings:\'tnum\';">'.$recip.'</td>'
					.'<td style="color:var(--nf-text-muted);font-feature-settings:\'tnum\';">'.$date.'</td>'
					.'<td class="text-end" style="white-space:nowrap;">'.$actions.'</td>'
					.'</tr>';
			}
			$body .= '</tbody></table>';
		}

		return '<div class="card"><div class="card-header">'.$header_left.$header_right.'</div>'.$body.'</div>';
	}

	public function _compose()
	{
		$this->title($this->lang('Composer une newsletter'))->icon('fas fa-paper-plane')->breadcrumb();

		/** @var \NF\Modules\Newsletter\Models\Newsletter $model */
		$model = $this->model('newsletter');
		$nb    = $model->confirmed_count();

		// Chargeur de modèle (remplit sujet + contenu côté client, sans rechargement).
		$loader   = '';
		$tpl_rows = NeoFrag()->db->select('id', 'name', 'subject', 'content')->from('nf_newsletter_templates')->order_by('name ASC')->get(FALSE);
		if ($tpl_rows)
		{
			$map     = [];
			$options = '<option value="">'.$this->lang('— Charger un modèle —').'</option>';
			foreach ($tpl_rows as $t)
			{
				$map[(int)$t['id']] = ['subject' => $t['subject'], 'content' => $t['content']];
				$options .= '<option value="'.(int)$t['id'].'">'.htmlspecialchars((string) ($t['name'])).'</option>';
			}

			$this->js('newsletter-compose');

			$loader = '<div class="card" style="margin-bottom:12px;"><div class="card-body">'
				.'<label class="form-label" style="margin-right:8px;font-weight:600;">'.$this->lang('Modèle').'</label>'
				.'<select id="nf-nl-template" class="form-select" style="max-width:320px;display:inline-block;width:auto;" data-templates="'.htmlspecialchars((string) (json_encode($map)), ENT_QUOTES).'">'.$options.'</select>'
				.' <small class="text-muted">'.$this->lang('Remplit le sujet et le contenu ci-dessous.').'</small>'
				.'</div></div>';
		}

		// Cibles : tous / membres / un groupe (les groupes alimentent les options group_<id>).
		$target_values = ['all' => $this->lang('Tous les abonnés confirmés'), 'members' => $this->lang('Membres uniquement')];
		foreach (NeoFrag()->db	->select('g.group_id', 'IFNULL(MAX(gl.title), g.name) AS title')
								->from('nf_groups g')
								->join('nf_groups_lang gl', 'gl.group_id = g.group_id', 'LEFT')
								->group_by('g.group_id', 'g.name')
								->order_by('title ASC')
								->get(FALSE) as $g)
		{
			$target_values['group_'.(int)$g['group_id']] = $this->lang('Groupe : %s', $g['title']);
		}

		$this->form()
			 ->add_rules([
				'subject' => [
					'label' => $this->lang('Sujet'),
					'type'  => 'text',
					'rules' => 'required'
				],
				'content' => [
					'label' => $this->lang('Contenu (HTML)'),
					'type'  => 'textarea',
					'rules' => 'required',
					'description' => $this->lang('HTML autorisé. Un lien de désinscription sera ajouté automatiquement.')
				],
				'target' => [
					'label'  => $this->lang('Cible'),
					'type'   => 'select',
					'value'  => 'all',
					'values' => $target_values,
					'size'   => 'col-6'
				],
				'scheduled_at' => [
					'label' => $this->lang('Date d\'envoi'),
					'type'  => 'datetime',
					'size'  => 'col-6',
					'description' => $this->lang('Laisser vide pour envoyer maintenant. Une date future programme l\'envoi (traité à l\'heure réelle par le cron).')
				]
			 ])
			 ->add_submit($this->lang('Valider (%d abonné(s) confirmé(s))', $nb));

		if ($this->form()->is_valid($post))
		{
			$when      = trim((string)($post['scheduled_at'] ?? ''));
			$is_future = $when !== '' && strtotime($when) > time();

			$target   = (string)($post['target'] ?? 'all');
			$segment  = 'all';
			$group_id = NULL;
			if ($target === 'members')                  { $segment = 'members'; }
			else if (strpos($target, 'group_') === 0)   { $segment = 'group'; $group_id = (int)substr($target, 6); }

			$campaign_id = $model->schedule($post['subject'], $post['content'], (int)$this->user->id, $is_future ? $when : NULL, $segment, $group_id);

			if ($is_future)
			{
				notify($this->lang('Campagne programmée pour le %s.', timetostr($this->lang('d/m/Y H:i'), $when)));
			}
			else
			{
				$r = $model->send_now($campaign_id);
				notify($r['failed'] > 0
					? $this->lang('Campagne envoyée : %d réussi(s), %d échec(s).', $r['sent'], $r['failed'])
					: $this->lang('Campagne envoyée à %d abonné(s).', $r['sent']));
			}

			redirect('admin/newsletter/campaigns');
		}

		return $loader.$this->admin_card('fas fa-paper-plane', $this->lang('Nouvelle campagne'), $this->form()->display());
	}

	public function _subscriber_delete($id)
	{
		$this->check_csrf('admin/newsletter/subscribers');

		NeoFrag()->db	->where('id', $id)
						->delete('nf_newsletter_subscribers');

		notify($this->lang('Abonné supprimé.'));
		redirect('admin/newsletter/subscribers');
	}

	public function _campaign_send($id)
	{
		$this->check_csrf('admin/newsletter/campaigns');

		/** @var \NF\Modules\Newsletter\Models\Newsletter $model */
		$model = $this->model('newsletter');
		$r     = $model->send_now($id);

		notify(($r['sent'] + $r['failed']) > 0
			? $this->lang('Campagne envoyée : %d réussi(s), %d échec(s).', $r['sent'], $r['failed'])
			: $this->lang('Rien à envoyer (aucun abonné confirmé).'),
			$r['failed'] > 0 ? 'warning' : 'success');

		redirect('admin/newsletter/campaigns');
	}

	public function _campaign_cancel($id)
	{
		$this->check_csrf('admin/newsletter/campaigns');

		/** @var \NF\Modules\Newsletter\Models\Newsletter $model */
		$model = $this->model('newsletter');
		$ok    = $model->cancel($id);

		notify($ok ? $this->lang('Campagne annulée.') : $this->lang('Annulation impossible (campagne déjà partie).'), $ok ? 'success' : 'warning');
		redirect('admin/newsletter/campaigns');
	}

	public function _templates($templates)
	{
		$this->title($this->lang('Modèles'))->icon('far fa-file-alt')->breadcrumb();

		if (empty($templates)) {
			$body = '<div class="nf-empty"><i class="far fa-file-alt"></i>'.$this->lang('Aucun modèle. Crée-en un pour réutiliser un sujet + contenu au composer.').'</div>';
		} else {
			$body = '<table class="table table-hover" style="margin:0;"><thead><tr><th>'.$this->lang('Nom').'</th><th>'.$this->lang('Sujet').'</th><th class="text-end"></th></tr></thead><tbody>';
			foreach ($templates as $t) {
				$body .= '<tr>'
					.'<td><strong>'.htmlspecialchars((string) ($t['name'])).'</strong></td>'
					.'<td><small class="text-muted">'.htmlspecialchars((string) ($t['subject'])).'</small></td>'
					.'<td class="text-end" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/newsletter/templates/edit/'.$t['id']).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/newsletter/templates/delete/'.$t['id']).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer ce modèle ?')), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}
			$body .= '</tbody></table>';
		}

		$actions = '<a class="btn btn-primary btn-sm" href="'.url('admin/newsletter/templates/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouveau modèle').'</a>';

		return $this->admin_card('far fa-file-alt', $this->lang('Modèles d\'email'), $body, count($templates).' '.$this->lang('modèle|modèles', count($templates)), $actions);
	}

	public function _template_add()  { return $this->_template_form(NULL); }
	public function _template_edit($t) { return $this->_template_form($t); }

	protected function _template_form($t)
	{
		$is_new = $t === NULL;
		$this->title($is_new ? $this->lang('Nouveau modèle') : $this->lang('Éditer : %s', $t['name']))->icon('far fa-file-alt')->breadcrumb();

		$this->form()
			 ->add_rules([
				'name'    => ['label' => $this->lang('Nom'), 'type' => 'text', 'value' => $is_new ? '' : $t['name'], 'rules' => 'required'],
				'subject' => ['label' => $this->lang('Sujet'), 'type' => 'text', 'value' => $is_new ? '' : $t['subject'], 'rules' => 'required'],
				'content' => ['label' => $this->lang('Contenu (HTML)'), 'type' => 'textarea', 'value' => $is_new ? '' : $t['content'], 'rules' => 'required']
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'), $is_new ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$data = ['name' => $post['name'], 'subject' => $post['subject'], 'content' => $post['content']];

			if ($is_new) NeoFrag()->db->insert('nf_newsletter_templates', $data);
			else         NeoFrag()->db->where('id', $t['id'])->update('nf_newsletter_templates', $data);

			notify($is_new ? $this->lang('Modèle créé.') : $this->lang('Modèle modifié.'));
			redirect('admin/newsletter/templates');
		}

		return $this->admin_back('admin/newsletter/templates', $this->lang('Modèles')).$this->admin_card($is_new ? 'fas fa-plus' : 'fas fa-edit', $is_new ? $this->lang('Nouveau modèle') : $this->lang('Éditer le modèle'), $this->form()->display());
	}

	public function _template_delete($id)
	{
		$this->check_csrf('admin/newsletter/templates');

		NeoFrag()->db->where('id', $id)->delete('nf_newsletter_templates');
		notify($this->lang('Modèle supprimé.'));
		redirect('admin/newsletter/templates');
	}
}
