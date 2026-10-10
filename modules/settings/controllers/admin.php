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
				'title' => $this->lang('Référencement'),
				'desc'  => $this->lang('Titre et description par langue, image de partage, Google et Bing'),
				'icon'  => 'fas fa-magnifying-glass-chart',
				'url'   => 'admin/settings/seo',
				'color' => 'success'
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
				'desc'  => $this->lang('Captcha : ALTCHA, Turnstile, hCaptcha, reCAPTCHA'),
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
				'title' => $this->lang('Confidentialité'),
				'desc'  => $this->lang('Pages légales du pied de page, services tiers et consentement des visiteurs'),
				'icon'  => 'fas fa-user-shield',
				'url'   => 'admin/settings/confidentialite',
				'color' => 'success'
			],
			[
				'title' => $this->lang('Copyright'),
				'desc'  => $this->lang('Texte du copyright affiché en bas de site'),
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
			$html .= '<div class="settings-hub-title">'.nf_texte($s['title']).'</div>';
			$html .= '<div class="settings-hub-desc">'.nf_texte($s['desc']).'</div>';
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
					'timezone' => [
						'label'       => $this->lang('Fuseau horaire'),
						'description' => $this->lang('Chaque visiteur voit les heures dans le fuseau de son navigateur, et un membre peut choisir le sien dans son profil. Celui-ci sert quand le fuseau du visiteur n’est pas connu : première visite, tâches automatiques.'),
						'values'      => nf_fuseaux_liste(),
						'value'       => nf_fuseau_site()->getName(),
						'type'        => 'select',
						'rules'       => 'required'
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
						// Google Analytics 4 (`G-…`) : Universal Analytics (`UA-…`) a cessé de compter en juillet
						// 2023, et ce champ refusait encore tout autre format (relevé le 2026-10-03).
						'description' => $this->lang('Format G-XXXXXXXXXX (Google Analytics 4)'),
						'value'       => $this->config->nf_analytics,
						'check'       => function($code){
							if (!is_empty($code) && !preg_match('/^(G-[A-Z0-9]{4,20}|UA-\d+-\d+)$/', $code))
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
				if ($var === 'pwa' || $var === 'theme_visiteur' || ($var === 'timezone' && !nf_fuseau_ouvrir($value)))
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
		$site_name        = nf_texte($this->config->nf_name ?: 'NeoFrag');
		$site_description = nf_texte($this->config->nf_description ?: '');
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
			.'<i class="fas fa-lock"></i> '.nf_texte($site_url)
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

	/**
	 * Le référencement (2026-10-03) : ce que les moteurs et les aperçus de partage montrent du
	 * site. Les textes existent PAR LANGUE — la description des Préférences générales, commune à toutes,
	 * reste le repli. Chaque texte a son propre réglage (`nf_seo_description_en`…) : un même nom pour six
	 * langues serait écrasé à chaque enregistrement, Config::__invoke() met à jour toutes ses lignes.
	 */
	public function seo()
	{
		$this	->subtitle($this->lang('Référencement'))
				->icon('fas fa-magnifying-glass-chart');

		$langues = $this->config->langs ?: [$this->config->lang];
		$regles  = [];

		foreach ($langues as $langue)
		{
			$code  = $langue->info()->name;
			$titre = $langue->info()->title;

			$regles['accroche_'.$code] = [
				'label'       => $this->lang('Accroche de l’accueil — %s', $titre),
				'value'       => nf_seo_reglage('accroche', $code),
				'description' => $this->lang('Suit le nom du site dans le titre de l’accueil : « %s — votre accroche ». 60 caractères au plus.', (string) $this->config->nf_name),
				'check'       => function($texte){
					if (mb_strlen(nf_seo_saisie($texte)) > 60)
					{
						return $this->lang('60 caractères au plus : au-delà, les moteurs coupent le titre.');
					}
				}
			];

			$regles['description_'.$code] = [
				'label'       => $this->lang('Description — %s', $titre),
				'type'        => 'textarea',
				'rows'        => 3,
				'value'       => nf_seo_reglage('description', $code),
				'description' => $this->lang('Ce que les moteurs affichent sous le titre, et les aperçus de partage. Entre 70 et 160 caractères ; vide, la description des Préférences générales sert.'),
				'check'       => function($texte){
					if (mb_strlen(nf_seo_saisie($texte)) > 160)
					{
						return $this->lang('160 caractères au plus : au-delà, les moteurs coupent la description.');
					}
				}
			];
		}

		$regles['image'] = [
			'label'  => $this->lang('Image de partage'),
			'value'  => nf_seo_reglage('image'),
			'type'   => 'file',
			'upload' => 'seo',
			'info'   => $this->lang(' d\'image (1 200 × 630 px conseillés, 600 px de large au moins, %d Mo au plus)', file_upload_max_size() / 1024 / 1024),
			'check'  => function($filename, $ext){
				if (!in_array($ext, ['jpeg', 'jpg', 'png', 'webp']))
				{
					return $this->lang('Une image JPEG, PNG ou WebP');
				}

				[$largeur] = getimagesize($filename) ?: [0];

				if ($largeur < 600)
				{
					return $this->lang('L\'image doit faire au moins %dpx de large', 600);
				}
			}
		];

		foreach (['google' => ['Google Search Console', 'https://search.google.com/search-console'], 'bing' => ['Bing Webmaster Tools', 'https://www.bing.com/webmasters']] as $outil => [$nom, $adresse])
		{
			$regles[$outil] = [
				'label'       => '<a href="'.$adresse.'" target="_blank" rel="noopener">'.$nom.'</a>',
				'value'       => nf_seo_reglage($outil),
				'description' => $this->lang('Le code de vérification par balise HTML : le code seul, ou la balise entière que l’outil fait copier.'),
				'check'       => function($code){
					if (nf_seo_saisie($code) !== '' && nf_seo_code_verification(nf_seo_saisie($code)) === '')
					{
						return $this->lang('Ce code est invalide');
					}
				}
			];
		}

		// IndexNow : le site signale lui-même ses pages aux moteurs qui participent.
		$regles['indexnow'] = [
			'label'       => $this->lang('Prévenir les moteurs'),
			'type'        => 'checkbox',
			'values'      => ['on' => $this->lang('Signaler chaque page qui paraît, change ou disparaît (IndexNow)')],
			'checked'     => ['on' => !empty($this->config->nf_seo_indexnow)],
			'description' => $this->lang('Bing, Yandex, Seznam, Naver, Yep et Amazon sont prévenus dans les minutes qui suivent, au lieu de l’apprendre à leur prochain passage. Google n’y participe pas : pour lui, le plan du site reste la voie. Demande la tâche planifiée du site (Surveillance).')
		];

		$this	->form()
				->add_rules($regles)
				->add_submit($this->lang('Valider'))
				->display_required(FALSE);

		if ($this->form()->is_valid($post))
		{
			foreach ($post as $var => $value)
			{
				if ($var === 'indexnow')
				{
					continue;
				}

				$valeur = in_array($var, ['google', 'bing'], TRUE) ? nf_seo_code_verification(nf_seo_saisie($value)) : nf_seo_saisie($value);
				$this->config('nf_seo_'.$var, $valeur);
			}

			// Hors de la boucle : une case décochée n'arrive pas dans le POST. Allumer crée la clé si besoin
			// et repart d'un relevé neuf — ce qui a changé pendant l'extinction, le plan du site le dit.
			$indexnow = !empty($post['indexnow']);

			if ($indexnow && empty($this->config->nf_seo_indexnow))
			{
				if (!nf_indexnow_cle_valide(nf_indexnow_cle()))
				{
					$this->config('nf_seo_indexnow_cle', bin2hex(random_bytes(16)));
				}

				if ($this->db->table_exists('nf_indexnow'))
				{
					$this->db->execute('TRUNCATE TABLE `nf_indexnow`');
				}

				$this->config('nf_seo_indexnow_etat', (string) json_encode(['allume' => date('Y-m-d H:i:s')]));
			}

			$this->config('nf_seo_indexnow', $indexnow ? '1' : '0', 'bool');

			$this->_audit('seo');
			notify($this->lang('Référencement sauvegardé avec succès'));

			refresh();
		}

		return '<div class="settings-section-back"><a href="'.url('admin/settings').'" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> '.$this->lang('Tous les paramètres').'</a>'
			.' <a href="'.url('admin/settings/seo-bilan').'" class="btn btn-light btn-sm"><i class="fas fa-clipboard-check"></i> '.$this->lang('Bilan du référencement').'</a>'
			.' <a href="'.url('admin/settings/seo-redirections').'" class="btn btn-light btn-sm"><i class="fas fa-route"></i> '.$this->lang('Redirections').'</a></div>'
			.'<div class="settings-section-card">'
			.'<div class="settings-section-header">'
			.'<div class="settings-section-icon"><i class="fas fa-magnifying-glass-chart"></i></div>'
			.'<div class="settings-section-meta"><div class="settings-section-title">'.$this->lang('Référencement').'</div>'
			.'<div class="settings-section-subtitle">'.$this->lang('Le plan du site, les liens entre langues et les données structurées se font tout seuls ; ici, ce que le site dit de lui-même.').'</div></div>'
			.'</div>'
			.'<div class="settings-section-body">'.$this->form()->display().'</div>'
			.'</div>';
	}

	/**
	 * Les redirections : une ancienne adresse mène à la nouvelle (301), au lieu de répondre 404.
	 * Le site les cherche juste avant de répondre « Page introuvable » (Libraries\Error) : une page qui
	 * existe n'est jamais détournée. Une page renommée laisse la sienne d'elle-même.
	 */
	public function seo_redirections()
	{
		$this	->subtitle($this->lang('Redirections'))
				->icon('fas fa-route');

		if (!$this->db->table_exists('nf_redirects'))
		{
			$this->error(404);
			return '';
		}

		$langues = nf_langues_du_site();

		$this	->form()
				->add_rules([
					'source' => [
						'label'       => $this->lang('Ancienne adresse'),
						'description' => $this->lang('Le chemin qui ne répond plus : « ancienne-page », « /fr/ancienne-page », ou l’adresse d’un ancien site (« page.php »). La langue et les paramètres sont ignorés.'),
						'rules'       => 'required',
						'check'       => function($saisie) use ($langues){
							if (nf_redirection_source(nf_seo_saisie($saisie), $langues) === '')
							{
								return $this->lang('Indiquez un chemin, pas seulement la racine du site.');
							}
						}
					],
					'cible' => [
						'label'       => $this->lang('Nouvelle adresse'),
						'description' => $this->lang('Un chemin du site (« nouvelle-page ») ou une adresse complète en https://.'),
						'rules'       => 'required',
						'check'       => function($saisie) use ($langues){
							if (nf_redirection_cible(nf_seo_saisie($saisie), $langues) === '')
							{
								return $this->lang('Une adresse du site ou en https:// seulement.');
							}
						}
					],
				])
				->add_submit($this->lang('Ajouter'))
				->display_required(FALSE);

		if ($this->form()->is_valid($post))
		{
			nf_redirection_ajouter(nf_seo_saisie($post['source']), nf_seo_saisie($post['cible']));
			$this->_audit('seo-redirections');
			notify($this->lang('Redirection ajoutée'));
			refresh();
		}

		$lignes = $this->db->select('id', 'source', 'target', 'hits', 'last_hit_at')->from('nf_redirects')->order_by('id DESC')->get();
		$liste  = '';

		foreach ($lignes as $ligne)
		{
			$liste .= '<tr>'
				.'<td><code>/'.nf_texte($ligne['source']).'</code></td>'
				.'<td><code>'.nf_texte(preg_match('#^https?://#', (string) $ligne['target']) ? (string) $ligne['target'] : '/'.$ligne['target']).'</code></td>'
				.'<td class="text-end">'.(int) $ligne['hits'].'</td>'
				.'<td>'.($ligne['last_hit_at'] ? timetostr($this->lang('d/m/Y'), (string) $ligne['last_hit_at']) : '—').'</td>'
				.'<td class="text-end"><a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/settings/seo-redirections-supprimer/'.(int) $ligne['id']).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a></td>'
				.'</tr>';
		}

		$tableau = $liste !== ''
			? '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>'.$this->lang('Ancienne adresse').'</th><th>'.$this->lang('Nouvelle adresse').'</th><th class="text-end">'.$this->lang('Visites').'</th><th>'.$this->lang('Dernière').'</th><th></th></tr></thead><tbody>'.$liste.'</tbody></table></div>'
			: '<p class="text-muted mb-0">'.$this->lang('Aucune redirection : une adresse inconnue répond « Page introuvable ».').'</p>';

		return '<div class="settings-section-back"><a href="'.url('admin/settings/seo').'" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> '.$this->lang('Référencement').'</a></div>'
			.$this->admin_card('fas fa-route', $this->lang('Redirections'), $tableau, $this->lang('Une ancienne adresse mène à la nouvelle : le classement acquis ne se perd pas.'))
			.$this->admin_card('fas fa-plus', $this->lang('Ajouter une redirection'), $this->form()->display());
	}

	public function seo_redirections_supprimer($id = 0)
	{
		$this->check_csrf('admin/settings/seo-redirections');

		if ($this->db->table_exists('nf_redirects'))
		{
			$this->db->where('id', (int) $id)->delete('nf_redirects');
			$this->_audit('seo-redirections');
			notify($this->lang('Redirection supprimée'));
		}

		redirect('admin/settings/seo-redirections');
	}

	/**
	 * Le bilan du référencement : ce que voit un moteur de recherche, mesuré sur le site, et
	 * pour chaque point ce qui le corrige. L'équivalent, lisible par un administrateur, de `check-seo`.
	 */
	public function seo_bilan()
	{
		$this	->subtitle($this->lang('Bilan du référencement'))
				->icon('fas fa-clipboard-check');

		$points = [];
		// Les textes arrivent de lang(), qui rend un objet : convertis ici, une fois.
		$point  = function(string $etat, $titre, $detail, string $lien = '', $action = '') use (&$points){
			$points[] = [$etat, (string) $titre, (string) $detail, $lien, (string) $action];
		};

		// 1. Le plan du site, dans la langue affichée — le même calcul que /sitemap.xml.
		$plan    = nf_seo_plan();
		$nombre  = count($plan['adresses']);
		$modules = $plan['modules'];
		arsort($modules);
		$detail  = implode(', ', array_map(fn ($m, $n) => ($this->module($m) ? $this->module($m)->info()->title : $m).' '.$n, array_keys($modules), $modules));

		$point($nombre > 1 ? 'ok' : 'alerte',
			$this->lang('Plan du site : %d page(s) annoncée(s) en %s', $nombre, $this->config->lang->info()->title),
			$nombre > 1 ? $detail : $this->lang('Seul l\'accueil est annoncé : aucun module n\'a encore de contenu que les visiteurs peuvent lire. Une rubrique vide n\'est pas annoncée ; si du contenu existe, vérifiez les droits dans la matrice des permissions.'),
			url('sitemap.xml'), $this->lang('Voir le plan'));

		// 2. Les textes, langue par langue.
		foreach ($this->config->langs ?: [$this->config->lang] as $langue)
		{
			$code        = $langue->info()->name;
			$accroche    = nf_seo_reglage('accroche', $code);
			$description = nf_seo_description(nf_seo_reglage('description', $code) ?: (string) $this->config->nf_description);
			$manques     = [];

			if ($accroche === '')
			{
				$manques[] = $this->lang('pas d\'accroche : le titre de l\'accueil n\'est que le nom du site');
			}

			if (mb_strlen($description) < 50 || nf_seo_meme_texte($description, (string) $this->config->nf_name))
			{
				$manques[] = $this->lang('une description trop courte, ou qui répète le nom du site');
			}

			$point($manques ? 'conseil' : 'ok',
				$this->lang('Textes de l\'accueil — %s', $langue->info()->title),
				$manques ? implode(' ; ', $manques) : '« '.nf_seo_titre('', (string) $this->config->nf_name, $accroche).' » — '.$description,
				url('admin/settings/seo'), $this->lang('Compléter'));
		}

		// 3. L'image de partage, par la même règle que l'en-tête des pages.
		$partage = nf_seo_image_partage();
		$sources = [
			'reglage' => $this->lang('Celle des réglages, montrée en grand dans les aperçus de Discord, X, Facebook et LinkedIn.'),
			'theme'   => $this->lang('Celle que fournit le thème, montrée en grand dans les aperçus de partage.'),
			'logo'    => $this->lang('Le logo sert à défaut : une image de 1 200 × 630 pixels donnerait un aperçu en grand.'),
			'favicon' => $this->lang('Le favicon sert à défaut : une image de 1 200 × 630 pixels donnerait un aperçu en grand.'),
			''        => $this->lang('Aucune : un lien du site partagé n\'aura pas d\'image.'),
		];
		$point($partage['grande'] ? 'ok' : ($partage['source'] !== '' ? 'conseil' : 'alerte'), $this->lang('Image de partage'), $sources[$partage['source']], url('admin/settings/seo'), $this->lang('Choisir'));

		// 4. Les outils des moteurs. Le plan à leur soumettre est l'index des langues, à la racine — celui
		// qu'annonce robots.txt —, et non celui de la langue affichée (`/fr/sitemap.xml`, conseillé à tort
		// jusqu'au 2026-10-03). Google se vérifie aussi par le DNS du domaine, sans réglage dans le site.
		$plan_index = nf_seo_adresse(site_origin(), (string) $this->url->base, '', 'sitemap.xml');
		$txt        = nf_seo_txt_du_domaine();

		// Bing importe aussi un site depuis Google Search Console, sans rien poser sur le site : quand Google
		// est vérifié, son point le dit plutôt que d'annoncer « non déclaré » un site peut-être importé.
		$google_verifie = FALSE;

		foreach (['google' => ['Google Search Console', 'https://search.google.com/search-console'], 'bing' => ['Bing Webmaster Tools', 'https://www.bing.com/webmasters']] as $outil => [$nom, $adresse])
		{
			$reglage = nf_seo_reglage($outil) !== '';
			$par_dns = !$reglage && $outil === 'google' && nf_seo_txt_annonce($txt, 'google-site-verification=');
			$importe = !$reglage && $outil === 'bing' && $google_verifie;

			if ($outil === 'google')
			{
				$google_verifie = $reglage || $par_dns;
			}

			$point($reglage || $par_dns ? 'ok' : ($importe ? 'info' : 'conseil'), $nom,
				$par_dns ? $this->lang('Vérifié par le DNS du domaine. Soumettez-y le plan du site : %s', $plan_index)
					: ($reglage ? $this->lang('Code de vérification posé. Soumettez-y le plan du site : %s', $plan_index)
					: ($importe ? $this->lang('Importé depuis Google Search Console ? Alors rien d\'autre à faire : l\'import ne laisse aucune trace que le site puisse lire. Sinon, collez ici son code de vérification.')
					: $this->lang('Non déclaré : l\'outil montre comment le moteur voit le site, et à quelle vitesse il le relit. Une vérification par DNS chez l\'hébergeur du domaine convient aussi.'))),
				$adresse, $this->lang('Ouvrir'));
		}

		// 5. IndexNow : le site prévient-il les moteurs, et son dernier envoi est-il passé ?
		$etat = nf_indexnow_etat();

		if (nf_demo())
		{
			$point('info', 'IndexNow', $this->lang('Une démonstration ne prévient jamais les moteurs.'));
		}
		else if (!nf_indexnow_actif())
		{
			$point('conseil', 'IndexNow', $this->lang('Éteint : Bing, Yandex et les autres moteurs participants découvrent une page à leur prochain passage, pas à sa publication.'), url('admin/settings/seo'), $this->lang('Allumer'));
		}
		else if (empty($etat['passage']) && strtotime((string) ($etat['allume'] ?? '')) > time() - 3600)
		{
			$point('info', 'IndexNow', $this->lang('Allumé : le premier passage de la tâche planifiée relèvera les pages du site, sans rien envoyer ; les changements partiront ensuite.'));
		}
		else if (empty($etat['passage']) || strtotime((string) $etat['passage']) < time() - 3600)
		{
			$point('alerte', 'IndexNow', $this->lang('La tâche planifiée du site ne passe pas : rien n’est envoyé. La Surveillance donne la ligne à installer.'), url('admin/monitoring'), $this->lang('Ouvrir'));
		}
		else
		{
			$attente = (int) $this->db->select('COUNT(*)')->from('nf_indexnow')->where('pending', 1)->row();
			$cle     = nf_indexnow_corps(site_origin(), (string) $this->url->base, nf_indexnow_cle(), [])['keyLocation'];

			if (($etat['issue'] ?? '') === 'refusee')
			{
				$point('alerte', 'IndexNow', $this->lang('Le dernier envoi a été refusé (réponse %d) : le plus souvent, le moteur n’a pas pu lire la clé du site à son adresse, %s. %d adresse(s) en attente.', (int) $etat['statut'], $cle, $attente), $cle, $this->lang('Ouvrir'));
			}
			else if (($etat['issue'] ?? '') === 'plus_tard')
			{
				$point('conseil', 'IndexNow', $this->lang('Le dernier envoi n’a pas abouti (réponse %d) ; nouvel essai dans l’heure. %d adresse(s) en attente.', (int) $etat['statut'], $attente));
			}
			else
			{
				$point('ok', 'IndexNow', !empty($etat['recue'])
					? $this->lang('En service : %d adresses suivies. Dernier envoi le %s : %d adresse(s), reçues.', (int) $etat['suivies'], timetostr($this->lang('d/m/Y H:i'), (string) $etat['recue']), (int) $etat['recues'])
					: $this->lang('En service : %d adresses suivies. Rien n’a changé depuis la mise en service.', (int) $etat['suivies']));
			}
		}

		// 6. Ce qui cache le site tout entier.
		$ferme = nf_seo_robots_ferme((string) $this->config->nf_robots_txt);
		$point($ferme ? 'alerte' : 'ok', 'robots.txt',
			$ferme ? $this->lang('« Disallow: / » interdit aux moteurs de lire le site entier.') : $this->lang('Les moteurs peuvent lire le site, et le fichier leur annonce le plan.'),
			url('admin/settings/general'), $this->lang('Modifier'));

		if ($this->config->nf_maintenance)
		{
			$point('alerte', $this->lang('Mode maintenance'), $this->lang('Le site est fermé : les moteurs ne voient que la page de maintenance.'), url('admin/settings/maintenance'), $this->lang('Modifier'));
		}

		// 7. Les contenus qui ont leur propre titre ou description.
		if ($this->db->table_exists('nf_seo_meta'))
		{
			$propres = [];

			foreach ($this->db->select('content_type', 'COUNT(DISTINCT content_id) AS nb')->from('nf_seo_meta')->group_by('content_type')->get() as $ligne)
			{
				$propres[] = $ligne['content_type'].' '.$ligne['nb'];
			}

			$point('info', $this->lang('Contenus au référencement personnalisé'),
				$propres ? implode(', ', $propres) : $this->lang('Aucun : titres et descriptions sont automatiques. Le bouton « Référencement » de la carte d\'édition d\'une actualité, d\'un billet, d\'une page ou d\'une page du wiki les règle.'));
		}

		$icones = ['ok' => 'fas fa-check-circle text-success', 'conseil' => 'fas fa-lightbulb text-warning', 'alerte' => 'fas fa-exclamation-triangle text-danger', 'info' => 'fas fa-info-circle text-info'];
		$liste  = '<ul class="list-group list-group-flush">';

		foreach ($points as [$etat, $titre, $detail, $lien, $action])
		{
			$liste .= '<li class="list-group-item d-flex align-items-start gap-3">'
				.'<i class="'.$icones[$etat].' mt-1"></i>'
				.'<div class="flex-grow-1"><div class="fw-semibold">'.nf_texte($titre).'</div>'
				.'<div class="text-muted small">'.nf_texte($detail).'</div></div>'
				.($lien !== '' ? '<a class="btn btn-sm btn-light text-nowrap" href="'.nf_texte($lien).'"'.(str_starts_with($lien, 'http') ? ' target="_blank" rel="noopener"' : '').'>'.nf_texte($action).'</a>' : '')
				.'</li>';
		}

		$liste .= '</ul>';

		return '<div class="settings-section-back"><a href="'.url('admin/settings/seo').'" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> '.$this->lang('Référencement').'</a></div>'
			.$this->admin_card('fas fa-clipboard-check', $this->lang('Bilan du référencement'), $liste, $this->lang('Ce que voit un moteur de recherche, mesuré sur le site.'), '', TRUE);
	}

	/**
	 * Le titre et la description qu'UN contenu donne aux moteurs, dans chaque langue du site.
	 * Une seule page pour tous les modules : le bouton « Référencement » de leur carte d'édition
	 * (nf_seo_bouton()) y mène, leur page publique les applique (nf_seo_contenu()). Le type est l'un de
	 * ceux que les modules déclarent (`declare_content_types()`), et le contenu doit exister.
	 */
	public function seo_contenu($type = '', $id = 0)
	{
		$types = \NF\NeoFrag\Addons\Module::content_types();
		$id    = (int) $id;
		$desc  = $types[$type] ?? NULL;

		if (!$desc || $id <= 0 || empty($desc['table']) || !$this->db->table_exists($desc['table']) || !$this->db->table_exists('nf_seo_meta')
			|| !$this->db->select($desc['pk'], $desc['pk'].' AS existe')->from($desc['table'])->where($desc['pk'], $id)->row())
		{
			$this->error(404);
			return '';
		}

		$this	->subtitle($this->lang('Référencement'))
				->icon('fas fa-magnifying-glass-chart');

		$langues = $this->config->langs ?: [$this->config->lang];
		$saisies = [];

		foreach ($this->db->select('lang', 'title', 'description')->from('nf_seo_meta')->where('content_type', $type)->where('content_id', $id)->get() as $ligne)
		{
			$saisies[$ligne['lang']] = $ligne;
		}

		$regles = [];

		foreach ($langues as $langue)
		{
			$code  = $langue->info()->name;
			$titre = $langue->info()->title;

			$regles['titre_'.$code] = [
				'label'       => $this->lang('Titre pour les moteurs — %s', $titre),
				'value'       => $saisies[$code]['title'] ?? '',
				'description' => $this->lang('Remplace le titre de la page dans les résultats et les aperçus de partage ; le nom du site suit. Vide, le titre de la page sert. 60 caractères au plus.'),
				'check'       => function($texte){
					if (mb_strlen(nf_seo_saisie($texte)) > 60)
					{
						return $this->lang('60 caractères au plus : au-delà, les moteurs coupent le titre.');
					}
				}
			];

			$regles['description_'.$code] = [
				'label'       => $this->lang('Description — %s', $titre),
				'type'        => 'textarea',
				'rows'        => 3,
				'value'       => $saisies[$code]['description'] ?? '',
				'description' => $this->lang('Ce que les moteurs affichent sous le titre. Vide, la description automatique de la page sert. 160 caractères au plus.'),
				'check'       => function($texte){
					if (mb_strlen(nf_seo_saisie($texte)) > 160)
					{
						return $this->lang('160 caractères au plus : au-delà, les moteurs coupent la description.');
					}
				}
			];
		}

		$this	->form()
				->add_rules($regles)
				->add_submit($this->lang('Valider'))
				->display_required(FALSE);

		if ($this->form()->is_valid($post))
		{
			foreach ($langues as $langue)
			{
				$code        = $langue->info()->name;
				$titre       = nf_seo_saisie($post['titre_'.$code] ?? '');
				$description = nf_seo_saisie($post['description_'.$code] ?? '');

				$this->db	->where('content_type', $type)
							->where('content_id', $id)
							->where('lang', $code)
							->delete('nf_seo_meta');

				if ($titre !== '' || $description !== '')
				{
					$this->db->insert('nf_seo_meta', [
						'content_type' => $type,
						'content_id'   => $id,
						'lang'         => $code,
						'title'        => $titre,
						'description'  => $description,
					]);
				}
			}

			$this->_audit('seo-contenu');
			notify($this->lang('Référencement sauvegardé avec succès'));

			redirect_back();
		}

		$adresse = \NF\NeoFrag\Addons\Module::content_url_of($type, $id);
		$voir    = $adresse !== '' ? ' <a class="btn btn-secondary btn-sm" href="'.nf_texte($adresse).'" target="_blank" rel="noopener">'.icon('fas fa-external-link-alt').' '.$this->lang('Voir la page').'</a>' : '';

		return '<div class="settings-section-back"><a href="'.url('admin/settings/seo').'" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> '.$this->lang('Référencement').'</a>'.$voir.'</div>'
			.'<div class="settings-section-card">'
			.'<div class="settings-section-header">'
			.'<div class="settings-section-icon"><i class="fas fa-magnifying-glass-chart"></i></div>'
			.'<div class="settings-section-meta"><div class="settings-section-title">'.$this->lang('Référencement de ce contenu').'</div>'
			.'<div class="settings-section-subtitle">'.$this->lang('Ce que ce contenu montre aux moteurs de recherche et dans les aperçus de partage, langue par langue. Tout ce qui reste vide est automatique.').'</div></div>'
			.'</div>'
			.'<div class="settings-section-body">'.$this->form()->display().'</div>'
			.'</div>';
	}

	/**
	 * Les inscriptions : ouvertes ou fermées, le règlement, le message de bienvenue.
	 *
	 * Le règlement, le titre et le message se traduisent langue par langue (2026-10-04) : ils n'avaient
	 * qu'une valeur, et un visiteur anglais lisait le règlement en français. Un onglet par langue du site ;
	 * l'adresse porte la langue (`admin/settings/registration/en`), que le formulaire garde. Chaque langue a
	 * sa valeur (`nf_registration_charte_en`…) ; une langue qui n'a pas la sienne montre la valeur commune
	 * (`nf_registration_charte`, la seule jusqu'ici), que le premier enregistrement pose si elle manque —
	 * un texte écrit une fois sert à toutes les langues tant qu'elles ne sont pas traduites.
	 */
	public function registration($langue = '')
	{
		$this	->subtitle($this->lang('Gestion des inscriptions'))
				->icon('fas fa-sign-in-alt fa-rotate-90')
				->js('admin/status_toggle');

		$langues = [];

		foreach ($this->config->langs ?: [$this->config->lang] as $l)
		{
			$langues[(string) $l->info()->name] = $l;
		}

		if (!isset($langues[$langue]))
		{
			$langue = (string) $this->config->lang->info()->name;
		}

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

		// Une valeur propre à la langue, ou la commune qu'elle montre faute de traduction.
		$propre = fn (string $nom, string $code): bool => trim(strip_tags((string) ($this->config->{$nom.'_'.$code} ?? ''))) !== '';
		// `lang()` rend un objet de traduction, pas une chaîne : le convertir, sinon le type de retour fait
		// tomber la page en 500 (vu au banc d'essai, 2026-10-04).
		$aide   = fn (string $nom): string => (string) ($propre($nom, $langue)
			? $this->lang('Texte propre à cette langue.')
			: $this->lang('Cette langue n\'a pas encore le sien : c\'est le texte commun qui s\'affiche. Enregistrez-le ici pour le traduire.'));

		$enregistrer = function (string $nom, $valeur) use ($langue): void {
			$this->config($nom.'_'.$langue, (string) $valeur);

			if (trim(strip_tags((string) ($this->config->$nom ?? ''))) === '')
			{
				$this->config($nom, (string) $valeur);
			}
		};

		// Form 1: Règlement (charte)
		$form_charte = $this->form()
			->add_rules([
				'registration_charte' => [
					'label'       => $this->lang('Règlement'),
					'value'       => $this->config->traduit('nf_registration_charte', $langue),
					'type'        => 'editor',
					'description' => $aide('nf_registration_charte')
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
					'value' => html_entity_decode($this->config->traduit('nf_welcome_title', $langue), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
					'type'  => 'text'
				],
				'welcome_content' => [
					'label'       => $this->lang('Message de bienvenue'),
					'value'       => $this->config->traduit('nf_welcome_content', $langue),
					'type'        => 'editor',
					'description' => $this->lang('Placez [pseudo] pour afficher automatiquement le pseudo du nouveau membre dans le message').' '.$this->lang('Il arrive en texte dans la messagerie : titres, listes et liens sont gardés, la mise en forme non.').' '.$aide('nf_welcome_content')
				]
			])
			->add_submit($this->lang('Valider'))
			->display_required(FALSE)
			->save();

		// Form 3: Validation de l'inscription par e-mail (éteinte par défaut : rien ne change sans ce choix)
		$form_validation = $this->form()
			->add_rules([
				'validation' => [
					'type'        => 'checkbox',
					'checked'     => ['on' => (bool) $this->config->nf_registration_validation],
					'values'      => ['on' => $this->lang('Faire valider l\'adresse e-mail d\'un nouveau membre avant sa première connexion')],
					'description' => $this->lang('Le membre reçoit un lien, valable deux jours ; tant qu\'il ne l\'a pas ouvert, il ne peut pas se connecter, et une tentative de connexion lui en renvoie un. Une inscription par Discord, GitHub ou Google n\'en a pas besoin.')
				]
			])
			->add_submit($this->lang('Valider'))
			->display_required(FALSE)
			->save();

		if ($form_validation->is_valid($post))
		{
			$this->config('nf_registration_validation', in_array('on', (array) $post['validation']));
			$this->_audit('registration_validation');
			notify($this->lang('Validation des inscriptions sauvegardée'));
			refresh();
		}
		else if ($form_charte->is_valid($post))
		{
			$enregistrer('nf_registration_charte', $post['registration_charte']);
			$this->_audit('registration');
			notify($this->lang('Règlement sauvegardé'));
			refresh();
		}
		else if ($form_welcome->is_valid($post))
		{
			$this	->config('nf_welcome',         in_array('on', (array)$post['welcome']))
					->config('nf_welcome_user_id', $post['welcome_user_id']);
			$enregistrer('nf_welcome_title',   $post['welcome_title']);
			$enregistrer('nf_welcome_content', $post['welcome_content']);
			$this->_audit('welcome');
			notify($this->lang('Message de bienvenue sauvegardé'));
			refresh();
		}

		// Layout
		$back_link = '<div class="settings-section-back"><a href="'.url('admin/settings').'" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> '.$this->lang('Tous les paramètres').'</a></div>';

		// Status hero
		$status_view = $this->view('admin/registration');

		// Un onglet par langue, coché quand le règlement et le message y sont traduits.
		$onglets = '';

		if (count($langues) > 1)
		{
			$onglets = '<div class="settings-section-card"><div class="settings-section-body">'
				.'<p class="text-muted mb-2">'.$this->lang('Le règlement, le titre et le message de bienvenue se traduisent langue par langue. Une langue sans texte propre montre le texte commun.').'</p>'
				.'<ul class="nav nav-pills">';

			foreach ($langues as $code => $l)
			{
				$traduite = $propre('nf_registration_charte', $code) && $propre('nf_welcome_content', $code);
				$onglets .= '<li class="nav-item"><a class="nav-link'.($code === $langue ? ' active' : '').'" href="'.url('admin/settings/registration/'.$code).'">'
					.nf_texte($l->info()->title).($traduite ? ' '.icon('fas fa-check') : '').'</a></li>';
			}

			$onglets .= '</ul></div></div>';
		}

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

		// Validation card
		$validation_card = '<div class="settings-section-card">'
			.'<div class="settings-section-header">'
			.'<div class="settings-section-icon"><i class="fas fa-envelope-open-text"></i></div>'
			.'<div class="settings-section-meta"><div class="settings-section-title">'.$this->lang('Validation par e-mail').'</div></div>'
			.'</div>'
			.'<div class="settings-section-body">'.$form_validation->display().'</div>'
			.'</div>';

		return $back_link.$status_view.$validation_card.$onglets.$charte_card.$welcome_card;
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
				$grid_html .= '<a href="'.nf_texte($current).'" target="_blank" rel="noopener" class="social-card-visit" title="'.$this->lang('Ouvrir le profil').'"><i class="fas fa-external-link-alt"></i></a>';
			}
			$grid_html .= '</div>'
				.'<input type="url" class="form-control social-card-input" '
				.'name="'.$token.'[social_'.$key.']" '
				.'value="'.nf_texte($current).'" '
				.'placeholder="'.nf_texte($placeholder).'" />'
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

		$captcha      = \NF\NeoFrag\Libraries\Captcha::class;
		$fournisseurs = [];
		$consoles     = [];

		foreach ($captcha::FOURNISSEURS as $cle => $classe)
		{
			$fournisseurs[$cle] = $classe::cles_requises() ? $classe::nom() : $classe::nom().' — '.$this->lang('sans clé, recommandé');

			if ($classe::cles_requises())
			{
				$consoles[] = '<a href="'.$classe::console().'" target="_blank" rel="noopener">'.$classe::nom().'</a>';
			}
		}

		$fournisseurs[$captcha::AUCUN] = $this->lang('Aucun (déconseillé)');

		$secret = (string) $this->config->nf_captcha_private_key;
		$choix  = (string) $this->config->nf_captcha_provider ?: $captcha::cle_active('', (string) $this->config->nf_captcha_public_key, $secret);

		$this	->form()
				->add_rules([
					'captcha_provider' => [
						'label'  => $this->lang('Fournisseur'),
						'values' => $fournisseurs,
						'value'  => $choix ?: $captcha::AUCUN,
						'type'   => 'select'
					],
					'captcha_public_key' => [
						'label'       => $this->lang('Clé de site'),
						'description' => $this->lang('Pour Turnstile, hCaptcha et reCAPTCHA. ALTCHA n\'en demande aucune.'),
						'value'       => $this->config->nf_captcha_public_key,
						'type'        => 'text'
					],
					'captcha_private_key' => [
						'label'       => $this->lang('Clé secrète'),
						'description' => $secret !== ''
							? $this->lang('Une clé secrète est déjà enregistrée (non affichée par sécurité) — laisse vide pour la conserver, ou saisis-en une nouvelle.')
							: $this->lang('La clé secrète du fournisseur, qui ne quitte jamais le serveur.'),
						'value'       => '',
						'type'        => 'password'
					]
				])
				->add_submit($this->lang('Valider'))
				->display_required(FALSE);

		if ($this->form()->is_valid($post))
		{
			// Un choix hors de la liste (un select scalaire n'est pas validé par le form) retombe sur ALTCHA.
			$fournisseur = (string) $post['captcha_provider'];
			$fournisseur = isset($captcha::FOURNISSEURS[$fournisseur]) || $fournisseur === $captcha::AUCUN ? $fournisseur : 'altcha';

			$this	->config('nf_captcha_provider',   $fournisseur)
					->config('nf_captcha_public_key', trim((string) $post['captcha_public_key']));

			// La clé secrète, chiffrée au repos, n'est ré-écrite QUE si elle est renseignée : le form met les
			// champs vides à NULL.
			if (!empty($post['captcha_private_key']))
			{
				$this->config('nf_captcha_private_key', $this->crypt->encrypt_secret(trim((string) $post['captcha_private_key'])));
			}

			$this->_audit('captcha');

			$classe = $captcha::FOURNISSEURS[$fournisseur] ?? NULL;

			if ($classe && $classe::cles_requises() && (trim((string) $post['captcha_public_key']) === '' || (empty($post['captcha_private_key']) && $secret === '')))
			{
				notify($this->lang('Réglages enregistrés — mais sans ses deux clés, ce fournisseur est remplacé par ALTCHA.'), 'warning');
			}
			else
			{
				notify($this->lang('Protection anti-robots enregistrée'));
			}

			refresh();
		}

		$aide = '<div class="alert alert-info">'
			.$this->lang('ALTCHA, le fournisseur par défaut, ne demande ni compte ni clé : il est hébergé par votre site et ne dépose aucun cookie.')
			.' '.$this->lang('Les autres demandent une clé de site et une clé secrète, à créer dans leur console :')
			.' '.implode(', ', $consoles).'.</div>';

		return $this->_layout(function($col) use ($aide){
			$col->append($this	->panel()
								->heading($this->lang('Protection anti-robots'), 'fas fa-shield-alt')
								->body($aide.$this->form()->display())
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
		$title       = nf_texte($this->config->nf_maintenance_title ?: $this->lang('Site en maintenance'));
		// Le même texte par défaut que la page de maintenance elle-même (views/maintenance.tpl.php) : l'aperçu
		// montrait une autre phrase que celle que le visiteur lit.
		$content     = nf_texte($this->config->nf_maintenance_content ?: $this->lang('Le site est momentanément indisponible, le temps d’une mise à jour. Merci de revenir dans quelques instants.'));

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

	/**
	 * `admin/settings/confidentialite` — ce que le pied de page affiche (helpers/theme.php, nf_liens_legaux()) et
	 * ce que le site demande à ses visiteurs (helpers/consentement.php). Les pages légales se choisissent parmi
	 * les pages publiées ; leur contenu est un texte juridique, il revient à l'exploitant du site. Le reste se
	 * lit : les services tiers que le site peut afficher, ceux qui ouvrent le bandeau, et où les régler.
	 */
	public function confidentialite()
	{
		$this	->subtitle($this->lang('Confidentialité'))
				->icon('fas fa-user-shield');

		$pages = ['' => (string) $this->lang('Aucune')];

		if (($module = @$this->module('pages')) && $module->is_enabled())
		{
			// Le titre dans la langue de l'administrateur, sinon dans une autre, sinon l'adresse de la page.
			$langue = (string) $this->config->lang->info()->name;

			foreach ($this->db	->select('p.name', 'pl.lang', 'pl.title')
								->from('nf_pages p')
								->join('nf_pages_lang pl', 'pl.page_id = p.page_id', 'LEFT')
								->where('p.published', '1')
								->order_by('p.name')
								->get() as $page)
			{
				$nom = (string) $page['name'];

				if (!isset($pages[$nom]) || $page['lang'] === $langue)
				{
					$pages[$nom] = (string) ($page['title'] ?: $nom);
				}
			}
		}

		$choisie = function (string $reglage, string $defaut) use ($pages): string {
			$nom = (string) $this->config->$reglage ?: $defaut;

			return isset($pages[$nom]) ? $nom : '';
		};

		$this	->form()
				->add_rules([
					'page_mentions' => [
						'label'       => $this->lang('Mentions légales'),
						'description' => $this->lang('L’identité de l’éditeur du site et de son hébergeur (loi pour la confiance dans l’économie numérique, art. 1-1).'),
						'values'      => $pages,
						'value'       => $choisie('nf_page_mentions', 'mentions-legales'),
						'type'        => 'select'
					],
					'page_confidentialite' => [
						'label'       => $this->lang('Politique de confidentialité'),
						'description' => $this->lang('Ce que le site fait des données de ses visiteurs et de ses membres (RGPD, art. 12 à 14).'),
						'values'      => $pages,
						'value'       => $choisie('nf_page_confidentialite', 'confidentialite'),
						'type'        => 'select'
					]
				])
				->add_submit($this->lang('Valider'))
				->display_required(FALSE);

		if ($this->form()->is_valid($post))
		{
			// Une page hors de la liste (un select n'est pas validé par le form) ne s'enregistre pas.
			foreach (['page_mentions' => 'nf_page_mentions', 'page_confidentialite' => 'nf_page_confidentialite'] as $champ => $reglage)
			{
				$nom = (string) ($post[$champ] ?? '');
				$this->config($reglage, isset($pages[$nom]) && $nom !== '' ? $nom : '-');
			}

			$this->_audit('confidentialite');
			notify($this->lang('Pages légales enregistrées'));
			refresh();
		}

		$site     = nf_consentement_du_site();
		$services = nf_consentement_services();
		$noms     = fn(array $cles): string => $cles ? implode(', ', array_map(fn(string $c): string => nf_texte($services[$c]['nom']), $cles)) : (string) $this->lang('aucun');

		$etat = '<p>'.$this->lang('Le pied de chaque page porte les liens vers ces deux pages, s’ils existent, et « Gérer mes cookies », toujours : chaque visiteur y retrouve ce que le site dépose et y change ses choix.').'</p>'
			.'<ul class="mb-3">'
			.'<li><strong>'.$this->lang('Services qui ouvrent le bandeau').'</strong> : '
			.($site['bandeau'] ? $noms($site['bandeau']).'. '.$this->lang('Ils agiraient sur toutes les pages : le bandeau demande l’accord du visiteur avant tout.') : $this->lang('Aucun : le site n’a rien à demander d’emblée, et n’affiche pas de bandeau.')).'</li>'
			.'<li><strong>'.$this->lang('Contenus d’autres sites').'</strong> : '.$noms($site['contenus']).'. '.$this->lang('Là où une page en contient, un avis tient leur place jusqu’à ce que le visiteur les accepte.').'</li>'
			.'<li><strong>'.$this->lang('Images d’autres sites').'</strong> : '.$this->lang('servies par le site lui-même ; le navigateur des visiteurs ne les demande jamais ailleurs.').'</li>'
			.'</ul>'
			.'<p class="mb-0">'.$this->lang('La mesure d’audience se règle dans %s, le captcha dans %s.', '<a href="'.url('admin/settings/general').'">'.$this->lang('Préférences générales').'</a>', '<a href="'.url('admin/settings/captcha').'">'.$this->lang('Sécurité anti-bots').'</a>').'</p>';

		return $this->_layout(function($col) use ($etat){
			$col->append($this	->panel()
								->heading($this->lang('Pages légales'), 'fas fa-scale-balanced')
								->body($this->form()->display()));
			$col->append($this	->panel()
								->heading($this->lang('Ce que le site demande à ses visiteurs'), 'fas fa-user-shield')
								->body($etat));
		});
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
			'neofrag'   => '<a href="https://neofrag-reborn.xyz">NeoFrag Reborn</a>',
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
