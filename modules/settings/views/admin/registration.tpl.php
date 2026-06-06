<?php $is_open = (int)$this->config->nf_registration_status === 1; ?>
<div class="nf-status-card <?php echo $is_open ? 'is-on' : 'is-off' ?>" data-toggle-endpoint="<?php echo url('admin/ajax/settings/registration.json') ?>" data-on-title="<?php echo $this->lang('Inscriptions ouvertes') ?>" data-on-desc="<?php echo $this->lang('N\'importe quel visiteur peut créer un compte sur le site.') ?>" data-on-icon="fas fa-user-check" data-off-title="<?php echo $this->lang('Inscriptions fermées') ?>" data-off-desc="<?php echo $this->lang('Aucun nouveau compte ne peut être créé. Le formulaire d\'inscription est désactivé.') ?>" data-off-icon="fas fa-user-slash">
	<div class="nf-status-info">
		<div class="nf-status-icon">
			<i class="fas fa-<?php echo $is_open ? 'user-check' : 'user-slash' ?>"></i>
		</div>
		<div class="nf-status-text">
			<div class="nf-status-title"><?php echo $is_open ? $this->lang('Inscriptions ouvertes') : $this->lang('Inscriptions fermées') ?></div>
			<div class="nf-status-desc">
				<?php if ($is_open): ?>
					<?php echo $this->lang('N\'importe quel visiteur peut créer un compte sur le site.') ?>
				<?php else: ?>
					<?php echo $this->lang('Aucun nouveau compte ne peut être créé. Le formulaire d\'inscription est désactivé.') ?>
				<?php endif ?>
			</div>
		</div>
	</div>
	<div class="nf-status-toggle">
		<div class="btn-group switch" role="group" aria-label="<?php echo $this->lang('Basculer le statut des inscriptions') ?>">
			<a href="#" class="btn <?php echo $is_open ? 'btn-success active' : 'btn-secondary' ?>"><i class="fas fa-check"></i> <?php echo $this->lang('Ouvertes') ?></a>
			<a href="#" class="btn <?php echo !$is_open ? 'btn-danger active' : 'btn-secondary' ?>"><i class="fas fa-times"></i> <?php echo $this->lang('Fermées') ?></a>
		</div>
	</div>
</div>
