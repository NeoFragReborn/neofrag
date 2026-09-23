<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Copyright\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($settings = [])
	{
		$keywords = [
			'name'      => '<a href="'.url().'">'.$this->config->nf_name.'</a>',
			'neofrag'   => '<a href="https://neofr.ag">NeoFrag Reborn</a>',
			'year'      => date('Y'),
			'copyright' => icon('far fa-copyright')
		];

		$copyright = utf8_html_entity_decode($this->config->nf_copyright);

		// Le texte LIVRÉ (install/seed.sql) est écrit en français : tant que l'administrateur ne l'a pas
		// changé, il se traduit. Il s'affichait « tous droits réservés » au pied de chaque page, dans
		// les six langues (2026-09-23). Un texte personnalisé reste le sien.
		if ($copyright === 'Copyright {copyright} {year} {name}, tous droits réservés <div class="float-end">Propulsé par {neofrag}</div>')
		{
			$copyright = $this->lang('Copyright %s %s %s, tous droits réservés', '{copyright}', '{year}', '{name}').' <div class="float-end">'.$this->lang('Propulsé par %s', '{neofrag}').'</div>';
		}

		if (!in_string('{neofrag}', $copyright))
		{
			$copyright .= '<div class="float-end">'.$this->lang('Propulsé par %s', '{neofrag}').'</div>';
		}

		return $this->panel()
					->body(preg_replace_callback('/\{('.implode('|', array_keys($keywords)).')\}/i', function($match) use ($keywords){
						return $keywords[$match[1]];
					}, $copyright));
	}
}
