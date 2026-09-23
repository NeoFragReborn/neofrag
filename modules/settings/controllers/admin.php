<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Settings\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		// Hub of section cards — no form here
		$this->icon('fas fa-cogs');

		$sections = [
			[
				'title' => $this->lang('Préférences générales'),
				'desc'  => $this->lang('Titre, description, favicon, contact, page d\'accueil, analytics'),
				'icon'  => 'fas fa-cog',
				'url'   => 'admin/settings/general',
				'color' => 'accent'
			],
			[
				'title' => $this->lang('Thèmes & addons'),
				'desc'  => $this->lang('Activer/désactiver les thèmes, modules, widgets'),
				'icon'  => 'fas fa-puzzle-piece',
				'url'   => 'admin/addons',
				'color' => 'info'
			],
			[
				'title' => $this->lang('Maintenance'),
				'desc'  => $this->lang('Mode maintenance, horaires d\'ouverture du site'),
				'icon'  => 'fas fa-power-off',
				'url'   => 'admin/settings/maintenance',
				'color' => 'warning'
			],
			[
				'title' => $this->lang('Gestion des inscriptions'),
				'desc'  => $this->lang('Statut, règlement, message de bienvenue'),
				'icon'  => 'fas fa-sign-in-alt',
				'url'   => 'admin/settings/registration',
				'color' => 'success'
			],
			[
				'title' => $this->lang('Notre structure'),
				'desc'  => $this->lang('Description de la structure, équipe, partenaires'),
				'icon'  => 'fas fa-users',
				'url'   => 'admin/settings/team',
				'color' => 'accent'
			],
			[
				'title' => $this->lang('Réseaux sociaux'),
				'desc'  => $this->lang('Liens vers Facebook, Twitter, Instagram, etc.'),
				'icon'  => 'fas fa-globe',
				'url'   => 'admin/settings/socials',
				'color' => 'info'
			],
			[
				'title' => $this->lang('Sécurité anti-bots'),
				'desc'  => $this->lang('Captcha, reCAPTCHA, protection contre les spambots'),
				'icon'  => 'fas fa-shield-alt',
				'url'   => 'admin/settings/captcha',
				'color' => 'danger'
			],
			[
				'title' => $this->lang('Email (SMTP)'),
				'desc'  => $this->lang('Serveur d\'envoi des emails — auto-détecté, ou SMTP personnalisé'),
				'icon'  => 'fas fa-envelope',
				'url'   => 'admin/settings/email',
				'color' => 'info'
			],
			[
				'title' => $this->lang('Copyright'),
				'desc'  => $this->lang('Mentions légales, copyright affiché en bas de site'),
				'icon'  => 'far fa-copyright',
				'url'   => 'admin/settings/copyright',
				'color' => 'accent'
			]
		];

		$html = '<div class="settings-hub">';
		$html .= '<div class="settings-hub-intro">';
		$html .= '<h2><i class="fas fa-cogs"></i> '.$this->lang('Paramètres').'</h2>';
		$html .= '<p>'.$this->lang('Configure ton site, choisis ton thème, gère les inscriptions et la sécurité.').'</p>';
		$html .= '</div>';

		$html .= '<div class="settings-hub-grid">';
		foreach ($sections as $s)
		{
			$html .= '<a class="settings-hub-card settings-hub-card--'.$s['color'].'" href="'.url($s['url']).'">';
			$html .= '<div class="settings-hub-icon"><i class="'.$s['icon'].'"></i></div>';
			$html .= '<div class="settings-hub-text">';
			$html .= '<div class="settings-hub-title">'.htmlspecialchars((string) ($s['title'])).'</div>';
			$html .= '<div class="settings-hub-desc">'.htmlspecialchars((string) ($s['desc'])).'</div>';
			$html .= '</div>';
			$html .= '<div class="settings-hub-arrow"><i class="fas fa-arrow-right"></i></div>';
			$html .= '</a>';
		}
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	public function general()
	{
		$this	->subtitle($this->lang('Préférences générales'))
				->icon('fas fa-cog');

		$modules = $pages = [];

		foreach (NeoFrag()->model2('addon')->get('module') as $module)
		{
			if (@$module->controller('index') && !in_array($module->info()->name, ['settings', 'user']))
			{
				$modules[] = $module;
			}
		}

		array_natsort($modules, function($a){
			return $a->info()->title;
		});

		foreach ($modules as $module)
		{
			$name = $module->info()->name;

			if ($name == 'pages')
			{
				foreach ($module->model()->get_pages() as $page)
				{
					if ($page['published'])
					{
						$pages['pages/'.$page['name']] = $this->lang('Page : %s', $page['title']);
					}
				}
			}
			else
			{
				$pages[$name] = $module->info()->title;
			}
		}

		$this	->form()
				->add_rules([
					'name' => [
						'label'  => $this->lang('Titre du site'),
						'value'  => $this->config->nf_name,
						'rules'  => 'required'
					],
					'description' => [
						'label'  => $this->lang('Description du site'),
						'value'  => $this->config->nf_description,
						'rules'  => 'required'
					],
					'favicon' => [
						'label'  => $this->lang('Favicon du site'),
						'value'  => $this->config->nf_favicon,
						'type'   => 'file',
						'upload' => 'favicons',
						'info'   => $this->lang(' d\'image (format carré min. %dpx et max. %d Mo)', 16, file_upload_max_size() / 1024 / 1024),
						'check'  => function($filename, $ext){
							if (!in_array($ext, ['gif', 'jpeg', 'jpg', 'png', 'ico']))
							{
								return $this->lang('Veuillez choisir un fichier d\'image');
							}

							list($w, $h) = getimagesize($filename);

							if ($w != $h)
							{
								return $this->lang('L\'image doit être carré');
							}
							else if ($w < 16)
							{
								return $this->lang('L\'image doit faire au moins %dpx', 16);
							}
						}
					],
					'contact' => [
						'label'  => $this->lang('Email de contact'),
						'value'  => $this->config->nf_contact,
						'type'   => 'email',
						'rules'  => 'required'
					],
					'default_page' => [
						'label'  => $this->lang('Page d\'accueil'),
						'values' => $pages,
						'value'  => $this->config->nf_default_page,
						'type'   => 'select',
						'rules'  => 'required'
					],
					'font' => [
						'label'       => $this->lang('Police du site'),
						'description' => $this->lang('Remplace la police de tous les thèmes. Les polices sont servies par Google Fonts ; « %s » ne fait appel à aucun service extérieur.', $this->lang('Police du thème')),
						'values'      => ['' => $this->lang('Police du thème')] + polices_disponibles(),
						'value'       => $this->config->nf_font,
						'type'        => 'select'
					],
					// Le menu « thème » du pied de page (cf. helpers/theme.php). Fermé, il disparaît, et un
					// choix déjà fait par un visiteur n'est plus honoré : c'est le cas du site vitrine.
					'theme_visiteur' => [
						'label'       => $this->lang('Choix du thème'),
						'type'        => 'checkbox',
						'values'      => ['on' => $this->lang('Laisser les visiteurs choisir le thème du site, dans le pied de page')],
						'checked'     => ['on' => nf_theme_choix_permis()],
						'description' => $this->lang('Le choix est gardé dans le navigateur du visiteur, pour ce site seulement. Décocher impose le thème par défaut à tous.')
					],
					'session_history_days' => [
						'label'       => $this->lang('Historique des connexions'),
						'description' => $this->lang('Nombre de jours de conservation des connexions (adresse IP, agent, date). 0 pour ne jamais purger.'),
						'value'       => (int) $this->config->nf_session_history_days,
						'type'        => 'number',
						'check'       => function($jours){
							if ($jours !== '' && (!ctype_digit((string) $jours) || (int) $jours > 3650))
							{
								return $this->lang('Indiquez un nombre de jours entre 0 et 3650.');
							}
						}
					],
					'analytics' => [
						'label'       => '<a href="https://analytics.google.com" target="_blank">'.$this->lang('Code Google Analytics').'</a>',
						'description' => $this->lang('Format UA-XXXXXXXXX-Y'),
						'value'       => $this->config->nf_analytics,
						'check'       => function($code){
							if (!is_empty($code) && !preg_match('/^UA-\d+-\d+$/', $code))
							{
								return $this->lang('Ce code est invalide');
							}
						}
					],
					'humans_txt' => [
						'label'  => '<a href="http://humanstxt.org" target="_blank">humans.txt</a>',
						'type'   => 'textarea',
						'value'  => $this->config->nf_humans_txt
					],
					'robots_txt' => [
						'label'  => '<a href="http://www.robotstxt.org" target="_blank">robots.txt</a>',
						'type'   => 'textarea',
						'value'  => $this->config->nf_robots_txt
					],
					/*
					 * L'interrupteur du service worker. La description dit ce que l'on gagne ET ce que
					 * l'on engage : c'est le seul réglage du produit dont l'effet survit à sa propre
					 * désactivation côté serveur, et l'administrateur doit le savoir avant de cocher.
					 */
					'pwa' => [
						'label'       => $this->lang('Application installable'),
						'type'        => 'checkbox',
						'values'      => ['on' => $this->lang('Garder les images, les styles et les scripts dans le navigateur des visiteurs')],
						'checked'     => ['on' => (bool) $this->config->nf_pwa],
						'description' => $this->lang('Les pages, elles, ne sont jamais gardées : un déploiement se voit tout de suite. Décocher désinstalle réellement chez les visiteurs, à leur prochaine visite.')
					]
				])
				->add_submit($this->lang('Valider'))
				->display_required(FALSE);

		if ($this->form()->is_valid($post))
		{
			foreach ($post as $var => $value)
			{
				if ($var === 'pwa' || $var === 'theme_visiteur')
				{
					continue;
				}

				$this->config('nf_'.$var, $value);
			}

			// Hors de la boucle : une case DÉCOCHÉE n'arrive pas dans le POST, et la boucle ne
			// l'éteindrait donc jamais. C'est l'extinction qui compte le plus ici.
			$this->config('nf_pwa', empty($post['pwa']) ? '0' : '1', 'bool');
			$this->config('nf_theme_visiteur', empty($post['theme_visiteur']) ? '0' : '1', 'bool');

			$this->_audit('general');
			notify($this->lang('Préférences générales sauvegardées avec succès'));

			refresh();
		}

		// Live preview values
		$site_name        = htmlspecialchars((string) ($this->config->nf_name ?: 'NeoFrag'));
		$site_description = htmlspecialchars((string) ($this->config->nf_description ?: ''));
		$favicon_url      = $this->config->nf_favicon
			? url(NeoFrag()->model2('file', $this->config->nf_favicon)->path())
			: '';
		$site_url         = url();

		// Layout
		$back_link = '<div class="settings-section-back"><a href="'.url('admin/settings').'" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> '.$this->lang('Tous les paramètres').'</a></div>';

		// Form card
		$form_card = '<div class="settings-section-card">'
			.'<div class="settings-section-header">'
			.'<div class="settings-section-icon"><i class="fas fa-cog"></i></div>'
			.'<div class="settings-section-meta"><div class="settings-section-title">'.$this->lang('Préférences générales').'</div></div>'
			.'</div>'
			.'<div class="settings-section-body">'.$this->form()->display().'</div>'
			.'</div>';

		// Preview card — simulates browser tab + site header
		$preview_card = '<div class="settings-section-card">'
			.'<div class="settings-section-header">'
			.'<div class="settings-section-icon"><i class="far fa-eye"></i></div>'
			.'<div class="settings-section-meta"><div class="settings-section-title">'.$this->lang('Aperçu de l\'identité').'</div></div>'
			.'</div>'
			.'<div class="identity-preview">';

		// Browser tab mockup
		$preview_card .= '<div class="identity-preview-browser">'
			.'<div class="identity-preview-browser-controls">'
			.'<span class="identity-preview-dot is-red"></span>'
			.'<span class="identity-preview-dot is-yellow"></span>'
			.'<span class="identity-preview-dot is-green"></span>'
			.'</div>'
			.'<div class="identity-preview-tab">';
		if ($favicon_url) {
			$preview_card .= '<img class="identity-preview-favicon" src="'.$favicon_url.'" alt="" />';
		} else {
			$preview_card .= '<i class="fas fa-globe identity-preview-favicon-fallback"></i>';
		}
		$preview_card .= '<span class="identity-preview-tab-title">'.$site_name.'</span>'
			.'<i class="fas fa-times identity-preview-tab-close"></i>'
			.'</div>'
			.'<div class="identity-preview-url">'
			.'<i class="fas fa-lock"></i> '.htmlspecialchars((string) ($site_url))
			.'</div>'
			.'</div>';

		// Site header mockup
		$preview_card .= '<div class="identity-preview-site">'
			.'<h2 class="identity-preview-site-title">'.$site_name.'</h2>';
		if ($site_description !== '') {
			$preview_card .= '<p class="identity-preview-site-desc">'.$site_description.'</p>';
		}
		$preview_card .= '</div>';

		$preview_card .= '</div></div>'; // close .identity-preview + .settings-section-card

		return $back_link
			.'<div class="row">'
			.'<div class="col-12 col-lg-7">'.$form_card.'</div>'
			.'<div class="col-12 col-lg-5">'.$preview_card.'</div>'
			.'</div>';
	}

	public function registration()
	{
		$this	->subtitle($this->lang('Gestion des inscriptions'))
				->icon('fas fa-sign-in-alt fa-rotate-90')
				->js('admin/status_toggle');

		$users = $this->db	->select('id as user_id', 'username')
							->from('nf_user')
							->where('deleted', FALSE)
							->order_by('username')
							->get();

		$list_users = [];
		foreach ($users as $user)
		{
			$list_users[$user['user_id']] = $user['username'];
		}
		array_natsort($list_users);

		// Form 1: Règlement (charte)
		$form_charte = $this->form()
			->add_rules([
				'registration_charte' => [
					'label' => $this->lang('Règlement'),
					'value' => $this->config->nf_registration_charte,
					'type'  => 'editor'
				]
			])
			->add_submit($this->lang('Valider'))
			->display_required(FALSE)
			->save();

		// Form 2: Message de bienvenue
		$form_welcome = $this->form()
			->add_rules([
				'welcome' => [
					'type'    => 'checkbox',
					'checked' => ['on' => $this->config->nf_welcome],
					'values'  => ['on' => $this->lang('Envoyer un message privé aux nouveaux membres')]
				],
				'welcome_user_id' => [
					'label'  => $this->lang('Auteur du message'),
					'values' => $list_users,
					'value'  => $this->config->nf_welcome_user_id,
					'type'   => 'select',
					'size'   => 'col-5'
				],
				'welcome_title' => [
					'label' => $this->lang('Titre du message'),
					'value' => $this->config->nf_welcome_title,
					'type'  => 'text'
				],
				'welcome_content' => [
					'label'       => $this->lang('Message de bienvenue'),
					'value'       => $this->config->nf_welcome_content,
					'type'        => 'editor',
					'description' => $this->lang('Placez [pseudo] pour afficher automatiquement le pseudo du nouveau membre dans le message')
				]
			])
			->add_submit($this->lang('Valider'))
			->display_required(FALSE)
			->save();

		if ($form_charte->is_valid($post))
		{
			$this->config('nf_registration_charte', $post['registration_charte']);
			$this->_audit('registration');
			notify($this->lang('Règlement sauvegardé'));
			refresh();
		}
		else if ($form_welcome->is_valid($post))
		{
			$this	->config('nf_welcome',         in_array('on', (array)$post['welcome']))
					->config('nf_welcome_user_id', $post['welcome_user_id'])
					->config('nf_welcome_title',   $post['welcome_title'])
					->config('nf_welcome_content', $post['welcome_content']);
			$this->_audit('welcome');
			notify($this->lang('Message de bienvenue sauvegardé'));
			refresh();
		}

		// Layout
		$back_link = '<div class="settings-section-back"><a href="'.url('admin/settings').'" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> '.$this->lang('Tous les paramètres').'</a></div>';

		// Status hero
		$status_view = $this->view('admin/registration');

		// Charte card
		$charte_card = '<div class="settings-section-card">'
			.'<div class="settings-section-header">'
			.'<div class="settings-section-icon"><i class="far fa-file-alt"></i></div>'
			.'<div class="settings-section-meta"><div class="settings-section-title">'.$this->lang('Règlement d\'inscription').'</div></div>'
			.'</div>'
			.'<div class="settings-section-body">'.$form_charte->display().'</div>'
			.'</div>';

		// Welcome card
		$welcome_card = '<div class="settings-section-card">'
			.'<div class="settings-section-header">'
			.'<div class="settings-section-icon"><i class="fas fa-hand-paper"></i></div>'
			.'<div class="settings-section-meta"><div class="settings-section-title">'.$this->lang('Message de bienvenue').'</div></div>'
			.'</div>'
			.'<div class="settings-section-body">'.$form_welcome->display().'</div>'
			.'</div>';

		return $back_link.$status_view.$charte_card.$welcome_card;
	}

	public function team()
	{
		$this	->subtitle($this->lang('Notre structure'))
				->icon('fas fa-users');

		$this	->form()
				->add_rules([
					'team_name' => [
						'label'       => $this->lang('Nom de l\'équipe'),
						'value'       => $this->config->nf_team_name,
						'type'        => 'text'
					],
					'team_logo' => [
						'label'       => $this->lang('Logo'),
						'value'       => $this->config->nf_team_logo,
						'type'        => 'file',
						'upload'      => 'logos',
						'info'        => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
						'check'       => function($filename, $ext){
							if (!in_array($ext, ['gif', 'jpeg', 'jpg', 'png']))
							{
								return $this->lang('Veuillez choisir un fichier d\'image');
							}
						},
						'description' => $this->lang('Le logo pourra être affiché dans le widget type <b>header</b> <i>(en remplacement du titre et slogan)</i>.')
					],
					'team_type' => [
						'label'       => $this->lang('Type de structure'),
						'value'       => $this->config->nf_team_type,
						'type'        => 'text',
						'size'        => 'col-4',
						'description' => $this->lang('<b>Exemple:</b> Association, entreprise, marque, etc...')
					],
					'team_creation' => [
						'label'       => $this->lang('Date de création'),
						'value'       => $this->config->nf_team_creation,
						'type'        => 'date',
						'size'        => 'col-4'
					],
					'team_biographie' => [
						'label'       => $this->lang('Biographie'),
						'value'       => $this->config->nf_team_biographie,
						'type'        => 'textarea'
					]
				])
				->add_submit($this->lang('Valider'))
				->display_required(FALSE);

		if ($this->form()->is_valid($post))
		{
			foreach ($post as $var => $value)
			{
				$this->config('nf_'.$var, $value);
			}

			$this->_audit('team');
			notify($this->lang('Informations sauvegardées avec succès'));

			refresh();
		}

		return $this->_layout(function($col){
			$col->append($this	->panel()
								->heading($this->lang('Notre structure'), 'fas fa-users')
								->body($this->form()->display())
			);
		});
	}

	public function socials()
	{
		$this	->subtitle($this->lang('Réseaux sociaux'))
				->icon('fas fa-globe');

		// Network catalog: brand color + icon + display name + URL placeholder
		$networks = [
			'facebook'   => ['Facebook',    'fab fa-facebook-f',  '#1877f2', 'https://facebook.com/...'],
			'twitter'    => ['Twitter / X', 'fab fa-x-twitter',   '#000000', 'https://x.com/...'],
			'instagram'  => ['Instagram',   'fab fa-instagram',   '#e4405f', 'https://instagram.com/...'],
			'threads'    => ['Threads',     'fab fa-threads',     '#000000', 'https://threads.net/@...'],
			'bluesky'    => ['Bluesky',     'fab fa-bluesky',     '#0085ff', 'https://bsky.app/profile/...'],
			'mastodon'   => ['Mastodon',    'fab fa-mastodon',    '#6364ff', 'https://mastodon.social/@...'],
			'tiktok'     => ['TikTok',      'fab fa-tiktok',      '#010101', 'https://tiktok.com/@...'],
			'youtube'    => ['YouTube',     'fab fa-youtube',     '#ff0000', 'https://youtube.com/@...'],
			'twitch'     => ['Twitch',      'fab fa-twitch',      '#9146ff', 'https://twitch.tv/...'],
			'discord'    => ['Discord',     'fab fa-discord',     '#5865f2', 'https://discord.gg/...'],
			'github'     => ['GitHub',      'fab fa-github',      '#181717', 'https://github.com/...'],
			'steam'      => ['Steam',       'fab fa-steam',       '#171a21', 'https://steamcommunity.com/groups/...'],
			'linkedin'   => ['LinkedIn',    'fab fa-linkedin-in', '#0a66c2', 'https://linkedin.com/in/...'],
			'dribble'    => ['Dribbble',    'fab fa-dribbble',    '#ea4c89', 'https://dribbble.com/...'],
			'behance'    => ['Behance',     'fab fa-behance',     '#1769ff', 'https://behance.net/...'],
			'deviantart' => ['DeviantArt',  'fab fa-deviantart',  '#05cc47', 'https://deviantart.com/...'],
			'flickr'     => ['Flickr',      'fab fa-flickr',      '#ff0084', 'https://flickr.com/photos/...'],
			'google'     => ['Google',      'fab fa-google',      '#4285f4', 'https://...']
		];

		// Register all networks as form rules (preserves CSRF + validation through form lib)
		$rules = [];
		foreach ($networks as $key => list($label, $icon, $color, $placeholder))
		{
			$rules['social_'.$key] = [
				'label' => $label,
				'value' => $this->config->{'nf_social_'.$key},
				'type'  => 'url'
			];
		}

		$form = $this->form()->add_rules($rules);

		if ($form->is_valid($post))
		{
			foreach ($post as $var => $value)
			{
				$this->config('nf_'.$var, $value);
			}
			$this->_audit('socials');
			notify($this->lang('Réseaux sociaux sauvegardés avec succès'));
			refresh();
		}

		// Manually render the form as a grid of cards (using form's own token for CSRF/POST routing).
		$token = $form->token();

		// Layout
		$back_link = '<div class="settings-section-back"><a href="'.url('admin/settings').'" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> '.$this->lang('Tous les paramètres').'</a></div>';

		$grid_html = '<div class="socials-grid">';
		foreach ($networks as $key => list($label, $icon, $color, $placeholder))
		{
			$current = utf8_html_entity_decode($this->config->{'nf_social_'.$key} ?: '');
			$has_value = $current !== '';
			$grid_html .= '<div class="social-card '.($has_value ? 'is-active' : 'is-empty').'">'
				.'<div class="social-card-header">'
				.'<div class="social-card-icon" style="background:'.$color.'"><i class="'.$icon.'"></i></div>'
				.'<div class="social-card-name">'.$label.'</div>';
			if ($has_value) {
				$grid_html .= '<a href="'.htmlspecialchars((string) ($current)).'" target="_blank" rel="noopener" class="social-card-visit" title="'.$this->lang('Ouvrir le profil').'"><i class="fas fa-external-link-alt"></i></a>';
			}
			$grid_html .= '</div>'
				.'<input type="url" class="form-control social-card-input" '
				.'name="'.$token.'[social_'.$key.']" '
				.'value="'.htmlspecialchars((string) ($current)).'" '
				.'placeholder="'.htmlspecialchars((string) ($placeholder)).'" />'
				.'</div>';
		}
		$grid_html .= '</div>';

		$form_html = '<form action="'.url('admin/settings/socials').'" method="post" class="socials-form">'
			.$grid_html
			.'<div class="socials-actions">'
			.'<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> '.$this->lang('Valider').'</button>'
			.'</div>'
			.'</form>';

		$card = '<div class="settings-section-card">'
			.'<div class="settings-section-header">'
			.'<div class="settings-section-icon"><i class="fas fa-share-alt"></i></div>'
			.'<div class="settings-section-meta"><div class="settings-section-title">'.$this->lang('Réseaux sociaux').'</div>'
			.'<div class="settings-section-subtitle">'.$this->lang('Laissez vide pour ne pas afficher un réseau.').'</div></div>'
			.'</div>'
			.'<div class="settings-section-body">'.$form_html.'</div>'
			.'</div>';

		return $back_link.$card;
	}

	public function captcha()
	{
		$this	->subtitle($this->lang('Sécurité anti-bots'))
				->icon('fas fa-shield-alt');

		$this	->form()
				->add_rules([
					'captcha_public_key' => [
						'label' => $this->lang('Clé publique Google'),
						'value' => $this->config->nf_captcha_public_key,
						'type'  => 'text'
					],
					'captcha_private_key' => [
						'label' => $this->lang('Clé privée Google'),
						'value' => $this->config->nf_captcha_private_key,
						'type'  => 'text'
					]
				])
				->add_submit($this->lang('Valider'))
				->display_required(FALSE);

		if ($this->form()->is_valid($post))
		{
			foreach ($post as $var => $value)
			{
				$this->config('nf_'.$var, $value);
			}

			$this->_audit('captcha');
			notify($this->lang('Configuration de Google reCAPTCHA sauvegardée avec succès'));

			refresh();
		}

		return $this->_layout(function($col){
			$col->append($this	->panel()
								->heading('Configuration de Google reCAPTCHA', 'fas fa-shield-alt')
								->body('<div class="alert alert-info"><a href="https://www.google.com/recaptcha/intro/index.html" target="_blank">https://www.google.com/recaptcha/intro/index.html</a></div>'.$this->form()->display())
			);
		});
	}

	public function email()
	{
		$this	->subtitle($this->lang('Email (SMTP)'))
				->icon('fas fa-envelope');

		$this	->form()
				->add_rules([
					'smtp_host' => [
						'label'       => $this->lang('Serveur SMTP'),
						'description' => $this->lang('Laisser vide : NeoFrag envoie tout seul (mail() ou relais local de l\'hébergeur).'),
						'value'       => $this->config->nf_smtp_host,
						'type'        => 'text'
					],
					'smtp_port' => [
						'label' => $this->lang('Port'),
						'value' => $this->config->nf_smtp_port ?: '',
						'type'  => 'text'
					],
					'smtp_secure' => [
						'label'  => $this->lang('Sécurité'),
						'values' => ['' => $this->lang('Aucune'), 'tls' => 'TLS', 'ssl' => 'SSL'],
						'value'  => $this->config->nf_smtp_secure,
						'type'   => 'select'
					],
					'smtp_username' => [
						'label' => $this->lang('Utilisateur'),
						'value' => $this->config->nf_smtp_username,
						'type'  => 'text'
					],
					'smtp_password' => [
						'label'       => $this->lang('Mot de passe'),
						'description' => $this->config->nf_smtp_password
							? $this->lang('Un mot de passe est déjà enregistré (non affiché par sécurité) — laisse vide pour le conserver, ou saisis-en un nouveau.')
							: $this->lang('Mot de passe du compte SMTP.'),
						'value'       => '',
						'type'        => 'password'
					]
				])
				->add_submit($this->lang('Valider'))
				->display_required(FALSE);

		if ($this->form()->is_valid($post))
		{
			// secure : forcer une valeur de la whitelist (un select scalaire n'est pas validé par le form).
			$secure = in_array($post['smtp_secure'], ['', 'tls', 'ssl'], TRUE) ? $post['smtp_secure'] : '';

			$this	->config('nf_smtp_host',     trim($post['smtp_host']))
					->config('nf_smtp_port',     (int)$post['smtp_port'], 'int')
					->config('nf_smtp_secure',   $secure)
					->config('nf_smtp_username', trim($post['smtp_username']));

			// Mot de passe : ne ré-écrire QUE s'il est renseigné. Le form met les champs vides à NULL (pas ''),
			// donc on teste avec empty() — sinon un envoi avec le champ vide écraserait le mot de passe par NULL.
			if (!empty($post['smtp_password']))
			{
				$this->config('nf_smtp_password', $this->crypt->encrypt_secret($post['smtp_password']));
			}

			$this->_audit('email');

			// Garde-fou : un utilisateur SMTP sans mot de passe désactive silencieusement l'authentification.
			if (trim($post['smtp_username']) !== '' && empty($post['smtp_password']) && !$this->config->nf_smtp_password)
			{
				notify($this->lang('Réglages enregistrés — mais le mot de passe SMTP est vide : l\'authentification ne sera pas active.'), 'warning');
			}
			else
			{
				notify($this->lang('Configuration email enregistrée'));
			}

			refresh();
		}

		return $this->_layout(function($col){
			$col->append($this	->panel()
								->heading($this->lang('Serveur d\'envoi des emails'), 'fas fa-envelope')
								->body('<div class="alert alert-info">'.$this->lang('NeoFrag envoie les emails <b>tout seul</b> sur la plupart des hébergements (rien à régler). Renseigne un serveur SMTP <b>uniquement si l\'envoi échoue</b> : celui de ton hébergeur (souvent <code>mail.ton-domaine</code>, port 465/SSL ou 587/TLS) ou un service externe (Brevo, Mailgun…). Teste ensuite un envoi depuis la page <a href="%s">Emails</a>.', url('admin/emails')).'</div>'.$this->form()->display())
			);
		});
	}

	public function maintenance()
	{
		$this	->subtitle($this->lang('Maintenance'))
				->icon('fas fa-power-off')
				->css('admin/maintenance')
				->js('admin/status_toggle');

		// Pas de `fast_mode()` ici : il centre le bouton d'envoi alors que le formulaire voisin
		// aligne le sien sur la colonne des champs. Deux boutons d'envoi à deux endroits
		// différents sur un même écran — exactement l'incohérence à supprimer. Le champ reçoit
		// aussi un libellé : il n'en avait aucun, et se présentait comme une case vide.
		$form_opening = $this->form()
			->add_rules([
				'opening' => [
					'label'       => $this->lang('Date de réouverture'),
					'type'        => 'datetime',
					'value'       => $this->config->nf_maintenance_opening,
					'description' => $this->lang('Affichée aux visiteurs sur la page de maintenance. Laisse vide pour ne pas annoncer de date.')
				]
			])
			->display_required(FALSE)
			->add_submit($this->lang('Programmer'), 'far fa-clock')
			->save();

		// array_filter : une config vide -> explode(' ', '') renvoie [''] (et non []), ce qui
		// court-circuitait le défaut ci-dessous (isset vrai sur une valeur vide) → radio non coché.
		$position = array_values(array_filter(explode(' ', (string)$this->config->nf_maintenance_background_position), 'strlen'));

		$form_maintenance = $this->form()
			->add_rules([
				'title' => [
					'label' => $this->lang('Titre'),
					'type'  => 'text',
					'value' => $this->config->nf_maintenance_title
				],
				'content' => [
					'label' => $this->lang('Contenu'),
					'type'  => 'textarea',
					'value' => $this->config->nf_maintenance_content
				],
				'logo' => [
					'label'  => $this->lang('Logo'),
					'value'  => $this->config->nf_maintenance_logo,
					'type'   => 'file',
					'upload' => 'maintenance',
					'info'   => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
					'check'  => function($filename, $ext){
						if (!in_array($ext, ['gif', 'jpeg', 'jpg', 'png']))
						{
							return $this->lang('Veuillez choisir un fichier d\'image');
						}
					}
				],
				'background' => [
					'label'  => $this->lang('Image de fond'),
					'value'  => $this->config->nf_maintenance_background,
					'type'   => 'file',
					'upload' => 'maintenance',
					'info'   => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
					'check'  => function($filename, $ext){
						if (!in_array($ext, ['gif', 'jpeg', 'jpg', 'png']))
						{
							return $this->lang('Veuillez choisir un fichier d\'image');
						}
					}
				],
				'repeat' => [
					'label'  => $this->lang('Répéter l\'image'),
					'value'  => $this->config->nf_maintenance_background_repeat ?: 'no-repeat',
					'values' => [
						'no-repeat' => $this->lang('Non'),
						'repeat-x'  => $this->lang('Horizontalement'),
						'repeat-y'  => $this->lang('Verticalement'),
						'repeat'    => $this->lang('Les deux')
					],
					'type'   => 'radio'
				],
				'positionX' => [
					'label'  => $this->lang('Position'),
					'value'  => isset($position[0]) ? $position[0] : 'center',
					'values' => [
						'left'   => $this->lang('Gauche'),
						'center' => $this->lang('Centré'),
						'right'  => $this->lang('Droite')
					],
					'type'   => 'radio'
				],
				'positionY' => [
					'value'  => isset($position[1]) ? $position[1] : 'top',
					'values' => [
						'top'    => $this->lang('Haut'),
						'center' => $this->lang('Milieu'),
						'bottom' => $this->lang('Bas')
					],
					'type'   => 'radio'
				],
				'background_color' => [
					'label' => $this->lang('Couleur de fond'),
					'value' => $this->config->nf_maintenance_background_color ?: '#343a40',
					'type'  => 'colorpicker',
					'size'  => 'col-md-6 col-xl-4'
				],
				'text_color' => [
					'label' => $this->lang('Couleur du texte'),
					'value' => $this->config->nf_maintenance_text_color ?: '#fff',
					'type'  => 'colorpicker',
					'size'  => 'col-md-6 col-xl-4'
				]
			])
			->add_submit($this->lang('Valider'))
			->save();

		if ($form_opening->is_valid($post))
		{
			$this->config('nf_maintenance_opening', $post['opening']);
			refresh();
		}
		else if ($form_maintenance->is_valid($post))
		{
			$this	->config('nf_maintenance_title',               $post['title'])
					->config('nf_maintenance_content',             $post['content'])
					->config('nf_maintenance_logo',                $post['logo'], 'int')
					->config('nf_maintenance_background',          $post['background'], 'int')
					->config('nf_maintenance_background_repeat',   $post['repeat'])
					->config('nf_maintenance_background_position', trim($post['positionX'].' '.$post['positionY']))
					->config('nf_maintenance_background_color',    trim($post['background_color']))
					->config('nf_maintenance_text_color',          trim($post['text_color']));

			$this->module('tools')->api()->scss();

			$this->_audit('maintenance');
			refresh();
		}

		// Custom layout: status hero + 2-col (form + preview)
		$back_link = '<div class="settings-section-back"><a href="'.url('admin/settings').'" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> '.$this->lang('Tous les paramètres').'</a></div>';

		// Status hero
		$status_view = $this->view('admin/maintenance');

		// Opening date card
		$opening_card = '<div class="settings-section-card">'
			.'<div class="settings-section-header">'
			.'<div class="settings-section-icon"><i class="far fa-clock"></i></div>'
			.'<div class="settings-section-meta"><div class="settings-section-title">'.$this->lang('Ouverture programmée').'</div></div>'
			.'</div>'
			.'<div class="settings-section-body">'.$form_opening->display().'</div>'
			.'</div>';

		// Customization form (this still uses NeoFrag form display)
		$customize_card = '<div class="settings-section-card">'
			.'<div class="settings-section-header">'
			.'<div class="settings-section-icon"><i class="fas fa-paint-brush"></i></div>'
			.'<div class="settings-section-meta"><div class="settings-section-title">'.$this->lang('Personnalisation de la page de maintenance').'</div></div>'
			.'</div>'
			.'<div class="settings-section-body">'.$form_maintenance->display().'</div>'
			.'</div>';

		// Live preview pane
		$logo_url    = $this->config->nf_maintenance_logo
			? url(NeoFrag()->model2('file', $this->config->nf_maintenance_logo)->path())
			: '';
		$bg_url      = $this->config->nf_maintenance_background
			? url(NeoFrag()->model2('file', $this->config->nf_maintenance_background)->path())
			: '';
		$bg_color    = $this->config->nf_maintenance_background_color ?: '#343a40';
		$text_color  = $this->config->nf_maintenance_text_color ?: '#ffffff';
		$bg_repeat   = $this->config->nf_maintenance_background_repeat ?: 'no-repeat';
		$bg_position = $this->config->nf_maintenance_background_position ?: 'center top';
		$title       = htmlspecialchars((string) ($this->config->nf_maintenance_title ?: $this->lang('Site en maintenance')));
		// Le même texte par défaut que la page de maintenance elle-même (views/maintenance.tpl.php) : l'aperçu
		// montrait une autre phrase que celle que le visiteur lit.
		$content     = htmlspecialchars((string) ($this->config->nf_maintenance_content ?: $this->lang('Le site est momentanément indisponible, le temps d’une mise à jour. Merci de revenir dans quelques instants.')));

		$preview_styles = 'background-color:'.$bg_color.';';
		if ($bg_url) {
			$preview_styles .= 'background-image:url('.$bg_url.');background-repeat:'.$bg_repeat.';background-position:'.$bg_position.';';
		}

		$preview_card = '<div class="settings-section-card">'
			.'<div class="settings-section-header">'
			.'<div class="settings-section-icon"><i class="far fa-eye"></i></div>'
			.'<div class="settings-section-meta"><div class="settings-section-title">'.$this->lang('Aperçu de la page de maintenance').'</div></div>'
			.'</div>'
			.'<div class="maintenance-preview-frame">'
			.'<div class="maintenance-preview-content" style="'.$preview_styles.'color:'.$text_color.'">';
		if ($logo_url) {
			$preview_card .= '<img class="maintenance-preview-logo" src="'.$logo_url.'" alt="">';
		}
		$preview_card .= '<h1 class="maintenance-preview-title" style="color:'.$text_color.'">'.$title.'</h1>'
			.'<div class="maintenance-preview-text">'.$content.'</div>';
		if ($this->config->nf_maintenance_opening) {
			$preview_card .= '<div class="maintenance-preview-clock">'
				.'<i class="far fa-clock"></i> '.$this->lang('Réouverture').' : '
				.timetostr($this->lang('l j F Y, H:i'), $this->config->nf_maintenance_opening)
				.'</div>';
		}
		$preview_card .= '</div></div></div>';

		// Layout
		$html = $back_link
			.$status_view
			.'<div class="row">'
			.'<div class="col-12 col-lg-7">'.$opening_card.$customize_card.'</div>'
			.'<div class="col-12 col-lg-5">'.$preview_card.'</div>'
			.'</div>';

		return $html;
	}

	public function copyright()
	{
		$this	->subtitle($this->lang('Copyright'))
				->icon('far fa-copyright');

		$form_copyright = $this->form()
			->add_rules([
				'copyright' => [
					'label' => $this->lang('Texte du copyright'),
					'value' => $this->config->nf_copyright,
					'type'  => 'textarea'
				]
			])
			->add_submit($this->lang('Valider'))
			->display_required(FALSE)
			->save();

		if ($form_copyright->is_valid($post))
		{
			$this->config('nf_copyright', $post['copyright']);
			$this->_audit('copyright');
			notify($this->lang('Copyright modifié'));
			refresh();
		}

		// Render preview with magic words replaced (same logic as widgets/copyright/controllers/index.php)
		$keywords = [
			'name'      => '<a href="'.url().'">'.$this->config->nf_name.'</a>',
			'neofrag'   => '<a href="https://neofr.ag">NeoFrag Reborn</a>',
			'year'      => date('Y'),
			'copyright' => icon('far fa-copyright')
		];
		$copyright_raw = utf8_html_entity_decode($this->config->nf_copyright);
		if (!in_string('{neofrag}', $copyright_raw))
		{
			$copyright_raw .= '<div class="float-end">'.$this->lang('Propulsé par %s', '{neofrag}').'</div>';
		}
		$copyright_rendered = preg_replace_callback('/\{('.implode('|', array_keys($keywords)).')\}/i', function($match) use ($keywords){
			return $keywords[$match[1]];
		}, $copyright_raw);

		// Layout
		$back_link = '<div class="settings-section-back"><a href="'.url('admin/settings').'" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> '.$this->lang('Tous les paramètres').'</a></div>';

		// Help card (magic words)
		$help_card = '<div class="settings-section-card">'
			.'<div class="settings-section-header">'
			.'<div class="settings-section-icon"><i class="fas fa-magic"></i></div>'
			.'<div class="settings-section-meta"><div class="settings-section-title">'.$this->lang('Mots magiques disponibles').'</div></div>'
			.'</div>'
			.'<div class="settings-section-body">'
			.'<dl class="nf-magic-words">'
			.'<dt><code>{name}</code></dt><dd>'.$this->lang('Nom du site').'</dd>'
			.'<dt><code>{neofrag}</code></dt><dd>'.$this->lang('Lien vers NeoFrag Reborn').'</dd>'
			.'<dt><code>{copyright}</code></dt><dd>'.$this->lang('Symbole').' '.icon('far fa-copyright').'</dd>'
			.'<dt><code>{year}</code></dt><dd>'.$this->lang('Année courante').'</dd>'
			.'</dl>'
			.'</div>'
			.'</div>';

		// Form card
		$form_card = '<div class="settings-section-card">'
			.'<div class="settings-section-header">'
			.'<div class="settings-section-icon"><i class="far fa-copyright"></i></div>'
			.'<div class="settings-section-meta"><div class="settings-section-title">'.$this->lang('Copyright').'</div></div>'
			.'</div>'
			.'<div class="settings-section-body">'.$form_copyright->display().'</div>'
			.'</div>';

		// Preview card
		$preview_card = '<div class="settings-section-card">'
			.'<div class="settings-section-header">'
			.'<div class="settings-section-icon"><i class="far fa-eye"></i></div>'
			.'<div class="settings-section-meta"><div class="settings-section-title">'.$this->lang('Aperçu').'</div></div>'
			.'</div>'
			.'<div class="copyright-preview-frame">'
			.'<div class="copyright-preview-content clearfix">'.$copyright_rendered.'</div>'
			.'</div>'
			.'</div>';

		return $back_link
			.'<div class="row">'
			.'<div class="col-12 col-lg-7">'.$help_card.$form_card.'</div>'
			.'<div class="col-12 col-lg-5">'.$preview_card.'</div>'
			.'</div>';
	}

	public function _layout($callback)
	{
		// No side nav anymore. Layout is now content-driven:
		// - Single back-link at top
		// - $right = main column (default col-12)
		// - $left  = aside column (only used by maintenance) — stacked below or side-by-side via responsive grid
		//
		// For backward compat, both $right and $left are called by some methods.
		// We render them side-by-side on >=lg (left aside col-4, right main col-8),
		// stacked on smaller screens.

		$right = $this->col()->size('col-12');
		$left  = $this->col()->size('col-12');

		$callback($right, $left);

		$back_link = '<div class="settings-section-back">'
			.'<a href="'.url('admin/settings').'" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> '.$this->lang('Tous les paramètres').'</a>'
			.'</div>';

		// If $left has content (maintenance), use 2-col layout. Otherwise 1-col.
		$left_str = (string)$left;
		$right_str = (string)$right;

		// Detect if left has actual panel content (>200 chars rough heuristic; empty col is very short)
		$has_left = strlen(trim(strip_tags($left_str))) > 20;

		if ($has_left)
		{
			return $back_link
				.'<div class="row settings-section-row">'
				.'<div class="col-12 col-lg-4 settings-section-aside">'.$left_str.'</div>'
				.'<div class="col-12 col-lg-8 settings-section-main">'.$right_str.'</div>'
				.'</div>';
		}

		return $back_link.'<div class="settings-section-main">'.$right_str.'</div>';
	}

	/** Trace une sauvegarde de réglages dans le journal d'audit (section, sans les valeurs). */
	private function _audit($section)
	{
		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('settings.saved', ['target_type' => 'settings', 'target_id' => $section]);
	}
}
