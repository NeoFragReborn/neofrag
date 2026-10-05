<?php
/**
 * Addons admin view — modern cards grid.
 */
?>
<?php
$addons = $addons ?? [];
// Le nombre d'extensions de chaque type, pour les filtres.
$comptes = ["all" => count($addons)];
foreach ($addons as $a) { $t = $a->type ? $a->type->name : "addon"; $comptes[$t] = ($comptes[$t] ?? 0) + 1; }
$filtres = [
	"module"        => ["fas fa-cube",        $this->lang('Modules')],
	"widget"        => ["fas fa-puzzle-piece", $this->lang('Widgets')],
	"theme"         => ["fas fa-paint-brush",  $this->lang('Thèmes')],
	"language"      => ["fas fa-globe",        $this->lang('Langues')],
	"authenticator" => ["fas fa-key",          $this->lang('Authentificateurs')],
];
?>
<?php /* La barre : le type, la recherche, le statut, la vue. Le type et le statut se combinent (les
         modules inactifs) ; la page s'ouvre sur les modules, et en liste — elle alignait les quelque
         120 extensions en grandes cartes, sur plus de 10 000 pixels (relevé le 2026-10-02). */ ?>
<div class="addons-toolbar">
	<div class="addons-filters-group" role="group" aria-label="<?php echo $this->lang('Type') ?>">
		<button type="button" class="addons-filter-btn" data-type="all"><?php echo $this->lang('Tous') ?> <span class="addons-filter-count"><?php echo $comptes["all"] ?></span></button>
		<?php foreach ($filtres as $type => [$icone, $libelle]): if (empty($comptes[$type])) continue; ?>
		<button type="button" class="addons-filter-btn" data-type="<?php echo $type ?>"><i class="<?php echo $icone ?>"></i> <?php echo $libelle ?> <span class="addons-filter-count"><?php echo $comptes[$type] ?></span></button>
		<?php endforeach ?>
	</div>
	<input type="search" class="form-control form-control-sm addons-recherche" placeholder="<?php echo $this->lang('Rechercher une extension') ?>" aria-label="<?php echo $this->lang('Rechercher une extension') ?>">
	<div class="addons-status-group" role="group" aria-label="<?php echo $this->lang('Statut') ?>">
		<button type="button" class="addons-filter-btn" data-statut="activated"><i class="fas fa-circle" style="color:#16a34a;font-size:8px;"></i> <?php echo $this->lang('Actifs') ?></button>
		<button type="button" class="addons-filter-btn" data-statut="deactivated"><i class="far fa-circle" style="font-size:8px;"></i> <?php echo $this->lang('Inactifs') ?></button>
	</div>
	<div class="addons-vue-group" role="group" aria-label="<?php echo $this->lang('Affichage') ?>">
		<button type="button" class="addons-vue-btn" data-vue="liste" title="<?php echo $this->lang('Liste') ?>" aria-label="<?php echo $this->lang('Liste') ?>"><i class="fas fa-list"></i></button>
		<button type="button" class="addons-vue-btn" data-vue="grille" title="<?php echo $this->lang('Grille') ?>" aria-label="<?php echo $this->lang('Grille') ?>"><i class="fas fa-th-large"></i></button>
	</div>
</div>
<p class="addons-vide text-body-secondary small" hidden><?php echo $this->lang('Aucune extension ne correspond.') ?></p>
<div id="addons" class="addons-grid">
	<?php foreach ($addons as $addon): ?>
		<?php
		$is_enabled = $addon->addon()->is_enabled();
		$type_name  = $addon->type ? $addon->type->name : 'addon';
		$type_label_data = $addon->controller()->__label;
		$type_label = $type_label_data[1] ?? '';
		$type_color = $type_label_data[3] ?? 'gray';
		/*
		 * L'APERÇU de la carte, produit par `tools/capturer-apercus.php`.
		 *
		 * `__path()` résout `<type>s/<nom>/images/thumbnail.jpg`, ce qui est juste pour un module,
		 * un widget ou un thème, et FAUX pour les addons de `addons/` : il y cherche `languages/`
		 * et `authenticators/`, deux dossiers qui n'existent pas. D'où le repli explicite.
		 */
		$thumbnail = $addon->addon()->__path('images', 'thumbnail.jpg');

		if (!$thumbnail)
		{
			// La CLASSE de l'addon sait dans quel fichier elle vit, donc dans quel dossier : c'est
			// exact quel que soit le type, et cela ne depend d'aucune convention de nommage.
			$fichier = (new ReflectionClass($addon->addon()))->getFileName();
			$candidat = $fichier ? dirname($fichier).'/images/thumbnail.jpg' : '';

			if ($candidat && is_file($candidat) && str_starts_with($candidat, NEOFRAG_CMS.'/'))
			{
				$thumbnail = substr($candidat, strlen(NEOFRAG_CMS) + 1);
			}
		}
		$icon       = isset($addon->addon()->info()->icon) ? $addon->addon()->info()->icon : ($type_label_data[2] ?? 'fas fa-cube');
		$title      = $addon->addon()->info()->title;
		$version    = $addon->addon()->info()->version ?? '';
		$description = $addon->addon()->info()->description ?? '';
		?>
		<div class="addon-card mix addon-<?php echo $type_name ?> <?php echo $is_enabled ? 'activated' : 'deactivated' ?>" data-type="<?php echo $type_name ?>" data-texte="<?php echo nf_texte(mb_strtolower(strip_tags($title.' '.$description.' '.($addon->addon()->info()->name ?? '')))) ?>">
			<?php /* La bande d'apercu existe TOUJOURS, avec image ou avec l'icone au centre. Quand
			         elle n'apparaissait que pour les themes — les seuls a livrer un thumbnail — les
			         cartes d'une meme ligne differaient de 50 px de haut, et la grille etirait les
			         plus courtes en laissant un grand vide. Signale le 2026-09-22. */ ?>
			<?php if ($thumbnail): ?>
			<div class="addon-card-thumbnail" style="background-image: url(<?php echo url($thumbnail) ?>);"></div>
			<?php else: ?>
			<div class="addon-card-thumbnail addon-card-thumbnail-vide">
				<div class="addon-card-icon-wrap">
					<?php if (preg_match('/^fa[bsr]?\s+fa-/', $icon)): ?>
						<i class="<?php echo nf_texte($icon) ?>"></i>
					<?php else: ?>
						<span class="addon-card-icon-emoji"><?php echo $icon ?></span>
					<?php endif ?>
				</div>
			</div>
			<?php endif ?>
			<div class="addon-card-body">
				<div class="addon-card-header">
					<div class="addon-card-title-wrap">
						<span class="badge <?php echo badge_class($type_color) ?>"><?php echo $type_label ?></span>
						<?php if ($is_enabled): ?>
						<span class="badge text-bg-success"><span class="dot"></span> <?php echo $this->lang('Actif') ?></span>
						<?php else: ?>
						<span class="badge text-bg-secondary"><span class="dot"></span> <?php echo $this->lang('Inactif') ?></span>
						<?php endif ?>
					</div>
					<div class="dropdown addon-card-actions">
						<a href="#" class="addon-card-action-btn" data-bs-toggle="dropdown" aria-label="<?php echo $this->lang('Actions') ?>"><i class="fas fa-ellipsis-h"></i></a>
						<div class="dropdown-menu dropdown-menu-end">
							<?php foreach ($addon->addon()->__actions as $name => $action): ?>
								<?php if (list($title2, $iconA, $colorA, $modal) = $action): ?>
									<?php $url = url('admin/addons/'.$name.'/'.$addon->url()).(in_array($name, ['enable', 'disable', 'order', 'reset', 'delete'], TRUE) ? '?_='.$csrf : '') ?>
									<a class="dropdown-item" <?php echo $modal ? 'href="#" data-modal-ajax="'.$url.'"' : 'href="'.$url.'"' ?>>
										<i class="<?php echo $iconA ?> text-<?php echo $colorA ?>"></i> <?php echo $title2 ?>
									</a>
								<?php else: ?>
									<div class="dropdown-divider"></div>
								<?php endif ?>
							<?php endforeach ?>
						</div>
					</div>
				</div>
				<h3 class="addon-card-title"><?php echo $title ?></h3>
				<?php /* Rendue MEME vide : sa hauteur est reservee dans la feuille, sinon une carte
				         sans description remonte son numero de version et casse l'alignement. */ ?>
				<p class="addon-card-desc"><?php echo $description ?></p>
				<?php if ($version): ?>
				<div class="addon-card-meta"><i class="fas fa-tag"></i> v<?php echo $version ?></div>
				<?php endif ?>
			</div>
		</div>
	<?php endforeach ?>
</div>
