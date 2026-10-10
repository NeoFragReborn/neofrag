<?php
declare(strict_types=1);
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
							require_once NEOFRAG_CMS . '/neofrag/installer.php';
							if (!\NF\NeoFrag\Installer::zip_entries_safe($zip))
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
										$requis  = (array) ($addon->info()->requires ?? []);
										$titre   = (string) ($addon->info()->title ?? $match[1]);

										$nf_version = $depends['neofrag'];

										if (!empty($version) && !empty($nf_version))
										{
											$type = strtolower($match2[1]);

											// Le nom du type, traduit : il entre dans des phrases ENTIÈRES (« Le module Forum a été installé »).
											// Les phrases étaient coupées autour des variables (« a été » + « installé »), intraduisibles.
											$nom_type = ['module' => $this->lang('module'), 'widget' => $this->lang('widget'), 'theme' => $this->lang('thème')][$type] ?? $type;

											$addon = NeoFrag()->$type($name = strtolower($match[1]));

											if ($addon)
											{
												$update = TRUE;

												if (($cmp = version_compare($version, version_format($addon->info()->version))) === 0)
												{
													return [
														'warning' => (string) $this->lang('Le %s %s est déjà installé en version %s', $nom_type, $addon->info()->title, $version)
													];
												}
												else if ($cmp === -1)
												{
													return [
														'danger' => (string) $this->lang('Le %s %s est déjà installé avec une version supérieure', $nom_type, $addon->info()->title)
													];
												}
											}

											// Les modules qu'il déclare dans `requires` doivent être là AVANT : un widget posé sans
											// son module cherche ses tables. Le marketplace le refusait déjà ; une archive envoyée
											// par « Ajouter » passait (2026-10-09). Une archive qui porte le module et son widget
											// installe le module d'abord (dossier modules/ avant widgets/).
											if ($manquants = array_values(array_filter($requis, static fn ($dep): bool => is_string($dep) && $dep !== '' && !NeoFrag()->module($dep))))
											{
												return [
													'danger' => (string) $this->lang('<b>%s</b> a besoin de : %s. Installe-les d\'abord.', $titre, implode(', ', $manquants))
												];
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
														'success' => (string) (empty($update)
															? $this->lang('Le %s %s a été installé', $nom_type, $addon->info()->title)
															: $this->lang('Le %s %s a été mis à jour', $nom_type, $addon->info()->title))
													];
												}

												return [
													'danger' => (string) (empty($update)
														? $this->lang('Le %s %s n\'a pas pu être installé', $nom_type, $addon ? $addon->info()->title : $name)
														: $this->lang('Le %s %s n\'a pas pu être mis à jour', $nom_type, $addon ? $addon->info()->title : $name))
												];
											}

											return [
												'danger' => (string) $this->lang('Le %s %s nécessite la version %s de NeoFrag, veuillez mettre à jour votre site', $nom_type, $addon ? $addon->info()->title : $name, $nf_version)
											];
										}

										return [
											'danger' => (string) $this->lang('Le composant ne peut pas être installé, veuillez vérifier la présence des numéros de version')
										];
									}
								}

								return [
									'danger' => (string) $this->lang('Le composant ne peut pas être installé, veuillez vérifier son contenu')
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
					->submit($this->lang('Ajouter'))
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
		require_once NEOFRAG_CMS . '/neofrag/installer.php';

		// nf_marketplace_url ne peut surcharger le défaut que vers un hôte autorisé (HTTPS, port 443) :
		// une valeur injectée en base ne redirige pas les fetchs vers un hôte arbitraire.
		$cfg = $this->config->nf_marketplace_url;
		$url = \NF\NeoFrag\Installer::sanitize_marketplace_url(is_string($cfg) ? $cfg : NULL);

		$catalog = \NF\NeoFrag\Installer::fetch_catalog($url);

		if ($catalog === NULL)
		{
			return $this->modal('Marketplace', 'fas fa-store')
						->body('<div class="alert alert-warning" style="margin:0">'.$this->lang('Marketplace injoignable pour le moment, réessayez plus tard.').'</div>')
						->close();
		}

		// Installables = modules, thèmes, et widgets AUTONOMES du catalogue, non déjà installés. Un widget
		// qu'un module emmène (`provides_widgets`) vient avec lui ; ceux qu'aucun module n'emmène — À propos,
		// Steam, Twitch… — n'étaient proposés nulle part ici : on ne pouvait les avoir que par « Ajouter »
		// (trouvé par check-extensions, 2026-10-04).
		$available = $widget_metas = $emmenes = [];
		$trop_recents = 0;
		foreach ($catalog['addons'] as $a)
		{
			if (($a['type'] ?? '') === 'module')
			{
				foreach ((array) ($a['provides_widgets'] ?? []) as $w)
				{
					$emmenes[(string) $w] = TRUE;
				}
			}
		}

		foreach ($catalog['addons'] as $a)
		{
			$type = $a['type'] ?? '';
			$name = $a['name'] ?? '';

			if ($type === 'widget' && $name !== '')
			{
				$widget_metas[$name] = $a;
			}
			if (!in_array($type, ['module', 'theme', 'widget'], TRUE) || $name === '' || ($type === 'widget' && isset($emmenes[$name])))
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
			// Un addon fabriqué pour un cœur plus récent ne s'installe pas ici : il appellerait des fonctions que ce
			// cœur n'a pas, et la première page tomberait en erreur. Jusqu'à la 1.2.28, seule la mise à jour lisait
			// `requires.base`. Le serveur du marketplace sert désormais à chaque site le catalogue de SA version
			// (2026-10-05) : ce contrôle est le filet, pour un catalogue qui ne serait pas celui de ce cœur.
			if (!$this->_base_compatible($a['requires']['base'] ?? ''))
			{
				$trop_recents++;
				continue;
			}
			$available[$type.':'.$name] = $a;
		}

		$alerte_recents = $trop_recents ? (string) $this->lang('%d addon(s) du marketplace demandent une version plus récente de NeoFrag : mets d\'abord ton site à jour.', $trop_recents) : '';

		if (!$available)
		{
			return $this->modal('Marketplace', 'fas fa-store')
						->body('<div class="alert alert-'.($alerte_recents ? 'warning' : 'info').'" style="margin:0">'.($alerte_recents ?: $this->lang('Tous les addons du marketplace sont déjà installés.')).'</div>')
						->close();
		}

		$options = [];
		// Le type, traduit : `lang(ucfirst($type))` demandait « Theme », qui n'est pas un texte français.
		$types_catalogue = ['module' => $this->lang('Module'), 'theme' => $this->lang('Thème'), 'widget' => $this->lang('Widget')];
		foreach ($available as $key => $a)
		{
			$options[$key] = (\NF\NeoFrag\Installer::texte_catalogue($a, 'title') ?: $a['name']).' — '.($types_catalogue[$a['type']] ?? $a['type']).' · '.$this->lang('%d Ko', (int) round(($a['size'] ?? 0) / 1024));
		}

		return $this->form2()
					->info(($alerte_recents ? '<div class="alert alert-warning" style="margin-bottom:1rem">'.$alerte_recents.'</div>' : '').'<div class="alert alert-primary" style="margin-bottom:1rem">'.$this->lang('%d addon(s) disponible(s) sur le marketplace. Téléchargés et vérifiés (SHA-256) à l\'installation.', count($available)).'</div>')
					->rule($this->form_checkbox('addons')->data($options))
					->success(function($data) use ($available, $widget_metas, $url){
						require_once NEOFRAG_CMS . '/neofrag/installer.php';

						foreach ((array) ($data['addons'] ?? []) as $key)
						{
							if (!isset($available[$key]))
							{
								continue;
							}
							$meta = $available[$key];

							/**
							 * Les addons dont celui-ci dépend doivent être présents AVANT.
							 *
							 * Seule la version du cœur (`requires.base`) était vérifiée, jamais les
							 * addons requis. Or `events` déclare `games` et `teams`, et son
							 * `install.sql` porte des clés étrangères vers `nf_games_modes` et
							 * `nf_games_maps` : l'installer sur un site sans `games` échouait à
							 * l'import SQL, sans un mot en amont. L'installeur web, lui, fermait
							 * déjà les dépendances (`Installer::close_requires`) — c'était la voie
							 * marketplace qui les ignorait.
							 */
							if ($manquants = $this->_dependances_manquantes($meta))
							{
								notify($this->lang(
									'<b>%s</b> a besoin de : %s. Installe-les d\'abord.',
									$meta['title'] ?: $meta['name'],
									implode(', ', $manquants)
								), 'warning');
								continue;
							}

							try
							{
								$r = \NF\NeoFrag\Installer::download_and_extract($meta, $url, NEOFRAG_CMS);
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
									$wr = \NF\NeoFrag\Installer::download_and_extract($widget_metas[$w], $url, NEOFRAG_CMS);
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
					->submit($this->lang('Installer'))
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
		require_once NEOFRAG_CMS . '/neofrag/installer.php';

		$cfg = $this->config->nf_marketplace_url;
		$url = \NF\NeoFrag\Installer::sanitize_marketplace_url(is_string($cfg) ? $cfg : NULL);

		$catalog = \NF\NeoFrag\Installer::fetch_catalog($url);

		if ($catalog === NULL)
		{
			return $this->modal('Mises à jour', 'fas fa-arrow-up')
						->body('<div class="alert alert-warning" style="margin:0">'.$this->lang('Marketplace injoignable : impossible de vérifier les mises à jour.').'</div>')
						->close();
		}

		// Version du CŒUR : si une plus récente que l'installée est publiée, on le SIGNALE, et on renvoie au
		// Monitoring, qui fait la mise à jour en un clic (sauvegarde avant d'écrire, retour arrière si une étape
		// échoue). Rien ne s'écrit ici. Jusqu'au 2026-10-04, ce message demandait de remplacer les fichiers à
		// la main. La version publiée se lit dans le manifeste du canal de mise à jour, que le Monitoring garde en
		// cache, et à défaut dans le catalogue : le serveur du marketplace sert désormais à chaque site le
		// catalogue de SA version (2026-10-05), dont `base_version` ne dit donc plus qu'une version plus récente
		// existe.
		$publiee   = is_string($catalog['base_version'] ?? NULL) ? $catalog['base_version'] : '';
		$manifeste = is_file($fichier = NEOFRAG_CMS.'/cache/monitoring/version.json') ? json_decode((string) @file_get_contents($fichier), TRUE) : NULL;
		$annoncee  = is_array($manifeste) && is_string($manifeste['neofrag']['version'] ?? NULL) ? $manifeste['neofrag']['version'] : '';

		if ($annoncee !== '' && ($publiee === '' || version_compare(version_format($annoncee), version_format($publiee), '>')))
		{
			$publiee = $annoncee;
		}

		$core_notice = '';
		if ($publiee !== '' && version_compare(version_format($publiee), version_format(NEOFRAG_VERSION), '>'))
		{
			$core_notice = '<div class="alert alert-info" style="margin:0 0 1rem">'.icon('fas fa-cube fa-fw').' '
				.$this->lang('<b>NeoFrag %s</b> est disponible (tu utilises %s) : <a href="%s">Monitoring</a> le met à jour en un clic, avec une sauvegarde avant d\'écrire.', $publiee, NEOFRAG_VERSION, url('admin/monitoring'))
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
						require_once NEOFRAG_CMS . '/neofrag/installer.php';

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
								$r = \NF\NeoFrag\Installer::download_and_extract($meta, $url, NEOFRAG_CMS);
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
									$wr = \NF\NeoFrag\Installer::download_and_extract($widget_metas[$w], $url, NEOFRAG_CMS);
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
					->submit($this->lang('Mettre à jour'))
					->modal('Mises à jour', 'fas fa-arrow-up')
					->cancel();
	}

	/**
	 * Addons déclarés par `requires.addons` qui ne sont pas installés sur ce site.
	 *
	 * Rend la liste des noms manquants, vide si tout est là. Un catalogue ancien, qui ne porte pas
	 * encore le champ, rend donc toujours une liste vide : la vérification s'ajoute sans casser les
	 * installations existantes.
	 */
	private function _dependances_manquantes($meta): array
	{
		$requis = (array) ($meta['requires']['addons'] ?? []);

		if (!$requis)
		{
			return [];
		}

		$installes = [];

		foreach (NeoFrag()->model2('addon')->get('module') as $addon)
		{
			// `$addon->name` vaut FALSE sur un addon chargé : le nom est dans `info()`. La liste ne
			// contenait donc qu'une seule entrée, vide, et TOUTE dépendance déclarée était annoncée
			// comme manquante — y compris quand le module était bel et bien installé.
			$installes[(string) $addon->info()->name] = TRUE;
		}

		return array_values(array_filter($requis, static function($nom) use ($installes){
			return is_string($nom) && $nom !== '' && !isset($installes[$nom]);
		}));
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
			'theme'         => $this->lang('thème'),
			'widget'        => $this->lang('widget'),
			'module'        => $this->lang('module'),
			'authenticator' => $this->lang('authentificateur')
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
					'label' => $name.' ('.$type_labels[$type].')'
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
					->submit($this->lang('Installer la sélection'))
					->modal('Scanner le disque', 'fas fa-sync')
					->cancel();
	}
}
