<div class="nf-erreur text-center py-5">
	<p class="display-4 fw-bold text-body-secondary mb-2">404</p>
	<h1 class="h3 mb-3"><?php echo $this->lang('Page introuvable') ?></h1>
	<p class="text-body-secondary mb-4"><?php echo $this->lang('Cette adresse ne mène à aucune page : elle a peut-être changé, ou la page a été supprimée.') ?></p>
	<?php if ($this->url->admin): ?>
		<a href="<?php echo url('admin') ?>" class="btn btn-primary"><i class="fas fa-th-large me-1"></i> <?php echo $this->lang('Retour au tableau de bord') ?></a>
	<?php else: ?>
		<a href="<?php echo url() ?>" class="btn btn-primary"><i class="fas fa-home me-1"></i> <?php echo $this->lang('Retour à l’accueil') ?></a>
	<?php endif ?>
</div>
