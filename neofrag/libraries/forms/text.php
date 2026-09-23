<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries\Forms;

class Text extends Labelable
{
	protected $_type   = 'text';
	protected $_data   = [];
	protected $_addons = [];
	protected $_iconpicker;

	public function __invoke($name)
	{
		$this->_template[] = function(&$input){
			$input = parent	::html('input', TRUE)
							->attr('class', 'form-control')
							->attr('type',  $this->_type)
							->attr_if($this->_value !== '', 'value', $this->_value)
							->attr_if($this->_disabled,     'disabled')
							->attr_if($this->_read_only,    'readonly')
							->attr_if(is_a($this, 'NF\NeoFrag\Libraries\Forms\Password'), 'autocomplete');

			$this->_placeholder($input);

			if ($this->_data)
			{
				$this	->js('form')
						->js('form_text');

				$encode = function($data){
					if (method_exists($data, '__toArray'))
					{
						$data = $data->__toArray();
					}

					array_walk($data, function(&$value, $key){
						$value = utf8_html_entity_decode($value);
					});

					natsort($data);

					return utf8_htmlentities(json_encode(array_values($data)));
				};

				$input	->append_attr('class', 'autocomplete')
						->attr('data-source', $encode($this->_data));
			}
		};

		parent::__invoke($name);

		$this->_check[] = function($post, &$data){
			if ($this->_iconpicker)
			{
				if (!$this->_iconpicker[0]->check($post, $data))
				{
					$this->_errors = array_merge($this->_errors, $this->_iconpicker[0]->errors());
				}
			}
		};

		$this->_template[] = function(&$input){
			// Le groupe de Bootstrap 5 : les étiquettes sont des ENFANTS DIRECTS de `.input-group`, avant
			// ou après le champ. Bootstrap 4 les enveloppait dans `.input-group-prepend` /
			// `.input-group-append`, que la version 5 a supprimés ; le nom était fabriqué ici
			// (`'input-group-'.$align`), et aucune recherche de classe ne le voyait (2026-09-23).
			$addons = [
				'prepend' => '',
				'append'  => ''
			];

			$add_group = function($addon, $align) use (&$addons){
				if (!in_array($align, ['prepend', 'append']))
				{
					$align = 'prepend';
				}

				$addons[$align] .= '<span class="input-group-text">'.$addon.'</span>';
			};

			if ($this->_iconpicker)
			{
				list($iconpicker, $align) = $this->_iconpicker;

				$iconpicker->disabled_if($this->_disabled || $this->_read_only);

				$add_group($iconpicker, $align);
			}

			foreach ($this->_addons as $addon)
			{
				$add_group($addon, $addon->align());
			}

			// Un groupe seulement s'il y a une étiquette : `if ($addons)` était toujours vrai (deux clés),
			// et CHAQUE champ texte se retrouvait enveloppé dans un groupe vide.
			if ($addons['prepend'] !== '' || $addons['append'] !== '')
			{
				$input = parent	::html()
								->attr('class', 'input-group')
								->content($addons['prepend'].$input.$addons['append']);
			}
		};

		return $this;
	}

	public function data($data)
	{
		$this->_data = $data;
		return $this;
	}

	public function addon($label, $align = 'prepend')
	{
		if (!is_a($label, 'NF\NeoFrag\Libraries\Label'))
		{
			$label = $this	->label()
							->icon($label)
							->align($align);
		}

		$this->_addons[] = $label;
		return $this;
	}

	public function iconpicker($name, $value = '', $required = FALSE, $align = 'left')
	{
		$this->_iconpicker = [parent::form_iconpicker($name)->value($value)->required_if($required), $align];
		return $this;
	}
}
