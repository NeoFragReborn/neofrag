<?php $is_open = !$this->config->nf_maintenance; ?>
<div class="nf-status-card <?php echo $is_open ? 'is-on' : 'is-off' ?>" data-toggle-endpoint="<?php echo url('admin/ajax/settings/maintenance.json') ?>" data-on-title="<?php echo $this->lang('Site ouvert au public') ?>" data-on-desc="<?php echo $this->lang('Tous les visiteurs peuvent accéder normalement au site.') ?>" data-on-icon="fas fa-check-circle" data-off-title="<?php echo $this->lang('Site fermé — Mode maintenance') ?>" data-off-desc="<?php echo $this->lang('Seuls les administrateurs peuvent accéder au site. Les visiteurs voient la page de maintenance.') ?>" data-off-icon="fas fa-power-off">
	<div class="nf-status-info">
		<div class="nf-status-icon">
			<i class="fas fa-<?php echo $is_open ? 'check-circle' : 'power-off' ?>"></i>
		</div>
		<div class="nf-status-text">
			<div class="nf-status-title"><?php echo $is_open ? $this->lang('Site ouvert au public') : $this->lang('Site fermé — Mode maintenance') ?></div>
			<div class="nf-status-desc">
				<?php if ($is_open): ?>
					<?php echo $this->lang('Tous les visiteurs peuvent accéder normalement au site.') ?>
				<?php else: ?>
					<?php echo $this->lang('Seuls les administrateurs peuvent accéder au site. Les visiteurs voient la page de maintenance.') ?>
				<?php endif ?>
			</div>
		</div>
	</div>
	<div class="nf-status-toggle">
		<div class="btn-group switch" role="group" aria-label="<?php echo $this->lang('Basculer le mode maintenance') ?>">
			<a href="#" class="btn <?php echo $is_open ? 'btn-success active' : 'btn-secondary' ?>"><i class="fas fa-check"></i> <?php echo $this->lang('Ouvert') ?></a>
			<a href="#" class="btn <?php echo !$is_open ? 'btn-danger active' : 'btn-secondary' ?>"><i class="fas fa-power-off"></i> <?php echo $this->lang('Fermé') ?></a>
		</div>
	</div>
</div>
