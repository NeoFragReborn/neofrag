<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries\Forms;

class Image extends File
{
	protected $_mimes = [
		'image/png',
		'image/jpeg',
		'image/gif'
	];
	protected $_width;
	protected $_height;
	protected $_aspect; // 'square' = carré imposé | 'ratio' = proportion imposée | NULL = forme libre (boîte max)
	protected $_default;

	// Garde anti-« décompression bomb » : une source démesurée saturerait la RAM au moment où GD la
	// décode (imagecreatefrom* alloue ~largeur×hauteur×4 octets) avant même d'être réduite. On la
	// refuse en amont. 4000px/côté ≈ 16 Mpx ≈ 64 Mo en truecolor : large pour une photo, mais borné.
	const MAX_SOURCE_SIDE = 4000;

	public function __invoke($name, $upload_dir = '')
	{
		$this->_precheck = function($file){
			if (!($info = @getimagesize($file)))
			{
				$this->_errors[] = $this->lang('Fichier image invalide.');
				return;
			}

			list($width, $height) = $info;

			if (($this->_width || $this->_height) && ($width > self::MAX_SOURCE_SIDE || $height > self::MAX_SOURCE_SIDE))
			{
				$this->_errors[] = $this->lang('Image trop grande : %dpx maximum par côté.', self::MAX_SOURCE_SIDE);
			}
			else if ($this->_aspect === 'square')
			{
				// Avatar & co. : n'importe quelle taille TANT QUE l'image est carrée (réduite ensuite).
				if ($width !== $height)
				{
					$this->_errors[] = $this->lang('L\'image doit être carrée (largeur = hauteur).');
				}
			}
			else if ($this->_aspect === 'ratio')
			{
				// Couverture & co. : ratio fixe imposé, n'importe quelle taille (réduite ensuite).
				if ($width * $this->_height !== $height * $this->_width)
				{
					$this->_errors[] = $this->lang('L\'image doit respecter le ratio %d:%d.', $this->_width, $this->_height);
				}
			}
			// _aspect NULL (boîte max) : aucune contrainte de forme, juste la garde anti-bombe ci-dessus.
		};

		// Après upload : ré-encodage + réduction (image_normalize). Le ré-encodage GD purge tout payload
		// caché (polyglotte, EXIF piégé, code ajouté) et la réduction borne le stockage. Sans GD, no-op :
		// on reste protégé par magic-bytes + extension + en-têtes de service (nosniff).
		$this->_uploaded = function($file){
			if ($this->_width && $this->_height && $file->path)
			{
				image_normalize($file->path, $this->_width, $this->_height);
			}
		};

		$this->_thumbnail = function(){
			$hint = '';

			if ($this->_aspect === 'square')
			{
				$hint = $this->lang('Image carrée (réduite à %dpx)', $this->_width);
			}
			else if ($this->_aspect === 'ratio')
			{
				$hint = $this->lang('Ratio %d:%d (réduite à %d×%dpx)', $this->_width, $this->_height, $this->_width, $this->_height);
			}
			else if ($this->_width && $this->_height)
			{
				$hint = $this->lang('Réduite à %d×%dpx max', $this->_width, $this->_height);
			}

			return $this->html()
						->attr('class', 'text-center')
						->append_if($path = ($this->_value ? $this->_value->path() : $this->_default), '<img class="img-thumbnail" src="'.$path.'" alt="" />')
						->append_if($hint, '<p class="m-4">'.$hint.' <i>('.human_size(file_upload_max_size()).' max.)</i></p>');
		};

		return parent::__invoke($name, $upload_dir);
	}

	public function __call($name, $args)
	{
		//TODO 5.6 compatibility
		if ($name == 'default')
		{
			$this->_default = $args[0];
			return $this;
		}

		return parent::__call($name, $args);
	}

	public function square($size)
	{
		$this->_width  = $this->_height = $size;
		$this->_aspect = 'square';
		return $this;
	}

	public function rectangle($width, $height)
	{
		$this->_width  = $width;
		$this->_height = $height;
		$this->_aspect = 'ratio';
		return $this;
	}

	// Boîte maximale sans contrainte de forme : l'image garde ses proportions et est seulement réduite
	// pour tenir dans width×height, puis ré-encodée. Pour les uploads libres (galerie…).
	public function max($width, $height = NULL)
	{
		$this->_width  = $width;
		$this->_height = $height ?: $width;
		$this->_aspect = NULL;
		return $this;
	}
}
