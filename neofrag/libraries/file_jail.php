<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Garde-fous filesystem du gestionnaire de fichiers webmaster (Monitoring). Classe PURE, sans
 * dépendance au framework : la logique de sécurité critique (sortie de jail, zone protégée) est
 * ainsi testable isolément. Aucune méthode ici n'écrit ni ne lit le disque hors realpath().
 */

namespace NF\NeoFrag\Libraries;

class File_Jail
{
	/**
	 * Résout $rel SOUS $root et renvoie le chemin absolu réel, ou NULL si ça s'échappe du jail
	 * (segment `..`, chemin absolu, lien symbolique sortant). Gère les fichiers inexistants (création)
	 * en validant le dossier parent.
	 */
	public static function resolve(string $root, string $rel): ?string
	{
		$root = self::_real_root($root);

		if ($root === NULL)
		{
			return NULL;
		}

		$rel = ltrim(str_replace('\\', '/', $rel), '/');
		$abs = $root.'/'.$rel;

		if (($real = realpath($abs)) !== FALSE)
		{
			$real = str_replace('\\', '/', $real);
		}
		else
		{
			// Cible inexistante (nouveau fichier/dossier) : le PARENT doit exister et être dans le jail.
			$parent = realpath(dirname($abs));

			if ($parent === FALSE)
			{
				return NULL;
			}

			$real = str_replace('\\', '/', $parent).'/'.basename($abs);
		}

		return self::_contains($root, $real) ? $real : NULL;
	}

	/**
	 * Vrai si $abs tombe dans une zone protégée (refus lecture ET écriture). $protected = chemins
	 * relatifs au root (ex. ['config', 'logs', 'backups']).
	 */
	public static function is_protected(string $root, string $abs, array $protected): bool
	{
		$root = self::_real_root($root);

		if ($root === NULL)
		{
			return TRUE; // root introuvable => on refuse par défaut
		}

		$abs = str_replace('\\', '/', $abs);
		$rel = ltrim(substr($abs, strlen($root)), '/');

		foreach ($protected as $p)
		{
			$p = trim(str_replace('\\', '/', $p), '/');

			if ($p !== '' && ($rel === $p || strpos($rel, $p.'/') === 0))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/** Heuristique binaire : un octet nul dans les premiers 8 Ko => pas d'éditeur texte. */
	public static function is_binary(string $content): bool
	{
		return strpos(substr($content, 0, 8000), "\0") !== FALSE;
	}

	/** Mode CodeMirror déduit de l'extension (htmlmixed/php/css/js/sql/markdown…). */
	public static function editor_mode(string $filename): string
	{
		if (preg_match('/\.tpl\.php$/i', $filename) || preg_match('/\.php$/i', $filename))
		{
			return 'application/x-httpd-php';
		}

		static $map = [
			'js'    => 'javascript', 'mjs'  => 'javascript', 'json' => 'application/json',
			'css'   => 'css',        'scss' => 'css',        'less' => 'css',
			'html'  => 'htmlmixed',  'htm'  => 'htmlmixed',  'tpl'  => 'htmlmixed',
			'xml'   => 'xml',        'svg'  => 'xml',
			'sql'   => 'sql',        'md'   => 'markdown',   'markdown' => 'markdown',
			'sh'    => 'shell',      'yml'  => 'yaml',       'yaml' => 'yaml',
			'ini'   => 'properties', 'env'  => 'properties', 'conf' => 'properties',
		];

		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

		return $map[$ext] ?? 'text/plain';
	}

	private static function _real_root(string $root): ?string
	{
		$real = realpath($root);

		return $real === FALSE ? NULL : rtrim(str_replace('\\', '/', $real), '/');
	}

	/** $abs est-il égal à $root ou strictement dessous ? */
	private static function _contains(string $root, string $abs): bool
	{
		$abs = rtrim($abs, '/');

		return $abs === $root || strpos($abs.'/', $root.'/') === 0;
	}
}
