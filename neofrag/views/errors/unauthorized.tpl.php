<div class="nf-erreur text-center py-5">
	<p class="display-4 fw-bold text-body-secondary mb-2">403</p>
	<h1 class="h3 mb-3"><?php echo $this->lang('Accès non autorisé') ?></h1>
	<p class="text-body-secondary mb-4"><?php echo $this->lang('Votre compte n’a pas les droits nécessaires pour voir cette page.') ?></p>
	<?php if ($this->url->admin): ?>
		<a href="<?php echo url('admin') ?>" class="btn btn-primary"><i class="fas fa-th-large me-1"></i> <?php echo $this->lang('Retour au tableau de bord') ?></a>
	<?php else: ?>
		<a href="<?php echo url() ?>" class="btn btn-primary"><i class="fas fa-home me-1"></i> <?php echo $this->lang('Retour à l’accueil') ?></a>
	<?php endif ?>
</div>
