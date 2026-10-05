<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Monitoring\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\NeoFrag\Installer;

class Admin_Ajax extends Controller_Module
{
	private $_notifications = [];

	public function index($refresh)
	{
		if ($refresh || $this->module->need_checking())
		{
			$this->config('nf_monitoring_last_check', time());

			//https://www.php.net/supported-versions.php
			/*
			 * Des CHAINES, pas des nombres : `version_compare()` attend des chaines, et un
			 * flottant ne sait pas comparer des versions — 7.10 vaudrait moins que 7.4.
			 */
			$current             = '7.4';
			$security_fixes_only = '7.2';
			$last_end_of_life    = '7.1';

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

			/*
			 * Le journal des erreurs : s'écrit-il encore, et que dit-il des dernières 24 heures ? Un
			 * dossier `logs/` non inscriptible faisait taire le site sans rien dire (PHP se rabat sur le
			 * journal du serveur), et rien ne poussait à lire le journal — il fallait y penser, et y
			 * aller par SSH ou FTP. Au-delà de 20 Mo, il est mis de côté (`php.log.1`) avant d'être relu.
			 */
			require_once NEOFRAG_CMS.'/neofrag/helpers/journal.php';

			if (!is_dir('logs') || !is_writable('logs'))
			{
				$this->_notify($this->lang('Le dossier %s n’est pas inscriptible : les erreurs du site ne sont plus enregistrées', '<code>logs</code>'), 'danger');
			}
			else
			{
				if (is_file('logs/php.log') && filesize('logs/php.log') > 20 * 1048576)
				{
					@rename('logs/php.log', 'logs/php.log.1');
				}

				$recentes = count(array_filter(nf_journal_entrees(nf_journal_fin('logs/php.log')), static fn (array $e): bool => ($e['date'] ?? 0) >= time() - 86400 && in_array(nf_journal_gravite($e['texte']), ['fatale', 'erreur'], TRUE)));

				if ($recentes)
				{
					$this->_notify($this->lang('%d erreur dans les dernières 24 heures|%d erreurs dans les dernières 24 heures', $recentes, $recentes).' — <a href="'.url('admin/monitoring/journal').'">'.$this->lang('voir le journal').'</a>', 'warning');
				}
			}

			dir_create('cache/monitoring');

			// Origine des manifestes (version.json, checksum.json). `nf_monitoring_check_url` peut la
			// surcharger, mais UNIQUEMENT vers un hôte de l'allow-list, en HTTPS/443 — même garantie
			// anti-SSRF que le marketplace, dont c'est délibérément la même liste d'hôtes.
			//
			// Ce réglage était VIDE par défaut et déclaré nulle part : version.json n'était donc jamais
			// téléchargé, le thème admin ne voyait jamais de nouvelle version, et le bouton de mise à
			// jour n'apparaissait jamais. Il a maintenant un défaut valide.
			require_once NEOFRAG_CMS.'/neofrag/installer.php';

			$cfg       = $this->config->nf_monitoring_check_url;
			$check_url = Installer::sanitize_update_url(is_string($cfg) ? trim($cfg) : NULL);

			// Injoignable -> MODE DÉGRADÉ : arbre local seul, aucune fausse alerte d'intégrité.
			$version  = Installer::fetch_version_manifest($check_url);
			$checksum = Installer::fetch_checksum_manifest($check_url);

			// Le thème admin lit le manifeste depuis ce cache pour savoir s'il existe une version plus
			// récente. Il n'est réécrit que lorsque le téléchargement a réussi : une coupure réseau ne
			// doit pas faire disparaître une mise à jour déjà signalée.
			if ($version !== NULL)
			{
				file_put_contents('cache/monitoring/version.json', json_encode($version));
			}

			if ($checksum !== NULL)
			{
				file_put_contents('cache/monitoring/checksum.json', json_encode($checksum));
			}

			/*
			 * La liste de contrôle publiée est celle de la DERNIÈRE version. Un site qui n'est pas à son
			 * niveau la comparait quand même à ses fichiers : tout ce que la version suivante change y
			 * paraissait « corrompu », et ce qu'elle ajoute « manquant » — 102 alertes sur la vitrine en
			 * 1.2.12, juste avant le clic qui passait en 1.2.13 (relevé le 2026-10-02). La comparaison
			 * n'a de sens qu'à version égale : sinon, l'arbre local seul, et un mot pour le dire.
			 */
			$publiee = is_array($version) ? (string) ($version['neofrag']['version'] ?? '') : '';

			if ($checksum && $publiee !== '' && version_compare(version_format($publiee), version_format(NEOFRAG_VERSION), '!='))
			{
				$this->_notify($this->lang('La vérification des fichiers reprendra une fois le site au niveau de la version publiée (%s) : la liste de contrôle disponible est la sienne.', nf_texte($publiee)), 'info');
				$checksum      = NULL;
				$autre_version = TRUE;
			}

			// Scan local des md5
			$local_files = array_merge(
				dir_scan(array_diff($this->model()->folders, ['backups', 'cache', 'config', 'logs', 'overrides', 'upload']), 'md5_file'),
				['index.php' => md5_file('index.php')]
			);

			if ($checksum)
			{
				// Les fichiers qu'on ne compare pas : sources Sass, dépôt git, cartes de sources. La règle
				// s'applique AUX DEUX CÔTÉS. Elle n'écartait que les fichiers locaux, alors que le manifeste
				// publié liste les sources Sass livrées dans le paquet : le Monitoring les attendait sans
				// jamais les lire, et les déclarait « manquantes » — quatre erreurs et « Le navire coule ! »
				// dès la publication de la 1.2.0 (signalé le 2026-10-01).
				$ignore = static fn (string $file): bool => in_string('/sass/', $file) || in_string('/.git/', $file) || (bool) preg_match('_\.css\.map$_', $file);

				$checksum = array_filter($checksum, static fn ($md5, $file): bool => !$ignore((string) $file), ARRAY_FILTER_USE_BOTH);

				// Mode officiel : comparer chaque fichier local au checksum upstream
				foreach ($local_files as $file => $md5)
				{
					if ($ignore((string) $file))
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
				if ($check_url !== '' && empty($autre_version))
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

			// La MEME variable servait aux deux boucles imbriquees : lisible de travers, et un piege
			// pour la prochaine modification. Chaque niveau a desormais son nom.
			foreach ($this->model()->check_server() as $groupe)
			{
				foreach ($groupe['check'] as $name => $sonde)
				{
					$title  = NULL;
					$result = $sonde['check']($this->_notifications, $title);

					// Le CAST n'est pas cosmetique : une sonde qui pose un titre y met parfois un
					// objet de langue. json_encode le rend en objet vide, et le script ecrivait
					// « [object Object] » dans la case — vu sur « Envoi d'email », page de
					// supervision, le 2026-09-22.
					$server[$name] = $title === NULL ? $result : [$result, (string) $title];
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

		// Sur la démonstration, l'onglet Fichiers montre un arbre d'exemple : l'arborescence réelle du
		// serveur ne sort pas, même sans le contenu des fichiers (demandé le 2026-10-02).
		if (nf_demo())
		{
			$fichier  = static fn (string $nom): array => ['text' => $nom, 'tags' => []];
			$exemple  = [
				['text' => 'modules', 'tags' => [], 'nodes' => [['text' => 'exemple', 'tags' => [], 'nodes' => [$fichier('exemple.php')]]]],
				['text' => 'themes', 'tags' => [], 'nodes' => [['text' => 'exemple', 'tags' => [], 'nodes' => [$fichier('style.css')]]]],
				$fichier('index.php'),
			];

			if (is_array($result))
			{
				$result['files'] = $exemple;
			}
			else if (is_object($result))
			{
				$result->files = $exemple;
			}
		}

		return $this->json($result);
	}

	public function phpinfo()
	{
		// Le phpinfo() complet — chemins, variables d'environnement, configuration — ne sort pas sur
		// la démonstration, qui est publique et partage son serveur avec le site officiel.
		if (nf_demo())
		{
			return $this->modal($this->lang('Informations détaillées'))
						->body('<p class="text-center text-muted m-0 py-3">'.icon('fas fa-lock').' '.$this->lang('Indisponible sur la démonstration.').'</p>');
		}

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

	/**
	 * Mise à jour du cœur par superposition du paquet officiel.
	 *
	 * Ce bouton était désactivé par un garde-fou binaire (NEOFRAG_ALLOW_AUTOUPDATE), pour une raison
	 * réelle : le code téléchargeait la release UPSTREAM (neofrag.download) et l'étalait par-dessus,
	 * ce qui aurait écrasé tout le code divergé de ce fork. Un interrupteur global n'était toutefois
	 * qu'un pansement — il empêchait aussi les mises à jour légitimes. Ce sont maintenant quatre
	 * garanties de nature, qui laissent passer la bonne mise à jour et rien d'autre :
	 *
	 *   1. l'origine vient de l'allow-list (sanitize_update_url) — neofrag.download n'y est pas, et
	 *      une valeur injectée en base ne peut pas l'y faire entrer ;
	 *   2. le manifeste ne fournit qu'un NOM DE FICHIER, jamais une URL — ni hôte, ni chemin, donc
	 *      ni redirection ni remontée de répertoire ;
	 *   3. l'empreinte SHA-256 est vérifiée AVANT qu'un seul fichier du site ne soit touché ;
	 *   4. l'archive est contrôlée entrée par entrée (anti-zip-slip, symlinks refusés).
	 *
	 * Une sauvegarde est prise juste avant d'écrire, comme précédemment.
	 */
	public function update()
	{
		if (nf_demo())
		{
			return;
		}

		require_once NEOFRAG_CMS.'/neofrag/installer.php';

		if (!($version = $this->theme('admin')->update()))
		{
			return; // déjà à jour, ou manifeste jamais récupéré
		}

		// Le manifeste dit QUOI télécharger ; l'allow-list dit D'OÙ. Les deux moitiés viennent de
		// sources différentes, et c'est voulu : compromettre le manifeste ne déplace pas l'origine.
		$cfg  = $this->config->nf_monitoring_check_url;
		$base = Installer::sanitize_update_url(is_string($cfg) ? trim($cfg) : NULL);

		$name   = (string)($version->file   ?? '');
		$sha256 = (string)($version->sha256 ?? '');

		if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.zip$/', $name) || !preg_match('/^[a-f0-9]{64}$/i', $sha256))
		{
			error_log('[update] manifeste incomplet ou refusé (file="'.$name.'")');
			exit($this->lang('Le manifeste de mise à jour est incomplet : nom de paquet ou empreinte SHA-256 absent ou invalide.'));
		}

		$this->_stream(function() use ($version, $base, $name, $sha256){
			$archive = $this->_backup();

			dir_create('cache/monitoring');

			$file = 'cache/monitoring/neofrag.zip';

			// Téléchargement + vérification d'empreinte. Tout échec lève AVANT la moindre écriture
			// dans l'arborescence du site : à ce stade, le site est encore intact.
			try
			{
				Installer::download_update($base.'/'.$name, $file, $sha256, function($size, $total){
					$this->_flush(2, $total > 0 ? $size / $total * 100 : 0);
				});
			}
			catch (\Throwable $e)
			{
				// Rien n'a encore été écrit dans l'arborescence : il n'y a rien à annuler. La
				// sauvegarde prise plus haut reste disponible, mais elle n'est pas nécessaire ici.
				@unlink($file);
				throw new \RuntimeException((string) $this->lang('Mise à jour interrompue : %s', $e->getMessage()), 0, $e);
			}

			$patch_name = preg_replace('/[^a-z0-9]/i', '_', $version->version);

			// ── À partir d'ici, le site est modifié : tout échec doit être ANNULÉ ─────────────
			//
			// Le bloc couvre les deux écritures irréversibles à la main : la pose des fichiers et la
			// migration de la base. C'est exactement le périmètre où un arrêt laisse un site
			// mi-ancien mi-neuf — des tables migrées sous du code ancien, ou l'inverse.
			//
			// L'application des fichiers vit dans Installer : elle est ainsi testable hors HTTP
			// (cf. tests/Unit/UpdatePackageTest.php), ce qu'une closure de contrôleur n'est pas.
			// Anti-zip-slip, filtrage des entrées, préservation de config/ et balayage des vestiges
			// de neofrag/ y sont réunis, avec leurs gardes.
			try
			{
				$applique = Installer::apply_update_package($file, NEOFRAG_CMS, function($n, $total){
					$this->_flush(3, $n / $total * 100);
				});

				// Les migrations que la version apporte, DANS le bloc annulable : une migration en
				// échec ramène les fichiers, au lieu de laisser un code neuf sur une base ancienne.
				// (index.php les rattrape aussi au premier passage d'un code mis à jour par FTP.)
				$migrations = nf_migrations_du_code(NEOFRAG_CMS) ?? [];

				// Une mise à jour RÉUSSIE n'est pas une anomalie : elle va au journal d'audit de
				// l'administration (qui, quand, quelle version), pas au journal d'erreurs, que
				// check-journal veut muet. Seuls les échecs, plus bas, y écrivent (2026-09-23).
				(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('core.updated', [
					'target_type' => 'neofrag',
					'target_id'   => (string) $version->version,
					'details'     => ['de' => NEOFRAG_VERSION, 'fichiers' => $applique['written'], 'vestiges' => $applique['removed'], 'migrations' => $migrations],
				]);

				if (!$this->config->nf_version)
				{
					$this->config('nf_version', version_format(NEOFRAG_VERSION));
				}

				if ($patch = @NeoFrag()->install($patch_name))
				{
					$patch->up();
				}

				$this->_flush(4, 100);
			}
			catch (\Throwable $e)
			{
				@unlink($file);
				throw new \RuntimeException($this->lang('Mise à jour interrompue : %s', $e->getMessage()).' '.$this->_retour_arriere($archive), 0, $e);
			}

			unlink($file);

			// Volontairement HORS du périmètre annulable. Une feuille de style qui ne compile pas se
			// reconstruit d'un bouton dans « Outils » ; annuler pour autant une mise à jour par
			// ailleurs réussie serait disproportionné. On le signale, on n'y touche pas.
			try
			{
				$this->module('tools')->api()->scss();
			}
			catch (\Throwable $e)
			{
				error_log('[update] recompilation des feuilles de style échouée : '.$e->getMessage());
			}

			$this	->config('nf_update_callback',       $patch_name)
					->config('nf_version',               version_format($version->version))
					->config('nf_migrations_version',    (string) $version->version)
					->config('nf_monitoring_last_check', 0);
		});
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
		if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', '1'); }
		@ini_set('zlib.output_compression', 0);
		@ini_set('output_buffering', 'off');
		@ini_set('implicit_flush', 1);
		while (ob_get_level())
		{
			@ob_end_clean();
		}
		ob_implicit_flush(TRUE);

		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('X-Accel-Buffering: no');     // nginx : disable buffering
		header('Content-Encoding: identity'); // disable gzip
		header('Connection: close');

		set_time_limit(0);

		/*
		 * Comment le flux FINIT, dit explicitement : `[100, "OK"]` quand tout s'est bien passé,
		 * `[99, message, référence]` sinon. Le navigateur annonçait « Sauvegarde réalisée » ou « Mise à
		 * jour effectuée avec succès » dès que le flux s'arrêtait, échec compris — une mise à jour
		 * interrompue répondait même par un texte brut, que rien ne lisait (relevé le 2026-10-02).
		 */
		try
		{
			$callback();

			echo PHP_EOL.';'.json_encode([100, 'OK']).PHP_EOL;
		}
		catch (\Throwable $e)
		{
			$reference = nf_journaliser_erreur('monitoring', $e->getMessage(), nf_chemin_relatif($e->getFile()).':'.$e->getLine());

			echo PHP_EOL.';'.json_encode([99, $e->getMessage(), $reference], JSON_UNESCAPED_UNICODE).PHP_EOL;
		}

		exit;
	}

	/**
	 * Fabrique une archive complète du site — base comprise — sous backups/, et REND son chemin.
	 *
	 * Le chemin rendu est ce qui rend le retour arrière possible : sans lui, l'appelant ne sait pas
	 * quelle archive il vient de prendre, et ne peut donc pas la remettre en place s'il échoue.
	 *
	 * @return string chemin absolu de l'archive écrite
	 */
	private function _backup()
	{
		require_once NEOFRAG_CMS.'/neofrag/installer.php';

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

		/*
		 * Chaque écriture est vérifiée. L'ouverture de l'archive et sa fermeture n'étaient pas
		 * regardées : dans un dossier non inscriptible, ou sur un disque plein, la sauvegarde
		 * « réussissait » et rendait le chemin d'une archive qui n'existait pas — et la mise à jour,
		 * qui s'appuie dessus pour revenir en arrière, n'aurait rien eu à remettre (relevé le 2026-10-02).
		 */
		$echec = fn () => new \RuntimeException((string) $this->lang('La sauvegarde n’a pas pu être écrite dans le dossier %s : vérifiez ses droits d’écriture et l’espace disque.', 'backups'));

		if (($flux = @fopen($dump, 'w+b')) === FALSE)
		{
			throw $echec();
		}

		$this->mysqldump->dump($flux, function($value){
			$this->_flush(0, $value);
		});

		// `$file` servait AUSSI de variable de boucle plus bas : après la boucle, il ne désignait
		// plus l'archive mais le dernier fichier ajouté dedans. Tant que personne ne se resservait
		// du nom, ça ne se voyait pas ; le retour arrière, lui, a besoin de ce nom.
		$archive = $file.'.zip';

		$zip = new \ZipArchive;

		if ($zip->open($archive, \ZipArchive::CREATE) !== TRUE)
		{
			@unlink($dump);
			throw $echec();
		}

		$zip->addFile($dump, Installer::BACKUP_SQL_ENTRY);

		// `vendor/` ne figure pas dans la liste des dossiers du panneau — et pourtant un paquet de
		// mise à jour le livre (cf. Installer::UPDATE_MAX_PACKAGE, « vendor/ inclus »). Une archive
		// qui ne le contient pas ne permet donc PAS d'annuler une mise à jour : elle remettrait
		// l'ancien code sur les nouvelles dépendances. Une sauvegarde incomplète est pire qu'une
		// sauvegarde absente, parce qu'on croit l'avoir.
		//
		// On l'ajoute ici et pas dans `folders`, parce que cette liste sert aussi à l'empreinte
		// d'intégrité et au calcul d'occupation : y toucher changerait le sens de deux autres
		// mesures pour résoudre un problème qui n'appartient qu'à la sauvegarde.
		$files = array_merge(
			array_keys(dir_scan(array_diff($this->model()->folders, ['backups']))),
			is_dir('vendor') ? array_keys(dir_scan(['vendor'])) : [],
			['index.php', '.htaccess']
		);

		$total = count($files);
		$i     = 0;

		foreach ($files as $chemin)
		{
			$zip->addFile($chemin);

			$this->_flush(1, ++$i / $total * 100);
		}

		if (!$zip->close() || !is_file($archive) || !filesize($archive))
		{
			@unlink($dump);
			@unlink($archive);
			throw $echec();
		}

		unlink($dump);

		// La nouvelle archive écrite, les anciennes se retirent : les cinq plus récentes restent
		// toujours, les autres partent passé trente jours (nf_sauvegardes_a_retirer()).
		$dates = [];

		foreach (glob('backups/*.zip') ?: [] as $chemin)
		{
			$dates[basename($chemin)] = (int) filemtime($chemin);
		}

		foreach (nf_sauvegardes_a_retirer($dates, time()) as $ancienne)
		{
			@unlink('backups/'.$ancienne);
		}

		// Chemin absolu : l'archive a été écrite relativement au répertoire courant, mais le retour
		// arrière peut survenir alors qu'on ne l'a plus.
		return rtrim(NEOFRAG_CMS, '/').'/'.$archive;
	}

	/**
	 * Annule une mise à jour en remettant l'archive prise juste avant, et rend une phrase à afficher.
	 *
	 * Cette fonction ne relève JAMAIS : elle est appelée depuis la branche d'erreur d'une mise à jour
	 * déjà interrompue. Une exception ici remplacerait le message expliquant ce qui a échoué par un
	 * autre message expliquant que le sauvetage a échoué — en perdant le premier, qui est le plus
	 * utile des deux. On les rend donc tous les deux, l'un derrière l'autre.
	 */
	private function _retour_arriere($archive)
	{
		try
		{
			$this->model()->restaurer($archive, function($n, $total){
				$this->_flush(5, $total > 0 ? $n / $total * 100 : 0);
			});

			return (string)$this->lang('Le site a été remis dans l\'état où il était avant la mise à jour.');
		}
		catch (\Throwable $e)
		{
			error_log('[update] retour arrière IMPOSSIBLE : '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());

			return (string)$this->lang('Le retour arrière a échoué à son tour (%s). La sauvegarde %s est intacte : elle peut être restaurée depuis le panneau de surveillance, ou téléchargée.', $e->getMessage(), basename($archive));
		}
	}

	private function _notify($message, $type = 'danger')
	{
		// Cast string : $message peut être un objet Language qui se sérialise en {} via json_encode()
		// Map 'error' → 'danger' (legacy) car 'error' n'est pas une couleur valide dans get_colors()
		if ($type === 'error') $type = 'danger';
		$this->_notifications[] = [(string)$message, is_color($type) ? $type : 'danger'];
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

		// Sur la démonstration : une arborescence d'exemple, aucun vrai fichier (la démo est publique,
		// et partage son serveur avec le site officiel).
		if (nf_demo())
		{
			return $this->json(['path' => '', 'entries' => array_merge(
				array_map(static fn ($n) => ['name' => $n, 'path' => $n, 'type' => 'dir'], ['modules', 'themes', 'upload', 'widgets']),
				array_map(static fn ($n) => ['name' => $n, 'path' => $n, 'type' => 'file'], ['exemple.css', 'exemple.php', 'LISEZ-MOI.txt'])
			), 'demo' => TRUE]);
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

		$sort = function($a, $b){ return strcasecmp((string) $a['name'], (string) $b['name']); };
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

		if (nf_demo())
		{
			return $this->json([
				'path'    => basename((string) post('path')),
				'content' => (string) $this->lang('Fichier d’exemple : sur la démonstration, le gestionnaire de fichiers ne montre aucun vrai fichier du serveur. Sur votre site, vous lisez et modifiez ici vos fichiers, protégés par le mot de passe webmaster.'),
				'mode'    => 'text',
				'demo'    => TRUE,
			]);
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
