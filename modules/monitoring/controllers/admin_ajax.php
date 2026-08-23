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
							$output[] = ['text' => utf8_htmlentities($name), 'tags' => $tags, 'nodes' => $treeview($node, $dir.$name.'/')];
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
							$output[] = ['text' => utf8_htmlentities($name), 'tags' => $tags];
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
							$output[] = ['text' => utf8_htmlentities($name), 'tags' => $tags, 'nodes' => $treeview($node, $dir.$name.'/')];
						}
						else
						{
							$tags = [];
							if (file_exists($dir.$name) && !is_writable($dir.$name))
							{
								$tags[] = $this->lang('Protégé en écriture');
							}
							$output[] = ['text' => utf8_htmlentities($name), 'tags' => $tags];
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
		// apache_setenv n'existe que sous mod_php : en PHP 8, l'appeler sous fpm-fcgi/PHP-FPM lève une
		// Error « fonction inconnue » que @ ne masque pas → fatal au lancement de la sauvegarde.
		if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', 1); }
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

		// Défense en profondeur : si le dossier a été créé au runtime (déploiement sans le
		// .htaccess versionné), on le re-pose — l'archive contient le dump SQL + config/.
		if (!file_exists('backups/.htaccess'))
		{
			file_put_contents('backups/.htaccess', "Require all denied\n");
		}

		// Suffixe aléatoire : le nom ne doit pas être devinable (l'archive contient des secrets,
		// et la protection HTTP dépend de la config serveur — AllowOverride, parité nginx).
		while (file_exists(($file = 'backups/'.date('YmdHis').'-'.bin2hex(random_bytes(8))).'.zip') || file_exists($dump = $file.'.sql'))
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

	// ===== Gestionnaire de fichiers webmaster ================================================
	// Toutes les écritures sont gardées par : super-admin (effective_admin) + sudo webmaster + CSRF,
	// et confinées sous NEOFRAG_CMS par File_Jail. Zones protégées (secrets/garde-fou) interdites.

	const FM_MAX_BYTES = 2097152; // 2 Mo : lecture/édition texte
	const FM_PROTECTED = ['config', 'logs', 'backups', 'cache'];
	const FM_NO_DELETE = ['index.php', '.htaccess', 'composer.json', 'composer.lock'];

	// Gardes : renvoient NULL si OK, sinon le tableau de réponse à émettre. L'appelant fait
	// `if ($e = $this->_fm_deny_*()) return $this->json($e);` — la sortie passe TOUJOURS par un
	// `return $this->json(...)`, jamais par un `return;` nu (qui produit une réponse vide).
	private function _fm_deny_admin(): ?array
	{
		return $this->access->effective_admin() ? NULL : ['error' => (string) $this->lang('Action réservée à l\'administrateur.')];
	}

	/** NULL si la fenêtre sudo est ouverte, sinon signale au JS d'afficher la modale. */
	private function _fm_deny_sudo(): ?array
	{
		return (new \NF\NeoFrag\Libraries\Webmaster($this))->sudo_active() ? NULL : ['sudo' => 'required'];
	}

	private function _fm_deny_csrf(): ?array
	{
		$tokens = (array) $this->session('csrf');

		return (!empty($tokens['monitoring']) && is_string(post('csrf')) && hash_equals($tokens['monitoring'], (string) post('csrf')))
			? NULL
			: ['error' => (string) $this->lang('Jeton de sécurité invalide. Recharge la page.')];
	}

	/** Résout un chemin relatif sous la racine d'install, en refusant les zones protégées. NULL = rejet. */
	private function _fm_path($rel): ?string
	{
		$root = NEOFRAG_CMS;
		$abs  = \NF\NeoFrag\Libraries\File_Jail::resolve($root, (string) $rel);

		if ($abs === NULL || \NF\NeoFrag\Libraries\File_Jail::is_protected($root, $abs, self::FM_PROTECTED))
		{
			return NULL;
		}

		return $abs;
	}

	/** Chemin relatif (affichage) depuis l'absolu. */
	private function _fm_rel(string $abs): string
	{
		$root = rtrim(str_replace('\\', '/', realpath(NEOFRAG_CMS) ?: NEOFRAG_CMS), '/');
		return ltrim(substr(str_replace('\\', '/', $abs), strlen($root)), '/');
	}

	/** POST password → ouvre la fenêtre sudo (rate-limité + audité). */
	public function sudo()
	{
		if ($e = $this->_fm_deny_admin())
		{
			return $this->json($e);
		}

		$r = (new \NF\NeoFrag\Libraries\Webmaster($this))->attempt((string) post('password'));

		if (!empty($r['ok']))
		{
			return $this->json(['ok' => TRUE]);
		}

		$msg = !empty($r['locked'])
			? (string) $this->lang('Trop de tentatives. Réessaie dans %d min.', (int) ceil(($r['retry_after'] ?? 0) / 60))
			: (string) $this->lang('Mot de passe webmaster incorrect.');

		return $this->json(['ok' => FALSE, 'error' => $msg]);
	}

	/** Liste le contenu d'un dossier (jaillé, zones protégées masquées). */
	public function fs_list()
	{
		if ($e = $this->_fm_deny_admin())
		{
			return $this->json($e);
		}

		$abs = $this->_fm_path((string) post('dir') ?: '.');

		if ($abs === NULL || !is_dir($abs))
		{
			return $this->json(['error' => (string) $this->lang('Dossier inaccessible.')]);
		}

		$dirs = $files = [];

		foreach (scandir($abs) ?: [] as $name)
		{
			if ($name === '.' || $name === '..')
			{
				continue;
			}

			$child = $abs.'/'.$name;

			if (\NF\NeoFrag\Libraries\File_Jail::is_protected(NEOFRAG_CMS, $child, self::FM_PROTECTED))
			{
				continue; // config/, logs/, backups/, cache/ jamais exposés
			}

			$rel = $this->_fm_rel($child);

			if (is_dir($child))
			{
				$dirs[] = ['name' => $name, 'path' => $rel, 'type' => 'dir'];
			}
			else
			{
				$files[] = ['name' => $name, 'path' => $rel, 'type' => 'file'];
			}
		}

		$sort = function($a, $b){ return strcasecmp($a['name'], $b['name']); };
		usort($dirs, $sort);
		usort($files, $sort);

		return $this->json(['path' => $this->_fm_rel($abs), 'entries' => array_merge($dirs, $files)]);
	}

	/** Contenu d'un fichier (refus binaire/trop gros). */
	public function fs_read()
	{
		if ($e = $this->_fm_deny_admin())
		{
			return $this->json($e);
		}

		$abs = $this->_fm_path((string) post('path'));

		if ($abs === NULL || !is_file($abs))
		{
			return $this->json(['error' => (string) $this->lang('Fichier inaccessible.')]);
		}

		if (filesize($abs) > self::FM_MAX_BYTES)
		{
			return $this->json(['error' => (string) $this->lang('Fichier trop volumineux pour l\'éditeur (%s max).', human_size(self::FM_MAX_BYTES))]);
		}

		$content = (string) file_get_contents($abs);

		if (\NF\NeoFrag\Libraries\File_Jail::is_binary($content))
		{
			return $this->json(['error' => (string) $this->lang('Fichier binaire : édition impossible.'), 'binary' => TRUE]);
		}

		return $this->json([
			'path'    => $this->_fm_rel($abs),
			'content' => $content,
			'mode'    => \NF\NeoFrag\Libraries\File_Jail::editor_mode($abs)
		]);
	}

	/** Enregistre un fichier (sudo) : écriture atomique + sauvegarde .nfbak de l'ancienne version. */
	public function fs_save()
	{
		if ($e = $this->_fm_deny_admin() ?? $this->_fm_deny_csrf() ?? $this->_fm_deny_sudo())
		{
			return $this->json($e);
		}

		$abs     = $this->_fm_path((string) post('path'));
		$content = (string) post('content');

		if ($abs === NULL || is_dir($abs))
		{
			return $this->json(['error' => (string) $this->lang('Chemin invalide.')]);
		}

		if (strlen($content) > self::FM_MAX_BYTES)
		{
			return $this->json(['error' => (string) $this->lang('Contenu trop volumineux (%s max).', human_size(self::FM_MAX_BYTES))]);
		}

		if (is_file($abs))
		{
			@copy($abs, $abs.'.nfbak'); // undo rapide
		}

		$tmp = $abs.'.nftmp';

		if (@file_put_contents($tmp, $content, LOCK_EX) === FALSE || !@rename($tmp, $abs))
		{
			@unlink($tmp);
			return $this->json(['error' => (string) $this->lang('Écriture impossible (permissions ?).')]);
		}

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('webmaster.file_saved', ['target_id' => $this->_fm_rel($abs), 'details' => ['bytes' => strlen($content)]]);

		return $this->json(['ok' => TRUE]);
	}

	/** Crée un dossier (sudo). */
	public function fs_mkdir()
	{
		if ($e = $this->_fm_deny_admin() ?? $this->_fm_deny_csrf() ?? $this->_fm_deny_sudo())
		{
			return $this->json($e);
		}

		$name = trim(basename(str_replace('\\', '/', (string) post('name'))));
		$abs  = $this->_fm_path(trim((string) post('dir'), '/').'/'.$name);

		if ($name === '' || $abs === NULL)
		{
			return $this->json(['error' => (string) $this->lang('Nom de dossier invalide.')]);
		}

		if (is_dir($abs) || !@mkdir($abs, 0755))
		{
			return $this->json(['error' => (string) $this->lang('Création du dossier impossible.')]);
		}

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('webmaster.dir_created', ['target_id' => $this->_fm_rel($abs)]);

		return $this->json(['ok' => TRUE]);
	}

	/** Renomme un fichier/dossier dans son dossier (sudo). */
	public function fs_rename()
	{
		if ($e = $this->_fm_deny_admin() ?? $this->_fm_deny_csrf() ?? $this->_fm_deny_sudo())
		{
			return $this->json($e);
		}

		$src  = $this->_fm_path((string) post('path'));
		$name = trim(basename(str_replace('\\', '/', (string) post('name'))));

		if ($src === NULL || !file_exists($src) || $name === '')
		{
			return $this->json(['error' => (string) $this->lang('Renommage impossible.')]);
		}

		$dst = $this->_fm_path($this->_fm_rel(dirname($src)).'/'.$name);

		if ($dst === NULL || file_exists($dst) || !@rename($src, $dst))
		{
			return $this->json(['error' => (string) $this->lang('Renommage impossible (cible existante ?).')]);
		}

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('webmaster.renamed', ['target_id' => $this->_fm_rel($src), 'details' => ['to' => $this->_fm_rel($dst)]]);

		return $this->json(['ok' => TRUE]);
	}

	/** Supprime un fichier ou un dossier (sudo). Refuse les fichiers critiques racine. */
	public function fs_delete()
	{
		if ($e = $this->_fm_deny_admin() ?? $this->_fm_deny_csrf() ?? $this->_fm_deny_sudo())
		{
			return $this->json($e);
		}

		$abs = $this->_fm_path((string) post('path'));

		if ($abs === NULL || !file_exists($abs))
		{
			return $this->json(['error' => (string) $this->lang('Suppression impossible.')]);
		}

		if (in_array($this->_fm_rel($abs), self::FM_NO_DELETE, TRUE))
		{
			return $this->json(['error' => (string) $this->lang('Ce fichier critique ne peut pas être supprimé ici.')]);
		}

		$ok = is_dir($abs) ? dir_remove($abs) : @unlink($abs);

		if (!$ok)
		{
			return $this->json(['error' => (string) $this->lang('Suppression impossible (permissions ?).')]);
		}

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('webmaster.deleted', ['target_id' => $this->_fm_rel($abs)]);

		return $this->json(['ok' => TRUE]);
	}
}
