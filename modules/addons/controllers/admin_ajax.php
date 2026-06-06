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
	 * Origine FIXE (nf_marketplace_url) ; aucune URL saisie par l'utilisateur (anti-SSRF).
	 */
	public function marketplace()
	{
		require_once NEOFRAG_CMS . '/install/lib/installer.php';

		$url = \NF\Install\Lib\Installer::MARKETPLACE_URL_DEFAULT;
		if (is_string($cfg = $this->config->nf_marketplace_url) && $cfg !== '')
		{
			$url = $cfg;
		}

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
