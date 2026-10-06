<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;

class Js extends Library
{
	protected $_file;

	public function __invoke($file): static
	{
		$this->_file = $file;

		$this->output->data->append('js', $this);

		return $this;
	}

	public function __toString(): string
	{
		return '<script type="text/javascript" src="'.$this->path().'"></script>';
	}

	public function path(): string
	{
		if (is_valid_url($this->_file))
		{
			$path = $this->_file;
		}
		else
		{
			$path = path($this->_file.'.js', 'js', $this->__caller);

			// ?v= = date du fichier ET compteur des réglages : un script de thème peut les lire (nf_version_asset()).
			if (($v = nf_version_asset($this->_file.'.js', 'js', $this->__caller)) !== '')
			{
				$path .= '?v='.$v;
			}
		}

		return $path;
	}
}
