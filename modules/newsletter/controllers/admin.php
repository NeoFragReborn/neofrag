<?php
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
				.'<th style="width:1%;">#</th><th>'.$this->lang('Email').'</th><th>'.$this->lang('Statut').'</th><th>'.$this->lang('Date inscription').'</th><th class="text-right">'.$this->lang('Actions').'</th>'
				.'</tr></thead><tbody>';
			foreach ($subs as $s) {
				$status = $s['confirmed']
					? '<span class="badge badge-success"><span class="dot"></span> '.$this->lang('Confirmé').'</span>'
					: '<span class="badge badge-warning"><span class="dot"></span> '.$this->lang('En attente').'</span>';
				$body .= '<tr>'
					.'<td style="font-family:\'JetBrains Mono\',monospace;font-size:12px;color:var(--nf-text-muted);">#'.(int)$s['id'].'</td>'
					.'<td><strong>'.htmlspecialchars($s['email']).'</strong></td>'
					.'<td>'.$status.'</td>'
					.'<td style="color:var(--nf-text-muted);font-feature-settings:\'tnum\';">'.date('Y-m-d H:i', $s['ts']).'</td>'
					.'<td class="text-right"><a class="btn btn-sm btn-outline-danger" href="'.url('admin/newsletter/subscribers/delete/'.$s['id']).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer cet abonné ?'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a></td>'
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
			$body = '<div class="nf-empty"><i class="fas fa-paper-plane"></i>'.$this->lang('Aucune campagne envoyée pour le moment.').'</div>';
		} else {
			$body = '<table class="table table-hover" style="margin:0;"><thead><tr>'
				.'<th style="width:1%;">#</th><th>'.$this->lang('Sujet').'</th><th>'.$this->lang('Auteur').'</th><th class="text-right">'.$this->lang('Destinataires').'</th><th>'.$this->lang('Date').'</th>'
				.'</tr></thead><tbody>';
			foreach ($campaigns as $c) {
				$body .= '<tr>'
					.'<td style="font-family:\'JetBrains Mono\',monospace;font-size:12px;color:var(--nf-text-muted);">#'.(int)$c['id'].'</td>'
					.'<td><strong>'.htmlspecialchars($c['subject']).'</strong></td>'
					.'<td>'.htmlspecialchars($c['username'] ?? '—').'</td>'
					.'<td class="text-right" style="font-feature-settings:\'tnum\';">'.(int)$c['sent_to'].'</td>'
					.'<td style="color:var(--nf-text-muted);font-feature-settings:\'tnum\';">'.($c['sent_at'] ? timetostr('j M Y H:i', $c['sent_at']) : '—').'</td>'
					.'</tr>';
			}
			$body .= '</tbody></table>';
		}

		return '<div class="card"><div class="card-header">'.$header_left.$header_right.'</div>'.$body.'</div>';
	}

	public function _compose()
	{
		$this->title($this->lang('Composer une newsletter'))->icon('fas fa-paper-plane')->breadcrumb();

		$nb = (int)NeoFrag()->db->select('COUNT(*)')->from('nf_newsletter_subscribers')->where('confirmed', 1)->row();

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
				]
			 ])
			 ->add_submit($this->lang('Envoyer à %d abonné(s) confirmé(s)', $nb));

		if ($this->form()->is_valid($post))
		{
			// Save campaign
			NeoFrag()->db->insert('nf_newsletter_campaigns', [
				'subject' => $post['subject'],
				'content' => $post['content'],
				'user_id' => $this->user->id
			]);
			$campaign_id = (int)NeoFrag()->db->driver()->insert_id();

			// Get all confirmed subscribers
			$subs = NeoFrag()->db	->select('email', 'token')
									->from('nf_newsletter_subscribers')
									->where('confirmed', 1)
									->get();

			$sent = 0;
			foreach ($subs as $s)
			{
				$unsub_url = url('newsletter/unsubscribe/'.$s['token']);
				$content_with_unsub = $post['content']
					.'<hr><p style="font-size:0.85em;color:#888;text-align:center">'
					.$this->lang('Tu reçois ce mail car tu es inscrit à la newsletter de %s.', htmlspecialchars($this->config->nf_name)).' '
					.'<a href="'.$unsub_url.'">'.$this->lang('Se désinscrire').'</a></p>';

				$success = $this->email
					->to($s['email'])
					->subject($post['subject'])
					->message(function() use ($content_with_unsub){
						return ['content' => $content_with_unsub];
					})
					->send();

				if ($success)
				{
					$sent++;
				}
			}

			NeoFrag()->db	->where('id', $campaign_id)
							->update('nf_newsletter_campaigns', [
								'sent_to' => $sent,
								'sent_at' => NeoFrag()->date()->sql()
							]);

			notify($this->lang('Campagne envoyée à %d abonné(s).', $sent));
			redirect('admin/newsletter/campaigns');
		}

		return $this->admin_back('admin/newsletter', $this->lang('Newsletter')).$this->admin_card('fas fa-paper-plane', $this->lang('Nouvelle campagne'), $this->form()->display());
	}

	public function _subscriber_delete($id)
	{
		NeoFrag()->db	->from('nf_newsletter_subscribers')
						->where('id', $id)
						->delete();

		notify($this->lang('Abonné supprimé.'));
		redirect('admin/newsletter/subscribers');
	}
}
