<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Addons\Controllers;

use NF\NeoFrag\Core\Debug;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use ZipArchive;

class Admin_Ajax extends Controller_Module
{
	public function install()
	{
		return $this->form2()
					->rule($this->form_file('addon')
								->mime('application/x-zip-compressed')
								->mime('application/zip')
								->temp()
					)
					->success(function($data){
						$zip = new ZipArchive;
						if ($zip->open($tmp_file = $data['addon']) === TRUE)
						{
							// Anti-zip-slip : REFUSER toute archive dont une entrée s'échappe du dossier
							// d'extraction (chemin absolu / « .. » / antislash) AVANT d'extraire.
							require_once NEOFRAG_CMS . '/install/lib/installer.php';
							if (!\NF\Install\Lib\Installer::zip_entries_safe($zip))
							{
								$zip->close();
								@unlink($tmp_file);
								notify($this->lang('Archive refusée : elle contient un chemin non sûr.'), 'danger');
								$this->modal->dispose();
								return;
							}

							dir_create($tmp = dir_temp());
																			
							$zip->extractTo($tmp);
							$zip->close();
				    
							$folders = array_filter(scandir($tmp), function($a) use ($tmp){
								return !in_array($a, ['.', '..']) && is_dir($tmp.'/'.$a);
							});

							$install_addon = function ($dir, $types = NULL){
								if ($types === NULL)
								{
									$types = ['Module', 'Widget', 'Theme'];
								}
								else if (!is_array($types))
								{
									$types = (array)$types;
								}

								foreach (scandir($dir) as $filename)
								{
									if (!is_dir($file = $dir.'/'.$filename) &&
										preg_match('/^(.+?)\.php$/', $filename, $match) &&
										preg_match('/use NF\\\NeoFrag\\\Addons\\\('.implode('|', $types).');/m', $content = file_get_contents($file), $match2))
									{
										file_put_contents($file, preg_replace('/^(namespace )NF\\\/m', '\1NF_Temp\\', $content));

										require_once $file;

										try
										{
											$class = new \ReflectionClass('NF_Temp\\'.$match2[1].'s\\'.$match[1].'\\'.$match[1]);
										}
										catch (\ReflectionException $e)
										{
											break;
										}

										$addon = $class->newInstanceArgs([NeoFrag()]);

										$version = $addon->info()->version;
										$depends = $addon->info()->depends;

										$nf_version = $depends['neofrag'];

										if (!empty($version) && !empty($nf_version))
										{
											$type = strtolower($match2[1]);

											$addon = NeoFrag()->$type($name = strtolower($match[1]));

											if ($addon)
											{
												$update = TRUE;

												if (($cmp = version_compare($version, version_format($addon->info()->version))) === 0)
												{
													return [
														'warning' => 'Le '.$type.' '.$addon->info()->title.' est déjà installé en version '.$version
													];
												}
												else if ($cmp === -1)
												{
													return [
														'danger' => 'Le '.$type.' '.$addon->info()->title.' est déjà installé avec une version supérieure'
													];
												}
											}

											if (($cmp = version_compare($nf_version, version_format(NEOFRAG_VERSION))) !== 1)
											{
												file_put_contents($file, $content);
												dir_copy($dir, $type.'s/'.$name);

												if (!NeoFrag()->collection('addon')->where('name', $name)->where('type_id', $type_id = NeoFrag()->collection('addon_type')->where('name', $type)->row()->id)->row()->id)
												{
													NeoFrag()	->model2('addon')
																->set('name', $name)
																->set('type', $type_id)
																->set('data', [
																	'enabled' => TRUE
																])
																->create();
												}

												if ($addon = NeoFrag()->$type($name))
												{
													$addon->reset();

													return [
														'success' => 'Le '.$type.' '.$addon->info()->title.' a été '.(empty($update) ? 'installé' : 'mis-à-jour')
													];
												}

												return [
													'danger' => 'Le '.$type.' '.($addon ? $addon->info()->title : $name).' n\'a pas pu être '.(empty($update) ? 'installé' : 'mis-à-jour')
												];
											}

											return [
												'danger' => 'Le '.$type.' '.($addon ? $addon->info()->title : $name).' nécessite la version '.$nf_version.' de NeoFrag, veuillez mettre jour votre site'
											];
										}

										return [
											'danger' => 'Le composant ne peut pas être installé, veuillez vérifier la présence des numéros de version'
										];
									}
								}

								return [
									'danger' => 'Le composant ne peut pas être installé, veuillez vérifier son contenu'
								];
							};

							$types   = ['modules', 'widgets', 'themes'];

							$results = [
								'danger'  => [],
								'success' => [],
								'warning' => []
							];

							if (count($folders) == 1 && !in_array($folder = current($folders), $types))
							{
								$results = array_merge_recursive($results, $install_addon($tmp.'/'.$folder));
							}
							else
							{
								foreach (array_intersect($folders, $types) as $folder)
								{
									foreach (scandir($tmp.'/'.$folder) as $dir)
									{
										if (!in_array($dir, ['.', '..']) && is_dir($dir = $tmp.'/'.$folder.'/'.$dir))
										{
											$results = array_merge_recursive($results, $install_addon($dir, substr(ucfirst($folder), 0, -1)));
										}
									}
								}
							}

							dir_remove($tmp);
							unlink($tmp_file);

							foreach (array_filter($results) as $type => $messages)
							{
								foreach ($messages as $message)
								{
									notify($message, $type);

									// Trace l'install d'addon (surface d'exécution de code) — succès comme échec.
									if (in_array($type, ['success', 'danger'], TRUE))
									{
										(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('addon.install', [
											'details' => $message,
											'success' => $type === 'success'
										]);
									}
								}
							}

							$this->modal->dispose();
						}
					})
					->submit('Ajouter')
					->modal('Ajouter', 'fas fa-plus')
					->cancel();
	}

	/**
	 * Marketplace distant : liste les addons (modules / thèmes) du catalogue non installés et les
	 * installe en UN CLIC — télécharge + vérifie SHA-256 + extrait (anti-zip-slip) via Installer,
	 * puis registration framework (nf_addon + reset → install.sql). Widgets appariés inclus.
	 * Origine FIXE validée contre l'allow-list (anti-SSRF) ; aucune URL saisie par l'utilisateur.
	 */
	public function marketplace()
	{
		require_once NEOFRAG_CMS . '/install/lib/installer.php';

		// nf_marketplace_url ne peut surcharger le défaut que vers un hôte autorisé (HTTPS, port 443) :
		// une valeur injectée en base ne redirige pas les fetchs vers un hôte arbitraire.
		$cfg = $this->config->nf_marketplace_url;
		$url = \NF\Install\Lib\Installer::sanitize_marketplace_url(is_string($cfg) ? $cfg : NULL);

		$catalog = \NF\Install\Lib\Installer::fetch_catalog($url);

		if ($catalog === NULL)
		{
			return $this->modal('Marketplace', 'fas fa-store')
						->body('<div class="alert alert-warning" style="margin:0">'.$this->lang('Marketplace injoignable pour le moment, réessayez plus tard.').'</div>')
						->close();
		}

		// Installables = modules/thèmes du catalogue NON déjà installés. + map des widgets (zips séparés).
		$available = $widget_metas = [];
		foreach ($catalog['addons'] as $a)
		{
			$type = $a['type'] ?? '';
			$name = $a['name'] ?? '';

			if ($type === 'widget' && $name !== '')
			{
				$widget_metas[$name] = $a;
			}
			if (!in_array($type, ['module', 'theme'], TRUE) || $name === '')
			{
				continue;
			}
			if (!($type_id = @NeoFrag()->collection('addon_type')->where('name', $type)->row()->id))
			{
				continue;
			}
			if (NeoFrag()->collection('addon')->where('name', $name)->where('type_id', $type_id)->row()->id)
			{
				continue; // déjà installé
			}
			$available[$type.':'.$name] = $a;
		}

		if (!$available)
		{
			return $this->modal('Marketplace', 'fas fa-store')
						->body('<div class="alert alert-info" style="margin:0">'.$this->lang('Tous les addons du marketplace sont déjà installés.').'</div>')
						->close();
		}

		$options = [];
		foreach ($available as $key => $a)
		{
			$options[$key] = ($a['title'] ?: $a['name']).' — '.$this->lang(ucfirst($a['type'])).' · '.(int) round(($a['size'] ?? 0) / 1024).' Ko';
		}

		return $this->form2()
					->info('<div class="alert alert-primary" style="margin-bottom:1rem">'.$this->lang('%d addon(s) disponible(s) sur le marketplace. Téléchargés et vérifiés (SHA-256) à l\'installation.', count($available)).'</div>')
					->rule($this->form_checkbox('addons')->data($options))
					->success(function($data) use ($available, $widget_metas, $url){
						require_once NEOFRAG_CMS . '/install/lib/installer.php';

						foreach ((array) ($data['addons'] ?? []) as $key)
						{
							if (!isset($available[$key]))
							{
								continue;
							}
							$meta = $available[$key];

							try
							{
								$r = \NF\Install\Lib\Installer::download_and_extract($meta, $url, NEOFRAG_CMS);
							}
							catch (\Throwable $e)
							{
								notify($this->lang('<b>%s</b> : %s', $meta['title'] ?: $meta['name'], $e->getMessage()), 'danger');
								(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('addon.install', ['details' => 'marketplace: '.$meta['type'].'/'.$meta['name'].' — '.$e->getMessage(), 'success' => FALSE]);
								continue;
							}

							$this->_marketplace_register($r['type'], $r['name']);

							// Widgets appariés : zip SÉPARÉ → on télécharge leurs fichiers aussi (best-effort).
							foreach ($r['widgets'] as $w)
							{
								if (!isset($widget_metas[$w]))
								{
									continue;
								}
								try
								{
									$wr = \NF\Install\Lib\Installer::download_and_extract($widget_metas[$w], $url, NEOFRAG_CMS);
									$this->_marketplace_register($wr['type'], $wr['name']);
								}
								catch (\Throwable $e)
								{
									// Le module reste installé ; on prévient juste que son widget apparié a échoué.
									notify($this->lang('Widget <b>%s</b> non installé : %s', $w, $e->getMessage()), 'warning');
								}
							}

							notify($this->lang('<b>%s</b> installé depuis le marketplace', $meta['title'] ?: $meta['name']));
							(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('addon.install', ['details' => 'marketplace: '.$meta['type'].'/'.$meta['name'], 'success' => TRUE]);
						}

						$this->modal->dispose();
					})
					->submit('Installer')
					->modal('Marketplace', 'fas fa-store')
					->cancel();
	}

	/**
	 * Mises à jour des addons (modèle « tout bundlé » : tout est déjà installé, le marketplace ne sert
	 * plus qu'aux MAJ + addons tiers). Compare la version installée — info()->version, lue sur le DISQUE —
	 * de chaque addon enregistré à celle annoncée par le catalogue distant, et propose celles dont le
	 * catalogue offre une version SUPÉRIEURE (et compatible avec la version du CMS). À la validation :
	 * télécharge + vérifie SHA-256 + ré-extrait (anti-zip-slip) via Installer, puis addon->update() :
	 * réimporte install.sql (idempotent — crée d'éventuelles nouvelles tables) PUIS applique les migrations
	 * de schéma en attente (modules/<x>/install/migrations/*.up.sql, ALTER/rename/données, suivies dans
	 * nf_addon_migrations). Origine FIXE validée (allow-list, anti-SSRF) ; le client n'envoie que type:name.
	 */
	public function updates()
	{
		require_once NEOFRAG_CMS . '/install/lib/installer.php';

		$cfg = $this->config->nf_marketplace_url;
		$url = \NF\Install\Lib\Installer::sanitize_marketplace_url(is_string($cfg) ? $cfg : NULL);

		$catalog = \NF\Install\Lib\Installer::fetch_catalog($url);

		if ($catalog === NULL)
		{
			return $this->modal('Mises à jour', 'fas fa-arrow-up')
						->body('<div class="alert alert-warning" style="margin:0">'.$this->lang('Marketplace injoignable : impossible de vérifier les mises à jour.').'</div>')
						->close();
		}

		// Version du CŒUR : le catalogue porte la version du CMS pour laquelle il a été bâti. Si elle est
		// plus récente que l'installée, on le SIGNALE (mise à jour MANUELLE — l'auto-update par overlay
		// reste désactivé sur ce fork, cf. monitoring). Aucun téléchargement/écrasement automatique ici.
		$core_notice = '';
		if (is_string($base = $catalog['base_version'] ?? '') && $base !== '' && version_compare(version_format($base), version_format(NEOFRAG_VERSION), '>'))
		{
			$core_notice = '<div class="alert alert-info" style="margin:0 0 1rem">'.icon('fas fa-cube fa-fw').' '
				.$this->lang('<b>NeoFrag %s</b> est disponible (tu utilises %s). Télécharge la release et remplace les fichiers — hors <code>config/</code>, <code>upload/</code>, <code>backups/</code> — puis visite le site (les migrations s\'appliquent).', $base, NEOFRAG_VERSION)
				.'</div>';
		}

		// Index du catalogue par type:name (+ map des widgets, dont les fichiers sont dans un zip séparé).
		$by_key = $widget_metas = [];
		foreach ($catalog['addons'] as $a)
		{
			$type = $a['type'] ?? '';
			$name = $a['name'] ?? '';
			if ($type === 'widget' && $name !== '')
			{
				$widget_metas[$name] = $a;
			}
			if (in_array($type, ['module', 'widget', 'theme'], TRUE) && $name !== '')
			{
				$by_key[$type.':'.$name] = $a;
			}
		}

		// Addons installés dont le catalogue propose une version SUPÉRIEURE ET compatible avec le CMS.
		$available = [];
		foreach (NeoFrag()->collection('addon')->get() as $addon)
		{
			if (!($object = $addon->addon()) || !isset($by_key[$key = $addon->type->name.':'.$addon->name]))
			{
				continue;
			}
			$meta   = $by_key[$key];
			$latest = (string) ($meta['version'] ?? '');

			if ($latest === '' || version_compare(version_format($object->info()->version), version_format($latest)) >= 0)
			{
				continue; // déjà à jour (ou version locale plus récente)
			}
			if (!$this->_base_compatible($meta['requires']['base'] ?? ''))
			{
				continue; // la MAJ exige une version du CMS plus récente que l'actuelle
			}

			$available[$key] = [
				'meta'    => $meta,
				'title'   => $meta['title'] ?: $addon->name,
				'current' => $object->info()->version,
				'latest'  => $latest,
			];
		}

		if (!$available)
		{
			return $this->modal('Mises à jour', 'fas fa-arrow-up')
						->body($core_notice.'<div class="alert alert-info" style="margin:0">'.$this->lang('Tous vos addons sont à jour.').'</div>')
						->close();
		}

		$options = [];
		foreach ($available as $key => $a)
		{
			$options[$key] = $a['title'].' — '.$a['current'].' → '.$a['latest'];
		}

		return $this->form2()
					->info($core_notice.'<div class="alert alert-primary" style="margin-bottom:1rem">'.$this->lang('%d mise(s) à jour disponible(s). Téléchargées et vérifiées (SHA-256) avant application.', count($available)).'</div>')
					->rule($this->form_checkbox('addons')->data($options)->value(array_keys($options)))
					->success(function($data) use ($available, $widget_metas, $url){
						require_once NEOFRAG_CMS . '/install/lib/installer.php';

						// Le client n'envoie que des clés type:name ; le meta (file/sha256…) vient du catalogue SERVEUR.
						foreach ((array) ($data['addons'] ?? []) as $key)
						{
							if (!isset($available[$key]))
							{
								continue;
							}
							$meta = $available[$key]['meta'];

							try
							{
								$r = \NF\Install\Lib\Installer::download_and_extract($meta, $url, NEOFRAG_CMS);
							}
							catch (\Throwable $e)
							{
								notify($this->lang('<b>%s</b> : %s', $available[$key]['title'], $e->getMessage()), 'danger');
								(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('addon.update', ['details' => $meta['type'].'/'.$meta['name'].' — '.$e->getMessage(), 'success' => FALSE]);
								continue;
							}

							$this->_apply_addon_update($r['type'], $r['name']);

							// Widgets appariés : zip SÉPARÉ → on rafraîchit aussi leurs fichiers (best-effort).
							foreach ($r['widgets'] as $w)
							{
								if (!isset($widget_metas[$w]))
								{
									continue;
								}
								try
								{
									$wr = \NF\Install\Lib\Installer::download_and_extract($widget_metas[$w], $url, NEOFRAG_CMS);
									$this->_apply_addon_update($wr['type'], $wr['name']);
								}
								catch (\Throwable $e)
								{
									notify($this->lang('Widget <b>%s</b> non mis à jour : %s', $w, $e->getMessage()), 'warning');
								}
							}

							notify($this->lang('<b>%s</b> mis à jour en version %s', $available[$key]['title'], $available[$key]['latest']));
							(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('addon.update', ['details' => $meta['type'].'/'.$meta['name'].' → '.$available[$key]['latest'], 'success' => TRUE]);
						}

						$this->modal->dispose();
					})
					->submit('Mettre à jour')
					->modal('Mises à jour', 'fas fa-arrow-up')
					->cancel();
	}

	/** Vrai si la contrainte catalogue requires.base (ex. « >=1.0.0 ») est satisfaite par NEOFRAG_VERSION. */
	private function _base_compatible($req)
	{
		if (!is_string($req) || $req === '')
		{
			return TRUE;
		}
		if (preg_match('/^\s*(>=|<=|>|<|==|=)?\s*([0-9][0-9.]*)/', $req, $m))
		{
			$op = $m[1] !== '' ? $m[1] : '>=';
			return (bool) version_compare(version_format(NEOFRAG_VERSION), version_format($m[2]), $op);
		}
		return TRUE;
	}

	/**
	 * Applique une MAJ sur un addon DÉJÀ installé : recharge l'addon (fichiers neufs sur le disque) et
	 * lance update() — réimport install.sql + migrations de schéma en attente. Si l'addon n'est pas (encore)
	 * enregistré (cas limite), retombe sur une install fraîche (register + reset, qui baseline les migrations).
	 */
	private function _apply_addon_update($type, $name)
	{
		if ($addon = NeoFrag()->$type($name))
		{
			$addon->update();
		}
		else
		{
			$this->_marketplace_register($type, $name);
		}
	}

	/** Registration framework d'un addon dont les fichiers sont déjà sur le disque (nf_addon + reset). */
	private function _marketplace_register($type, $name)
	{
		if (!($type_id = @NeoFrag()->collection('addon_type')->where('name', $type)->row()->id))
		{
			return;
		}
		if (!NeoFrag()->collection('addon')->where('name', $name)->where('type_id', $type_id)->row()->id)
		{
			NeoFrag()->model2('addon')->set('name', $name)->set('type', $type_id)->set('data', ['enabled' => TRUE])->create();
		}
		if ($addon = NeoFrag()->$type($name))
		{
			$addon->reset();
		}
	}

	/*
	 * Scan disque : détecte les addons présents sur le disque mais non enregistrés
	 * en base (nf_addon), puis installe ceux sélectionnés. Complète l'install par
	 * ZIP en couvrant aussi les addons déjà déposés à la main (et le type
	 * authenticator, que l'upload ZIP ne gère pas).
	 */
	public function scan()
	{
		$type_labels = [
			'theme'         => 'thème',
			'widget'        => 'widget',
			'module'        => 'module',
			'authenticator' => 'authentificateur'
		];

		// type => [dossier, classe de base attendue, préfixe de dossier]
		$map = [
			'theme'         => ['themes',  'Theme',         ''],
			'widget'        => ['widgets', 'Widget',        ''],
			'module'        => ['modules', 'Module',        ''],
			'authenticator' => ['addons',  'Authenticator', 'authenticator_']
		];

		$found = [];

		foreach ($map as $type => list($dir, $base, $prefix))
		{
			if (!($type_id = @NeoFrag()->collection('addon_type')->where('name', $type)->row()->id) || !is_dir($dir))
			{
				continue;
			}

			foreach (scandir($dir) as $folder)
			{
				if (in_array($folder, ['.', '..'], TRUE) || !is_dir($dir.'/'.$folder))
				{
					continue;
				}

				if ($prefix !== '' && strpos($folder, $prefix) !== 0)
				{
					continue;
				}

				$name   = $prefix !== '' ? substr($folder, strlen($prefix)) : $folder;
				$file   = $dir.'/'.$folder.'/'.$folder.'.php';
				$needle = 'use NF\\NeoFrag\\Addons\\'.$base;

				if (!is_file($file) || strpos(file_get_contents($file), $needle) === FALSE)
				{
					continue;
				}

				if (NeoFrag()->collection('addon')->where('name', $name)->where('type_id', $type_id)->row()->id)
				{
					continue;
				}

				$found[$type.':'.$name] = [
					'type'  => $type,
					'name'  => $name,
					'label' => $name.' ('.$this->lang($type_labels[$type]).')'
				];
			}
		}

		if (!$found)
		{
			return $this->modal('Scanner le disque', 'fas fa-sync')
						->body('<div class="alert alert-info" style="margin:0">'.$this->lang('Aucun nouvel addon détecté sur le disque.').'</div>')
						->close();
		}

		$options = [];
		$checked = [];

		foreach ($found as $key => $addon)
		{
			$options[$key] = $addon['label'];

			// Modules décochés par défaut (install plus intrusive : tables, permissions, nav)
			if ($addon['type'] !== 'module')
			{
				$checked[] = $key;
			}
		}

		return $this->form2()
					->info('<div class="alert alert-primary" style="margin-bottom:1rem">'.$this->lang('%d addon(s) détecté(s) sur le disque. Sélectionnez ceux à installer.', count($found)).'</div>')
					->rule($this->form_checkbox('addons')
								->data($options)
								->value($checked)
					)
					->success(function($data) use ($found){
						$selected = (array)($data['addons'] ?? []);
						$count    = 0;

						foreach ($selected as $key)
						{
							if (!isset($found[$key]))
							{
								continue;
							}

							$type = $found[$key]['type'];
							$name = $found[$key]['name'];

							if (!($type_id = @NeoFrag()->collection('addon_type')->where('name', $type)->row()->id))
							{
								continue;
							}

							if (NeoFrag()->collection('addon')->where('name', $name)->where('type_id', $type_id)->row()->id)
							{
								continue;
							}

							$row = NeoFrag()->model2('addon')
											->set('name', $name)
											->set('type', $type_id)
											->set('data', ['enabled' => TRUE])
											->create();

							if ($addon = NeoFrag()->$type($name))
							{
								$addon->reset();
								$count++;

								notify($this->lang('<b>%s</b> installé', $addon->info()->title));

								(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('addon.install', [
									'details' => 'scan: '.$type.'/'.$name,
									'success' => TRUE
								]);
							}
							else if (is_object($row) && method_exists($row, 'delete'))
							{
								// Classe introuvable malgré le pré-contrôle → on retire la ligne orpheline
								$row->delete();
							}
						}

						if (!$count)
						{
							notify($this->lang('Aucun addon installé.'), 'info');
						}

						refresh();
					})
					->submit('Installer la sélection')
					->modal('Scanner le disque', 'fas fa-sync')
					->cancel();
	}
}
