<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries;

/**
 * @method $this icon_if(mixed $condition, mixed ...$args) icon() sous condition (suffixe _if, NeoFrag::__call)
 */
class Label extends Html
{
	protected $_title = '';
	protected $_icon;
	protected $_url;
	protected $_tooltip;
	protected $_popover;
	protected $_color;

	public function __invoke()
	{
		$args = func_get_args();

		$this->_tag = 'span';

		if (func_num_args())
		{
			$this->_title = $args[0];

			if (isset($args[1]))
			{
				$this->_icon = $args[1];

				if (isset($args[2]))
				{
					$this->_color = $args[2];

					if (isset($args[3]))
					{
						$this->_url = $args[3];
					}
				}
			}
		}

		$this->_template[] = function(&$content, &$attrs, &$tag){
			$output = [];

			if ($this->_icon)
			{
				$output[] = icon($this->_icon);
			}

			if ($this->_title)
			{
				$output[] = $this->lang($this->_title);
			}

			$content = implode(' ', $output);

			if ($this->_url !== NULL)
			{
				$attrs['href'] = url($this->_url);
				$tag           = 'a';
			}

			if ($this->_tooltip)
			{
				$attrs['data-bs-toggle'] = 'tooltip';
				$attrs['data-bs-html']   = 'true';
				$attrs['title']       = $this->lang($this->_tooltip);
			}
			else if ($this->_popover)
			{
				$attrs['data-bs-toggle']  = 'popover';
				$attrs['data-bs-html']    = 'true';
				$attrs['title']        = $this->lang($this->_popover[1]);
				$attrs['data-bs-content'] = $this->lang($this->_popover[0]);
			}

			if ($color = $this->_color)
			{
				$attrs['class'] = 'badge';

				if (preg_match('/#([0-9A-F]{3}){1,2}/i', $color))
				{
					if (!isset($attrs['style']))
					{
						$attrs['style'] = '';
					}

					// Le texte suit le fond : blanc d'office, il tombait à 3,8:1 sur le rouge d'un type
					// d'événement « Tournoi » (check-mise-en-page, 2026-09-23).
					$attrs['style'] .= ';background-color: '.$color.';color: '.couleur_lisible_sur($color);

					$attrs['style'] = ltrim($attrs['style'], ';');
				}
				// Tout autre nom passe par badge_class(), qui connaît les couleurs de Bootstrap et
				// ramène les autres à une pastille lisible. Un nom inconnu n'ajoutait AUCUNE classe :
				// la pastille gardait le texte blanc de `.badge`, sans fond — invisible sur une carte.
				else
				{
					$attrs['class'] .= ' '.badge_class($color);
				}
			}
		};

		return $this;
	}

	public function __toString(): string
	{
		$output = parent::__toString();
		return $output != '<span></span>' ? $output : '';
	}

	public function url($url = '')
	{
		if (func_num_args())
		{
			$this->_url = $url;
			return $this;
		}
		else
		{
			return url($this->_url);
		}
	}

	public function title($title = '')
	{
		if (func_num_args())
		{
			$this->_title = $title;
			return $this;
		}
		else
		{
			return $this->_title;
		}
	}

	public function tooltip($title)
	{
		$this->_tooltip = $title;
		return $this;
	}

	public function popover($content, $title = '')
	{
		$this->_popover = [$content, $title];
		return $this;
	}

	public function popover_ajax($url)
	{
		$this->js('popover');

		return $this->url('#')
					->data([
						'popover-ajax' => url($url)
					]);
	}

	public function icon($icon = '')
	{
		if (func_num_args())
		{
			$this->_icon = $icon;
			return $this;
		}
		else if (!is_empty($this->_icon))
		{
			return icon($this->_icon);
		}
	}

	public function color($color)
	{
		$this->_color = $color;
		return $this;
	}
}
