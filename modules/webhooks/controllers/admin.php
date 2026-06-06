<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Webhooks\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Webhooks\Webhooks;

class Admin extends Controller_Module
{
	public function index($hooks)
	{
		$this->title($this->lang('Webhooks'))->icon('fas fa-bolt');

		if (empty($hooks))
		{
			$body = $this->admin_empty('fas fa-bolt', $this->lang('Aucun webhook. Ajoutez-en un pour notifier un service externe (Discord, Zapier…).'));
		}
		else
		{
			$body = '<table class="table table-hover" style="margin:0;"><thead><tr><th>'.$this->lang('Titre').'</th><th>'.$this->lang('URL').'</th><th>'.$this->lang('Événements').'</th><th class="text-right">'.$this->lang('Actif').'</th><th class="text-right"></th></tr></thead><tbody>';
			foreach ($hooks as $h)
			{
				$slug   = url_title($h['title']);
				$events = $h['events'] === '*' ? $this->lang('Tous') : (string)count(array_filter(explode(',', $h['events']))).' '.$this->lang('événement|événements', count(array_filter(explode(',', $h['events']))));
				$body  .= '<tr>'
					.'<td><strong>'.htmlspecialchars($h['title']).'</strong></td>'
					.'<td><small class="text-muted">'.htmlspecialchars($h['url']).'</small></td>'
					.'<td>'.htmlspecialchars($events).'</td>'
					.'<td class="text-right">'.(!empty($h['enabled']) ? '<span class="badge badge-success">'.$this->lang('Oui').'</span>' : '<span class="badge badge-secondary">'.$this->lang('Non').'</span>').'</td>'
					.'<td class="text-right" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-primary" href="'.url('admin/webhooks/edit/'.$h['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.url('admin/webhooks/delete/'.$h['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer ce webhook ?'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}
			$body .= '</tbody></table>';
		}

		$actions = '<a class="btn btn-primary btn-sm" href="'.url('admin/webhooks/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouveau webhook').'</a>';

		return $this->admin_card('fas fa-bolt', $this->lang('Webhooks sortants'), $body, count($hooks).' '.$this->lang('webhook|webhooks', count($hooks)), $actions);
	}

	public function _add()  { return $this->_form(NULL); }
	public function _edit($h) { return $this->_form($h); }
	public function _delete($h)
	{
		NeoFrag()->db->where('id', $h['id'])->delete('nf_webhooks');
		notify($this->lang('Webhook supprimé.'));
		redirect('admin/webhooks');
	}

	protected function _form($h)
	{
		$is_new = $h === NULL;
		$this->title($is_new ? $this->lang('Nouveau webhook') : $this->lang('Éditer : %s', $h['title']))->icon('fas fa-bolt')->breadcrumb();

		$checked = $is_new ? [] : array_fill_keys(array_filter(array_map('trim', explode(',', $h['events']))), TRUE);

		$this->form()
			 ->add_rules([
				'title'   => ['label' => $this->lang('Titre'),  'type' => 'text', 'value' => $is_new ? '' : $h['title'], 'rules' => 'required'],
				'url'     => ['label' => $this->lang('URL de destination'), 'type' => 'text', 'value' => $is_new ? 'https://' : $h['url'], 'rules' => 'required'],
				'secret'  => ['label' => $this->lang('Secret (signature HMAC, optionnel)'), 'type' => 'text', 'value' => $is_new ? '' : $h['secret']],
				'events'  => ['label' => $this->lang('Événements déclencheurs'), 'type' => 'checkbox', 'value' => array_keys($checked), 'values' => Webhooks::EVENTS, 'checked' => $checked],
				'enabled' => ['label' => $this->lang('Activation'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Webhook actif')], 'checked' => ['1' => ($is_new || !empty($h['enabled']))]]
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			$events = array_values(array_intersect(array_keys(Webhooks::EVENTS), $post['events'] ?? []));

			$data = [
				'title'   => $post['title'],
				'url'     => $post['url'],
				'secret'  => $post['secret'] ?? '',
				'events'  => implode(',', $events),
				'enabled' => in_array('1', $post['enabled'] ?? []) ? 1 : 0
			];

			if ($is_new) NeoFrag()->db->insert('nf_webhooks', $data);
			else         NeoFrag()->db->where('id', $h['id'])->update('nf_webhooks', $data);

			notify($is_new ? $this->lang('Webhook créé.') : $this->lang('Webhook modifié.'));
			redirect('admin/webhooks');
		}

		return $this->admin_back('admin/webhooks', $this->lang('Webhooks'))
			 . $this->admin_card($is_new ? 'fas fa-plus' : 'fas fa-edit', $is_new ? $this->lang('Nouveau webhook') : $this->lang('Éditer le webhook'), $this->form()->display());
	}
}
