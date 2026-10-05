<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Widget Vidéo — lecteur principal + playlist (vidéos de nf_media).
 */

namespace NF\Widgets\Video\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($settings = [])
	{
		$count = max(1, min(20, (int) ($settings['count'] ?? 5)));

		$videos = $this->db	->select('filename', 'title')
							->from('nf_media')
							->where('mime_type', ['video/mp4', 'video/webm'])
							->order_by('created_at DESC', 'id DESC')
							->limit($count)
							->get(FALSE);

		if (!$videos)
		{
			return '';
		}

		$this->js('video');

		$base  = $this->url->base.'upload/media/';
		$first = $videos[0];

		$body  = '<div class="nf-video-widget">';
		$body .= '<video class="nf-video-player" controls preload="metadata" style="width:100%;border-radius:4px;background:#000;" src="'.nf_texte($base.$first['filename']).'"></video>';

		if (count($videos) > 1)
		{
			$body .= '<div class="nf-video-list list-group mt-2">';
			foreach ($videos as $i => $v)
			{
				$label = $v['title'] !== '' ? $v['title'] : $v['filename'];
				$body .= '<button type="button" class="list-group-item list-group-item-action'.($i === 0 ? ' active' : '').'" data-video-src="'.nf_texte($base.$v['filename']).'">'
					.icon('fas fa-play').' '.nf_texte($label)
					.'</button>';
			}
			$body .= '</div>';
		}

		$body .= '</div>';

		return $this->panel()->heading($this->lang('Vidéos'), 'fas fa-film')->body($body);
	}
}
