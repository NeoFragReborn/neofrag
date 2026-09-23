<?php
/**
 * https://neofr.ag
 * Nebula — thème communautaire « NeoFrag Reborn ». DA dark navy + teal,
 * glassmorphism. Généraliste : navbar + home à widgets + sidebar, pour les sites
 * communautaires (équipes, guildes, eSport). CC BY-NC-SA 4.0.
 */

namespace NF\Themes\Nebula;

use NF\NeoFrag\Addons\Theme;

class Nebula extends Theme
{
	protected function __info()
	{
		return [
			'title'       => 'Nebula',
			'description' => $this->lang('Thème communautaire NeoFrag Reborn : dark navy + teal, glassmorphism. Home à widgets, navbar + colonne latérale — pour ta team, ta guilde ou ta communauté.'),
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'Creative Commons CC BY-NC-SA 4.0',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0.0',
			'depends' => [
				'neofrag' => '0.2.1'
			],
			// « Navigation » est declaree EN DERNIER a dessein : le numero d'une zone est son rang
			// dans cette liste (cf. Output::region), et l'inserer ailleurs renumeroterait toutes les
			// dispositions deja en base. Elle est rendue DANS la barre vitree, pas sous l'en-tete.
			'zones'       => ['Header', 'Avant-contenu', 'Contenu', 'Post-contenu', 'Footer', 'Navigation'],
			'regions'     => [
				'header'         => 'Header',
				'before_content' => 'Avant-contenu',
				'content'        => 'Contenu',
				'after_content'  => 'Post-contenu',
				'footer'         => 'Footer',
				'navigation'     => 'Navigation',
			]
		];
	}

	public function __init()
	{
		$this	->css('bootstrap.min')->css('nf-bs5-bridge')
				->css('icons/fontawesome.min')
				->css('style')
				// APRES la feuille du theme, et jamais avant : elle retablit ce que le theme
				// ecrase sans le vouloir — cadre des boutons « contour », coins des cartes.
				// Voir css/nf-apres-theme.css.
				->css('nf-apres-theme')
				->js('bootstrap.bundle.min')
				->js('modal')
				->js('notify')
				->js('confirm')
				->js('theme')
				->js('nebula');
	}

	public function styles_row()
	{
		return $this->view('live_editor/row');
	}

	public function styles_widget()
	{
		return $this->view('live_editor/widget');
	}

	public function install($dispositions = [])
	{
		$dispositions = $this->array();

		// LA BARRE DU HAUT. Le gabarit dessine sa propre barre vitree — logo, menu, boutons de
		// compte — et rend la zone « Navigation » A L'INTERIEUR. Le menu du site est donc CE
		// widget : l'administrateur le configure comme n'importe quel autre, et il n'apparait
		// qu'une fois.
		//
		// L'en-tete (zone « Header ») reste VIDE a l'installation, et c'est delibere : la barre
		// porte deja le nom du site et la navigation. Jusqu'au 2026-09-22 cette zone recevait un
		// widget « header » et un widget « navigation », ce qui affichait le nom du site DEUX fois
		// et le menu DEUX fois sur toute installation neuve. La zone reste disponible pour qui
		// veut y placer quelque chose.
		$dispositions->set('*', 'Navigation', $this->array([
			$this->row(
					$this->col(
						$this	->widget($this->db->insert('nf_widgets', [
									'widget'   => 'navigation',
									'type'     => 'index',
									'settings' => serialize([
										'links'   => [
											['title' => utf8_htmlentities($this->lang('Accueil')),    'url' => ''],
											['title' => utf8_htmlentities($this->lang('Actualités')), 'url' => 'news'],
											['title' => utf8_htmlentities($this->lang('Forum')),      'url' => 'forum'],
											['title' => utf8_htmlentities($this->lang('Galerie')),    'url' => 'gallery'],
											['title' => utf8_htmlentities($this->lang('Membres')),    'url' => 'members'],
											['title' => utf8_htmlentities($this->lang('Contact')),    'url' => 'contact']
										]
									])
								]))
					)
				)
				->style('row-default')
		]));

		// Home : slider en avant-contenu (mise en avant).
		$dispositions->set('/', 'Avant-contenu', $this->array([
			$this->row(
					$this->col(
						$this->widget($this->db->insert('nf_widgets', [
							'widget' => 'slider',
							'type'   => 'index'
						]))
					)
				)
				->style('row-default')
		]));

		// Toutes pages : module principal + colonne latérale.
		$dispositions->set('*', 'Contenu', $this->array([
			$this->row(
					$this->col(
							$this->widget($this->db->insert('nf_widgets', [
								'widget' => 'module',
								'type'   => 'index'
							]))
						)
						->size('col-md-8'),
					$this->col(
							$this	->widget($this->db->insert('nf_widgets', [
										'widget' => 'user',
										'type'   => 'index'
									]))
									->style('panel-color'),
							$this	->widget($this->db->insert('nf_widgets', [
										'widget' => 'members',
										'type'   => 'online'
									]))
									->style('panel-default'),
							$this	->widget($this->db->insert('nf_widgets', [
										'widget' => 'news',
										'type'   => 'categories'
									]))
									->style('panel-default')
						)
						->size('col-md-4')
				)
				->style('row-default')
		]));

		foreach (['forum/*', 'news/_news/*', 'user/*'] as $page)
		{
			$dispositions->set($page, 'Contenu', $this->array([
				$this->row(
						$this->col(
							$this->widget($this->db->insert('nf_widgets', [
								'widget' => 'breadcrumb',
								'type'   => 'index'
							]))
						)
					)
					->style('row-default'),
				$this->row(
						$this->col(
							$this->widget($this->db->insert('nf_widgets', [
								'widget' => 'module',
								'type'   => 'index'
							]))
						)
					)
					->style('row-default')
			]));
		}

		$dispositions->set('forum/*', 'Post-contenu', $this->array([
			$this->row(
					$this->col(
						$this	->widget($this->db->insert('nf_widgets', [
									'widget' => 'forum',
									'type'   => 'statistics'
								]))
								->style('panel-header')
					)
					->size('col-md-4'),
					$this->col(
						$this	->widget($this->db->insert('nf_widgets', [
									'widget' => 'forum',
									'type'   => 'activity'
								]))
								->style('panel-header')
					)
					->size('col-md-8')
				)
				->style('row-default')
		]));

		return parent::install($dispositions);
	}
}
