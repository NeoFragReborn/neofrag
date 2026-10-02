<?php
declare(strict_types=1);
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
		$left .= $this->_diagnostic_card();
		$left .= $this->_adresse_card();

		// RIGHT column: notifications + storage + tree
		$right = '<div class="row">';

		// Notifications card (8/12)
		$right .= '<div class="col-12 col-lg-8">';
		$right .= '<div class="card panel-notifications">'
			.'<div class="nf-card-header">'
			.'<span><i class="far fa-bell"></i> '.$this->lang('Notifications').'</span>'
			.'<span class="d-flex gap-2">'
			.'<a class="btn btn-outline-secondary btn-sm" href="'.url('admin/monitoring/journal').'"><i class="fas fa-file-medical-alt"></i> '.$this->lang('Journal des erreurs').'</a>'
			.'<a class="btn btn-secondary btn-sm refresh" href="#" title="'.$this->lang('Actualiser').'"><i class="fas fa-sync"></i></a>'
			.'</span>'
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
			.'<a class="btn btn-secondary btn-sm" href="'.url('admin/monitoring/files').'" title="'.htmlspecialchars((string) ($this->lang('Gérer / éditer les fichiers')), ENT_QUOTES).'"><i class="fas fa-folder-tree"></i> '.$this->lang('Gérer les fichiers').'</a>'
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
		$reset = '<a class="btn btn-secondary btn-sm" href="'.url('admin/monitoring/cron/reset').'?_='.$this->_csrf_token().'" data-confirm="'.htmlspecialchars((string) ($this->lang('Générer une nouvelle clé ? L\'ancienne URL de cron cessera de fonctionner.')), ENT_QUOTES).'"><i class="fas fa-key"></i> '.$this->lang('Régénérer la clé').'</a>';

		$header = '<div class="nf-card-header"><span><i class="far fa-clock"></i> '.$this->lang('Parution programmée (cron)').'</span>'.$reset.'</div>';

		if ($key === '')
		{
			$body = '<div class="card-body"><div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle"></i> '
				.$this->lang('Aucune clé de cron définie : la parution des contenus programmés ne fonctionnera pas. Cliquez sur « Régénérer la clé » pour en créer une.').'</div></div>';

			return '<div class="row mt-3"><div class="col-12"><div class="card">'.$header.$body.'</div></div></div>';
		}

		$origin = ($this->url->https ? 'https' : 'http').'://'.($_SERVER['HTTP_HOST'] ?? '');
		$url    = $origin.url('monitoring/cron').'?key='.rawurlencode($key);
		$esc    = htmlspecialchars((string) ($url), ENT_QUOTES);

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

	/**
	 * Le journal des erreurs, lu depuis l'administration (2026-10-02). Il fallait jusque-là lire
	 * `logs/php.log` par SSH ou FTP ; l'administrateur voit maintenant ce qui a échoué, regroupé, du
	 * plus récent au plus ancien, et retrouve une erreur par la référence que le visiteur a lue sur
	 * la page d'erreur. Ce qui est sensible (chemins, mots de passe, clés, e-mails, adresses IP) est
	 * masqué à l'écran ; le fichier entier se télécharge. Réservé à l'administrateur.
	 */
	public function _journal()
	{
		$this->_administrateur_seulement();

		require_once NEOFRAG_CMS.'/neofrag/helpers/journal.php';

		$this->title($this->lang('Journal des erreurs'))->icon('fas fa-file-medical-alt');

		$fichier   = NEOFRAG_CMS.'/logs/php.log';
		$gravites  = ['fatale' => $this->lang('Fatales'), 'erreur' => $this->lang('Erreurs'), 'avertissement' => $this->lang('Avertissements'), 'information' => $this->lang('Informations')];
		$etiquettes = ['fatale' => $this->lang('Fatale'), 'erreur' => $this->lang('Erreur'), 'avertissement' => $this->lang('Avertissement'), 'information' => $this->lang('Information')];
		$periodes  = ['24h' => $this->lang('Dernières 24 heures'), '7j' => $this->lang('7 derniers jours'), 'tout' => $this->lang('Tout ce qui est lu')];
		$filtre    = isset($gravites[$_GET['gravite'] ?? '']) ? (string) $_GET['gravite'] : '';
		$periode   = isset($periodes[$_GET['periode'] ?? '']) ? (string) $_GET['periode'] : '7j';
		$recherche = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 100));
		$depuis    = ['24h' => time() - 86400, '7j' => time() - 7 * 86400, 'tout' => 0][$periode];

		$entrees = array_values(array_filter(nf_journal_entrees(nf_journal_fin($fichier)), static fn (array $e): bool => ($e['date'] ?? 0) >= $depuis));
		$comptes = array_fill_keys(array_keys($gravites), 0);
		$retenues = [];

		foreach ($entrees as $entree)
		{
			$gravite = nf_journal_gravite($entree['texte']);
			$comptes[$gravite]++;

			if (($filtre === '' || $filtre === $gravite) && ($recherche === '' || mb_stripos($entree['texte'], $recherche) !== FALSE))
			{
				$retenues[] = $entree;
			}
		}

		$groupes = [];

		foreach (array_slice(nf_journal_regrouper($retenues, NEOFRAG_CMS), 0, 100) as $g)
		{
			$groupes[] = [
				'gravite'    => nf_journal_gravite($g['exemple']),
				'message'    => nf_journal_masquer($g['message'], NEOFRAG_CMS),
				'nombre'     => $g['nombre'],
				'dernier'    => $g['dernier'] !== NULL ? time_span($g['dernier']) : '',
				'references' => $g['references'],
				'exemple'    => nf_journal_masquer($g['exemple'], NEOFRAG_CMS),
			];
		}

		clearstatcache(TRUE, $fichier);
		$existe  = is_file($fichier);
		$etat    = '<p class="small text-body-secondary mb-3">'
			.($existe
				? $this->lang('Fichier %s : %s, dernière écriture %s. Seuls ses 2 derniers Mo sont lus ; les chemins, mots de passe, clés, adresses e-mail et adresses IP sont masqués à l’écran.', '<code>logs/php.log</code>', $this->_format_bytes((int) filesize($fichier)), time_span((int) filemtime($fichier)))
				: $this->lang('Aucune erreur n’a encore été enregistrée : le fichier %s n’existe pas.', '<code>logs/php.log</code>'))
			.'</p>';

		if (!is_writable(is_dir(NEOFRAG_CMS.'/logs') ? NEOFRAG_CMS.'/logs' : NEOFRAG_CMS))
		{
			$etat .= '<div class="alert alert-danger small">'.$this->lang('Le dossier %s n’est pas inscriptible : les erreurs du site ne sont plus enregistrées. Donnez-lui les droits d’écriture du serveur web.', '<code>logs</code>').'</div>';
		}

		$actions = $existe
			? '<a class="btn btn-outline-secondary btn-sm" href="'.url('admin/monitoring/journal/telecharger').'"><i class="fas fa-download"></i> '.$this->lang('Télécharger').'</a>'
			 .' <a class="btn btn-outline-danger btn-sm" href="'.$this->csrf_url('admin/monitoring/journal/vider').'" data-confirm="'.htmlspecialchars((string) $this->lang('Vider le journal ? L’actuel est gardé à côté (logs/php.log.1), à la place du précédent.'), ENT_QUOTES).'"><i class="far fa-trash-alt"></i> '.$this->lang('Vider').'</a>'
			: '';

		return $this->admin_back('admin/monitoring')
			.$this->admin_card('fas fa-file-medical-alt', $this->lang('Journal des erreurs'), $etat.$this->view('journal', [
				'groupes'   => $groupes,
				'gravites'  => $gravites,
				'etiquettes' => $etiquettes,
				'comptes'   => $comptes,
				'filtre'    => $filtre,
				'periode'   => $periode,
				'periodes'  => $periodes,
				'recherche' => $recherche,
				'vide'      => $this->admin_empty('fas fa-check-circle', (string) $this->lang('Rien à signaler'), (string) $this->lang('Aucune entrée ne correspond, sur la période choisie.')),
			]), '', $actions);
	}

	/** Le journal entier, tel quel : l'administrateur le garde, ou le transmet à qui l'aide. */
	public function _journal_telecharger()
	{
		$this->_administrateur_seulement();

		$fichier = NEOFRAG_CMS.'/logs/php.log';

		if (!is_file($fichier))
		{
			redirect('admin/monitoring/journal');
		}

		header('Content-Type: text/plain; charset=UTF-8');
		header('Content-Disposition: attachment; filename="php-'.date('Ymd-Hi').'.log"');
		header('Content-Length: '.filesize($fichier));
		readfile($fichier);
		exit;
	}

	/**
	 * Vide le journal en gardant l'actuel à côté (`logs/php.log.1`, à la place du précédent) : on repart
	 * d'un journal propre sans rien perdre de ce qui vient d'arriver.
	 */
	public function _journal_vider()
	{
		$this->_administrateur_seulement();
		$this->check_csrf('admin/monitoring/journal');

		$fichier = NEOFRAG_CMS.'/logs/php.log';

		if (is_file($fichier) && @rename($fichier, $fichier.'.1'))
		{
			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('monitoring.journal.vide', ['details' => 'logs/php.log → logs/php.log.1']);
			notify($this->lang('Journal vidé : l’ancien est gardé dans logs/php.log.1.'));
		}
		else
		{
			notify($this->lang('Le journal n’a pas pu être vidé : vérifiez les droits d’écriture du dossier logs.'), 'danger');
		}

		redirect('admin/monitoring/journal');
	}

	/**
	 * Carte « Diagnostic » : les trois outils qui ne se réglaient que dans `config/neofrag.php`, par FTP —
	 * le mode débogage, la trace des pages, le relevé des traductions —, allumés ici pour une heure
	 * (demandé, 2026-10-02). Allumé dans le fichier, un outil ne s'éteint que là.
	 */
	private function _diagnostic_card(): string
	{
		$traductions = (int) $this->db->select('COUNT(*)')->from('nf_log_i18n')->row();
		$lignes      = '';

		foreach ($this->_diagnostics() as $outil => [$icone, $titre, $texte])
		{
			$jusqua = nf_diagnostic_jusqua($outil);

			if (nf_diagnostic_a_demeure($outil))
			{
				$etat   = '<span class="badge text-bg-warning">'.$this->lang('Allumé').'</span>';
				$note   = $this->lang('Il est allumé dans %s : il ne s’éteint que là.', '<code>config/neofrag.php</code>');
				$bouton = '';
			}
			else if ($jusqua > time())
			{
				$minutes = (int) ceil(($jusqua - time()) / 60);
				$etat    = '<span class="badge text-bg-warning">'.$this->lang('Allumé').'</span>';
				$note    = $this->lang('Il s’éteindra tout seul dans %d minute.|Il s’éteindra tout seul dans %d minutes.', $minutes, $minutes);
				$bouton  = '<a class="btn btn-outline-secondary btn-sm" href="'.$this->csrf_url('admin/monitoring/diagnostic/'.$outil.'/eteindre').'"><i class="fas fa-power-off"></i> '.$this->lang('Éteindre').'</a>';
			}
			else
			{
				$etat   = '<span class="badge text-bg-secondary">'.$this->lang('Éteint').'</span>';
				$note   = '';
				$bouton = '<a class="btn btn-outline-primary btn-sm" href="'.$this->csrf_url('admin/monitoring/diagnostic/'.$outil.'/allumer').'"><i class="'.$icone.'"></i> '.$this->lang('Allumer pour une heure').'</a>';
			}

			$lien = [
				'debogage'    => '',
				'trace'       => '<a class="small" href="'.url('admin/monitoring/trace').'">'.$this->lang('Lire la trace').'</a>',
				'traductions' => '<a class="small" href="'.url('admin/monitoring/traductions').'">'.$this->lang('Traductions manquantes').' ('.$traductions.')</a>',
			][$outil];

			$lignes .= '<div'.($lignes !== '' ? ' class="border-top pt-3 mt-3"' : '').'>'
				.'<div class="d-flex justify-content-between align-items-center gap-2 mb-1"><strong><i class="'.$icone.'"></i> '.$titre.'</strong>'.$etat.'</div>'
				.'<p class="mb-2">'.$texte.'</p>'
				.($note !== '' ? '<p class="text-body-secondary mb-2">'.$note.'</p>' : '')
				.(($bouton.$lien) !== '' ? '<div class="d-flex flex-wrap align-items-center gap-2">'.$bouton.($lien !== '' ? '<span class="ms-auto">'.$lien.'</span>' : '').'</div>' : '')
				.'</div>';
		}

		return '<div class="card">'
			.'<div class="nf-card-header"><span><i class="fas fa-stethoscope"></i> '.$this->lang('Diagnostic').'</span></div>'
			.'<div class="card-body small">'.$lignes.'</div>'
			.'</div>';
	}

	/** @return array<string, array{0: string, 1: string, 2: string}> l'icône, le nom et ce que fait chaque outil */
	private function _diagnostics(): array
	{
		return [
			'debogage'    => ['fas fa-bug', (string) $this->lang('Mode débogage'), (string) $this->lang('La barre de débogage en bas de page (requêtes, temps, mémoire) et le détail des erreurs, pour les administrateurs connectés seulement : les visiteurs voient le site normal.')],
			'trace'       => ['fas fa-shoe-prints', (string) $this->lang('Trace des pages'), (string) $this->lang('Chaque page servie, avec ses requêtes à la base et leur durée, dans %s. Volumineuse : le temps d’un diagnostic.', '<code>logs/neofrag.log</code>')],
			'traductions' => ['fas fa-language', (string) $this->lang('Relevé des traductions'), (string) $this->lang('Note les textes qui n’ont pas de traduction dans la langue de la visite. Les administrateurs voient le drapeau de chaque texte traduit : un texte sans drapeau n’est pas traduit.')],
		];
	}

	/** Allume un outil de diagnostic pour une heure, ou l'éteint. */
	public function _diagnostic($outil, $action)
	{
		$this->_administrateur_seulement();
		$this->check_csrf('admin/monitoring');

		$outils = $this->_diagnostics();

		if (!isset($outils[$outil]))
		{
			redirect('admin/monitoring');
		}

		$titre = $outils[$outil][1];

		if ($action === 'allumer')
		{
			if (nf_diagnostic_regler($outil, time() + 3600))
			{
				(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('monitoring.'.$outil.'.allume', ['details' => '1 h']);
				notify($this->lang('%s : en service pour une heure.', $titre));
			}
			else
			{
				notify($this->lang('Impossible d’allumer « %s » : vérifiez les droits d’écriture du dossier cache.', $titre), 'danger');
			}
		}
		else
		{
			nf_diagnostic_regler($outil, 0);
			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('monitoring.'.$outil.'.eteint');
			notify($this->lang('%s : hors service.', $titre));
		}

		redirect('admin/monitoring');
	}

	/**
	 * La trace des pages, lue depuis l'administration : les dernières pages servies, la plus récente
	 * d'abord, chacune avec ses requêtes à la base et leur durée. Ce qui est sensible — les valeurs
	 * passées aux requêtes, les chemins, les adresses — est masqué à l'écran ; le fichier se télécharge.
	 */
	public function _trace()
	{
		$this->_administrateur_seulement();

		require_once NEOFRAG_CMS.'/neofrag/helpers/journal.php';

		$this->title($this->lang('Trace des pages'))->icon('fas fa-shoe-prints');

		$fichier   = NEOFRAG_CMS.'/logs/neofrag.log';
		$recherche = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 100));
		$lu        = 2097152;
		$pages     = nf_trace_pages(nf_journal_fin($fichier, $lu));

		clearstatcache(TRUE, $fichier);
		$existe = is_file($fichier);

		// Le début de la lecture tombe au milieu d'une page : elle est incomplète, on la laisse.
		if ($existe && filesize($fichier) > $lu)
		{
			array_shift($pages);
		}

		$pages = array_reverse($pages);

		if ($recherche !== '')
		{
			$pages = array_values(array_filter($pages, static fn (array $p): bool => mb_stripos($p['titre'], $recherche) !== FALSE));
		}

		$affichees = [];

		foreach (array_slice($pages, 0, 50) as $p)
		{
			$affichees[] = [
				'titre'    => $p['titre'] !== '' ? nf_journal_masquer($p['titre'], NEOFRAG_CMS) : (string) $this->lang('Page sans adresse notée'),
				'date'     => $p['date'] !== NULL ? time_span($p['date']) : '',
				'requetes' => $p['requetes'],
				'duree'    => $p['duree'],
				'memoire'  => $p['memoire'],
				'texte'    => implode("\n", array_map(static fn (string $l): string => nf_trace_masquer($l, NEOFRAG_CMS), $p['lignes'])),
			];
		}

		$etat = '<p class="small text-body-secondary mb-3">'
			.($existe
				? $this->lang('Fichier %s : %s, dernière écriture %s. Seuls ses 2 derniers Mo sont lus ; les valeurs passées aux requêtes, les chemins et les adresses sont masqués à l’écran.', '<code>logs/neofrag.log</code>', $this->_format_bytes((int) filesize($fichier)), time_span((int) filemtime($fichier)))
				: $this->lang('Aucune page n’a encore été tracée : allumez la trace dans Monitoring → Diagnostic.'))
			.'</p>';

		if ($existe && !nf_trace_active())
		{
			$etat .= '<div class="alert alert-info small">'.$this->lang('La trace est éteinte : ce fichier montre les dernières pages tracées, rien de plus récent.').'</div>';
		}

		$actions = $existe
			? '<a class="btn btn-outline-secondary btn-sm" href="'.url('admin/monitoring/trace/telecharger').'"><i class="fas fa-download"></i> '.$this->lang('Télécharger').'</a>'
			 .' <a class="btn btn-outline-danger btn-sm" href="'.$this->csrf_url('admin/monitoring/trace/vider').'" data-confirm="'.htmlspecialchars((string) $this->lang('Vider la trace ? L’actuelle est gardée à côté (logs/neofrag.log.1), à la place de la précédente.'), ENT_QUOTES).'"><i class="far fa-trash-alt"></i> '.$this->lang('Vider').'</a>'
			: '';

		return $this->admin_back('admin/monitoring')
			.$this->admin_card('fas fa-shoe-prints', $this->lang('Trace des pages'), $etat.$this->view('trace', [
				'pages'     => $affichees,
				'recherche' => $recherche,
				'vide'      => $this->admin_empty('fas fa-shoe-prints', (string) $this->lang('Aucune page'), (string) $this->lang('Aucune page tracée ne correspond.')),
			]), '', $actions);
	}

	/** La trace entière, telle quelle. */
	public function _trace_telecharger()
	{
		$this->_administrateur_seulement();

		$fichier = NEOFRAG_CMS.'/logs/neofrag.log';

		if (!is_file($fichier))
		{
			redirect('admin/monitoring/trace');
		}

		header('Content-Type: text/plain; charset=UTF-8');
		header('Content-Disposition: attachment; filename="trace-'.date('Ymd-Hi').'.log"');
		header('Content-Length: '.filesize($fichier));
		readfile($fichier);
		exit;
	}

	/** Vide la trace en gardant l'actuelle à côté (`logs/neofrag.log.1`, à la place de la précédente). */
	public function _trace_vider()
	{
		$this->_administrateur_seulement();
		$this->check_csrf('admin/monitoring/trace');

		$fichier = NEOFRAG_CMS.'/logs/neofrag.log';

		if (is_file($fichier) && @rename($fichier, $fichier.'.1'))
		{
			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('monitoring.trace.vide', ['details' => 'logs/neofrag.log → logs/neofrag.log.1']);
			notify($this->lang('Trace vidée : l’ancienne est gardée dans logs/neofrag.log.1.'));
		}
		else
		{
			notify($this->lang('La trace n’a pas pu être vidée : vérifiez les droits d’écriture du dossier logs.'), 'danger');
		}

		redirect('admin/monitoring/trace');
	}

	/**
	 * Les traductions manquantes que le relevé a notées (`nf_log_i18n`) : par langue, le texte d'origine
	 * et l'extension qui l'emploie — de quoi compléter un fichier de langue sans chercher.
	 */
	public function _traductions()
	{
		$this->_administrateur_seulement();

		$this->title($this->lang('Traductions manquantes'))->icon('fas fa-language');

		$lignes     = $this->db->select('language', 'key', 'locale', 'file')->from('nf_log_i18n')->order_by('language', 'file', 'id')->get();
		$par_langue = [];

		foreach ($lignes as $l)
		{
			// `file` est la classe qui a demandé le texte : NF\Modules\Forum\Forum → modules/forum.
			$parties = explode('\\', (string) $l['file']);
			$origine = count($parties) >= 3 && $parties[0] === 'NF' ? strtolower($parties[1]).'/'.strtolower($parties[2]) : (string) $l['file'];

			$par_langue[(string) $l['language']][] = [
				'texte'   => (string) $l['locale'],
				'cle'     => (string) $l['key'],
				'origine' => $origine,
			];
		}

		$etat = '<p class="small text-body-secondary mb-3">'
			.(nf_traductions_actives()
				? $this->lang('Le relevé est allumé : chaque texte demandé sans traduction s’ajoute ici.')
				: $this->lang('Le relevé est éteint : allumez-le dans Monitoring → Diagnostic, puis parcourez le site dans la langue à vérifier.'))
			.'</p>';

		$actions = $lignes
			? '<a class="btn btn-outline-danger btn-sm" href="'.$this->csrf_url('admin/monitoring/traductions/vider').'" data-confirm="'.htmlspecialchars((string) $this->lang('Vider la liste des traductions manquantes ?'), ENT_QUOTES).'"><i class="far fa-trash-alt"></i> '.$this->lang('Vider').'</a>'
			: '';

		$corps = '';

		foreach ($par_langue as $langue => $textes)
		{
			$corps .= '<h3 class="h6 mt-3">'.htmlspecialchars(strtoupper($langue)).' <span class="badge text-bg-secondary">'.count($textes).'</span></h3>'
				.'<div class="table-responsive"><table class="table table-sm small align-middle"><thead><tr><th>'.$this->lang('Texte d’origine').'</th><th>'.$this->lang('Extension').'</th><th>'.$this->lang('Clé').'</th></tr></thead><tbody>';

			foreach ($textes as $t)
			{
				$corps .= '<tr><td style="overflow-wrap:anywhere">'.htmlspecialchars($t['texte'], ENT_QUOTES, 'UTF-8', FALSE).'</td><td><code>'.htmlspecialchars($t['origine']).'</code></td><td><code>'.htmlspecialchars($t['cle']).'</code></td></tr>';
			}

			$corps .= '</tbody></table></div>';
		}

		return $this->admin_back('admin/monitoring')
			.$this->admin_card('fas fa-language', $this->lang('Traductions manquantes'), $etat.($corps !== '' ? $corps : $this->admin_empty('fas fa-check-circle', (string) $this->lang('Rien à signaler'), (string) $this->lang('Aucune traduction manquante n’a été notée.'))), '', $actions);
	}

	public function _traductions_vider()
	{
		$this->_administrateur_seulement();
		$this->check_csrf('admin/monitoring/traductions');

		$this->db->execute('DELETE FROM nf_log_i18n');
		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('monitoring.traductions.vide');
		notify($this->lang('Liste des traductions manquantes vidée.'));
		redirect('admin/monitoring/traductions');
	}

	/**
	 * Carte « Adresse du site » : l'adresse enregistrée dans `config/url.php`, sur laquelle se construisent
	 * les liens des courriels (mot de passe oublié, validation), les retours des connexions externes et
	 * les partages. Après un changement de domaine, elle restait l'ancienne tant qu'on ne modifiait pas ce
	 * fichier par FTP ; la carte le signale, et la remplace en un clic par l'adresse de la visite.
	 */
	private function _adresse_card(): string
	{
		[$enregistree, $actuelle] = $this->_adresses();

		if ($enregistree !== '' && ($actuelle === '' || strcasecmp($enregistree, $actuelle) === 0))
		{
			return '<div class="card">'
				.'<div class="nf-card-header"><span><i class="fas fa-globe"></i> '.$this->lang('Adresse du site').'</span><span class="badge text-bg-success">'.$this->lang('À jour').'</span></div>'
				.'<div class="card-body small"><p class="mb-0"><code>'.htmlspecialchars($enregistree).'</code> — '.$this->lang('les liens des courriels et des partages pointent ici.').'</p></div>'
				.'</div>';
		}

		$texte = $enregistree === ''
			? $this->lang('Aucune adresse n’est enregistrée : les liens des courriels se construisent sur l’adresse demandée par chaque visiteur, qu’un tiers peut falsifier.')
			: $this->lang('L’adresse enregistrée, %s, n’est pas celle par laquelle vous consultez le site : les liens des courriels, des connexions externes et des partages y mènent encore.', '<code>'.htmlspecialchars($enregistree).'</code>');

		$bouton = $actuelle !== '' && !nf_demo()
			? '<a class="btn btn-outline-primary btn-sm" href="'.$this->csrf_url('admin/monitoring/adresse').'" data-confirm="'.htmlspecialchars((string) $this->lang('Enregistrer %s comme adresse du site ?', $actuelle), ENT_QUOTES).'"><i class="fas fa-check"></i> '.$this->lang('Utiliser %s', htmlspecialchars($actuelle)).'</a>'
			: '';

		return '<div class="card">'
			.'<div class="nf-card-header"><span><i class="fas fa-globe"></i> '.$this->lang('Adresse du site').'</span><span class="badge text-bg-warning">'.$this->lang('À vérifier').'</span></div>'
			.'<div class="card-body small"><p class="mb-'.($bouton !== '' ? '3' : '0').'">'.$texte.'</p>'.$bouton.'</div>'
			.'</div>';
	}

	/**
	 * L'adresse enregistrée et celle de la visite (« https://exemple.fr »). Celle de la visite est vide
	 * quand on consulte le site en local (127.0.0.1, localhost) : ce n'est pas une adresse à enregistrer.
	 *
	 * @return array{0: string, 1: string}
	 */
	private function _adresses(): array
	{
		$url = [];

		if (is_file($fichier = NEOFRAG_CMS.'/config/url.php'))
		{
			include $fichier;
		}

		$enregistree = rtrim((string) ($url['site'] ?? ''), '/');
		$actuelle    = \NF\NeoFrag\Installer::request_origin($_SERVER);
		$hote        = strtolower((string) parse_url($actuelle, PHP_URL_HOST));

		if ($hote === '' || in_array(trim($hote, '[]'), ['localhost', '127.0.0.1', '::1'], TRUE) || str_ends_with($hote, '.localhost'))
		{
			$actuelle = '';
		}

		return [$enregistree, $actuelle];
	}

	/** Enregistre l'adresse de la visite comme adresse du site (`config/url.php`). */
	public function _adresse()
	{
		$this->_administrateur_seulement();
		$this->check_csrf('admin/monitoring');
		$this->_require_sudo();

		if (nf_demo())
		{
			notify($this->lang('Action désactivée sur le site de démonstration.'), 'warning');
			redirect('admin/monitoring');
		}

		[$enregistree, $actuelle] = $this->_adresses();

		if ($actuelle === '')
		{
			redirect('admin/monitoring');
		}

		try
		{
			\NF\NeoFrag\Installer::write_site_url(NEOFRAG_CMS.'/config', $actuelle);

			if (function_exists('opcache_invalidate'))
			{
				@opcache_invalidate(NEOFRAG_CMS.'/config/url.php', TRUE);
			}

			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('monitoring.adresse', ['details' => ($enregistree !== '' ? $enregistree : '—').' → '.$actuelle]);
			notify($this->lang('Adresse du site enregistrée : %s.', $actuelle));
		}
		catch (\RuntimeException $e)
		{
			notify($this->lang('L’adresse n’a pas pu être enregistrée : le fichier %s n’est pas inscriptible.', 'config/url.php'), 'danger');
		}

		redirect('admin/monitoring');
	}

	private function _administrateur_seulement(): void
	{
		if (!$this->access->effective_admin())
		{
			notify($this->lang('Action réservée à l\'administrateur.'), 'danger');
			redirect('admin/monitoring');
		}
	}

	/** Carte « Sécurité webmaster » : état + formulaire définir/changer le mot de passe sudo. */
	private function _webmaster_card()
	{
		$wm         = new \NF\NeoFrag\Libraries\Webmaster($this);
		$configured = $wm->is_configured();
		$csrf       = htmlspecialchars((string) ($this->_csrf_token()), ENT_QUOTES);

		$status = $configured
			? '<span class="badge text-bg-success">'.$this->lang('Défini').'</span>'
			: '<span class="badge text-bg-danger">'.$this->lang('Non défini').'</span>';

		$intro = $configured
			? '<p class="text-muted mb-2">'.$this->lang('Garde les actions sensibles (édition de fichiers, suppressions…). Pour le changer, saisis l\'actuel.').'</p>'
			: '<div class="alert alert-warning mb-2"><i class="fas fa-exclamation-triangle"></i> '.$this->lang('Aucun mot de passe webmaster. Définis-en un pour débloquer les actions sensibles.').'</div>';

		$current = $configured
			? '<input type="password" name="current" class="form-control form-control-sm mb-2" autocomplete="off" placeholder="'.htmlspecialchars((string) ($this->lang('Mot de passe webmaster actuel')), ENT_QUOTES).'" required>'
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
			.'<input type="password" name="password" class="form-control form-control-sm mb-2" autocomplete="new-password" placeholder="'.htmlspecialchars((string) ($this->lang('Nouveau mot de passe (8 car. min., distinct du login)')), ENT_QUOTES).'" required>'
			.'<input type="password" name="password2" class="form-control form-control-sm mb-2" autocomplete="new-password" placeholder="'.htmlspecialchars((string) ($this->lang('Confirmation')), ENT_QUOTES).'" required>'
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

	/**
	 * Remet le site dans l'état d'une sauvegarde choisie dans la liste.
	 *
	 * Le pendant manuel du retour arrière automatique d'une mise à jour ratée : même code, même
	 * contrat (cf. Monitoring\Models\Monitoring::restaurer). Il sert quand l'échec n'est pas venu
	 * d'une mise à jour — un module tiers qui casse tout, une manipulation regrettée.
	 *
	 * Gardes identiques à la suppression : super-admin, fenêtre sudo ouverte, jeton CSRF. Une
	 * restauration est au moins aussi destructrice qu'une suppression.
	 */
	public function _backup_restore($filename)
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

		if (!preg_match('/^\d{14}(-[a-f0-9]{16})?$/', $slug) || !file_exists($file))
		{
			notify($this->lang('Sauvegarde introuvable.'), 'danger');
			redirect('admin/monitoring');
		}

		// Une restauration réécrit des milliers de fichiers puis réimporte la base : elle dépasse
		// sans peine le temps d'exécution par défaut.
		@set_time_limit(0);

		try
		{
			$resultat = $this->model()->restaurer($file);

			// Pas de forme singulière : une archive de site porte des milliers de fichiers. Une
			// alternance « fichier|fichiers » n'aurait ici qu'un cas, et il serait faux pour l'autre
			// nombre de la même phrase.
			notify($this->lang(
				'Sauvegarde restaurée : %d fichiers remis en place, %d vestiges retirés, base réimportée.',
				$resultat['restored'],
				$resultat['removed']
			));
		}
		catch (\Throwable $e)
		{
			error_log('[restore] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());

			notify($this->lang('La restauration a échoué : %s', $e->getMessage()), 'danger');
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
		$h .= '<div class="nf-stat-label"><i class="'.$icon.'"></i> '.htmlspecialchars((string) ($label)).'</div>';
		$h .= '<div class="nf-stat-value" style="font-size:18px;font-family:\'JetBrains Mono\',monospace;letter-spacing:0;">'.htmlspecialchars((string) ($value)).'</div>';
		if ($trend) $h .= '<div class="nf-stat-trend">'.htmlspecialchars((string) ($trend)).'</div>';
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

		require_once NEOFRAG_CMS.'/neofrag/installer.php';

		/*
		 * Ce qu'apporte la version. Le manifeste de NeoFrag d'origine portait ses « nouveautés »
		 * (`features`) ; celui de NeoFrag Reborn n'en a pas, et la fenêtre montrait un bloc vide en
		 * laissant un avertissement au journal à chaque mise à jour (relevé le 2026-10-02). Elle dit
		 * maintenant ce qui va se passer, et renvoie au journal des versions.
		 */
		$nouveautes = '<p class="mb-1">'.$this->lang('NeoFrag %s est disponible.', utf8_htmlentities((string) $version->version)).'</p>'
			.'<p class="text-muted small mb-0">'.$this->lang('Une sauvegarde complète du site est faite avant de commencer ; en cas d’échec, le site revient à son état d’avant.')
			.' <a href="'.\NF\NeoFrag\Installer::CHANGELOG_URL.'" target="_blank" rel="noopener">'.$this->lang('Ce qu’apporte cette version').'</a></p>';

		return $this->modal($this->lang('Mise à jour de NeoFrag'), 'fas fa-rocket')
					->large()
					->set_id('modal-update')
					->body('<div class="update-features">
								'.$nouveautes.'
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
