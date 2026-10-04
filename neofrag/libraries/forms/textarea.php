<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries\Forms;

class Textarea extends Labelable
{
	protected $_rows   = 15;
	protected $_editor = FALSE;

	public function __invoke($name)
	{
		$this->_template[] = function(&$input){
			$cls = 'form-control';
			if ($this->_editor)
			{
				$cls .= ' editor';
			}

			$input = parent	::html('textarea')
							->attr('class', $cls)
							->attr('rows', $this->_rows)
							->attr_if($this->_disabled,  'disabled')
							->attr_if($this->_read_only, 'readonly')
							// Encodée, sans double encodage : une valeur brute qui contenait
							// `</textarea>` fermait la zone et injectait la suite dans la page —
							// même famille que form.php (2026-09-23). Le navigateur décode les
							// entités d'une zone de texte : l'éditeur reçoit le même contenu.
							->content(htmlspecialchars((string) $this->_value, ENT_QUOTES, 'UTF-8', FALSE));

			$this->_placeholder($input);

			if ($this->_editor)
			{
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
								// Les images collées ou glissées partent au site (cf. Editeur_Images).
								\NF\NeoFrag\Libraries\Editeur_Images::tinymce().
								'selector: "textarea.editor:not(.mce-attached)",'.
								'height: 320,'.
								'menubar: false,'.
								'branding: false,'.
								'promotion: false,'.
								'license_key: "gpl",'.
								'skin: (document.documentElement.getAttribute("data-theme") === "dark") ? "oxide-dark" : "oxide",'.
								'content_css: (document.documentElement.getAttribute("data-theme") === "dark") ? "dark" : "default",'.
								'plugins: "advlist autolink lists link image charmap preview anchor searchreplace wordcount visualblocks code fullscreen emoticons codesample help",'.
								'toolbar: "undo redo | bold italic underline strikethrough | forecolor | alignleft aligncenter alignright | bullist numlist | link image | emoticons | removeformat | code fullscreen",'.
								'content_style: "body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;font-size:14px;}",'.
								'init_instance_callback: function(ed){ ed.getElement().classList.add("mce-attached"); }'.
							'});'.
							'window.__nf_tinymce_attach = __nf_tinymce_attach;'.
						'}'.
					'})();'
				);
			}
		};

		$field = parent::__invoke($name);

		if ($this->_editor)
		{
			// Contenu HTML riche (TinyMCE) non fiable → sanitize serveur dès le submit (allow-list
			// stricte, anti XSS stocké). Idempotent avec la sanitization au rendu.
			$this->_check[] = function($post, &$data){
				if (isset($data[$this->_name]) && is_string($data[$this->_name]))
				{
					$data[$this->_name] = sanitize_html($data[$this->_name]);
				}
			};
		}

		return $field;
	}

	public function rows($rows)
	{
		$this->_rows = $rows;
		return $this;
	}

	/**
	 * Active l'éditeur riche TinyMCE sur cette textarea. La valeur soumise est assainie au submit
	 * (sanitize_html, anti XSS stocké).
	 */
	public function editor($enable = TRUE)
	{
		$this->_editor = (bool)$enable;
		return $this;
	}
}
