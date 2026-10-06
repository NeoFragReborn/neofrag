<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;

class Css extends Library
{
	protected $_file;
	protected $_media;

	public function __invoke($file, $media = ''): static
	{
		$this->_file  = $file;
		$this->_media = $media;

		$this->output->data->append('css', $this);

		return $this;
	}

	public function __toString(): string
	{
		if (is_valid_url($this->_file))
		{
			$path = $this->_file;
		}
		else
		{
			$path = path($this->_file.'.css', 'css', $this->__caller);

			// ?v= = date du fichier ET compteur des réglages : une feuille de thème lit ses réglages (nf_version_asset()).
			if (($v = nf_version_asset($this->_file.'.css', 'css', $this->__caller)) !== '')
			{
				$path .= '?v='.$v;
			}
		}

		return $this->html('link', TRUE)
					->attr('rel',  'stylesheet')
					->attr('href', $path)
					->attr('type', 'text/css')
					->attr_if(!is_empty($this->_media), 'media', $this->_media)
					->__toString();
	}
}
