<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Monitoring\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin_Ajax extends Controller_Module
{
	private $_notifications = [];

	public function index($refresh)
	{
		if ($refresh || $this->module->need_checking())
		{
			$this->config('nf_monitoring_last_check', time());

			//https://www.php.net/supported-versions.php
			$current             = 7.4;
			$security_fixes_only = 7.2;
			$last_end_of_life    = 7.1;

			if (version_compare(PHP_VERSION, $last_end_of_life, '<='))
			{
				$this->_notify($this->lang('Cette version de PHP est obsolète, veuillez mettre à jour votre serveur'), 'danger');
			}
			else if (version_compare(PHP_VERSION, $security_fixes_only, '<='))
			{
				$this->_notify($this->lang('Cette version de PHP est sera bientôt obsolète, il est recommandé de mettre à jour votre serveur'), 'warning');
			}
			else if (version_compare(PHP_VERSION, $current, '<'))
			{
				$this->_notify($this->lang('Il est recommandé d\'utiliser PHP %s', $current), 'info');
			}

			if ($this->db->get_info('driver') != 'mysqli')
			{
				$this->_notify($this->lang('Il est recommandé d\'utiliser MySQLi'), 'info');
			}

			dir_create('cache/monitoring');

			// Scan local des fichiers + tentative de récupération du checksum officiel.
			// Le domaine est configurable via la setting `nf_monitoring_check_url`
			// (default: https://neofr.ag, mais peut être ton propre miroir genre https://neofrag.new).
			// Si network échoue, on tombe en mode dégradé : tree local sans comparaison cross-checksum.
			// Source de checksums officiels (réglage nf_monitoring_check_url). VIDE par défaut : la
			// vérification d'intégrité distante est désactivée tant qu'aucun miroir n'est configuré
			// (NeoFrag Reborn ne sert pas encore de checksums) → tree local seul, sans fausse alarme.
			$check_url = rtrim(trim((string)($this->config->nf_monitoring_check_url ?? '')), '/');

			$version  = NULL;
			$checksum = NULL;
			foreach ($check_url !== '' ? ['version', 'checksum'] : [] as $file)
			{
				$url = $check_url.'/'.$file.'.json?v='.version_format(NEOFRAG_VERSION).($this->config->nf_update_beta ? '&beta=1' : '');
				if ($$file = $this->network($url)->type('text')->get())
				{
					file_put_contents('cache/monitoring/'.$file.'.json', $$file);
					$$file = (array)json_decode($$file);
				}
			}

			// Scan local des md5
			$local_files = array_merge(
				dir_scan(array_diff($this->model()->folders, ['backups', 'cache', 'config', 'logs', 'overrides', 'upload']), 'md5_file'),
				['index.php' => md5_file('index.php')]
			);

			if ($checksum)
			{
				// Mode officiel : comparer chaque fichier local au checksum upstream
				foreach ($local_files as $file => $md5)
				{
					if (in_string('/sass/', $file) || in_string('/.git/', $file) || preg_match('_\.css\.map$_', $file))
					{
						continue;
					}
					if (!isset($checksum[$file])) $checksum[$file] = '';
					$checksum[$file] .= '|'.$md5;
				}
				ksort($checksum);

				$tree = [];
				foreach ($checksum as $file => $md5)
				{
					$dirs = explode('/', $file);
					$basename = array_pop($dirs);
					$node = &$tree;
					foreach ($dirs as $dir)
					{
						if (!isset($node[$dir])) $node[$dir] = [];
						$node = &$node[$dir];
					}
					if (!isset($node[$basename])) $node[$basename] = '';
					$node[$basename] .= $md5;
					unset($node);
				}

				$treeview = function($tree, $dir = '') use (&$treeview){
					$output = [];
					foreach ($tree as $name => $node)
					{
						if (is_array($node))
						{
							$tags = [];
							if (file_exists($dir.$name) && !is_writable($dir.$name))
							{
								$tags[] = $this->lang('Protégé en écriture');
								$this->_notify($this->lang('Le dossier <code>%s</code> est protégé en écriture', $dir.$name), 'warning');
							}
							$output[] = ['text' => $name, 'tags' => $tags, 'nodes' => $treeview($node, $dir.$name.'/')];
						}
						else
						{
							$parts = explode('|', $node);
							if (!isset($parts[1])) $parts[] = '';
							list($nf_md5, $md5) = $parts;
							$tags = [];
							if ($nf_md5 === '')
							{
								if (!preg_match('#^(?:modules|themes|widgets)/#', $dir))
								{
									$tags[] = $this->lang('Inconnu');
									$this->_notify($this->lang('Le fichier <code>%s</code> ne devrait pas se trouver là', $dir.$name), 'danger');
								}
							}
							else if ($md5 === '')
							{
								$tags[] = $this->lang('Manquant');
								$this->_notify($this->lang('Le fichier <code>%s</code> est manquant', $dir.$name), 'danger');
							}
							else if ($nf_md5 != $md5)
							{
								$tags[] = $this->lang('Corrompu');
								$this->_notify($this->lang('Le fichier <code>%s</code> est corrompu', $dir.$name), 'warning');
							}
							if ($md5 !== '' && !is_writable($dir.$name))
							{
								$tags[] = $this->lang('Protégé en écriture');
								$this->_notify($this->lang('Le fichier <code>%s</code> est protégé en écriture', $dir.$name), 'warning');
							}
							$output[] = ['text' => $name, 'tags' => $tags];
						}
					}
					return $output;
				};
			}
			else
			{
				// Mode dégradé : pas de checksum officiel. Tree local sans comparaison. On n'alarme
				// QUE si un miroir a été configuré mais est injoignable (sinon = comportement normal).
				if ($check_url !== '')
				{
					$this->_notify($this->lang('Vérification d\'intégrité indisponible : impossible de joindre %s. Le tree affiche seulement les fichiers locaux.', $check_url), 'info');
				}

				$tree = [];
				foreach ($local_files as $file => $md5)
				{
					if (in_string('/sass/', $file) || in_string('/.git/', $file) || preg_match('_\.css\.map$_', $file))
					{
						continue;
					}
					$dirs = explode('/', $file);
					$basename = array_pop($dirs);
					$node = &$tree;
					foreach ($dirs as $dir)
					{
						if (!isset($node[$dir])) $node[$dir] = [];
						$node = &$node[$dir];
					}
					if (!isset($node[$basename])) $node[$basename] = TRUE;
					unset($node);
				}
				ksort($tree);

				$treeview = function($tree, $dir = '') use (&$treeview){
					$output = [];
					foreach ($tree as $name => $node)
					{
						if (is_array($node))
						{
							$tags = [];
							if (file_exists($dir.$name) && !is_writable($dir.$name))
							{
								$tags[] = $this->lang('Protégé en écriture');
							}
							$output[] = ['text' => $name, 'tags' => $tags, 'nodes' => $treeview($node, $dir.$name.'/')];
						}
						else
						{
							$tags = [];
							if (file_exists($dir.$name) && !is_writable($dir.$name))
							{
								$tags[] = $this->lang('Protégé en écriture');
							}
							$output[] = ['text' => $name, 'tags' => $tags];
						}
					}
					return $output;
				};
			}

			$server = [];

			foreach ($this->model()->check_server() as $check)
			{
				foreach ($check['check'] as $name => $check)
				{
					$title = NULL;
					$result = $check['check']($this->_notifications, $title);
					$server[$name] = $title === NULL ? $result : [$result, $title];
				}
			}

			$result = [
				'storage' => [
					'total'    => disk_total_space(NEOFRAG_CMS) ?: 0,
					'free'     => disk_free_space(NEOFRAG_CMS) ?: 0,
					'files'    => array_sum(dir_scan($this->model()->folders, 'filesize')) + filesize('index.php'),
					'database' => $this->db->get_size()
				],
				'server' => $server
			];

			$result['files']         = $treeview($tree);
			$result['notifications'] = $this->_notifications;

			file_put_contents('cache/monitoring/monitoring.json', json_encode($result));
		}
		else
		{
			$result = json_decode(file_get_contents('cache/monitoring/monitoring.json'));
		}

		return $this->json($result);
	}

	public function phpinfo()
	{
		$extensions = get_loaded_extensions();
		natcasesort($extensions);

		$phpinfo = $this->array
						->append($this	->panel()
										->body($this->view('phpinfo', array_merge($this->model()->get_info(), [
											'extensions' => $extensions
										])))
						);

		ob_start();
		phpinfo();

		if (preg_match_all('#(?:<h1>(.*?)</h1>.*?)?(?:<h2>(.*?)</h2>.*?)?<table.*?>(.*?)</table>#s', ob_get_clean(), $matches, PREG_SET_ORDER))
		{
			foreach (array_offset_left($matches) as $match)
			{
				if ($match[1])
				{
					$phpinfo->append($this->panel()->heading($match[1] ? '<h1 class="text-center m-0">'.$match[1].'</h1>' : ''));
				}

				$phpinfo->append($this	->panel()
										->heading($match[2] ? '<h2 class="text-center m-0">'.$match[2].'</h2>' : '')
										->body('<table class="table table-hover table-striped">'.$match[3].'</table>', FALSE));
			}
		}

		return $this->css('phpinfo')
					->modal($this->lang('Informations détaillées'))
					->body($phpinfo)
					->large();
	}

	public function backup()
	{
		if (nf_demo())
		{
			return;
		}

		$this->_stream(function(){
			$this->_backup();
		});
	}

	public function update()
	{
		// Garde-fou fork : l'auto-update télécharge la release upstream officielle
		// (neofrag.download) et la superpose au code — ce qui écraserait les
		// modifications de ce fork divergé (NeoFrag Reborn). Désactivé sauf opt-in explicite.
		if (!defined('NEOFRAG_ALLOW_AUTOUPDATE') || !NEOFRAG_ALLOW_AUTOUPDATE)
		{
			error_log('[monitoring] auto-update bloqué (fork) — NEOFRAG_ALLOW_AUTOUPDATE non défini');
			exit('Auto-update désactivé sur ce fork : il superposerait la release upstream et écraserait le code forké. Pour le réactiver à vos risques, définir NEOFRAG_ALLOW_AUTOUPDATE = TRUE dans config/neofrag.php.');
		}

		if ($version = $this->theme('admin')->update())
		{
			$this->_stream(function() use ($version){
				$this->_backup();

				dir_create('cache/monitoring');

				$this	->network('https://neofrag.download/?v='.version_format($version->version))
						->stream($file = 'cache/monitoring/neofrag.zip', function($size, $total){
							$this->_flush(2, $size / $total * 100);
						});

				$scan_zip = function($callback) use ($file){
					if ($zip = zip_open($file))
					{
						while ($zip_entry = zip_read($zip))
						{
							$entry_name = zip_entry_name($zip_entry);

							if (preg_match('#/|^index.php$#', $entry_name) && (!preg_match('#^(config|install)/#', $entry_name) || !file_exists($entry_name)) && zip_entry_open($zip, $zip_entry, 'r'))
							{
								$callback($zip_entry, $entry_name);
							}

							zip_entry_close($zip_entry);
						}

						zip_close($zip);
					}
				};

				$files = [];

				$scan_zip(function($zip_entry, $entry_name) use (&$files){
					$files[] = $entry_name;
				});

				if ($total = count($files))
				{
					$scan_zip(function($zip_entry, $entry_name) use ($total){
						static $i = 0;
						$this->_flush(3, ++$i / $total * 100);

						if (substr($entry_name, -1) != '/')
						{
							dir_create(preg_replace('#/[^/]+$#', '', $entry_name));
							file_put_contents($entry_name, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));
						}
					});

					unlink($file);

					foreach (array_diff(array_keys(dir_scan('neofrag')), array_filter($files, function($a){
						return preg_match('_^neofrag/_', $a);
					})) as $file)
					{
						unlink($file);
					}

					if (!$this->config->nf_version)
					{
						$this->config('nf_version', version_format(NEOFRAG_VERSION));
					}

					if ($patch = @NeoFrag()->install($patch_name = preg_replace('/[^a-z0-9]/i', '_', $version->version)))
					{
						$patch->up();
					}

					$this->_flush(4, 100);

					$this->module('tools')->api()->scss();

					$this	->config('nf_update_callback',       $patch_name)
							->config('nf_version',               version_format($version->version))
							->config('nf_monitoring_last_check', 0);
				}
			});
		}
	}

	private function _flush($step, $value)
	{
		$value = ceil($value);

		static $i;
		static $n;

		if ($i === NULL || $n != $step || $i != $value)
		{
			if ($i !== NULL)
			{
				echo ';';
			}

			echo json_encode([$n = $step, $i = $value]).PHP_EOL;

			while (ob_get_level())
			{
				@ob_end_flush();
			}
			@flush();
		}
	}

	private function _stream($callback)
	{
		// Désactive tout buffering serveur pour permettre le streaming progressif vers le browser.
		// Sans ça, le JS xhr.progress n'est jamais déclenché et la progress bar reste figée à 0.
		@apache_setenv('no-gzip', 1);
		@ini_set('zlib.output_compression', 0);
		@ini_set('output_buffering', 'off');
		@ini_set('implicit_flush', 1);
		while (ob_get_level())
		{
			@ob_end_clean();
		}
		ob_implicit_flush(1);

		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('X-Accel-Buffering: no');     // nginx : disable buffering
		header('Content-Encoding: identity'); // disable gzip
		header('Connection: close');

		set_time_limit(0);

		try
		{
			$callback();
		}
		catch (\Throwable $e)
		{
			// Émet l'erreur dans le stream pour que le JS puisse la voir
			echo PHP_EOL.';'.json_encode([99, 'ERROR: '.$e->getMessage()]).PHP_EOL;
			error_log('[monitoring backup] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
		}

		exit;
	}

	private function _backup()
	{
		dir_create('backups');

		while (file_exists(($file = 'backups/'.date('YmdHis')).'.zip') || file_exists($dump = $file.'.sql'))
		{
			sleep(1);
		}

		$this->mysqldump->dump(fopen($dump, 'w+b'), function($value){
			$this->_flush(0, $value);
		});

		$zip = new \ZipArchive;
		$zip->open($file.'.zip', \ZipArchive::CREATE);

		$zip->addFile($dump, 'DATABASE.sql');

		$files = array_merge(array_keys(dir_scan(array_diff($this->model()->folders, ['backups']))), ['index.php', '.htaccess']);

		$total = count($files);
		$i     = 0;

		foreach ($files as $file)
		{
			$zip->addFile($file);

			$this->_flush(1, ++$i / $total * 100);
		}

		$zip->close();

		unlink($dump);
	}

	private function _notify($message, $type = 'danger')
	{
		// Cast string : $message peut être un objet Language qui se sérialise en {} via json_encode()
		// Map 'error' → 'danger' (legacy) car 'error' n'est pas une couleur valide dans get_colors()
		if ($type === 'error') $type = 'danger';
		$this->_notifications[] = [(string)$message, get_colors($type) ? $type : 'danger'];
	}
}
