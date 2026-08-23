<?php
// R1.3 — Page de sélection : choix d'un module pour ouvrir sa matrice de permissions.
?>
<?php if (empty($modules)): ?>
	<div class="alert alert-info text-center">
		<?php echo icon('fas fa-info-circle').' '.$this->lang('Aucun module ne déclare de permissions.') ?>
	</div>
<?php else: ?>
	<p class="text-muted mb-3">
		<?php echo $this->lang('Sélectionne un module pour gérer ses permissions par rôle :') ?>
	</p>
	<div class="row matrix-modules">
		<?php foreach ($modules as $m): ?>
			<div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3">
				<a class="btn btn-outline-primary btn-block matrix-module-btn" href="<?php echo url('admin/access/matrix/'.urlencode($m['name'])) ?>">
					<i class="<?php echo htmlspecialchars($m['icon']) ?> me-2"></i>
					<?php echo htmlspecialchars($m['title']) ?>
					<small class="d-block text-muted mt-1"><?php echo htmlspecialchars($m['name']) ?></small>
				</a>
			</div>
		<?php endforeach ?>
	</div>
<?php endif ?>
