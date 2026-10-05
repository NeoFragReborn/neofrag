<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Api\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Api\Api;

class Admin extends Controller_Module
{
	/** Les clés d'accès : leur nom, leurs droits, leur dernier usage, et de quoi les révoquer. */
	public function index()
	{
		$this->title($this->lang('API'))->icon('fas fa-plug');

		$libelles = $this->_module()->scope_labels();
		$lignes   = '';

		foreach ($this->_modele()->liste() as $cle)
		{
			$active  = empty($cle['revoked_at']);
			$droits  = implode(', ', array_map(static fn (string $d): string => nf_texte($libelles[$d] ?? $d), array_filter(explode(',', (string) $cle['scopes']))));
			$usage   = !empty($cle['last_used_at']) ? time_span((string) $cle['last_used_at']).' <small class="text-muted">'.nf_texte($cle['last_ip']).'</small>' : '<span class="text-muted">'.$this->lang('Jamais').'</span>';
			$lignes .= '<tr'.($active ? '' : ' class="text-muted"').'><td><strong>'.nf_texte($cle['name']).'</strong><br /><code>'.nf_texte($cle['prefix']).'…</code></td>'
				.'<td>'.($droits ?: '—').'</td><td>'.$usage.'</td>'
				.'<td>'.($active ? '<span class="badge text-bg-success">'.$this->lang('Active').'</span>' : '<span class="badge text-bg-secondary">'.$this->lang('Révoquée').'</span>').'</td>'
				.'<td class="text-end">'.($active ? '<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/api/revoke/'.$cle['token_id'].'/'.url_title((string) $cle['name'])).'" data-confirm="'.nf_texte($this->lang('Révoquer cette clé ? Le programme qui l’utilise perdra l’accès immédiatement.')).'">'.icon('fas fa-ban').' '.$this->lang('Révoquer').'</a>' : '').'</td></tr>';
		}

		$corps = $lignes
			? '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>'.$this->lang('Clé').'</th><th>'.$this->lang('Droits').'</th><th>'.$this->lang('Dernier usage').'</th><th>'.$this->lang('État').'</th><th></th></tr></thead><tbody>'.$lignes.'</tbody></table></div>'
			: $this->admin_empty('fas fa-key', $this->lang('Aucune clé d’accès pour le moment.'), $this->lang('Une clé permet à un programme — le bot Discord, une intégration — de lire ou d’écrire sur le site par l’API, avec les seuls droits que vous lui donnez.'));

		$aide = '<p class="text-muted mb-3">'.$this->lang('Adresse de l’API : %s — chaque requête porte l’en-tête %s.', '<code>'.nf_texte(site_origin().$this->url->base.'api/v1/').'</code>', '<code>Authorization: Bearer nfr_…</code>').'</p>';

		return $this->admin_card('fas fa-plug', $this->lang('Clés d’accès'), $aide.$corps, '',
			'<a class="btn btn-primary btn-sm" href="'.url('admin/api/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle clé').'</a>');
	}

	/** Créer une clé : elle n'est montrée qu'une fois, juste après sa création. */
	public function _add()
	{
		$this->title($this->lang('Nouvelle clé'))->icon('fas fa-key')->breadcrumb();

		$this->form()
			 ->add_rules([
				'name'   => ['label' => $this->lang('Nom'), 'type' => 'text', 'rules' => 'required',
				             'description' => $this->lang('Pour reconnaître la clé : « Bot Discord », « Intégration de l’équipe »…')],
				'scopes' => ['label' => $this->lang('Droits'), 'type' => 'checkbox', 'values' => $this->_module()->scope_labels(),
				             'description' => $this->lang('Ne donnez que les droits dont le programme a besoin.')],
			 ])
			 ->add_submit($this->lang('Créer la clé'), 'fas fa-key')
			 ->add_back('admin/api');

		if ($this->form()->is_valid($post))
		{
			$droits = array_values(array_intersect(Api::SCOPES, array_map('strval', (array) ($post['scopes'] ?? []))));
			$cle    = $this->_modele()->creer(trim((string) $post['name']), $droits, $this->user() ? (int) $this->user->id : NULL);

			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('api.token.created', ['details' => trim((string) $post['name']).' — '.implode(', ', $droits)]);

			return $this->admin_card('fas fa-key', $this->lang('Clé créée'),
				'<div class="alert alert-warning">'.icon('fas fa-exclamation-triangle').' '.$this->lang('Copiez cette clé maintenant : elle ne sera plus jamais affichée. Si vous la perdez, révoquez-la et créez-en une autre.').'</div>'
				// Pas de bouton « Copier » : il demanderait du JavaScript en ligne, que la politique de
				// sécurité du site refuse. Le champ se sélectionne et se copie.
				.'<input type="text" class="form-control font-monospace mb-3" readonly="readonly" value="'.nf_texte($cle).'" aria-label="'.$this->lang('Clé d’accès').'" />'
				.'<a class="btn btn-light" href="'.url('admin/api').'">'.$this->lang('Retour aux clés').'</a>');
		}

		return $this->admin_card('fas fa-key', $this->lang('Nouvelle clé'), $this->form()->display());
	}

	public function _revoke($cle)
	{
		$this->check_csrf('admin/api');

		$this->_modele()->revoquer((int) $cle['token_id']);

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('api.token.revoked', ['details' => (string) $cle['name']]);

		notify($this->lang('Clé révoquée.'));
		redirect('admin/api');
	}

	private function _module(): Api
	{
		$module = $this->module('api');

		if (!$module instanceof Api)
		{
			throw new \LogicException('module de l’API introuvable');
		}

		return $module;
	}

	/** Le modèle de l'API, typé : pour l'analyse statique, `$this->model()` rend un modèle générique. */
	private function _modele(): \NF\Modules\Api\Models\Api
	{
		$modele = $this->model('api');

		if (!$modele instanceof \NF\Modules\Api\Models\Api)
		{
			throw new \LogicException('modèle de l’API introuvable');
		}

		return $modele;
	}
}
