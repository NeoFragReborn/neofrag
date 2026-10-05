<div class="donations-list">
	<?php foreach ($campaigns as $c): ?>
	<div class="card donation-card">
		<div class="card-body">
			<div class="d-flex align-items-start mb-2">
				<div class="flex-grow-1">
					<h4 class="mb-1"><a href="<?php echo url('donations/'.$c['name']) ?>"><?php echo nf_texte($c['title']) ?></a></h4>
					<?php if ($c['deadline']): ?>
					<small class="text-muted"><i class="far fa-calendar"></i> <?php echo $this->lang('Jusqu\'au %s', timetostr('j F Y', $c['deadline'])) ?></small>
					<?php endif ?>
				</div>
				<span class="badge text-bg-success rounded-pill"><?php echo $c['percentage'] ?>%</span>
			</div>

			<div class="donation-progress mb-3">
				<div class="donation-progress-bar" style="width: <?php echo $c['percentage'] ?>%"></div>
			</div>

			<div class="d-flex justify-content-between donation-stats">
				<span><strong><?php echo number_format($c['raised'], 2, ',', ' ') ?> <?php echo nf_texte($c['currency']) ?></strong> / <?php echo number_format($c['goal_amount'], 2, ',', ' ') ?> <?php echo nf_texte($c['currency']) ?></span>
				<span class="text-muted"><i class="fas fa-users"></i> <?php echo $c['count'] ?> <?php echo $this->lang($c['count'] > 1 ? 'donateurs' : 'donateur') ?></span>
			</div>

			<a href="<?php echo url('donations/'.$c['name']) ?>" class="btn btn-primary d-block w-100 mt-3"><?php echo $this->lang('Voir la campagne') ?></a>
		</div>
	</div>
	<?php endforeach ?>
	<?php if (empty($campaigns)): ?>
	<div class="text-center text-muted py-4"><?php echo $this->lang('Aucune campagne active pour le moment.') ?></div>
	<?php endif ?>
</div>
