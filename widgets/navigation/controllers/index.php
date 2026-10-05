<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Navigation\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($settings = [])
	{
		return $this->_display($settings, 'horizontal', !empty($settings['panel']));
	}

	public function vertical($settings = [])
	{
		return $this->_display($settings, 'vertical', !isset($settings['panel']) || $settings['panel']);
	}

	/**
	 * Le lien mène-t-il à une page que le site sert ? Le menu livré par l'installation pointe vers des
	 * modules (Actualités, Forum, Galerie…) qu'un profil « Cœur seul » n'installe pas, et un module
	 * désinstallé laissait son lien : un clic, une page 404 (relevé le 2026-10-04). On suit la règle du
	 * routeur (neofrag/core/output.php) : un module installé est servi s'il est activé ; un segment qui
	 * n'est pas un module revient au module Pages, qui ne sert que les pages qui existent. Un lien
	 * externe, une ancre, l'accueil ou une fenêtre (`modal`) ne se jugent pas ici.
	 */
	protected function _cible_servie(array $link): bool
	{
		$url = $link['url'];

		if (isset($link['modal']) || !is_string($url) || preg_match('#^(?:[a-z][a-z0-9+.-]*:|//|\#)#i', $url))
		{
			return TRUE;
		}

		$segment = (string) strtok(trim($url, '/'), '/?#');

		if ($segment === '' || $segment === 'index')
		{
			return TRUE;
		}

		if ($module = @NeoFrag()->module(str_replace('-', '_', $segment)))
		{
			return $module->is_enabled();
		}

		if (!($pages = @NeoFrag()->module('pages')) || !$pages->is_enabled())
		{
			return FALSE;
		}

		$modele = $pages->model('pages');

		return $modele instanceof \NF\Modules\Pages\Models\Pages && (bool) $modele->page_publique($segment);
	}

	protected function _display($settings, $type, $panel)
	{
		$this->js('navigation');
		$this->css('navigation');

		$nav = $this->html('ul')
					->attr('class', 'nav')
					->append_attr_if($type == 'vertical', 'class', 'flex-column');

		array_walk($settings['links'], $f = function(&$link) use (&$f){
			$link = array_merge([
				'title'  => '',
				'url'    => '',
				'icon'   => '',
				'access' => TRUE
			], $link);

			if (is_array($link['url']))
			{
				array_walk($link['url'], $f);
			}
		});

		$actives = [];

		$is_active = function($link){
			return (($url = ltrim(preg_replace('_^'.preg_quote(url(), '_').'_', '', url($link)), '/')) == $this->url->request) || ($url && strpos($this->url->request, $url) === 0);
		};

		$nav_link = function($link, $active){
			return $this->html('a')
						->attr('class', 'nav-link')
						->append_attr_if($active, 'class', 'active')
						->exec(function($a) use ($link){
							if (isset($link['modal']))
							{
								$this->js('modal');

								$a	->attr('href',            '#')
									->attr('data-modal-ajax', url($link['modal']));
							}
							else
							{
								$a->attr('href', !is_array($link['url']) ? url($link['url']) : '#');
							}
						})
						->content(icon($link['icon']).' '.$this->lang($link['title']));
		};

		$show_link = function($link, &$active = FALSE) use (&$actives, &$nav_link){
			if ($link['access'] && $this->_cible_servie($link))
			{
				return $this->html('li')
							->attr('class', 'nav-item')
							->content($nav_link($link, $actives && $actives[0] == $link['url'] && ($active = TRUE)));
			}
		};

		foreach ($settings['links'] as $link)
		{
			if (is_array($link['url']))
			{
				foreach ($link['url'] as $link)
				{
					if ($is_active($link['url']))
					{
						$actives[] = $link['url'];
					}
				}
			}
			else if ($is_active($link['url']))
			{
				$actives[] = $link['url'];
			}
		}

		usort($actives, function($a, $b){
			return strlen($b) <=> strlen($a);
		});

		foreach ($settings['links'] as $link)
		{
			if (is_array($link['url']))
			{
				$active  = FALSE;
				$submenu = '';

				foreach ($link['url'] as $link2)
				{
					$submenu .= $show_link($link2, $active);
				}

				if ($submenu)
				{
					$nav->append($this	->html('li')
										->attr('class', 'nav-item')
										->content(
											$nav_link($link, $active)
												->attr('data-nf-sous-menu', '')
												->attr('aria-expanded', 'false')
												->attr('href',        '#')
												->content(icon($link['icon']).' <span class="d-none d-sm-inline">'.$this->lang($link['title']).'</span><span class="fas fa-angle-down"></span>').'<ul class="nav flex-column">'.$submenu.'</ul>'
										)
					);
				}
			}
			else
			{
				$nav->append($show_link($link));
			}
		}

		// Le menu HORIZONTAL se replie derrière un bouton quand ses entrées ne tiennent plus sur une
		// ligne : sur un téléphone, elles passaient sur deux ou trois rangées (signalé le
		// 2026-09-23). C'est le script du widget qui mesure — un nombre d'entrées et une largeur que
		// seule la page connaît ; sans script, le menu reste tel quel, entier.
		if ($type == 'horizontal')
		{
			$id  = 'nf-nav-'.substr(md5(uniqid('', TRUE)), 0, 8);
			$nav = '<div class="nf-nav-repliable">'.
						'<button type="button" class="nf-nav-toggle" aria-expanded="false" aria-controls="'.$id.'">'.icon('fas fa-bars').' <span>'.$this->lang('Menu').'</span></button>'.
						$nav->attr('id', $id).
					'</div>';
		}

		if ($panel)
		{
			$nav = $this	->panel()
							->body($nav, FALSE);
		}

		return $nav;
	}
}
