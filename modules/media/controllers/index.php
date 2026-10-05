<?php
declare(strict_types=1);
namespace NF\Modules\Media\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Media\Media;

class Index extends Controller_Module
{
	public function index($medias)
	{
		$this->title($this->lang('Galerie médias'))->icon('fas fa-photo-video')->breadcrumb();

		if (empty($medias))
		{
			$body = '<div class="alert alert-info text-center">'.$this->lang('Aucune image dans la bibliothèque pour le moment.').'</div>';
		}
		else
		{
			$body = '<div class="row">';
			foreach ($medias as $m)
			{
				$url_img = url('upload/media/'.$m['filename']);
				$body .= '<div class="col-12 col-lg-6 col-md-3 mb-3">';
				$body .= '<a href="'.$url_img.'" target="_blank" class="d-block">';
				$body .= '<img src="'.$url_img.'" alt="'.nf_texte($m['title'] ?? $m['original_name']).'" class="img-fluid img-thumbnail" loading="lazy">';
				$body .= '</a>';
				$body .= '<small class="text-muted d-block">'.nf_texte($m['title'] ?? $m['original_name']).'</small>';
				$body .= '</div>';
			}
			$body .= '</div>';
		}

		return $this->panel()->title($this->lang('Médias'), 'fas fa-photo-video')->body($body);
	}
}
