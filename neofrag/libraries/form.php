<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;

class Form extends Library
{
	static private $types = [
		'text',
		'password',
		'email',
		'url',
		'date',
		'datetime',
		'time',
		'number',
		'phone',
		'checkbox',
		'radio',
		'select',
		'tags',
		'file',
		'textarea',
		'editor',
		'colorpicker',
		'iconpicker',
		'legend'
	];

	static protected $_form;

	private $_buttons          = [];
	private $_confirm_deletion = [];
	private $_errors           = [];
	private $_rules            = [];
	private $_values           = [];
	private $_display_required = TRUE;
	private $_fast_mode        = FALSE;
	private $_display_captcha  = FALSE;

	static private function _token($id)
	{
		static $tokens;

		if ($tokens === NULL)
		{
			$tokens = NeoFrag()->session('form') ?: [];
		}

		if (empty($tokens[$id]))
		{
			NeoFrag()->session->set('form', $id, $tokens[$id] = unique_id(array_merge([$id], $tokens)));
		}

		return $tokens[$id];
	}

	public function __invoke()
	{
		if (!static::$_form)
		{
			static::$_form = $this;
			$this->id = $this->__id();
		}

		return static::$_form;
	}

	public function add_rules($rules, $values = [])
	{
		if (!is_array($rules))
		{
			$this->_values = $values;

			$paths = [];

			if ($path = $this->__caller->__path('forms', $rules.'.php', $paths))
			{
				include $path;
			}
			else
			{
				trigger_error('Unfound form: '.$rules.' in paths ['.implode(';', $paths).']', E_USER_WARNING);
			}
		}

		foreach ($rules as $var => $options)
		{
			if (!empty($options['rules']))
			{
				$options['rules'] = explode('|', $options['rules']);
			}

			$this->_rules[$var] = $options;
		}

		return $this;
	}

	public function add_captcha()
	{
		if (!$this->user())
		{
			$this->_display_captcha = $this->captcha->is_ok();
		}

		return $this;
	}

	public function add_back($url)
	{
		array_unshift($this->_buttons, [
			'label'  => NeoFrag()->lang('Retour'),
			'action' => $this->url->back() ?: $url,
			'icon'   => 'fas fa-arrow-left'
		]);

		return $this;
	}

	/**
	 * Bouton de validation du formulaire.
	 *
	 * L'icône a un défaut VOLONTAIRE : les 118 boutons d'envoi du projet étaient rendus nus alors
	 * que tous les boutons écrits à la main (barres d'outils, tableaux) portent une icône — d'où
	 * l'impression que « certains boutons de l'admin n'ont pas d'icône ». Le défaut est posé ici,
	 * une seule fois, plutôt que recopié sur chaque appel. Les écrans dont l'action n'est pas une
	 * validation (envoyer, supprimer, cloner…) passent leur propre icône en second argument.
	 */
	public function add_submit($label, $icon = 'fas fa-check')
	{
		$this->_buttons[] = [
			'type'  => 'submit',
			'label' => $label,
			'icon'  => $icon
		];

		return $this;
	}

	public function token($id = NULL)
	{
		if ($id === NULL)
		{
			$id = $this->id;
		}

		return self::_token($id);
	}

	public function confirm_deletion($title, $message = '')
	{
		$this->_confirm_deletion = [$title, $message];
		return $this;
	}

	public function display_required($display)
	{
		$this->_display_required = $display;
		return $this;
	}

	public function fast_mode()
	{
		$this->_fast_mode        = TRUE;
		$this->_display_required = FALSE;
		return $this;
	}

	public function is_valid(&$post = NULL)
	{
		// Site de démo : seules les écritures que la remise à zéro horaire sait défaire sont
		// permises (cf. nf_demo_ecriture_permise). Réglages, addons, comptes et envois de courrier
		// restent refusés — la remise à zéro ne les rétablit pas.
		if (nf_demo() && $this->url->admin && strtolower($_SERVER['REQUEST_METHOD']) == 'post'
			&& !nf_demo_ecriture_permise())
		{
			static $notified = FALSE;
			if (!$notified)
			{
				notify(NeoFrag()->lang('Cette partie est en lecture seule sur le site de démonstration.'), 'warning');
				$notified = TRUE;
			}
			return FALSE;
		}

		$post = post($token = $this->token());

		if (($this->_display_captcha && !$this->captcha->is_valid()) || strtolower($_SERVER['REQUEST_METHOD']) != 'post' || (empty($post) && empty($_FILES[$token])))
		{
			return FALSE;
		}

		if ($this->_confirm_deletion)
		{
			return $post === ['delete'];
		}

		foreach ($post as $key => &$value)
		{
			if (!in_array($key, array_keys($this->_rules)))
			{
				return FALSE;
			}
			else if (isset($this->_rules[$key]['type']) && $this->_rules[$key]['type'] === 'editor')
			{
				// Champ éditeur riche (TinyMCE) : contient du HTML légitime. Pas d'échappement brut
				// (le casserait — le HTML s'afficherait en balises littérales) ; il est assaini par
				// allow-list (HTMLPurifier) dans _check_editor.
			}
			else if (is_array($value))
			{
				array_walk_recursive($value, function(&$v, $k){
					$v = utf8_htmlentities(trim($v));
				});
			}
			else if ($value !== NULL)
			{
				$value = utf8_htmlentities(trim($value));
			}

			unset($value);
		}

		foreach ($this->_rules as $var => $options)
		{
			if (isset($options['type']) && $options['type'] == 'legend')
			{
				continue;
			}

			if (isset($options['type']) && $options['type'] == 'iconpicker' && !empty($post[$var]) && $post[$var] == 'empty')
			{
				$post[$var] = '';
			}

			if (!is_array($options) || !isset($options['type']) || !in_array($type = $options['type'], self::$types) || !method_exists($this, '_check_'.$type))
			{
				$type = 'text';
			}

			if (($error = $this->{'_check_'.$type}($post, $var, $options)) !== TRUE)
			{
				$this->_errors[$var] = $error;
			}
		}

		if (empty($this->_errors))
		{
			if ($this->_has_upload())
			{
				// `$_FILES[$token]` peut être ABSENT, même quand le formulaire déclare un envoi de
				// fichier : il n'existe que si la requête est en `multipart/form-data`. Un navigateur
				// l'envoie toujours — mais pas un client qui poste en `x-www-form-urlencoded`, ce que
				// font les outils de contrôle et tout appel programmatique.
				//
				// La lecture nue journalisait alors un `Undefined array key` à chaque envoi, sans rien
				// casser : la boucle ci-dessous ne fait rien quand `tmp_name` est vide, et un tableau
				// vide se comporte exactement comme l'absence de fichier. Constaté le 2026-09-16 dans
				// le journal du site de démonstration.
				$files = $_FILES[$token] ?? [];

				foreach ($this->_rules as $var => $options)
				{
					if (isset($options['type']) && $options['type'] == 'file')
					{
						if (!empty($post[$var]) && $post[$var] == 'delete' && !empty($options['value']))
						{
							NeoFrag()->model2('file', $options['value'])->delete();
							$options['value'] = $post[$var] = 0;
						}

						if (!empty($files['tmp_name'][$var]))
						{
							if (!($post[$var] = NeoFrag()->model2('file')->static_uploaded_file($files, isset($options['upload']) ? $options['upload'] : NULL, isset($options['value']) ? $options['value'] : NULL, $var)->id))
							{
								$this->_errors[$var] = NeoFrag()->lang('Erreur de transfert');
								return FALSE;
							}
							else if (isset($options['post_upload']) && is_callable($options['post_upload']))
							{
								call_user_func_array($options['post_upload'], [$post[$var]]);
							}
						}

						if (!empty($options['value']) && empty($post[$var]))
						{
							$post[$var] = $options['value'];
						}
					}
				}
			}

			return TRUE;
		}

		return FALSE;
	}

	public function get_errors()
	{
		return $this->_errors;
	}

	public function value($var)
	{
		return isset($this->_values[$var]) ? $this->_values[$var] : NULL;
	}

	private function _check_text($post, $var, $options)
	{
		if (!empty($options['rules']) && in_array('disabled', $options['rules']))
		{
			return TRUE;
		}

		if (!in_array($post[$var], ['', NULL]) &&
			!empty($options['values']) &&
			is_array($options['values']) &&
			is_array($post[$var]) &&
			array_diff(array_filter($post[$var]), array_map('utf8_htmlentities', array_keys($options['values'])))
		)
		{
			return NeoFrag()->lang('La valeur sélectionnée n\'est pas valide|Les valeurs sélectionnées ne sont pas valides', count($post[$var]));
		}

		$is_file = !empty($options['type']) && $options['type'] == 'file';

		if (	!empty($options['rules']) &&
				in_array('required', $options['rules']) &&
				(
					($is_file && empty($_FILES[$this->token()]['tmp_name'][$var])) ||
					(!$is_file && in_array($post[$var], ['', NULL]))
				)
			)
		{
			return NeoFrag()->lang('Veuillez remplir ce champ');
		}

		if ($is_file && !empty($_FILES[$this->token()]['error'][$var]) && $_FILES[$this->token()]['error'][$var] != 4)
		{
			// Le code 4 (UPLOAD_ERR_NO_FILE) n'arrive pas ici : l'absence de fichier relève de la
			// règle `required`, vérifiée juste au-dessus.
			$errors = [
				1 => NeoFrag()->lang('La taille du fichier téléchargé excède la valeur de upload_max_filesize, configurée dans le php.ini'),
				2 => NeoFrag()->lang('La taille du fichier téléchargé excède la valeur de MAX_FILE_SIZE, qui a été spécifiée dans le formulaire HTML'),
				3 => NeoFrag()->lang('Le fichier n\'a été que partiellement téléchargé'),
				6 => NeoFrag()->lang('Un dossier temporaire est manquant'),
				7 => NeoFrag()->lang('Échec de l\'écriture du fichier sur le disque'),
				8 => NeoFrag()->lang('Une extension PHP a arrêté l\'envoi de fichier')
			];

			return $errors[$_FILES[$this->token()]['error'][$var]] ?? NeoFrag()->lang('Erreur');
		}

		if (isset($options['check']) && is_callable($options['check']))
		{
			if (!empty($options['type']) && $options['type'] == 'file')
			{
				$error = !empty($_FILES[$this->token()]['tmp_name'][$var]) ? call_user_func_array($options['check'], [$_FILES[$this->token()]['tmp_name'][$var], extension($_FILES[$this->token()]['name'][$var])]) : TRUE;
			}
			else
			{
				$error = call_user_func_array($options['check'], [$post[$var], $post]);
			}

			if (!in_array($error, [TRUE, NULL], TRUE))
			{
				return $error;
			}
		}

		return TRUE;
	}

	private function _check_file(&$post, $var, $options)
	{
		if (empty($post[$var]))
		{
			$post[$var] = NULL;
		}

		return $this->_check_text($post, $var, $options);
	}

	private function _check_checkbox(&$post, $var, $options)
	{
		$post[$var] = array_filter(isset($post[$var]) ? $post[$var] : [], function($a){
			return strlen($a);
		});
		return $this->_check_text($post, $var, $options);
	}

	private function _check_email($post, $var, $options)
	{
		if ($post[$var] !== '' && !is_valid_email($post[$var]))
		{
			return NeoFrag()->lang('Veuillez entrer une adresse email valide');
		}

		return $this->_check_text($post, $var, $options);
	}

	private function _check_url($post, $var, $options)
	{
		if ($post[$var] !== '' && !is_valid_url($post[$var]))
		{
			return NeoFrag()->lang('Veuillez entrer une adresse url valide');
		}

		return $this->_check_text($post, $var, $options);
	}

	private function _check_number(&$post, $var, $options)
	{
		if ($post[$var] !== '' && $post[$var] != (int)$post[$var])
		{
			return NeoFrag()->lang('Nombre invalide');
		}

		return $this->_check_text($post, $var, $options);
	}

	private function _check_phone(&$post, $var, $options)
	{
		if ($post[$var] !== '' && !preg_match('/^0[1-9]([. ]?)\d{2}(?:\1\d{2}){3}$/', $post[$var], $match))
		{
			return NeoFrag()->lang('Numéro de téléphone invalide');
		}

		return $this->_check_text($post, $var, $options);
	}

	private function _check_datetime(&$post, $var, $options)
	{
		$this->config->lang->datetime2sql($post[$var]);
		return $this->_check_text($post, $var, $options);
	}

	private function _check_time(&$post, $var, $options)
	{
		$this->config->lang->time2sql($post[$var]);
		return $this->_check_text($post, $var, $options);
	}

	private function _check_date(&$post, $var, $options)
	{
		$this->config->lang->date2sql($post[$var]);
		return $this->_check_text($post, $var, $options);
	}

	private function _check_editor(&$post, $var, $options)
	{
		// Contenu HTML riche (TinyMCE) non fiable → assaini par allow-list (anti XSS stocké) au lieu
		// d'être échappé en brut. Idempotent avec la sanitization au rendu (bbcode()/forum_render).
		$post[$var] = sanitize_html(trim($post[$var]));
		return $this->_check_text($post, $var, $options);
	}

	public function display()
	{
		if ($this->_confirm_deletion)
		{
			list($title, $message) = $this->_confirm_deletion;

			if ($this->url->ajax())
			{
				return '<div class="modal-header">
							<h5 class="modal-title">'.$title.'</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="'.NeoFrag()->lang('Fermer').'"></button>
						</div>
						<div class="modal-body">
							'.$message.'
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'.NeoFrag()->lang('Annuler').'</button>
							<a class="btn btn-danger delete-confirm" href="'.url($this->url->request).'" data-form-id="'.$this->token().'">'.NeoFrag()->lang('Supprimer').'</a>
						</div>';
			}
			else
			{
				//TODO
				/*return 	'<p>'.$message.'</p><p>
							<button type="button" class="btn btn-secondary" onclick="$(this).parents(\'.alert\').alert(\'close\');">Annuler</button>
							<a class="btn btn-danger delete-confirm" href="'.url($this->url->request).'" data-form-id="'.$this->token().'" onclick="return confirm_deletion(this);">Supprimer</a>
						</p>';*/
			}

			return;
		}

		$output = '';

		if ($has_upload = $this->_has_upload())
		{
			$this->js('file')->css('form-file');
		}

		$output .= '<form action="'.url($this->url->request).'" method="post"'.($has_upload ? ' enctype="multipart/form-data"' : '').'>
						<fieldset>';

		$post = post($this->token());

		foreach ($this->_rules as $var => $options)
		{
			if (!is_array($options) || !isset($options['type']) || !in_array($type = $options['type'], self::$types))
			{
				$type = 'text';
			}

			if ($display = $this->{'_display_'.$type}($var, $options, isset($post[$var]) ? $post[$var] : NULL))
			{
				if ($type == 'legend')
				{
					$output .= $display;
				}
				else
				{
					$output .= '<div class="nf-field row'.(isset($this->_errors[$var]) ? ' nf-field-invalid' : '').'">';

					if ($this->_fast_mode)
					{
						$output .= '<div class="col">'.$display.'</div>';
					}
					else
					{
						$output .= '<label class="col-sm-3 col-form-label col-form-label-sm"'.(!in_array($type, ['radio', 'checkbox']) ? ' for="form_'.$this->token().'_'.$var.'"' : '').$this->_display_popover($var, $options, $icons).'>'.$icons.' '.(!empty($options['label']) ? $options['label'] : '');

						if (isset($options['rules']) && in_array('required', $options['rules']) && $this->_display_required)
						{
							$output .= '<em>*</em>';
						}

						// La taille demandée est reprise TELLE QUELLE si elle est une classe de
						// colonne Bootstrap valide. L'ancienne expression n'acceptait que `col-1` à
						// `col-9` : un `col-md-4` — l'idiome naturel, et responsive — ne matchait pas
						// et retombait silencieusement sur `col-sm-9`, pleine largeur. C'est ce qui
						// faisait dix-sept champs numériques larges de 900 px sur la page du barème
						// de gamification. Les points de rupture et les colonnes 10 à 12 sont donc
						// acceptés, et la classe n'est plus reconstruite : `col-md-4` reste `col-md-4`.
						$taille = !empty($options['size']) && preg_match('/^col-(?:(?:sm|md|lg|xl|xxl)-)?(?:[1-9]|1[0-2])$/', $options['size'])
							? $options['size']
							: 'col-sm-9';

						$output .= '</label><div class="'.$taille.'">'.$display.'</div>';
					}

					$output .= '</div>';
				}
			}
		}

		if ($this->_display_captcha)
		{
			NeoFrag()->js('https://www.google.com/recaptcha/api.js?hl='.$this->config->lang->info()->name.'&_=');
			$output .= '<div class="nf-field row"><div class="'.($this->_fast_mode ? 'input-group' : 'offset-3 col-9').'">'.$this->captcha->display().'</div></div>';
		}

		// La mention n'a de sens que s'il y a VRAIMENT une étoile à l'écran. Elle s'affichait sur
		// tout formulaire, y compris ceux dont aucun champ n'est requis — comme le barème de
		// gamification, dix-sept champs et pas une seule étoile : le lecteur cherche ce qu'elle
		// désigne, et ne trouve rien.
		$un_champ_requis = FALSE;

		foreach ($this->_rules as $options)
		{
			if (!empty($options['rules']) && in_array('required', (array) $options['rules'], TRUE))
			{
				$un_champ_requis = TRUE;
				break;
			}
		}

		if ($this->_display_required && $un_champ_requis)
		{
			$output .= '<div class="nf-field row"><div class="offset-lg-3 col-12 col-lg-9"><em class="text-muted">'.NeoFrag()->lang('* Toutes les informations marquées d\'une étoile sont requises').'</em></div></div>';
		}

		if (!empty($this->_buttons))
		{
			$output .= '<div class="'.($this->_fast_mode ? 'text-center' : 'nf-field row').'">';

			if (!$this->_fast_mode)
			{
				$output .= '<div class="offset-lg-3 col-12 col-lg-9">';
			}

			foreach ($this->_buttons as $i => $button)
			{
				if ($i > 0)
				{
					$output .= ' ';
				}

				$output .= $this->_display_button($button);
			}

			if (!$this->_fast_mode)
			{
				$output .= '</div>';
			}

			$output .= '</div>';
		}

		$output .= '</fieldset>
				</form>';

		$this->save();

		return $output;
	}

	public function save()
	{
		static::$_form = NULL;
		return $this;
	}

	public function set_id($id)
	{
		$this->id = $id;
		return $this;
	}

	private function _display_button($button)
	{
		$icone = !empty($button['icon']) ? icon($button['icon']).' ' : '';

		if (isset($button['type']) && $button['type'] == 'submit')
		{
			return '<button class="btn btn-primary" type="submit">'.$icone.$button['label'].'</button>';
		}
		else if (!empty($button['label']) && !empty($button['action']))
		{
			return '<a href="'.url($button['action']).'" class="btn btn-secondary">'.$icone.$button['label'].'</a>';
		}

		return '';
	}

	private function _display_value($var, $options)
	{
		$post = post();

		if (isset($post[$this->token()][$var]))
		{
			if (is_array($post[$this->token()][$var]))
			{
				return array_values(array_filter($post[$this->token()][$var]));
			}
			else
			{
				return utf8_htmlentities(trim($post[$this->token()][$var]));
			}
		}
		else if (isset($options['checked']))
		{
			return array_keys(array_filter($options['checked']));
		}
		else if (isset($options['value']))
		{
			// Un champ à valeurs MULTIPLES (cases à cocher, liste à choix multiple) porte un tableau
			// ici. Le convertir en chaîne posait « Array to string conversion » à chaque rendu du
			// formulaire concerné, et produisait le texte « Array » dans l'attribut. On rend la
			// première valeur, qui est ce que l'appelant attend d'un champ simple.
			return is_array($options['value'])
				? (string) (reset($options['value']) ?: '')
				: (string) $options['value'];
		}

		return isset($options['default'])
			? (is_array($options['default']) ? (string) (reset($options['default']) ?: '') : (string) $options['default'])
			: '';
	}

	/**
	 * Une valeur posée dans un attribut ou dans une zone de texte.
	 *
	 * La valeur passait par `addcslashes(…, '"')`, un échappement de CHAÎNE PHP sans aucun effet en
	 * HTML : le guillemet devenait `\"` et fermait tout de même l'attribut. Une citation dont la source
	 * valait `"><img src=x>` injectait donc une balise dans le formulaire d'édition — vu par
	 * check-mise-en-page, qui y a trouvé une « image cassée » (2026-09-23).
	 *
	 * Les valeurs enregistrées PAR CE FORMULAIRE sont déjà encodées à l'entrée (is_valid) ; celles qui
	 * arrivent par un autre chemin (import, API, SQL de démonstration) ne le sont pas. D'où
	 * `double_encode = FALSE` : l'entité existante reste telle quelle — le rendu ne change pas — et
	 * seul le caractère brut est encodé.
	 */
	static private function _attr($value): string
	{
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8', FALSE);
	}

	private function _display_popover($var, $options, &$icons = '')
	{
		$popover = $icons = [];

		if (!empty($options['description']))
		{
			$popover[] = ($icons[] = '<span class="text-info">'.icon('fas fa-info-circle').'</span>').' '.$options['description'];
		}

		if (!empty($this->_errors[$var]))
		{
			$popover[] = ($icons[] = '<span class="text-danger">'.icon('fas fa-exclamation-triangle').'</span>').' <span class="text-danger">'.$this->_errors[$var].'</span>';
		}

		$icons = implode(' ', $icons);

		if ($popover)
		{
			return ' data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="right" data-bs-html="true" data-bs-content="'.utf8_htmlentities(implode('<br /><br />', $popover)).'"';
		}
	}

	private function _display_text($var, $options, $post, $type = 'text')
	{
		$classes = [];

		if (in_array($type, ['date', 'datetime', 'time']))
		{
			$lang    = $this->config->lang->info()->name;
			$formats = $this->config->lang->date();
			$format  = $type === 'datetime' ? $formats['short_date_time'] : ($type === 'time' ? $formats['short_time'] : $formats['short_date']);

			NeoFrag()->css('flatpickr.min')->js('flatpickr.min');

			if ($lang !== 'en')
			{
				NeoFrag()->js('flatpickr/l10n/'.$lang);
			}

			NeoFrag()->js_load('flatpickr(".input-group.'.$type.' input", {'
				.'dateFormat: "'.$format.'", '
				.($lang !== 'en' ? 'locale: "'.$lang.'", ' : '')
				.'enableTime: '.($type !== 'date' ? 'true' : 'false').', '
				.'noCalendar: '.($type === 'time' ? 'true' : 'false').', '
				.'time_24hr: true, allowInput: true});');

			$classes[] = $type;

			if (empty($options['icon']))
			{
				$options['icon'] = $type == 'time' ? 'far fa-clock' : 'fas fa-calendar-alt';
			}

			$type = 'text';
		}
		else if ($type == 'email')
		{
			$type = 'text';

			if (empty($options['icon']))
			{
				$options['icon'] = 'far fa-envelope';
			}
		}
		else if ($type == 'url')
		{
			$type = 'text';

			if (empty($options['icon']))
			{
				$options['icon'] = 'fas fa-globe';
			}
		}
		else if ($type == 'phone')
		{
			$type = 'text';

			if (empty($options['icon']))
			{
				$options['icon'] = 'fas fa-phone';
			}
		}
		else if ($type == 'colorpicker')
		{
			$type = 'text';

			$classes[] = 'color';

			$options['icon'] = FALSE;

			NeoFrag()->js('colorpicker');
		}

		$output = '';

		// Balisage Bootstrap 5 : l'addon est un ENFANT DIRECT du groupe. Les wrappers
		// `input-group-prepend` / `input-group-append` de Bootstrap 4 étaient encore émis ici et
		// rattrapés en CSS dans chaque thème ; le rattrapage ne suffisait pas — chaque partie
		// gardait ses quatre coins arrondis, si bien que l'icône, le champ et la pipette
		// formaient trois boîtes accolées au lieu d'un seul champ. Bootstrap 5 s'en charge tout
		// seul dès que le wrapper disparaît, sans aucune règle de rattrapage.
		if (isset($options['icon']))
		{
			$output .= '<div class="input-group'.(!empty($classes) ? ' '.implode(' ', $classes) : '').'">
				<span class="input-group-text">'.($options['icon'] ? icon($options['icon']) : '<i class="nf-color-swatch"></i>').'</span>';
		}

		$placeholder = '';

		// Le champ de fichier était le SEUL champ sans classe : le navigateur dessinait alors son
		// widget natif gris, sans bordure ni rayon, au milieu de champs habillés par le thème.
		// Bootstrap habille `input[type=file].form-control` comme les autres.
		$class = ' class="form-control"';

		if ($type != 'file')
		{
			$value = ' value="'.self::_attr($this->_display_value($var, $options)).'"';

			if (!empty($options['placeholder']))
			{
				$placeholder = $options['placeholder'];
			}
			else if ($this->_fast_mode && !empty($options['label']))
			{
				$placeholder = $options['label'];
			}

			if ($placeholder)
			{
				$placeholder = ' placeholder="'.self::_attr($placeholder).'"';
			}
		}

		$input = '<input id="form_'.$this->token().'_'.$var.'" name="'.$this->token().'['.$var.']" type="'.$type.'"'.(!empty($value) ? $value : '').$class.($type == 'password' && isset($options['autocomplete']) && $options['autocomplete'] === FALSE ? ' autocomplete="off"' : '').(!empty($options['rules']) && in_array('disabled', $options['rules']) ? ' disabled="disabled"' : '').$placeholder.' />';

		if ($type == 'file')
		{
			$post = post();

			$input = '<div style="margin: 7px 0;"><p>'.icon('fas fa-download').' '.NeoFrag()->lang('Télécharger un fichier').(!empty($options['info']) ? $options['info'] : '').'</p>'.$input.'</div>';

			if (!empty($options['value']))
			{
				if (isset($post[$this->token()][$var]) && $post[$this->token()][$var] == 'delete')
				{
					$input = '<input type="hidden" name="'.$this->token().'['.$var.']" value="delete" />'.$input;
				}
				else
				{
					$input = '	<div class="row">
									<div class="col-12 col-lg-3">
										<div class="nf-file-preview card card-body p-2">
											<img src="'.url($this->db->select('path')->from('nf_file')->where('id', $options['value'])->row()).'" class="img-fluid mb-1" alt="" />
											<div class="text-center">
												<a class="btn btn-outline-danger d-block w-100 btn-sm form-file-delete" href="#" data-input="'.$this->token().'['.$var.']">'.icon('far fa-trash-alt').' '.NeoFrag()->lang('Supprimer').'</a>
											</div>
										</div>
									</div>
									<div class="col-12 col-lg-9">
										'.$input.'
									</div>
								</div>';
				}
			}
		}

		$output .= $input;

		if (isset($options['icon']))
		{
			if (in_array('color', $classes))
			{
				$output .= '<span class="input-group-text nf-color-toggle"><span class="fas fa-eye-dropper"></span></span>';
			}

			$output .= '</div>';
		}

		return $output;
	}

	private function _display_iconpicker($var, $options, $post)
	{
		NeoFrag()->js('iconpicker');

		return '<button id="form_'.$this->token().'_'.$var.'" name="'.$this->token().'['.$var.']" class="btn btn-light'.((isset($this->_errors[$var])) ? ' btn-danger' : '').' iconpicker" data-icon="'.self::_attr($this->_display_value($var, $options)).'"></button>';
	}

	private function _display_colorpicker($var, $options, $post)
	{
		return $this->_display_text($var, $options, $post, 'colorpicker');
	}

	private function _display_password($var, $options, $post)
	{
		return $this->_display_text($var, $options, $post, 'password');
	}

	private function _display_date($var, $options, $post)
	{
		if (isset($options['value']) && $options['value'] !== '')
		{
			$options['value'] = timetostr(NeoFrag()->lang('d/m/Y'), $options['value']);
		}
		else
		{
			$options['value'] = '';
		}

		return $this->_display_text($var, $options, $post, 'date');
	}

	private function _display_datetime($var, $options, $post)
	{
		if (isset($options['value']) && $options['value'] !== '')
		{
			$options['value'] = timetostr(NeoFrag()->lang('d/m/Y H:i'), $options['value']);
		}
		else
		{
			$options['value'] = '';
		}

		return $this->_display_text($var, $options, $post, 'datetime');
	}

	private function _display_time($var, $options, $post)
	{
		if (isset($options['value']) && $options['value'] !== '' && $options['value'] !== '00:00:00')
		{
			$options['value'] = timetostr(NeoFrag()->lang('H:i'), $options['value']);
		}
		else
		{
			$options['value'] = '';
		}

		return $this->_display_text($var, $options, $post, 'time');
	}

	private function _display_number($var, $options, $post)
	{
		return $this->_display_text($var, $options, $post, 'number');
	}

	private function _display_phone($var, $options, $post)
	{
		return $this->_display_text($var, $options, $post, 'phone');
	}

	private function _display_email($var, $options, $post)
	{
		return $this->_display_text($var, $options, $post, 'email');
	}

	private function _display_url($var, $options, $post)
	{
		return $this->_display_text($var, $options, $post, 'url');
	}

	private function _display_file($var, $options, $post)
	{
		return $this->_display_text($var, $options, $post, 'file');
	}

	private function _display_tags($var, $options, $post)
	{
		return $this->_display_text($var, $options, $post, 'tags');
	}

	private function _display_checkbox($var, $options, $post)
	{
		$output = '<input type="hidden" name="'.$this->token().'['.$var.'][]" value="" />';

		if (!empty($options['values']))
		{
			$user_value = (array)$this->_display_value($var, $options);

			$i = 0;

			// Les cases sont posées dans une GRILLE qui se remplit sur toute la largeur
			// disponible. Empilées une par ligne, un groupe un peu fourni devient une colonne
			// interminable : la page des statistiques en aligne vingt-deux, soit huit cents
			// pixels de haut pour une liste qui tient en trois colonnes, et toute la largeur de
			// l'écran reste vide à côté.
			$output .= '<div class="nf-check-grid">';

			foreach ($options['values'] as $value => $label)
			{
				$id = 'form_'.$this->token().'_'.$var.'_'.($i++);

				 $output .= '	<div class="form-check">
									<input class="form-check-input" type="checkbox" id="'.$id.'" name="'.$this->token().'['.$var.'][]" value="'.self::_attr($value).'"'.(in_array((string)$value, $user_value) ? ' checked="checked"' : '').' />
									<label class="form-check-label" for="'.$id.'">'.$label.'</label>
								</div>';
			}

			$output .= '</div>';
		}

		return $output;
	}

	private function _display_radio($var, $options, $post)
	{
		$output = '<input type="hidden" name="'.$this->token().'['.$var.']" value="" />';

		if (!empty($options['values']))
		{
			$user_value = $this->_display_value($var, $options);

			$i = 0;

			// `radio-inline` et `checkbox` sont des classes de Bootstrap 3 : elles ne sont définies
			// NULLE PART dans le projet, qui est en Bootstrap 5. Les boutons radio sortaient donc
			// sans aucun style — collés les uns aux autres, sans espace ni alignement.
			foreach ($options['values'] as $value => $label)
			{
				$id = 'form_'.$this->token().'_'.$var.'_'.($i++);

				 $output .= '	<div class="form-check form-check-inline">
									<input class="form-check-input" type="radio" id="'.$id.'" name="'.$this->token().'['.$var.']" value="'.self::_attr($value).'"'.($user_value == (string)$value ? ' checked="checked"' : '').' />
									<label class="form-check-label" for="'.$id.'">'.$label.'</label>
								</div>';
			}
		}

		return $output;
	}

	private function _display_select($var, $options, $post)
	{
		if (empty($options['values']) && (!isset($options['rules']) || !in_array('required', $options['rules'])))
		{
			return;
		}

		$output = '<select class="form-select" id="form_'.$this->token().'_'.$var.'" name="'.$this->token().'['.$var.']">
						<option></option>';

		if (!empty($options['values']))
		{
			$user_value = $this->_display_value($var, $options);

			foreach ($options['values'] as $value => $label)
			{
				$output .= '<option value="'.self::_attr($value).'"'.($user_value == (string)$value ? ' selected="selected"' : '').'>'.$label.'</option>';
			}
		}

		return $output.'</select>';
	}

	private function _display_textarea($var, $options, $post, $editor = FALSE)
	{
		return '<textarea id="form_'.$this->token().'_'.$var.'" class="form-control'.($editor ? ' editor' : '').'" rows="10" name="'.$this->token().'['.$var.']">'.self::_attr($this->_display_value($var, $options)).'</textarea>';
	}

	private function _display_editor($var, $options, $post)
	{
		// Migration WysiBB → TinyMCE 7 (GPL via CDN). Idempotent : si TinyMCE déjà chargé/initialisé sur la page,
		// le bloc inline ci-dessous ne re-charge ni ne re-init la textarea concernée.
		$this->js_load(
			'(function(){'.
				'if (window.__nf_tinymce_loaded) { __nf_tinymce_attach(); return; }'.
				'window.__nf_tinymce_loaded = true;'.
				'var s = document.createElement("script");'.
				's.src = "'.js('tinymce/tinymce.min.js').'";'.
				's.onload = __nf_tinymce_attach;'.
				'document.head.appendChild(s);'.
				'function __nf_tinymce_attach(){'.
					'if (typeof tinymce === "undefined") { setTimeout(__nf_tinymce_attach, 80); return; }'.
					'tinymce.init({'.
						'selector: "textarea.editor:not(.mce-attached)",'.
							'skin: (document.documentElement.getAttribute("data-theme") === "dark") ? "oxide-dark" : "oxide",'.
							'content_css: (document.documentElement.getAttribute("data-theme") === "dark") ? "dark" : "default",'.
						'height: 360,'.
						'menubar: false,'.
						'branding: false,'.
						'promotion: false,'.
						'license_key: "gpl",'.
						'plugins: "advlist autolink lists link image charmap preview anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media table emoticons codesample help",'.
						'toolbar: "undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table codesample | emoticons charmap | searchreplace fullscreen | removeformat",'.
						'content_style: "body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;font-size:14px;}",'.
						'image_advtab: true,'.
						'link_default_target: "_blank",'.
						'link_assume_external_targets: true,'.
						'init_instance_callback: function(ed){ ed.getElement().classList.add("mce-attached"); }'.
					'});'.
					'window.__nf_tinymce_attach = __nf_tinymce_attach;'.
				'}'.
			'})();'
		);

		return $this->_display_textarea($var, $options, $post, TRUE);
	}

	private function _display_legend($var, $options, $post)
	{
		return '<legend>'.(!empty($options['label']) ? $options['label'] : '').'</legend>';
	}

	private function _has_upload()
	{
		foreach ($this->_rules as $var => $options)
		{
			if (isset($options['type']) && $options['type'] == 'file')
			{
				return TRUE;
			}
		}

		return FALSE;
	}
}
