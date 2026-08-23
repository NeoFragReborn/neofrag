<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Monitoring\Models;

use NF\NeoFrag\Loadables\Model;

class Monitoring extends Model
{
	public $folders = ['addons', 'backups', 'cache', 'config', 'css', 'fonts', 'images', 'js', 'lib', 'logs', 'modules', 'neofrag', 'overrides', 'themes', 'upload', 'widgets'];

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
