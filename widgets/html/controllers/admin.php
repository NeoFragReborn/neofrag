<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Html\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index($settings = [])
	{
		// Widget HTML : saisie de code HTML brut (contenu admin/trusted) dans un textarea simple.
		// Remplace l'ancien éditeur WysiBB/BBCode (libs absentes → cassé ; mauvais outil pour du HTML brut).
		return $this->html($settings);
	}

	public function html($settings = [])
	{
		return '<textarea class="form-control" name="settings[content]" placeholder="'.$this->lang('Code HTML').'" rows="6">'.(isset($settings['content']) ? $settings['content'] : '').'</textarea>';
	}
}
