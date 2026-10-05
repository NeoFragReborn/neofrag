<div class="nf-erreur text-center py-5">
	<p class="display-4 fw-bold text-body-secondary mb-2">500</p>
	<h1 class="h3 mb-3"><?php echo $this->lang('Une erreur est survenue') ?></h1>
	<p class="text-body-secondary mb-3"><?php echo $this->lang('Cette page n’a pas pu s’afficher à cause d’un problème sur le site. L’erreur est notée ; si elle se reproduit, signalez-la à l’administrateur avec cette référence :') ?></p>
	<p class="mb-4"><code class="fs-5 px-3 py-2 border rounded d-inline-block"><?php echo nf_texte($reference ?? '') ?></code></p>
	<?php if ($this->url->admin): ?>
		<p class="small text-body-secondary mb-4"><?php echo $this->lang('Le détail de l’erreur est dans %s.', '<a href="'.url('admin/monitoring/journal').'">'.$this->lang('Monitoring → Journal').'</a>') ?></p>
		<a href="<?php echo url('admin') ?>" class="btn btn-primary"><i class="fas fa-th-large me-1"></i> <?php echo $this->lang('Retour au tableau de bord') ?></a>
	<?php else: ?>
		<a href="<?php echo url() ?>" class="btn btn-primary"><i class="fas fa-home me-1"></i> <?php echo $this->lang('Retour à l’accueil') ?></a>
	<?php endif ?>
</div>
