<?php
/**
 * https://neofr.ag
 * Monitoring admin v0.4 — modernized layout, raw card HTML for full control.
 */

namespace NF\Modules\Monitoring\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		$this	->css('monitoring')
				->js('monitoring')
				->js('modal')
				->js('knob')
				->js('treeview')
				->css('treeview');

		// Stat cards row
		$cards = '<div class="nf-stats-grid">';
		$cards .= $this->_stat_card('PHP', PHP_VERSION, 'fab fa-php');
		$cards .= $this->_stat_card($this->lang('Mémoire utilisée'), $this->_format_bytes(memory_get_peak_usage(true)), 'fas fa-memory', $this->lang('Pic d\'allocation'));
		$cards .= $this->_stat_card($this->lang('NeoFrag'), NEOFRAG_VERSION, 'fas fa-cube', $this->lang('Version installée'));
		$cards .= $this->_stat_card($this->lang('Serveur'), $this->_get_server_software(), 'fas fa-server', php_sapi_name());
		$cards .= '</div>';

		// LEFT column: santé du site + infos serveur
		$left = '<div class="card">'.$this->view('monitoring').'</div>';

		$left .= '<div class="card panel-infos">'
			.'<div class="nf-card-header">'
			.'<span><i class="fas fa-info-circle"></i> '.$this->lang('Informations serveur').'</span>'
			.'<a class="btn btn-secondary btn-sm" href="#" data-modal-ajax="'.url('admin/ajax/monitoring/phpinfo').'" title="'.$this->lang('Détails').'"><i class="fas fa-info"></i></a>'
			.'</div>'
			.$this->view('infos', ['check' => $this->model()->check_server()])
			.'</div>';

		$left .= $this->_webmaster_card();

		// RIGHT column: notifications + storage + tree
		$right = '<div class="row">';

		// Notifications card (8/12)
		$right .= '<div class="col-12 col-lg-8">';
		$right .= '<div class="card panel-notifications">'
			.'<div class="nf-card-header">'
			.'<span><i class="far fa-bell"></i> '.$this->lang('Notifications').'</span>'
			.'<a class="btn btn-secondary btn-sm refresh" href="#" title="'.$this->lang('Actualiser').'"><i class="fas fa-sync"></i></a>'
			.'</div>'
			.'<table class="table table-notifications m-0"></table>'
			.'</div>';
		$right .= '</div>';

		// Storage card (4/12)
		$right .= '<div class="col-12 col-lg-4">';
		$right .= '<div class="card panel-storage">'
			.'<div class="nf-card-header">'
			.'<span><i class="far fa-copy"></i> '.$this->lang('Stockage').'</span>'
			.'<a class="btn btn-secondary btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#modal-backup" title="'.$this->lang('Sauvegarder').'"><i class="far fa-save"></i></a>'
			.'</div>'
			.'<div class="card-body">'.$this->view('storage').'</div>'
			.'<div class="card-footer">'.$this->view('storage-footer').'</div>'
			.'</div>';
		$right .= '</div>';

		$right .= '</div>'; // /row

		// Tree
		$right .= '<div class="card">'
			.'<div class="nf-card-header">'
			.'<span><i class="fas fa-heartbeat"></i> '.$this->lang('Votre installation NeoFrag').'</span>'
			.'<a class="btn btn-secondary btn-sm" href="'.url('admin/monitoring/files').'" title="'.htmlspecialchars($this->lang('Gérer / éditer les fichiers'), ENT_QUOTES).'"><i class="fas fa-folder-tree"></i> '.$this->lang('Gérer les fichiers').'</a>'
			.'</div>'
			.'<div class="card-body" style="max-height:520px;overflow-y:auto;"><div id="tree"></div></div>'
			.'</div>';

		// Backups list (full width sous le layout principal)
		$backups_section = '<div class="row mt-3"><div class="col-12">'
			.$this->view('backups', ['backups' => $this->model()->get_backups(), 'csrf' => $this->_csrf_token()])
			.'</div></div>';

		// Final layout
		return $cards
			.'<div class="row">'
			.'<div class="col-12 col-lg-3">'.$left.'</div>'
			.'<div class="col-12 col-lg-9">'.$right.'</div>'
			.'</div>'
			.$backups_section
			.$this->_cron_section()
			.$this->_sudo_modal();
	}

	/**
	 * Modale « sudo webmaster » de la page Monitoring : déverrouille la fenêtre sudo. S'ouvre toute
	 * seule quand une action destructrice a été bloquée (pending_url en session, lu une seule fois) et,
	 * en cas de succès, renvoie vers l'action mémorisée. Rien si aucun mot de passe webmaster n'est défini.
	 */
	private function _sudo_modal()
	{
		$wm = new \NF\NeoFrag\Libraries\Webmaster($this);

		if (!$wm->is_configured())
		{
			return '';
		}

		$pending = (string) $this->session('webmaster', 'pending_url');

		if ($pending !== '')
		{
			$this->session->destroy('webmaster', 'pending_url'); // lecture unique
		}

		$modal = '<div class="modal fade" id="wm-sudo-modal" tabindex="-1" role="dialog"><div class="modal-dialog modal-sm" role="document"><div class="modal-content">'
			.'<div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-shield"></i> '.$this->lang('Confirmation webmaster').'</h5></div>'
			.'<div class="modal-body"><p class="text-muted small mb-2">'.$this->lang('Action sensible : saisis ton mot de passe webmaster.').'</p>'
			.'<input type="password" class="form-control" id="wm-sudo-pass" autocomplete="off">'
			.'<div class="text-danger small mt-2" id="wm-sudo-err" style="display:none;"></div></div>'
			.'<div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">'.$this->lang('Annuler').'</button>'
			.'<button type="button" class="btn btn-primary btn-sm" id="wm-sudo-ok">'.$this->lang('Valider').'</button></div>'
			.'</div></div></div>';

		$script = '<script>document.addEventListener("DOMContentLoaded",function(){'
			.'var SU='.json_encode((string) url('admin/ajax/monitoring/sudo')).',PENDING='.json_encode($pending).';'
			.'var pass=document.getElementById("wm-sudo-pass"),err=document.getElementById("wm-sudo-err"),modalEl=document.getElementById("wm-sudo-modal");'
			.'function submit(){NF.post(SU,{password:pass.value}).then(function(r){'
			.'if(r&&r.ok){if(PENDING){window.location.href=PENDING;}else{bootstrap.Modal.getOrCreateInstance(modalEl).hide();window.location.reload();}}'
			.'else{err.textContent=(r&&r.error)||"Erreur";err.style.display="";}});}'
			.'var ok=document.getElementById("wm-sudo-ok");if(ok){ok.addEventListener("click",submit);}'
			.'if(pass){pass.addEventListener("keydown",function(e){if(e.key==="Enter")submit();});}'
			.'if(PENDING){bootstrap.Modal.getOrCreateInstance(modalEl).show();setTimeout(function(){if(pass)pass.focus();},300);}'
			.'});</script>';

		return $modal.$script;
	}

	/** Carte d'aide listant l'URL + la ligne crontab de l'endpoint de parution programmée. */
	private function _cron_section()
	{
		$key   = (string)$this->config->nf_cron_key;
		$reset = '<a class="btn btn-secondary btn-sm" href="'.url('admin/monitoring/cron/reset').'?_='.$this->_csrf_token().'" data-confirm="'.htmlspecialchars($this->lang('Générer une nouvelle clé ? L\'ancienne URL de cron cessera de fonctionner.'), ENT_QUOTES).'"><i class="fas fa-key"></i> '.$this->lang('Régénérer la clé').'</a>';

		$header = '<div class="nf-card-header"><span><i class="far fa-clock"></i> '.$this->lang('Parution programmée (cron)').'</span>'.$reset.'</div>';

		if ($key === '')
		{
			$body = '<div class="card-body"><div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle"></i> '
				.$this->lang('Aucune clé de cron définie : la parution des contenus programmés ne fonctionnera pas. Cliquez sur « Régénérer la clé » pour en créer une.').'</div></div>';

			return '<div class="row mt-3"><div class="col-12"><div class="card">'.$header.$body.'</div></div></div>';
		}

		$origin = ($this->url->https ? 'https' : 'http').'://'.($_SERVER['HTTP_HOST'] ?? '');
		$url    = $origin.url('monitoring/cron').'?key='.rawurlencode($key);
		$esc    = htmlspecialchars($url, ENT_QUOTES);

		$body = '<div class="card-body">'
			.'<p class="text-muted mb-2">'.$this->lang('NeoFrag n\'a pas d\'ordonnanceur : un cron externe doit appeler cette URL régulièrement (toutes les 5 min) pour publier news et articles programmés à l\'heure réelle — notifications, webhooks et gamification compris.').'</p>'
			.'<label class="small fw-bold mb-1">URL</label>'
			.'<input type="text" class="form-control form-control-sm mb-2" readonly data-nf-select-on-click style="background:rgba(0,0,0,.15);color:inherit;border-color:rgba(128,128,128,.3);" value="'.$esc.'">'
			.'<label class="small fw-bold mb-1">crontab</label>'
			.'<pre class="mb-0" style="white-space:pre-wrap;word-break:break-all;background:rgba(0,0,0,.15);padding:8px;border-radius:6px;">*/5 * * * * curl -fsS "'.$esc.'" &gt;/dev/null 2&gt;&amp;1</pre>'
			.'</div>';

		return '<div class="row mt-3"><div class="col-12"><div class="card">'.$header.$body.'</div></div></div>';
	}

	/** Jeton CSRF de session pour les actions destructrices en GET (régén. clé, suppr./purge backups). */
	private function _csrf_token()
	{
		$tokens = (array) $this->session('csrf');
		if (empty($tokens['monitoring']))
		{
			$this->session->set('csrf', 'monitoring', $tokens['monitoring'] = bin2hex(random_bytes(16)));
		}
		return $tokens['monitoring'];
	}

	/** Rejette l'action si le jeton CSRF (param `_`) ne correspond pas — anti-forgerie sur les liens GET. */
	private function _check_csrf()
	{
		if (empty($_GET['_']) || !is_string($_GET['_']) || !hash_equals($this->_csrf_token(), $_GET['_']))
		{
			notify($this->lang('Action non autorisée (jeton de sécurité invalide). Réessaie depuis la page Monitoring.'), 'danger');
			redirect('admin/monitoring');
		}
	}

	/**
	 * Garde « sudo webmaster » des actions destructrices. Si un mot de passe webmaster est défini et que
	 * la fenêtre sudo n'est PAS ouverte : mémorise l'URL d'action et renvoie vers Monitoring, où une
	 * modale demande le mot de passe puis rejoue l'action. Sans mot de passe défini : ne gate pas
	 * (rétro-compatibilité). Si déjà déverrouillé : laisse passer.
	 */
	private function _require_sudo()
	{
		$wm = new \NF\NeoFrag\Libraries\Webmaster($this);

		if ($wm->is_configured() && !$wm->sudo_active())
		{
			$this->session->set('webmaster', 'pending_url', (string) ($_SERVER['REQUEST_URI'] ?? ''));
			notify($this->lang('Action sensible : confirme avec ton mot de passe webmaster.'), 'warning');
			redirect('admin/monitoring');
		}
	}

	/** Génère/rotation d'une nouvelle clé secrète pour l'endpoint de parution (cron). */
	public function _cron_reset()
	{
		$this->_check_csrf();
		$this->_require_sudo();

		if (nf_demo())
		{
			notify($this->lang('Action désactivée sur le site de démonstration.'), 'warning');
			redirect('admin/monitoring');
		}

		$this->config('nf_cron_key', bin2hex(random_bytes(32)));

		notify($this->lang('Nouvelle clé de cron générée. Mets à jour ta tâche planifiée avec la nouvelle URL.'));
		redirect('admin/monitoring');
	}

	/** Définit/change le mot de passe webmaster (sudo des actions sensibles). Super-admin only. */
	public function webmaster()
	{
		if (empty($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals($this->_csrf_token(), $_POST['csrf']))
		{
			notify($this->lang('Action non autorisée (jeton de sécurité invalide).'), 'danger');
			redirect('admin/monitoring');
		}

		if (!$this->access->effective_admin())
		{
			notify($this->lang('Action réservée à l\'administrateur.'), 'danger');
			redirect('admin/monitoring');
		}

		if (nf_demo())
		{
			notify($this->lang('Action désactivée sur le site de démonstration.'), 'warning');
			redirect('admin/monitoring');
		}

		$wm       = new \NF\NeoFrag\Libraries\Webmaster($this);
		$password = (string) post('password');
		$confirm  = (string) post('password2');

		// Pour CHANGER un mot de passe existant, il faut prouver qu'on connaît l'actuel.
		if ($wm->is_configured() && !$wm->verify((string) post('current')))
		{
			notify($this->lang('Mot de passe webmaster actuel incorrect.'), 'danger');
			redirect('admin/monitoring');
		}

		if (strlen(trim($password)) < 8)
		{
			notify($this->lang('Le mot de passe webmaster doit faire au moins 8 caractères.'), 'danger');
			redirect('admin/monitoring');
		}

		if ($password !== $confirm)
		{
			notify($this->lang('Les deux mots de passe ne correspondent pas.'), 'danger');
			redirect('admin/monitoring');
		}

		if (!$wm->set($password))
		{
			notify($this->lang('Impossible d\'écrire config/webmaster.php (vérifie les permissions du dossier config/).'), 'danger');
			redirect('admin/monitoring');
		}

		notify($this->lang('Mot de passe webmaster enregistré.'));
		redirect('admin/monitoring');
	}

	/** Page « Gestionnaire de fichiers » : arbre jaillé + éditeur. Écritures gardées par le sudo webmaster. */
	public function files()
	{
		if (!$this->access->effective_admin())
		{
			notify($this->lang('Action réservée à l\'administrateur.'), 'danger');
			redirect('admin/monitoring');
		}

		$wm = new \NF\NeoFrag\Libraries\Webmaster($this);

		return $this	->css('filemanager')
					->view('filemanager', [
						'csrf'          => $this->_csrf_token(),
						'wm_configured' => $wm->is_configured()
					]);
	}

	/** Carte « Sécurité webmaster » : état + formulaire définir/changer le mot de passe sudo. */
	private function _webmaster_card()
	{
		$wm         = new \NF\NeoFrag\Libraries\Webmaster($this);
		$configured = $wm->is_configured();
		$csrf       = htmlspecialchars($this->_csrf_token(), ENT_QUOTES);

		$status = $configured
			? '<span class="badge text-bg-success">'.$this->lang('Défini').'</span>'
			: '<span class="badge text-bg-danger">'.$this->lang('Non défini').'</span>';

		$intro = $configured
			? '<p class="text-muted mb-2">'.$this->lang('Garde les actions sensibles (édition de fichiers, suppressions…). Pour le changer, saisis l\'actuel.').'</p>'
			: '<div class="alert alert-warning mb-2"><i class="fas fa-exclamation-triangle"></i> '.$this->lang('Aucun mot de passe webmaster. Définis-en un pour débloquer les actions sensibles.').'</div>';

		$current = $configured
			? '<input type="password" name="current" class="form-control form-control-sm mb-2" autocomplete="off" placeholder="'.htmlspecialchars($this->lang('Mot de passe webmaster actuel'), ENT_QUOTES).'" required>'
			: '';

		$sudo_line = '';
		if ($configured)
		{
			$sudo_line = '<div class="small mb-2">'.$this->lang('Sudo :').' '.($wm->sudo_active()
				? '<span class="badge text-bg-success">'.$this->lang('Déverrouillé').'</span>'
				: '<span class="badge text-bg-secondary">'.$this->lang('Verrouillé').'</span> <button type="button" class="btn btn-outline-primary btn-sm py-0" data-bs-toggle="modal" data-bs-target="#wm-sudo-modal"><i class="fas fa-unlock"></i> '.$this->lang('Déverrouiller').'</button>')
				.'</div>';
		}

		$body = '<div class="card-body">'.$intro.$sudo_line
			.'<form method="post" action="'.url('admin/monitoring/webmaster').'" autocomplete="off">'
			.'<input type="hidden" name="csrf" value="'.$csrf.'">'
			.$current
			.'<input type="password" name="password" class="form-control form-control-sm mb-2" autocomplete="new-password" placeholder="'.htmlspecialchars($this->lang('Nouveau mot de passe (8 car. min., distinct du login)'), ENT_QUOTES).'" required>'
			.'<input type="password" name="password2" class="form-control form-control-sm mb-2" autocomplete="new-password" placeholder="'.htmlspecialchars($this->lang('Confirmation'), ENT_QUOTES).'" required>'
			.'<button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-key"></i> '.($configured ? $this->lang('Changer') : $this->lang('Définir')).'</button>'
			.'</form></div>';

		return '<div class="card panel-webmaster">'
			.'<div class="nf-card-header"><span><i class="fas fa-user-shield"></i> '.$this->lang('Sécurité webmaster').'</span>'.$status.'</div>'
			.$body
			.'</div>';
	}

	public function _backup_download($filename)
	{
		// L'URL porte le timestamp seul (le placeholder {url_title} = [a-z0-9-] n'accepte pas le point
		// de « .zip ») ; on reconstruit le vrai nom de fichier .zip côté serveur.
		$slug = basename((string)$filename);
		$dir  = rtrim(NEOFRAG_CMS, '/').'/backups';
		$file = $dir.'/'.$slug.'.zip';

		if (!preg_match('/^\d{14}(-[a-f0-9]{16})?$/', $slug) || !file_exists($file))
		{
			notify($this->lang('Sauvegarde introuvable.'), 'danger');
			redirect('admin/monitoring');
		}

		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="'.basename($file).'"');
		header('Content-Length: '.filesize($file));
		header('Cache-Control: no-cache, no-store, must-revalidate');
		readfile($file);
		exit;
	}

	public function _backup_delete($filename)
	{
		$this->_check_csrf();
		$this->_require_sudo();

		if (nf_demo())
		{
			notify($this->lang('Action désactivée sur le site de démonstration.'), 'warning');
			redirect('admin/monitoring');
		}

		$slug = basename((string)$filename);
		$file = rtrim(NEOFRAG_CMS, '/').'/backups/'.$slug.'.zip';
		if (preg_match('/^\d{14}(-[a-f0-9]{16})?$/', $slug) && file_exists($file))
		{
			@unlink($file);
			notify($this->lang('Sauvegarde supprimée.'));
		}
		else
		{
			notify($this->lang('Sauvegarde introuvable.'), 'danger');
		}
		redirect('admin/monitoring');
	}

	public function _backups_purge()
	{
		$this->_check_csrf();
		$this->_require_sudo();

		if (nf_demo())
		{
			notify($this->lang('Action désactivée sur le site de démonstration.'), 'warning');
			redirect('admin/monitoring');
		}

		// Purge les backups > 30 jours
		$count   = 0;
		$dir     = rtrim(NEOFRAG_CMS, '/').'/backups';
		$cutoff  = time() - 30 * 86400;
		if (is_dir($dir))
		{
			foreach (scandir($dir) as $f)
			{
				if (preg_match('/^\d{14}(-[a-f0-9]{16})?\.zip$/', $f))
				{
					$path = $dir.'/'.$f;
					if (filemtime($path) < $cutoff && @unlink($path))
					{
						$count++;
					}
				}
			}
		}
		notify($count > 0
			? $this->lang('%d sauvegarde supprimée|%d sauvegardes supprimées', $count, $count)
			: $this->lang('Aucune sauvegarde à purger.')
		);
		redirect('admin/monitoring');
	}

	private function _stat_card($label, $value, $icon, $trend = '')
	{
		$h  = '<div class="nf-stat-card">';
		$h .= '<div class="nf-stat-label"><i class="'.$icon.'"></i> '.htmlspecialchars($label).'</div>';
		$h .= '<div class="nf-stat-value" style="font-size:18px;font-family:\'JetBrains Mono\',monospace;letter-spacing:0;">'.htmlspecialchars($value).'</div>';
		if ($trend) $h .= '<div class="nf-stat-trend">'.htmlspecialchars($trend).'</div>';
		$h .= '</div>';
		return $h;
	}

	private function _format_bytes($bytes)
	{
		$units = ['o', 'Ko', 'Mo', 'Go', 'To'];
		$i = 0;
		while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
		return number_format($bytes, $i ? 1 : 0, ',', ' ').' '.$units[$i];
	}

	private function _get_server_software()
	{
		$software = $_SERVER['SERVER_SOFTWARE'] ?? '';
		if (stripos($software, 'apache') !== FALSE) return 'Apache';
		if (stripos($software, 'nginx') !== FALSE)  return 'Nginx';
		if (stripos($software, 'iis') !== FALSE)    return 'IIS';
		return $software ? explode('/', $software)[0] : 'CLI';
	}

	public function update($version)
	{
		$this->theme('admin')->js('update');

		return $this->modal($this->lang('Mise à jour de NeoFrag'), 'fas fa-rocket')
					->large()
					->set_id('modal-update')
					->body('<div class="update-features">
								'.$version->features.'
							</div>
							<hr />
							<div class="steps-body text-center">
								<div class="row" style="padding: 0 110px;">
									<div class="col">
										<div class="progress">
											<div class="progress-bar" role="progressbar" data-step="50,50"></div>
										</div>
									</div>
									<div class="col">
										<div class="progress">
											<div class="progress-bar" role="progressbar" data-step="100"></div>
										</div>
									</div>
									<div class="col">
										<div class="progress">
											<div class="progress-bar" role="progressbar" data-step="95,5"></div>
										</div>
									</div>
								</div>
								<div class="row steps-legends">
									<div class="col">
										<div class="step">
											'.icon('fas fa-sync').'
										</div>
										'.$this->lang('Lancement').'
									</div>
									<div class="col">
										<div class="step">
											'.icon('far fa-save').'
										</div>
										'.$this->lang('Sauvegarde').'
									</div>
									<div class="col">
										<div class="step">
											'.icon('far fa-arrow-alt-circle-down').'
										</div>
										'.$this->lang('Téléchargement').'
									</div>
									<div class="col">
										<div class="step">
											'.icon('fas fa-cog').'
										</div>
										'.$this->lang('Installation').'
									</div>
								</div>
							</div>')
					->submit($this->lang('Lancer la mise à jour'))
					->cancel();
	}
}
