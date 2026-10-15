<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Collectives\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($collectifs)
	{
		$this->title($this->lang('Collectifs'))->icon('fas fa-people-group');

		if (empty($collectifs))
		{
			$corps = $this->admin_empty('fas fa-people-group', $this->lang('Aucun collectif.'), $this->lang('Un collectif réunit des membres autour d’un projet : il reçoit une section privée du forum, un rôle et un salon sur Discord, et son chef gère ses membres.'));
		}
		else
		{
			$corps = '<table class="table table-hover"><thead><tr>'
				.'<th>'.$this->lang('Nom').'</th>'
				.'<th>'.$this->lang('Chef').'</th>'
				.'<th class="text-end">'.$this->lang('Membres').'</th>'
				.'<th>'.$this->lang('Espace').'</th>'
				.'<th class="text-end"></th></tr></thead><tbody>';

			foreach ($collectifs as $c)
			{
				$slug = url_title($c['name']);

				$corps .= '<tr>'
					.'<td><a href="'.url('collectives/'.(int) $c['collective_id'].'/'.$slug).'"><strong>'.nf_texte($c['name']).'</strong></a></td>'
					.'<td>'.(!empty($c['chef']) ? nf_texte($c['chef']) : '<span class="badge text-bg-warning">'.$this->lang('Sans chef').'</span>').'</td>'
					.'<td class="text-end">'.(int) $c['nombre'].'</td>'
					.'<td>'
					.(!empty($c['category_id']) ? '<span class="badge text-bg-secondary">'.icon('far fa-comments').' '.$this->lang('Forum').'</span> ' : '')
					.(!empty($c['discord']) ? '<span class="badge '.(!empty($c['discord_channel_id']) ? 'text-bg-success' : 'text-bg-warning').'" title="'.nf_texte(!empty($c['discord_channel_id']) ? $this->lang('Le salon existe sur le serveur') : $this->lang('Le bot créera le salon à son prochain passage')).'">'.icon('fab fa-discord').' Discord</span>' : '')
					.'</td>'
					.'<td class="text-end" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/collectives/'.(int) $c['collective_id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/collectives/delete/'.(int) $c['collective_id'].'/'.$slug).'" data-confirm="'.nf_texte($this->lang('Supprimer ce collectif ? Ses membres perdent son espace ; sa section du forum et son salon restent, réservés aux administrateurs.')).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}

			$corps .= '</tbody></table>';

			$pagination = (string) $this->module->pagination->get_pagination();

			if ($pagination !== '')
			{
				$corps .= '<div style="margin-top:12px;text-align:center;">'.$pagination.'</div>';
			}
		}

		return $this->admin_card('fas fa-people-group', $this->lang('Collectifs'), $corps, $this->lang('%d collectif|%d collectifs', count($collectifs), count($collectifs)), $this->admin_create('admin/collectives/add', $this->lang('Nouveau collectif')));
	}

	public function _add($collectif)
	{
		return $this->_formulaire(NULL);
	}

	public function _edit($collectif)
	{
		return $this->_formulaire($collectif);
	}

	public function _delete($collectif)
	{
		$this->check_csrf('admin/collectives');

		$this->_modele()->supprimer((int) $collectif['collective_id']);
		notify($this->lang('Collectif supprimé.'));
		redirect('admin/collectives');
	}

	private function _formulaire(?array $collectif)
	{
		$nouveau = $collectif === NULL;
		$modele  = $this->_modele();
		$chef    = '';

		foreach ($nouveau ? [] : $modele->membres((int) $collectif['collective_id']) as $m)
		{
			if ($m['rank'] === 'chef')
			{
				$chef = (string) $m['username'];
			}
		}

		$this->title($nouveau ? $this->lang('Nouveau collectif') : $this->lang('Éditer le collectif'))->icon('fas fa-people-group')->breadcrumb();

		$this->form()
			 ->add_rules([
				'name'        => ['label' => $this->lang('Nom'), 'type' => 'text', 'value' => $nouveau ? '' : $collectif['name'], 'rules' => 'required'],
				'description' => ['label' => $this->lang('Description'), 'type' => 'textarea', 'value' => $nouveau ? '' : (string) $collectif['description']],
				'chef'        => ['label' => $this->lang('Chef (pseudo)'), 'type' => 'text', 'value' => $chef, 'rules' => 'required', 'description' => $this->lang('Il gère les membres du collectif. Un ancien chef reste, comme administrateur du collectif.')],
				'discord'     => ['label' => $this->lang('Discord'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Un rôle et un salon privé sur le serveur Discord')], 'checked' => ['1' => $nouveau || !empty($collectif['discord'])]]
			 ])
			 ->add_submit($nouveau ? $this->lang('Créer') : $this->lang('Enregistrer'), $nouveau ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$chef_id = $modele->membre_par_pseudo(nf_texte_brut($post['chef']));

			if ($chef_id === NULL)
			{
				notify($this->lang('Aucun membre ne porte ce pseudo.'), 'danger');
			}
			else
			{
				$nom         = mb_substr(trim((string) $post['name']), 0, 100);
				$description = trim((string) $post['description']);
				$discord     = in_array('1', (array) ($post['discord'] ?? []), TRUE);

				if ($nouveau)
				{
					$modele->creer($nom, $description, $chef_id, $discord);
					notify($this->lang('Collectif créé.'));
				}
				else
				{
					$modele->modifier((int) $collectif['collective_id'], $nom, $description, $chef_id, $discord);
					notify($this->lang('Collectif modifié.'));
				}

				redirect('admin/collectives');
			}
		}

		return $this->admin_card($nouveau ? 'fas fa-plus' : 'fas fa-edit', $nouveau ? $this->lang('Nouveau collectif') : $this->lang('Éditer le collectif'), $this->form()->display());
	}

	private function _modele(): \NF\Modules\Collectives\Models\Collectives
	{
		$modele = $this->model();

		if (!$modele instanceof \NF\Modules\Collectives\Models\Collectives)
		{
			throw new \LogicException('modèle des collectifs introuvable');
		}

		return $modele;
	}
}
