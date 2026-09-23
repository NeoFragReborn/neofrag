<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Partners\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		$partners = $this->model()->get_partners();

		if (empty($partners)) {
			$body = $this->admin_empty('far fa-handshake', $this->lang('Aucun partenaire'));
		} else {
			$body = '<div class="nf-card-grid">';
			foreach ($partners as $p) {
				$body .= '<div class="nf-content-card">';
				if (!empty($p['logo_light'])) {
					$logo_path = NeoFrag()->model2('file', $p['logo_light'])->path();
					if ($logo_path) {
						$body .= '<div class="nf-content-card-thumb" style="background:#fff;display:flex;align-items:center;justify-content:center;aspect-ratio:auto;height:100px;"><img src="'.url($logo_path).'" alt="" style="max-width:80%;max-height:80%;object-fit:contain;width:auto;height:auto;"></div>';
					}
				}
				$body .= '<div class="nf-content-card-head">';
				$body .= '<div class="nf-content-card-title">'.htmlspecialchars((string) ($p['title'])).'</div>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-meta">';
				if (!empty($p['website'])) {
					$host = parse_url($p['website'], PHP_URL_HOST) ?: $p['website'];
					$body .= '<span><i class="fas fa-globe"></i> <a href="'.htmlspecialchars((string) ($p['website'])).'" target="_blank" rel="noopener">'.htmlspecialchars((string) ($host)).'</a></span>';
				}
				$body .= '<span title="'.$this->lang('Visites').'"><i class="fas fa-chart-line"></i> '.(int)$p['count'].'</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-foot">';
				$body .= '<span class="nf-content-card-spacer"></span>';
				if ($this->is_authorized('modify_partners')) $body .= '<a class="btn btn-sm btn-outline-primary" href="'.url('admin/partners/'.$p['partner_id'].'/'.$p['name']).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				if ($this->is_authorized('delete_partners')) $body .= '<a class="btn btn-sm btn-outline-danger" href="'.url('admin/partners/delete/'.$p['partner_id'].'/'.$p['name']).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer ?')), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$body .= '</div>';
				$body .= '</div>';
			}
			$body .= '</div>';
		}

		$actions = $this->is_authorized('add_partners')
			? '<a class="btn btn-sm btn-primary" href="'.url('admin/partners/add').'">'.icon('fas fa-plus').' '.$this->lang('Ajouter').'</a>'
			: '';
		return $this->admin_card('far fa-handshake', $this->lang('Partenaires'), $body, count($partners).' '.$this->lang('partenaire|partenaires', count($partners)), $actions);
	}

	public function add()
	{
		$this	->subtitle($this->lang('Ajouter un partenaire'))
				->form()
				->add_rules('partners')
				->add_submit($this->lang('Ajouter'), 'fas fa-plus')
				->add_back('admin/partners');

		if ($this->form()->is_valid($post))
		{
			$this->model()->add_partner($post['title'],
										$post['logo_light'],
										$post['logo_dark'],
										$post['description'],
										$post['website'],
										$post['facebook'],
										$post['twitter'],
										$post['code']);

			notify($this->lang('Partenaire ajouté avec succès'));

			redirect('admin/partners');
		}

		return $this->admin_card('far fa-handshake', $this->lang('Ajouter un partenaire'), $this->form()->display());
	}

	public function _edit($partner_id, $name, $logo_light, $logo_dark, $website, $facebook, $twitter, $count, $code, $title, $description)
	{
		$this	->subtitle($title)
				->form()
				->add_rules('partners', [
					'title'       => $title,
					'logo_light'  => $logo_light,
					'logo_dark'   => $logo_dark,
					'description' => $description,
					'website'     => $website,
					'facebook'    => $facebook,
					'twitter'     => $twitter,
					'code'        => $code
				])
				->add_submit($this->lang('Éditer'))
				->add_back('admin/partners');

		if ($this->form()->is_valid($post))
		{
			$this->model()->edit_partner(	$partner_id,
											$post['title'],
											$post['logo_light'],
											$post['logo_dark'],
											$post['description'],
											$post['website'],
											$post['facebook'],
											$post['twitter'],
											$post['code']);

			notify($this->lang('Partenaire modifié avec succès'));

			redirect_back('admin/partners');
		}

		return $this->admin_card('far fa-handshake', $this->lang('Éditer le partenaire').' — '.$title, $this->form()->display());
	}

	public function _delete($partner_id, $title)
	{
		$this	->title($this->lang('Supprimer le partenaire'))
				->subtitle($title)
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer le partenaire <b>%s</b> ?', $title));

		if ($this->form()->is_valid())
		{
			$this->model()->delete_partner($partner_id);

			return 'OK';
		}

		return $this->form()->display();
	}
}
