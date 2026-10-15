<?php if (empty($collectifs)): ?>
	<div class="alert alert-info"><?php echo $this->lang('Vous ne faites partie d’aucun collectif. Un collectif se crée dans l’administration, et son chef y ajoute ses membres.') ?></div>
<?php else: ?>
	<div class="row g-3">
		<?php foreach ($collectifs as $c): ?>
			<div class="col-12 col-md-6">
				<a class="card h-100 text-decoration-none" href="<?php echo url('collectives/'.(int) $c['collective_id'].'/'.url_title($c['name'])) ?>">
					<div class="card-body">
						<h3 class="h5 card-title"><?php echo icon('fas fa-people-group') ?> <?php echo nf_texte($c['name']) ?></h3>
						<?php if (!empty($c['description'])): ?>
							<p class="card-text text-muted"><?php echo nf_texte($c['description'], 160) ?></p>
						<?php endif ?>
						<span class="badge text-bg-secondary"><?php echo nf_texte($rangs[$c['rank']] ?? $c['rank']) ?></span>
						<small class="text-muted ms-2"><?php echo $this->lang('%d membre|%d membres', (int) $c['nombre'], (int) $c['nombre']) ?></small>
					</div>
				</a>
			</div>
		<?php endforeach ?>
	</div>
<?php endif ?>
