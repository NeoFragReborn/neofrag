<?php
/**
 * Addons admin view — modern cards grid.
 */
?>
<div class="addons-toolbar">
	<div class="addons-filters-group">
		<span class="addons-filters-label"><i class="fas fa-filter"></i> <?php echo $this->lang('Filtrer') ?></span>
		<button type="button" class="addons-filter-btn active" data-filter="all"><?php echo $this->lang('Tous') ?></button>
		<button type="button" class="addons-filter-btn" data-filter=".addon-module"><i class="fas fa-cube"></i> <?php echo $this->lang('Modules') ?></button>
		<button type="button" class="addons-filter-btn" data-filter=".addon-theme"><i class="fas fa-paint-brush"></i> <?php echo $this->lang('Thèmes') ?></button>
		<button type="button" class="addons-filter-btn" data-filter=".addon-widget"><i class="fas fa-puzzle-piece"></i> <?php echo $this->lang('Widgets') ?></button>
		<button type="button" class="addons-filter-btn" data-filter=".addon-language"><i class="fas fa-globe"></i> <?php echo $this->lang('Langues') ?></button>
		<button type="button" class="addons-filter-btn" data-filter=".addon-authenticator"><i class="fas fa-key"></i> <?php echo $this->lang('Authentificateurs') ?></button>
	</div>
	<div class="addons-status-group">
		<button type="button" class="addons-filter-btn" data-filter=".activated"><i class="fas fa-circle" style="color:#16a34a;font-size:8px;"></i> <?php echo $this->lang('Actifs') ?></button>
		<button type="button" class="addons-filter-btn" data-filter=".deactivated"><i class="far fa-circle" style="font-size:8px;"></i> <?php echo $this->lang('Inactifs') ?></button>
	</div>
</div>

<div id="addons" class="addons-grid">
	<?php foreach ($addons as $addon): ?>
		<?php
		$is_enabled = $addon->addon()->is_enabled();
		$type_name  = $addon->type ? $addon->type->name : 'addon';
		$type_label_data = $addon->controller()->__label;
		$type_label = $type_label_data[1] ?? '';
		$type_color = $type_label_data[3] ?? 'gray';
		$thumbnail  = $addon->addon()->__path('', 'thumbnail.png');
		$icon       = isset($addon->addon()->info()->icon) ? $addon->addon()->info()->icon : ($type_label_data[2] ?? 'fas fa-cube');
		$title      = $addon->addon()->info()->title;
		$version    = $addon->addon()->info()->version ?? '';
		$description = $addon->addon()->info()->description ?? '';
		?>
		<div class="addon-card mix addon-<?php echo $type_name ?> <?php echo $is_enabled ? 'activated' : 'deactivated' ?>">
			<?php if ($thumbnail): ?>
			<div class="addon-card-thumbnail" style="background-image: url(<?php echo url($thumbnail) ?>);"></div>
			<?php else: ?>
			<div class="addon-card-icon-wrap">
				<?php if (preg_match('/^fa[bsr]?\s+fa-/', $icon)): ?>
					<i class="<?php echo htmlspecialchars($icon) ?>"></i>
				<?php else: ?>
					<span class="addon-card-icon-emoji"><?php echo $icon ?></span>
				<?php endif ?>
			</div>
			<?php endif ?>
			<div class="addon-card-body">
				<div class="addon-card-header">
					<div class="addon-card-title-wrap">
						<span class="badge badge-<?php echo $type_color ?>"><?php echo $type_label ?></span>
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
				<?php if ($description): ?>
				<p class="addon-card-desc"><?php echo $description ?></p>
				<?php endif ?>
				<?php if ($version): ?>
				<div class="addon-card-meta"><i class="fas fa-tag"></i> v<?php echo $version ?></div>
				<?php endif ?>
			</div>
		</div>
	<?php endforeach ?>
</div>
