<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Monitoring\Models;

use NF\NeoFrag\Loadables\Model;
use NF\NeoFrag\Installer;

class Monitoring extends Model
{
	public $folders = ['addons', 'backups', 'cache', 'config', 'css', 'fonts', 'images', 'js', 'lib', 'logs', 'modules', 'neofrag', 'overrides', 'themes', 'upload', 'widgets'];

	/**
	 * Remet le site dans l'état d'une sauvegarde : d'abord les fichiers, ensuite la base.
	 *
	 * L'ordre n'est pas indifférent. Fichiers d'abord : si l'import de la base échoue ensuite, le
	 * site tourne au moins avec un code cohérent, capable d'afficher un message lisible. Base
	 * d'abord, c'est l'inverse — du code neuf par-dessus des tables anciennes, c'est-à-dire une page
	 * blanche et aucun moyen de dire pourquoi.
	 *
	 * Le dispositif vit ici, dans le modèle, et non dans l'un des deux contrôleurs qui s'en servent :
	 * le retour arrière automatique d'une mise à jour ratée et le bouton « Restaurer » du panneau
	 * doivent emprunter le MÊME chemin. Deux implémentations, ce serait deux comportements, dont un
	 * seul aurait jamais été éprouvé.
	 *
	 * @param  string        $archive  chemin absolu du .zip de backups/
	 * @param  callable|NULL $progress reçoit ($n, $total) pour l'affichage en flux
	 * @return array{restored:int,removed:int,sql:bool}
	 * @throws \Throwable   la restauration ne masque rien : l'appelant décide quoi en dire
	 */
	public function restaurer(string $archive, ?callable $progress = NULL): array
	{
		require_once NEOFRAG_CMS.'/neofrag/installer.php';

		// Le dump porte TOUTE la base en clair. Il ne sort donc pas de backups/, seul dossier dont
		// l'accès HTTP est refusé, il porte un nom non devinable, et il est effacé quoi qu'il arrive.
		$dump = dirname($archive).'/'.basename($archive, '.zip').'-restauration-'.bin2hex(random_bytes(6)).'.sql';

		try
		{
			$resultat = Installer::restore_backup_package($archive, NEOFRAG_CMS, $dump, $progress);

			$sql = @file_get_contents($dump);

			if ($sql === FALSE)
			{
				throw new \RuntimeException('La copie de la base extraite est illisible.');
			}

			// import() rend TRUE, ou le message d'erreur MySQL. Un `!== TRUE` nu suffirait, mais on
			// veut le message : « la restauration a échoué » sans la raison n'aide personne.
			if (($erreur = $this->db->import($sql)) !== TRUE)
			{
				throw new \RuntimeException('import de la base : '.$erreur);
			}
		}
		finally
		{
			if (file_exists($dump))
			{
				@unlink($dump);
			}
		}

		// Le cache n'est pas restauré (cf. restore_backup_package) : ce qu'il contient a été compilé
		// par la version qu'on vient d'annuler. On le vide pour qu'il se reconstruise proprement.
		$this->vider_cache();

		error_log('[restore] '.$resultat['restored'].' fichier(s) restauré(s), '.$resultat['removed'].' vestige(s) retiré(s), base réimportée depuis '.basename($archive));

		return $resultat;
	}

	/**
	 * Vide cache/ de ses artefacts, en laissant en place ce qui protège le dossier.
	 *
	 * Ni .htaccess ni index.html ne sont des artefacts : ce sont les gardes du dossier. Les emporter
	 * au passage transformerait un cache vidé en cache exposé.
	 */
	public function vider_cache(): int
	{
		$dir = rtrim(NEOFRAG_CMS, '/').'/cache';

		if (!is_dir($dir))
		{
			return 0;
		}

		$n  = 0;
		$it = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($it as $file)
		{
			if ($file->isDir())
			{
				@rmdir($file->getPathname());
			}
			else if (!in_array($file->getFilename(), ['.htaccess', 'index.html', 'index.php'], TRUE) && @unlink($file->getPathname()))
			{
				$n++;
			}
		}

		return $n;
	}

	/**
	 * Liste les backups .zip du dossier backups/, triés du plus récent au plus ancien.
	 * Retourne un array de [name, size_bytes, mtime, age_days].
	 */
	public function get_backups()
	{
		$backups = [];
		$dir = rtrim(NEOFRAG_CMS, '/').'/backups';
		if (!is_dir($dir))
		{
			return $backups;
		}

		foreach (scandir($dir) as $file)
		{
			// Suffixe aléatoire depuis 2026-06 ; l'ancien format date-seule reste listé/purgeable.
			if (preg_match('/^(\d{14})(-[a-f0-9]{16})?\.zip$/', $file, $m))
			{
				$path = $dir.'/'.$file;
				$mtime = filemtime($path) ?: 0;
				$backups[] = [
					'name'      => $file,
					'slug'      => substr($file, 0, -4),
					'size'      => filesize($path) ?: 0,
					'mtime'     => $mtime,
					'date'      => preg_replace('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1-$2-$3 $4:$5:$6', $m[1]),
					'age_days'  => $mtime ? floor((time() - $mtime) / 86400) : 0
				];
			}
		}

		usort($backups, function($a, $b){ return $b['mtime'] - $a['mtime']; });
		return $backups;
	}

	public function get_info()
	{
		return [
			'php_server'       => 'PHP '.PHP_VERSION,
			'web_server'       => preg_match('#(.+?)/(.+?) #', $_SERVER['SERVER_SOFTWARE'], $match) ? $match[1].' '.$match[2] : $_SERVER['SERVER_SOFTWARE'],
			'databases_server' => $this->db->get_info('server').' '.$this->db->get_info('version'),
			'databases_innodb' => $this->db->get_info('innodb')
		];
	}

	public function check_server()
	{
		$server = $this->get_info();

		return [
			[
				'title' => $server['php_server'],
				'icon'  => 'fas fa-server',
				'check' => [
					'php_curl' => [
						'title' => 'cURL',
						'check' => function(&$errors){
							if (!extension_loaded('curl'))
							{
								$errors[] = [$this->lang('L\'extension cURL doit être activée'), 'danger'];
								return FALSE;
							}

							return TRUE;
						}
					],
					'php_gd' => [
						'title' => 'GD',
						'check' => function(&$errors){
							if (!extension_loaded('gd'))
							{
								$errors[] = [$this->lang('L\'extension GD doit être activée'), 'danger'];
								return FALSE;
							}

							return TRUE;
						}
					],
					'php_json' => [
						'title' => 'JSON',
						'check' => function(&$errors){
							if (!extension_loaded('json'))
							{
								$errors[] = [$this->lang('L\'extension JSON doit être activée'), 'danger'];
								return FALSE;
							}

							return TRUE;
						}
					],
					'php_mbstring' => [
						'title' => 'mbstring',
						'check' => function(&$errors){
							if (!extension_loaded('mbstring'))
							{
								$errors[] = [$this->lang('L\'extension mbstring doit être activée'), 'danger'];
								return FALSE;
							}

							return TRUE;
						}
					],
					'php_zip' => [
						'title' => 'Zip',
						'check' => function(&$errors){
							if (!extension_loaded('zip'))
							{
								$errors[] = [$this->lang('L\'extension Zip doit être activée'), 'danger'];
								return FALSE;
							}

							return TRUE;
						}
					]
				]
			],
			[
				'title' => $server['web_server'],
				'icon'  => 'fas fa-globe',
				'check' => [
					'mod_rewrite' => [
						'title' => 'mod_rewrite',
						'check' => function(&$errors){
							if (!1)
							{
								$errors[] = [$this->lang('L\'option de réécriture d\'URL doit être activée'), 'danger'];
								return FALSE;
							}

							return TRUE;
						}
					]
				]
			],
			[
				'title' => $server['databases_server'],
				'icon'  => 'fas fa-database',
				'check' => [
					'innodb' => [
						'title' => 'InnoDB',
						'check' => function(&$errors) use ($server){
							if (!$server['databases_innodb'])
							{
								$errors[] = [$this->lang('Le moteur de stockage InnoDB doit être activé'), 'danger'];
								return FALSE;
							}

							return TRUE;
						}
					]
				]
			],
			[
				'title' => $this->lang('Envoi d\'email'),
				'icon'  => 'far fa-envelope',
				'check' => [
					'email' => [
						'title' => $this->lang('Transport email'),
						'check' => function(&$errors, &$title){
							// PAS d'envoi live ici : tester par un vrai envoi à chaque chargement spammerait
							// (et échoue selon l'hôte : relais externe, From rejeté…) → fausse alerte permanente.
							// On rapporte le transport configuré ; la délivrabilité réelle se teste à la demande.
							if ($this->email->has_smtp())
							{
								$title = 'SMTP';
								return TRUE;
							}

							// Aucun SMTP → fonction mail() de PHP (par défaut, OK sur la plupart des mutualisés).
							$title = $this->lang('mail() PHP');
							return TRUE;
						}
					]
				]
			]
		];
	}
}
