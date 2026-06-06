<?php
namespace NF\Widgets\Newsletter\Controllers;
use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function signup($config = [])
	{
		$nb = (int)NeoFrag()->db->select('COUNT(*)')->from('nf_newsletter_subscribers')->where('confirmed', 1)->row();

		$body = '<p class="mb-2"><small>'.$this->lang('Reçois nos actus directement par email.').'</small></p>';
		$body .= '<form method="post" action="'.url('newsletter').'">';
		$body .= '<div class="input-group input-group-sm">';
		$body .= '<input type="email" name="data[email]" class="form-control" placeholder="'.$this->lang('ton@email.fr').'" required>';
		$body .= '<div class="input-group-append"><button type="submit" class="btn btn-primary"><i class="fas fa-envelope"></i></button></div>';
		$body .= '</div>';
		$body .= '</form>';
		if ($nb > 0)
		{
			$body .= '<small class="text-muted d-block mt-2">'.$this->lang('%d abonné déjà inscrit|%d abonnés déjà inscrits', $nb, $nb).'</small>';
		}

		return $this->panel()
					->heading($this->lang('Newsletter'), 'far fa-envelope')
					->body($body);
	}
}
