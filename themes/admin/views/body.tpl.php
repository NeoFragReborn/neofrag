<?php
/**
 * NeoFrag Reborn — admin. Layout à barre latérale (sidebar) : brand + sections
 * (accordéon) + profil ; zone principale = header slim (breadcrumb + actions) +
 * contenu. Remplace l'ancienne topbar à onglets. DA vitrine (navy + teal).
 */
$sidebar  = $this->__caller->data->get('sidebar') ?: [];
$sections = $sidebar['sections'] ?? [];
$cur_segs = is_array($this->url->segments ?? null) ? $this->url->segments : [];
$cur_path = implode('/', $cur_segs);

$is_active = function($item_url) use ($cur_segs) {
	if (!is_string($item_url) || $item_url === '' || $item_url === 'admin') return false;
	$item_segs = explode('/', $item_url);
	for ($i = 1; $i < count($item_segs); $i++) {
		if (!isset($cur_segs[$i]) || $cur_segs[$i] !== $item_segs[$i]) return false;
	}
	return true;
};
$is_dashboard = ($cur_path === 'admin' || $cur_path === '');

// Section à ouvrir par défaut (celle qui contient l'item actif).
$active_section = $is_dashboard ? 'pinned' : 'pinned';
if (!$is_dashboard) {
	foreach ($sections as $sec) {
		foreach (($sec['items'] ?? []) as $it) {
			$u = is_string($it['url'] ?? '') ? $it['url'] : '';
			if ($u !== '' && $u !== 'admin' && $is_active($u)) { $active_section = $sec['id']; break 2; }
		}
	}
}
?>
<div class="nf-app">
	<aside class="nf-sidebar" id="nfSidebar">
		<div class="nf-sb-top">
			<a class="nf-sb-brand" href="<?php echo url('admin') ?>">
				<span class="nf-sb-mark"><i class="fas fa-bolt"></i></span>
				<span class="nf-sb-brand-text">NeoFrag<small>Reborn</small></span>
			</a>
		</div>

		<button type="button" class="nf-sb-search" id="nfCmdHint" title="<?php echo $this->lang('Recherche rapide (Ctrl+K)') ?>">
			<i class="fas fa-search"></i>
			<span><?php echo $this->lang('Rechercher…') ?></span>
			<kbd>Ctrl K</kbd>
		</button>

		<nav class="nf-sb-nav">
			<?php foreach ($sections as $sec):
				$items = $sec['items'] ?? [];
				if (empty($items)) continue;
				$open = ($sec['id'] === $active_section);
			?>
			<div class="nf-sb-section<?php echo $open ? ' open' : '' ?>" data-section="<?php echo htmlspecialchars($sec['id']) ?>">
				<button type="button" class="nf-sb-sec-head">
					<i class="nf-sb-sec-ico <?php echo htmlspecialchars($sec['icon'] ?? 'fas fa-folder') ?>"></i>
					<span class="nf-sb-sec-title"><?php echo htmlspecialchars($sec['title']) ?></span>
					<i class="nf-sb-chev fas fa-chevron-down"></i>
				</button>
				<ul class="nf-sb-items">
					<?php foreach ($items as $it):
						if (isset($it['access']) && !$it['access']) continue;
						$u = is_string($it['url'] ?? '') ? $it['url'] : '';
						$active = ($u === 'admin') ? $is_dashboard : $is_active($u);
						$pin_name = $it['name'] ?? '';
						$pinnable = ($sec['id'] !== 'pinned' && $pin_name !== '' && $u !== 'admin');
					?>
					<li>
						<a class="nf-sb-item<?php echo $active ? ' active' : '' ?><?php echo $pinnable ? ' has-pin' : '' ?>" href="<?php echo url($u) ?>" data-name="<?php echo htmlspecialchars($pin_name) ?>">
							<i class="<?php echo htmlspecialchars($it['icon'] ?? 'fas fa-circle') ?>"></i>
							<span><?php echo htmlspecialchars($it['title']) ?></span>
						</a>
						<?php if ($pinnable): ?>
						<button type="button" class="nf-sb-pin" data-pin="<?php echo htmlspecialchars($pin_name) ?>" title="<?php echo $this->lang('Épingler') ?>" aria-label="<?php echo $this->lang('Épingler') ?>"><i class="fas fa-thumbtack"></i></button>
						<?php endif ?>
					</li>
					<?php endforeach ?>
				</ul>
			</div>
			<?php endforeach ?>
		</nav>

		<div class="nf-sb-user">
			<a href="<?php echo url('user') ?>" class="nf-sb-user-link" title="<?php echo htmlspecialchars($this->user->username) ?>">
				<span class="nf-avatar"><?php echo strtoupper(substr($this->user->username, 0, 1)) ?></span>
				<span class="nf-sb-user-name"><?php echo htmlspecialchars($this->user->username) ?></span>
			</a>
			<a class="nf-icon-btn" href="<?php echo url('user/logout') ?>" title="<?php echo $this->lang('Se déconnecter') ?>" aria-label="<?php echo $this->lang('Se déconnecter') ?>">
				<i class="fas fa-sign-out-alt"></i>
			</a>
		</div>
	</aside>

	<main class="nf-main">
		<header class="nf-topbar">

			<button type="button" class="nf-icon-btn nf-sidebar-toggle" id="nfSidebarToggle" title="<?php echo $this->lang('Menu') ?>" aria-label="<?php echo $this->lang('Menu') ?>"><i class="fas fa-bars"></i></button>

			<?php if (!($error = $this->output->error())):
				$module        = $this->output->module();
				$module_name   = $module->info()->name;
				$module_method = $this->output->data->get('module', 'method');
				$module_title  = $this->output->data->get('module', 'title');
				$module_icon   = $this->output->data->get('module', 'icon');
			?>
			<nav class="nf-breadcrumb" aria-label="<?php echo $this->lang('Fil d\'ariane') ?>">
				<a href="<?php echo url('admin') ?>"><i class="fas fa-th-large"></i></a>
				<?php
				/**
				 * Fil d'Ariane : [accueil] / [module, cliquable dès qu'on l'a quitté] / [page courante]
				 *
				 * Sur 36 des 97 sous-pages d'administration, il n'existait AUCUN moyen de remonter au
				 * module, sinon le bouton du navigateur : ni bouton « Retour », ni lien dans le fil.
				 *
				 * Le nom du module vient désormais de `info()->title`, et non du titre posé par le
				 * contrôleur. C'est ce qui permet de traiter les 36 d'un coup : la moitié de ces pages
				 * appelle `title()`, qui REMPLACE le titre du module par celui de la sous-page. Le fil
				 * n'avait alors qu'un seul segment — « Abonnés » sans « Newsletter » devant — et il n'y
				 * avait plus rien à rendre cliquable. En repartant de l'addon lui-même, le nom du module
				 * est toujours connu, quoi que le contrôleur ait fait de son titre.
				 */
				$subtitle      = $this->output->data->get('module', 'subtitle');
				$titre_module  = $module->info()->title;
				$sur_index     = $module_method === 'index';

				// Ce que le contrôleur a posé, s'il désigne autre chose que le module lui-même.
				$titre_courant = $subtitle ?: ($module_title !== $titre_module ? $module_title : '');

				/**
				 * Tous les modules n'ont pas de page d'accueil d'administration.
				 *
				 * `access` déclare `'admin' => FALSE` : il n'apparaît pas au menu et n'expose aucune
				 * route `admin/access`. Le fil rendait pourtant son nom cliquable, et le lien menait
				 * droit à un 404 depuis chacune de ses pages. On ne lie donc que si le module dit
				 * lui-même qu'il a une page d'accueil.
				 */
				$a_un_accueil = (bool) ($module->info()->admin ?? FALSE);
				?>
				<?php if ($titre_module && $module_name !== 'admin'): ?>
				<span class="nf-breadcrumb-sep">/</span>
				<?php if (!$sur_index && $a_un_accueil): ?>
				<a class="nf-breadcrumb-current" href="<?php echo url('admin/'.$module_name) ?>"><?php if ($module_icon): ?><i class="<?php echo htmlspecialchars($module_icon) ?>"></i> <?php endif ?><?php echo $titre_module ?></a>
				<?php else: ?>
				<span class="nf-breadcrumb-current"><?php if ($module_icon): ?><i class="<?php echo htmlspecialchars($module_icon) ?>"></i> <?php endif ?><?php echo $titre_module ?></span>
				<?php endif ?>
				<?php endif ?>
				<?php if ($titre_courant): ?>
				<span class="nf-breadcrumb-sep">/</span>
				<span class="nf-breadcrumb-sub"><?php echo $titre_courant ?></span>
				<?php endif ?>
			</nav>

			<div class="nf-topbar-actions">
				<?php
				$actions = $this->array($this->output->data->get('module', 'actions'))
								->append_if($module_method == 'index' && $module->get_permissions('default') && $this->module('access')->is_authorized(), $this->button($this->lang('Permissions'), 'fas fa-unlock-alt', 'secondary')->outline()->modal_ajax('admin/ajax/access/matrix-modal/'.$module_name)->url('admin/access/matrix/'.$module_name))
								->append_if(isset($module->info()->settings) && $this->module('addons')->is_authorized(), $this->button($this->lang('Configuration'), 'fas fa-wrench', 'secondary')->outline()->modal_ajax('admin/addons/settings/'.$module->__addon->id.'/'.$module_name))
								->append_if(($help_controller = @$module->controller('admin_help')) && $help_controller->has_method($module_method), $this->button($this->lang('Aide'), 'far fa-life-ring', 'secondary')->outline()->modal_ajax('admin/addons/help/'.$module->__addon->id.'/'.$module_name.'/'.$module_method));
				if (!$actions->empty()):
				?>
				<div class="nf-actions"><?php echo $actions ?></div>
				<?php endif ?>
				<?php if ($update = $this->__caller->update()): ?>
				<a href="#" class="nf-update-pill" data-modal-ajax="<?php echo url('admin/monitoring/update') ?>" title="<?php echo $this->lang('Mise à jour disponible : NeoFrag %s', $update->version) ?>">
					<i class="far fa-bell"></i><span><?php echo $update->version ?></span>
				</a>
				<?php endif ?>
				<a href="<?php echo url() ?>" class="nf-icon-btn" title="<?php echo $this->lang('Voir le site') ?>" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i></a>
				<button type="button" class="nf-icon-btn theme-toggle" id="nfThemeToggle" title="<?php echo $this->lang('Basculer le thème') ?>" aria-label="<?php echo $this->lang('Basculer le thème') ?>"><i class="fas fa-moon"></i></button>
			</div>
			<?php else: ?>
			<nav class="nf-breadcrumb">
				<a href="<?php echo url('admin') ?>"><i class="fas fa-th-large"></i></a>
				<span class="nf-breadcrumb-sep">/</span>
				<span class="nf-breadcrumb-current"><?php echo $this->lang('Erreur') ?></span>
			</nav>
			<?php endif ?>
		</header>

		<div class="nf-content">
			<?php
			/**
			 * Le nom du module sert de classe CSS (`.module-statistics`, `.module-addons`… sont
			 * stylés dans themes/admin/css/style.css). Mais un BLOQUEUR DE PUBLICITÉ masque, par
			 * règle générique, tout élément dont la classe contient le jeton « ads » : la page
			 * d'administration de la régie publicitaire disparaissait donc ENTIÈREMENT — contenu
			 * vide, seuls le fil d'Ariane, la barre d'outils et le pied de page restaient.
			 *
			 * Constaté le 2026-09-15 et confirmé des deux côtés : le serveur envoyait bien la
			 * carte et son texte, et la page s'affiche correctement en navigation privée.
			 *
			 * On neutralise donc le jeton sans renommer le module : son nom sert aussi d'URL, de
			 * préfixe de table et de clé d'enregistrement d'addon. Aucune règle CSS ne visait
			 * `.module-ads` (vérifié), le changement n'a donc pas d'effet de bord.
			 */
			$classe_module = ['ads' => 'regie'];

			// On redemande le module plutôt que de réutiliser la variable du fil d'Ariane : celle-ci
			// n'est posée que dans la branche SANS erreur. Sur une page d'erreur, la ligne suivante
			// lisait donc une variable inexistante, et chaque 404 laissait un avertissement dans le
			// journal de production.
			$nom_module = ($addon_courant = $this->output->module()) ? $addon_courant->info()->name : '';
			?>
			<?php if (!$error): ?>
				<div class="module module-admin module-<?php echo $classe_module[$nom_module] ?? $nom_module ?>"><?php echo $module ?></div>
			<?php else: ?>
				<div class="module module-admin module-error"><?php echo $error ?></div>
			<?php endif ?>

			<footer class="nf-footer">
				<span><?php echo $this->lang('Propulsé par') ?> <strong>NeoFrag Reborn</strong> <?php echo NEOFRAG_VERSION ?></span>
				<ul>
					<?php foreach ([
						[$this->lang('Fonctionnalités'), 'https://neofr.ag/#features'],
						[$this->lang('Documentation'),   'https://docs.neofr.ag'],
						[$this->lang('Forum'),           'https://neofr.ag/forum']
					] as list($title, $href)): ?>
					<li><a href="<?php echo $href ?>" target="_blank" rel="noopener"><?php echo $title ?></a></li>
					<?php endforeach ?>
				</ul>
			</footer>
		</div>
	</main>
</div>

<!-- Command palette -->
<div class="nf-cmd-overlay" id="nfCmdOverlay" aria-hidden="true">
	<div class="nf-cmd-palette" role="dialog" aria-label="<?php echo $this->lang('Recherche rapide') ?>">
		<input type="text" class="nf-cmd-input" id="nfCmdInput" placeholder="<?php echo $this->lang('Tape une commande, un module, ou une action…') ?>" autocomplete="off">
		<div class="nf-cmd-results" id="nfCmdResults"></div>
		<div class="nf-cmd-footer">
			<span><kbd>↑</kbd><kbd>↓</kbd> <?php echo $this->lang('Naviguer') ?></span>
			<span><kbd>↵</kbd> <?php echo $this->lang('Sélectionner') ?></span>
			<span><kbd>Esc</kbd> <?php echo $this->lang('Fermer') ?></span>
		</div>
	</div>
</div>

<script>
window.nfHomeUrl = <?php echo json_encode(url(), JSON_UNESCAPED_SLASHES) ?>;
window.nfLogoutUrl = <?php echo json_encode(url('user/logout'), JSON_UNESCAPED_SLASHES) ?>;
window.nfSidebarData = <?php
	$cmd = [];
	foreach ($sections as $sec) {
		foreach ($sec['items'] as $it) {
			if (isset($it['access']) && !$it['access']) continue;
			if (!is_string($it['url'] ?? '')) continue;
			$cmd[] = [
				'title'   => (string) $it['title'],
				'icon'    => $it['icon'] ?? 'fas fa-circle',
				'url'     => url($it['url']),
				'section'     => (string) $sec['title'], 'sectionIcon' => $sec['icon'] ?? 'fas fa-folder'
			];
		}
	}
	echo json_encode($cmd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>;
</script>
<?php echo $this->view('theme/logo') ?>
