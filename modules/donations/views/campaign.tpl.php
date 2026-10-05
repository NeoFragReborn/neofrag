<div class="row">
	<div class="col-lg-8">
		<div class="card">
			<div class="card-body">
				<h2 class="donation-title"><?php echo nf_texte($c['title']) ?></h2>

				<?php if ($c['description']): ?>
				<div class="donation-description mb-4"><?php echo render_content($c['description']) ?></div>
				<?php endif ?>

				<div class="donation-progress-card mb-4">
					<div class="donation-progress-meta">
						<div>
							<div class="donation-amount"><?php echo number_format($c['raised'], 2, ',', ' ') ?> <span class="donation-currency"><?php echo nf_texte($c['currency']) ?></span></div>
							<div class="donation-goal"><?php echo $this->lang('sur %s d\'objectif', '<strong>'.number_format($c['goal_amount'], 2, ',', ' ').' '.nf_texte($c['currency']).'</strong>') ?></div>
						</div>
						<div class="donation-pct"><?php echo $c['percentage'] ?>%</div>
					</div>
					<div class="donation-progress">
						<div class="donation-progress-bar" style="width: <?php echo $c['percentage'] ?>%"></div>
					</div>
					<div class="donation-progress-stats">
						<span><i class="fas fa-users"></i> <?php echo (int)$c['count'] ?> <?php echo $this->lang($c['count'] > 1 ? 'donateurs' : 'donateur') ?></span>
						<?php if ($c['deadline']): ?>
						<span><i class="far fa-calendar"></i> <?php echo $this->lang('Échéance : %s', timetostr('j F Y', $c['deadline'])) ?></span>
						<?php endif ?>
					</div>
				</div>

				<?php if ($donate_url): ?>
				<a href="<?php echo nf_texte($donate_url) ?>" target="_blank" rel="noopener" class="btn btn-paypal d-block w-100 btn-lg">
					<i class="fab fa-paypal"></i> <?php echo $this->lang('Faire un don via PayPal') ?>
				</a>
				<small class="text-muted d-block mt-2 text-center">
					<?php echo $this->lang('Vous serez redirigé vers PayPal pour effectuer le paiement de manière sécurisée.') ?>
				</small>
				<?php else: ?>
				<div class="alert alert-warning mb-0">
					<i class="fas fa-exclamation-triangle"></i> <?php echo $this->lang('Le moyen de paiement n\'est pas configuré.') ?>
				</div>
				<?php endif ?>
			</div>
		</div>
	</div>

	<div class="col-lg-4">
		<div class="card">
			<h6 class="card-header"><i class="fas fa-heart text-danger"></i> <?php echo $this->lang('Derniers donateurs') ?></h6>
			<ul class="list-group list-group-flush donations-public-list">
				<?php if (empty($donations)): ?>
				<li class="list-group-item text-center text-muted py-3"><i class="far fa-comment-dots"></i><br><small><?php echo $this->lang('Soyez le premier !') ?></small></li>
				<?php else: foreach ($donations as $d):
					$display_name = $d['is_anonymous'] ? $this->lang('Anonyme') : $d['donor_name'];
				?>
				<li class="list-group-item donation-item">
					<div class="d-flex align-items-start">
						<div class="donation-avatar"><?php echo nf_texte(mb_strtoupper(mb_substr(nf_texte_brut($display_name), 0, 1))) ?></div>
						<div class="flex-grow-1 ms-3">
							<div class="donation-donor-row">
								<strong><?php echo nf_texte($display_name) ?></strong>
								<span class="donation-donor-amount"><?php echo number_format($d['amount'], 2, ',', ' ') ?> <?php echo nf_texte($d['currency']) ?></span>
							</div>
							<small class="text-muted"><?php echo timetostr('j F, H:i', $d['created_at']) ?></small>
							<?php if (!empty($d['message'])): ?>
							<div class="donation-message">"<?php echo nf_texte($d['message']) ?>"</div>
							<?php endif ?>
						</div>
					</div>
				</li>
				<?php endforeach; endif ?>
			</ul>
		</div>
	</div>
</div>
