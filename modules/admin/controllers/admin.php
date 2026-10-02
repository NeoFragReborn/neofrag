<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Modern dashboard v0.4 — sober stat-cards + activity + system state.
 *
 * couplage(articles): le flux d'activite du tableau de bord agrege le contenu recent de TOUS les
 * modules presents. Chaque requete est dans son propre try/catch : un module absent fait
 * simplement disparaitre sa ligne du flux, il n'interrompt pas le tableau de bord.
 * couplage(bugtracker): idem — meme flux, meme try/catch.
 */

namespace NF\Modules\Admin\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		if (!$this->access->effective_admin())
		{
			return '';
		}

		$this->title($this->lang('Tableau de bord'));

		$stats    = $this->_collect_stats();
		$activity = $this->_collect_activity();
		$system   = $this->_collect_system();
		$notifs   = $this->_collect_notifications();

		$html = '';

		// Stat cards
		$html .= '<div class="nf-stats-grid">';
		foreach ($stats as $s)
		{
			$html .= '<div class="nf-stat-card">';
			// `label` porte un objet de traduction differee, que `htmlspecialchars((string) ())` refuse.
			$html .= '<div class="nf-stat-label"><i class="'.$s['icon'].'"></i> '.htmlspecialchars((string) $s['label']).'</div>';
			$html .= '<div class="nf-stat-value">'.$s['value'].'</div>';
			if (!empty($s['trend']))
			{
				$cls = $s['trend_class'] ?? '';
				$ic  = $s['trend_icon'] ?? '';
				$html .= '<div class="nf-stat-trend '.$cls.'">';
				if ($ic) $html .= '<i class="'.$ic.'"></i> ';
				$html .= htmlspecialchars((string) ($s['trend']));
				$html .= '</div>';
			}
			$html .= '</div>';
		}
		$html .= '</div>';

		// Quick actions
		$qa = $this->_quick_actions();
		if (!empty($qa))
		{
			$html .= '<div class="nf-quick-actions">';
			foreach ($qa as $a)
			{
				$html .= '<a class="nf-quick-action" href="'.url($a['url']).'">';
				$html .= '<div class="nf-quick-action-icon"><i class="'.$a['icon'].'"></i></div>';
				$html .= '<div>';
				$html .= '<div class="nf-quick-action-title">'.htmlspecialchars((string) ($a['title'])).'</div>';
				$html .= '<div class="nf-quick-action-desc">'.htmlspecialchars((string) ($a['desc'])).'</div>';
				$html .= '</div></a>';
			}
			$html .= '</div>';
		}

		// Split (activity + system)
		$html .= '<div class="nf-split">';

		// Activity
		$html .= '<div class="card"><div class="card-header"><span><i class="far fa-clock"></i> '.$this->lang('Activité récente').'</span></div>';
		if (empty($activity))
		{
			$html .= '<div class="nf-empty"><i class="far fa-clock"></i>'.htmlspecialchars((string) ($this->lang('Aucune activité enregistrée pour le moment.'))).'</div>';
		}
		else
		{
			$html .= '<ul class="nf-activity-list">';
			foreach ($activity as $a)
			{
				$html .= '<li class="nf-activity-item">';
				$html .= '<div class="nf-activity-icon '.$a['kind'].'"><i class="'.$a['icon'].'"></i></div>';
				$html .= '<div class="nf-activity-body">';
				$html .= '<div class="nf-activity-title">'.$a['title'].'</div>';
				$html .= '<div class="nf-activity-meta">'.$a['meta'].'</div>';
				$html .= '</div></li>';
			}
			$html .= '</ul>';
		}
		$html .= '</div>';

		// System + notifications column
		$html .= '<div>';

		$html .= '<div class="card"><div class="card-header"><span><i class="fas fa-bolt"></i> '.$this->lang('État du système').'</span></div>';
		$html .= '<div style="padding:0 18px;">';
		foreach ($system as $row)
		{
			$html .= '<div class="nf-system-row">';
			$html .= '<span class="nf-system-label">'.htmlspecialchars((string) ($row['label'])).'</span>';
			if (!empty($row['badge']))
			{
				$html .= '<span class="badge '.$row['badge_class'].'">'.htmlspecialchars((string) ($row['badge'])).'</span>';
			}
			else
			{
				$cls = !empty($row['mono']) ? 'nf-system-value mono' : 'nf-system-value';
				$html .= '<span class="'.$cls.'">'.htmlspecialchars((string) ($row['value'])).'</span>';
			}
			$html .= '</div>';
		}
		$html .= '</div></div>';

		if (!empty($notifs))
		{
			$html .= '<div class="card"><div class="card-header"><span><i class="far fa-bell"></i> '.$this->lang('À traiter').'</span></div>';
			$html .= '<div style="padding:0;">';
			foreach ($notifs as $n)
			{
				$html .= '<div style="padding:12px 18px;border-bottom:1px solid var(--nf-border);">';
				$html .= '<div style="font-size:13px;font-weight:500;margin-bottom:2px;">'.htmlspecialchars((string) ($n['title'])).'</div>';
				$html .= '<a href="'.url($n['url']).'" style="font-size:12px;">'.htmlspecialchars((string) ($n['action'])).' →</a>';
				$html .= '</div>';
			}
			$html .= '</div></div>';
		}

		$html .= '</div></div>'; // /col + /split

		// Chatbox staff (talk audience='staff') — visible uniquement par les admins
		$html .= $this->_render_staff_chatbox();

		return $html;
	}

	private function _render_staff_chatbox()
	{
		// Récupère le salon staff (audience='staff', type='public') ; si aucun, on saute le rendu.
		$staff_talk = $this->db	->select('talk_id', 'name')
								->from('nf_talks')
								->where('type', 'public')
								->where('audience', 'staff')
								->where('deleted_at', NULL)
								->order_by('talk_id')
								->row();
		if (!is_array($staff_talk) || empty($staff_talk['talk_id']))
		{
			return '';
		}

		$talk_id = (int)$staff_talk['talk_id'];
		$name    = $staff_talk['name'];

		// Auto-join silencieux pour cet admin (si jamais pas déjà participant)
		$is_participant = $this->db	->select('1')
									->from('nf_talks_participants')
									->where('talk_id', $talk_id)
									->where('user_id', (int)$this->user->id)
									->row();
		if (!$is_participant)
		{
			try
			{
				$this->module('talks')->model()->add_participant($talk_id, (int)$this->user->id, 'admin');
			}
			catch (\Throwable $e) {}
		}

		// 5 derniers messages
		$messages = $this->db	->select('m.message', 'm.date', 'u.username', 'u.id as user_id')
								->from('nf_talks_messages m')
								->join('nf_user u', 'u.id = m.user_id', 'LEFT')
								->where('m.talk_id', $talk_id)
								->where('m.deleted_at', NULL)
								->order_by('m.date DESC')
								->limit(5)
								->get();
		$messages = array_reverse($messages);

		$conv_url = url('talks/'.$talk_id.'/'.url_title($name));
		$send_url = url('talks/staff-chat-send');

		// TinyMCE 7 community (GPL) via CDN jsdelivr — pilote chatbox staff.
		// Stack : éditeur full-featured open source, plugins inclus (advlist, lists, link, image, charmap,
		// emoticons, codesample, table, media, wordcount, autolink, searchreplace, visualblocks, visualchars,
		// fullscreen, insertdatetime, code, help, preview).

		$html  = '<div class="card mt-3"><div class="card-header d-flex justify-content-between align-items-center">';
		$html .= '<span><i class="fas fa-user-shield"></i> '.htmlspecialchars((string) ($this->lang('Chatbox staff'))).' <small class="text-muted">— '.htmlspecialchars((string) ($name)).'</small></span>';
		$html .= '<a href="'.$conv_url.'" class="btn btn-sm btn-outline-primary"><i class="far fa-comment-dots"></i> '.htmlspecialchars((string) ($this->lang('Ouvrir la conversation'))).'</a>';
		$html .= '</div>';
		$html .= '<div id="nf-staff-chat-messages" style="padding:14px 18px; max-height:300px; overflow-y:auto;">';
		if (empty($messages))
		{
			$html .= '<div class="text-muted text-center" style="padding:20px;"><i class="far fa-comment fa-2x"></i><br>'.htmlspecialchars((string) ($this->lang('Aucun message pour l\'instant. Soyez le premier à écrire dans la chatbox staff.'))).'</div>';
		}
		else
		{
			foreach ($messages as $m)
			{
				$is_me = (int)$m['user_id'] === (int)$this->user->id;
				$html .= '<div style="margin-bottom:10px;text-align:'.($is_me ? 'right' : 'left').';">';
				$html .= '<small class="text-muted">'.htmlspecialchars((string) ($m['username'] ?? '?')).' · '.time_span(strtotime($m['date'])).'</small>';
				$html .= '<div style="display:inline-block;max-width:85%;padding:10px 14px;background:'.($is_me ? 'rgba(13,110,253,0.10)' : 'rgba(0,0,0,0.04)').';border-radius:12px;text-align:left;">';
				$html .= \NF\Modules\Talks\Security::render_staff_message((string)$m['message']);
				$html .= '</div></div>';
			}
		}
		$html .= '</div>';

		// Form TinyMCE : textarea native + tinymce.init pour la richesse
		$html .= '<form method="post" action="'.$send_url.'" style="padding:12px 18px;border-top:1px solid var(--nf-border);" id="nf-staff-chat-form">';
		$html .= '<textarea name="talk_message" id="nf-staff-chat-editor" placeholder="'.htmlspecialchars((string) ($this->lang('Écrire un message au staff…'))).'"></textarea>';
		$html .= '<div class="text-end mt-2"><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i> '.htmlspecialchars((string) ($this->lang('Envoyer'))).'</button></div>';
		$html .= '</form>';
		$html .= '</div>';

		// TinyMCE 7 GPL auto-hébergé (js/tinymce, servi en statique via .htaccess)
		$html .= '<script src="'.js('tinymce/tinymce.min.js').'"></script>';
		$html .= '<script>(function(){
			function init(){
				if (typeof tinymce === "undefined") { setTimeout(init, 100); return; }
				tinymce.init({
					selector: "#nf-staff-chat-editor",
					height: 240,
					menubar: false,
					branding: false,
					promotion: false,
					license_key: "gpl",
					
					plugins: "advlist autolink lists link image charmap preview anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media table emoticons codesample help",
					toolbar: "undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table codesample | emoticons charmap | searchreplace fullscreen | removeformat",
					content_style: "body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;font-size:14px;}",
					skin: (document.documentElement.getAttribute("data-theme") === "dark") ? "oxide-dark" : "oxide",
					content_css: (document.documentElement.getAttribute("data-theme") === "dark") ? "dark" : "default",
					setup: function(editor){
						editor.on("init", function(){
							var box = document.getElementById("nf-staff-chat-messages");
							if (box) box.scrollTop = box.scrollHeight;
						});
					}
				});
			}
			if (document.readyState === "loading") { document.addEventListener("DOMContentLoaded", init); } else { init(); }
		})();</script>';

		return $html;
	}

	private function _safe_count($table, array $where = [])
	{
		try {
			$q = $this->db->from($table);
			foreach ($where as $w) {
				if (count($w) === 1) $q->where($w[0]);
				else $q->where($w[0], $w[1]);
			}
			return (int)$q->count();
		} catch (\Throwable $e) { return 0; }
	}

	private function _collect_stats()
	{
		$out = [];

		// Visiteurs : sessions actives sur 30j
		$visitors = $this->_safe_count('nf_session', [
			['last_activity > DATE_SUB(NOW(), INTERVAL 30 DAY)']
		]);
		$out[] = [
			'label' => $this->lang('Visiteurs (30j)'),
			'icon'  => 'fas fa-users',
			'value' => number_format($visitors, 0, ',', ' '),
			'trend' => $this->lang('Sessions actives sur 30 jours'),
			'trend_class' => '',
			'trend_icon'  => ''
		];

		// Articles publiés + brouillons
		$articles = $this->_safe_count('nf_articles', [['published', TRUE]]);
		$drafts   = $this->_safe_count('nf_articles', [['published', FALSE]]);
		$out[] = [
			'label' => $this->lang('Articles publiés'),
			'icon'  => 'far fa-newspaper',
			'value' => number_format($articles, 0, ',', ' '),
			'trend' => $drafts > 0 ? $drafts.' '.$this->lang('brouillon|brouillons', $drafts) : $this->lang('Aucun brouillon'),
			'trend_class' => $drafts > 0 ? 'warn' : '',
			'trend_icon'  => $drafts > 0 ? 'fas fa-pencil-alt' : ''
		];

		// Commentaires
		$comments = $this->_safe_count('nf_comment');
		$out[] = [
			'label' => $this->lang('Commentaires'),
			'icon'  => 'far fa-comments',
			'value' => number_format($comments, 0, ',', ' '),
			'trend' => $this->lang('Total des commentaires postés'),
			'trend_class' => '',
			'trend_icon'  => ''
		];

		// Bugs ouverts (open + in_progress)
		$bugs_open    = $this->_safe_count('nf_bug_tickets', [['status', 'open']]);
		$bugs_progress = $this->_safe_count('nf_bug_tickets', [['status', 'in_progress']]);
		$bugs = $bugs_open + $bugs_progress;
		$critical = $this->_safe_count('nf_bug_tickets', [['status', 'open'], ['priority', 'critical']]);
		$out[] = [
			'label' => $this->lang('Bugs ouverts'),
			'icon'  => 'fas fa-bug',
			'value' => number_format($bugs, 0, ',', ' '),
			'trend' => $critical > 0 ? $this->lang('%d critique|%d critiques', $critical, $critical) : ($bugs === 0 ? $this->lang('Aucun bug en cours') : $this->lang('Aucun critique')),
			'trend_class' => $critical > 0 ? 'down' : ($bugs === 0 ? 'up' : ''),
			'trend_icon'  => $critical > 0 ? 'fas fa-exclamation-triangle' : ($bugs === 0 ? 'fas fa-check' : 'fas fa-clipboard-check')
		];

		return $out;
	}

	private function _quick_actions()
	{
		$out = [];

		$candidates = [
			['articles', 'fas fa-plus',      $this->lang('Nouvel article'),    $this->lang('Publier du contenu long'), 'admin/articles'],
			['pages',    'far fa-file',      $this->lang('Nouvelle page'),     $this->lang('Page statique'),           'admin/pages'],
			['media',    'fas fa-upload',    $this->lang('Uploader média'),    $this->lang('Image, fichier, vidéo'),   'admin/media'],
			['surveys',  'far fa-chart-bar', $this->lang('Lancer un sondage'), $this->lang('Demander l\'avis'),        'admin/surveys']
		];

		foreach ($candidates as $c)
		{
			try {
				$mod = $this->module($c[0]);
				if ($mod && method_exists($mod, 'is_authorized') && $mod->is_authorized())
				{
					$out[] = [
						'icon'  => $c[1],
						'title' => $c[2],
						'desc'  => $c[3],
						'url'   => $c[4]
					];
				}
			} catch (\Throwable $e) {}
		}

		return array_slice($out, 0, 4);
	}

	private function _collect_activity()
	{
		$out = [];

		/**
		 * Journal d'audit.
		 *
		 * La colonne `a.target` n'existe PAS : la table porte `target_type` et `target_id`, comme
		 * celle de la moderation. La requete echouait donc a chaque chargement du tableau de bord,
		 * `$rows` valait NULL, et le `foreach` qui suit levait un avertissement — apres quoi le
		 * `catch` renvoyait vers le repli. Resultat : le journal d'audit n'affichait JAMAIS rien,
		 * et le tableau de bord montrait a la place la liste de repli, sans que rien ne le dise.
		 *
		 * Le `catch (\Throwable)` en fin de bloc, pose pour le cas ou la table serait absente,
		 * masquait ainsi une erreur de requete permanente.
		 */
		try {
			$rows = $this->db->select('a.id', 'a.user_id', 'a.action', 'a.target_type', 'a.target_id', 'a.created_at', 'u.username')
				->from('nf_audit_log a')
				->join('nf_user u', 'u.id = a.user_id', 'LEFT')
				->where('(a.user_id IS NULL OR a.user_id != '.nf_compte_masque().')')   // le compte de secours d'une démonstration
				->order_by('a.id DESC')->limit(8)->get();
			foreach ($rows as $r)
			{
				$action = strtolower($r['action'] ?? '');
				$kind = 'edit'; $icon = 'fas fa-pen';
				if (strpos($action, 'create') !== FALSE || strpos($action, 'add') !== FALSE) { $kind = 'create'; $icon = 'fas fa-plus'; }
				elseif (strpos($action, 'delete') !== FALSE) { $kind = 'delete'; $icon = 'fas fa-trash'; }
				elseif (strpos($action, 'login') !== FALSE) { $kind = 'login'; $icon = 'fas fa-sign-in-alt'; }

				$author = $r['username']
					? '<strong>'.htmlspecialchars((string) ($r['username'])).'</strong>'
					: '<em>'.htmlspecialchars((string) ($this->lang('Anonyme'))).'</em>';

				$ts = !empty($r['created_at']) ? strtotime($r['created_at']) : time();

				// La cible se lit en deux morceaux : « theme » + « vitrine » -> « theme vitrine ».
				$cible = trim(($r['target_type'] ?? '').' '.($r['target_id'] ?? ''));

				$out[] = [
					'kind'  => $kind,
					'icon'  => $icon,
					// Le libellé de l'action (« Mode débogage allumé »), et non son identifiant technique.
					'title' => $author.' — '.htmlspecialchars((new \NF\NeoFrag\Libraries\Audit_Log($this))->libelle((string) $r['action'])).($cible !== '' ? ' <span style="color:var(--nf-text-muted)">'.htmlspecialchars((string) ($cible)).'</span>' : ''),
					'meta'  => time_span($ts)
				];
			}
			if (!empty($out)) return $out;
		} catch (\Throwable $e) { /* table absent */ }

		// Fallback: agréger derniers articles + inscriptions + commentaires
		try {
			$users = $this->db->select('id', 'username', 'registration_date')
				->from('nf_user')->where('deleted', FALSE)
				->order_by('id DESC')->limit(3)->get();
			foreach ($users as $u)
			{
				$ts = !empty($u['registration_date']) ? strtotime($u['registration_date']) : 0;
				if (!$ts) continue;
				$out[] = [
					'kind' => 'login', 'icon' => 'fas fa-user-plus',
					'title' => '<strong>'.htmlspecialchars((string) ($u['username'])).'</strong> — '.htmlspecialchars((string) ($this->lang('Inscription'))),
					'meta'  => $this->lang('Utilisateurs').' · '.time_span($ts),
					'_ts'   => $ts
				];
			}
		} catch (\Throwable $e) {}

		try {
			$rows = $this->db->select('a.article_id', 'al.title', 'a.date', 'u.username')
				->from('nf_articles a')
				->join_lang('nf_articles_lang al', 'article_id', 'a.article_id', NULL, 'LEFT')
				->join('nf_user u', 'u.id = a.user_id', 'LEFT')
				->where('a.published', TRUE)
				->order_by('a.date DESC')->limit(3)->get();
			foreach ($rows as $r)
			{
				$ts = !empty($r['date']) ? strtotime($r['date']) : time();
				$out[] = [
					'kind' => 'create', 'icon' => 'far fa-newspaper',
					'title' => '<strong>'.htmlspecialchars((string) ($r['username'] ?: $this->lang('Anonyme'))).'</strong> — '.htmlspecialchars((string) ($this->lang('Article publié'))).' : <em>'.htmlspecialchars((string) ($r['title'])).'</em>',
					'meta'  => $this->lang('Articles').' · '.time_span($ts),
					'_ts'   => $ts
				];
			}
		} catch (\Throwable $e) {}

		usort($out, function($a, $b) { return ($b['_ts'] ?? 0) <=> ($a['_ts'] ?? 0); });
		return array_slice($out, 0, 8);
	}

	private function _collect_system()
	{
		$rows = [];

		$rows[] = ['label' => $this->lang('Version NeoFrag'), 'value' => NEOFRAG_VERSION, 'mono' => TRUE];
		$rows[] = ['label' => 'PHP', 'value' => PHP_VERSION, 'mono' => TRUE];
		$rows[] = ['label' => $this->lang('Base de données'), 'value' => 'MariaDB/MySQL', 'mono' => FALSE];

		$cache_active = is_dir('cache') && is_writable('cache');
		$rows[] = [
			'label' => $this->lang('Cache'),
			'badge' => $cache_active ? $this->lang('Actif') : $this->lang('Inactif'),
			'badge_class' => $cache_active ? 'text-bg-success' : 'text-bg-danger'
		];

		$maintenance = !empty($this->config->nf_maintenance);
		$rows[] = [
			'label' => $this->lang('Maintenance'),
			'badge' => $maintenance ? $this->lang('Activée') : $this->lang('Désactivée'),
			'badge_class' => $maintenance ? 'text-bg-warning' : 'text-bg-secondary'
		];

		$online = $this->_safe_count('nf_session', [
			['last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)']
		]);
		$rows[] = ['label' => $this->lang('Connectés (5 min)'), 'value' => (string)$online];

		// Extensions PHP critiques : GD (redimensionnement/ré-encodage des images uploadées), Zip
		// (marketplace), Fileinfo (détection MIME magic-bytes à l'upload), cURL (appels réseau).
		foreach ([
			'gd'       => $this->lang('GD (images)'),
			'zip'      => $this->lang('Zip (marketplace)'),
			'fileinfo' => $this->lang('Fileinfo (upload)'),
			'curl'     => $this->lang('cURL (réseau)')
		] as $ext => $label)
		{
			$loaded = extension_loaded($ext);
			$rows[] = [
				'label'       => $label,
				'badge'       => $loaded ? $this->lang('Présente') : $this->lang('Absente'),
				'badge_class' => $loaded ? 'text-bg-success' : 'text-bg-danger'
			];
		}

		return $rows;
	}

	private function _collect_notifications()
	{
		// Carrefour « dashboard » : chaque module expose controllers/dashboard.php → dashboard()
		// qui retourne ses éléments « à traiter » ({title, action, url}).
		$out = [];

		foreach (NeoFrag()->model2('addon')->get('module') as $module)
		{
			if ($controller = @$module->controller('dashboard'))
			{
				foreach ($controller->dashboard() as $item)
				{
					$out[] = $item;
				}
			}
		}

		return $out;
	}

	public function help($module_name, $method)
	{
		if (($module = $this->module($module_name)) && ($help = @$module->controller('admin_help')) && $help->has_method($method))
		{
			$this->ajax();
			return call_user_func_array([$help, $method]);
		}
	}
}
