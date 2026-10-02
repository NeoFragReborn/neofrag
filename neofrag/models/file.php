<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Models;

use NF\NeoFrag\Loadables\Model2;

class File extends Model2
{
	static public function __schema()
	{
		return [
			'id'   => self::field()->primary(),
			'user' => self::field()->depends('user/user')->default(NeoFrag()->user)->null(),
			'name' => self::field()->text(100),
			'path' => self::field()->text(100),
			'date' => self::field()->datetime()
		];
	}

	static public function filename($dir, $extension)
	{
		dir_create($dir = 'upload/'.($dir ?: 'unknow'));

		do
		{
			$file = unique_id().'.'.$extension;
		}
		while (check_file($filename = $dir.'/'.$file));

		return $filename;
	}

	static public function add($path, $name)
	{
		return NeoFrag()->model2('file')
						->set('name', $name)
						->set('path', $path)
						->create();
	}

	static public function uploaded_file($files, $dir = NULL, $file_id = NULL, $var = NULL)
	{
		// $var peut être l'index 0 d'un upload multiple ($_FILES[...]['name'][0]) : tester !== NULL plutôt
		// que la véracité, sinon 0 (falsy) fait prendre le tableau entier au lieu de l'élément
		// (→ basename(array) → TypeError). Identique pour NULL / nom de champ / index >= 1.
		$orig_name = $var !== NULL ? $files['name'][$var] : $files['name'];

		// Site de démonstration : aucun fichier n'est accepté. La remise à zéro horaire recharge la
		// BASE ; elle ne touche pas au disque. Un fichier déposé par un visiteur resterait donc sur
		// le serveur indéfiniment — avec tout ce que ça suppose si le fichier est illicite.
		if (nf_demo())
		{
			return FALSE;
		}

		// Garde central : refuse les extensions exécutables/dangereuses quel que soit le
		// type MIME annoncé par le client (défense en profondeur, cf. upload/.htaccess).
		if (is_dangerous_upload(basename($orig_name)))
		{
			return FALSE;
		}

		$filename = static::filename($dir, extension(basename($orig_name)));

		if (move_uploaded_file($var !== NULL ? $files['tmp_name'][$var] : $files['tmp_name'], $filename))
		{
			if (($file = NeoFrag()->model2('file', $file_id)) && $file->id)
			{
				@unlink($file->path);

				return $file->set('user', NeoFrag()->user)
							->set('name', $var !== NULL ? $files['name'][$var] : $files['name'])
							->set('path', $filename)
							->update();
			}
			else
			{
				return static::add($filename, $var !== NULL ? $files['name'][$var] : $files['name']);
			}
		}

		return FALSE;
	}

	static public function save_file($content, $file, $dir = NULL)
	{
		file_put_contents($filename = static::filename($dir, extension($file)), $content);
		return static::add($filename, $file);
	}

	public function path()
	{
		if ($this->path)
		{
			return url($this->path);
		}
	}

	/**
	 * La balise <img> du fichier, ou RIEN quand il n'y a pas de fichier.
	 *
	 * `'<img src="'.$fichier->path().'" …'` écrit sans garde donnait `src=""` pour une catégorie ou
	 * un jeu sans icône : le navigateur recharge alors la PAGE elle-même comme image, et dessine une
	 * image cassée. check-mise-en-page en a trouvé dans cinq listes de l'administration (2026-09-23).
	 */
	public function img(string $attributs = 'alt=""'): string
	{
		return ($chemin = $this->path()) ? '<img src="'.$chemin.'" '.$attributs.' />' : '';
	}

	public function delete()
	{
		// Sur une démonstration, rien n'est supprimé, ni le fichier ni sa ligne : la remise à zéro ne
		// restaure ni le disque ni `nf_file`, et une image supprimée par un visiteur restait cassée pour
		// tous (galeries, icônes, logos, pièces jointes par cascade — audit du 2026-10-02). Aucun fichier
		// n'y entre (uploaded_file() refuse) : en garder ne fait rien grossir.
		if (nf_demo())
		{
			return TRUE;
		}

		@unlink($this->path);

		return parent::delete();
	}
}
