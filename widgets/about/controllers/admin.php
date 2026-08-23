<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\About\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index($settings = [])
	{
		return $this->view('admin', array_merge([
			'display_panel'      => 'oui',
			'display_teamname'   => 'oui',
			'display_logo'       => 'oui',
			'display_type'       => 'oui',
			'display_date'       => 'oui',
			'display_biographie' => 'oui',
			'biographie_align'   => 'text-start',
			'teamname_align'     => 'text-start',
			'logo_align'         => 'text-start',
			'logo_width'         => 200,
			'padding_top'        => 0,
			'padding_right'      => 0,
			'padding_bottom'     => 0,
			'padding_left'       => 0,
			'margin_top'         => 0,
			'margin_right'       => 0,
			'margin_bottom'      => 0,
			'margin_left'        => 0,
			'style_title'        => '',
			'style_text'         => ''
		], $settings));
	}
}
