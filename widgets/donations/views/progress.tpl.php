<div class="widget-donation">
	<div class="widget-donation-amount-row">
		<div>
			<div class="widget-donation-raised"><?php echo number_format($c['raised'], 0, ',', ' ') ?> <span class="widget-donation-currency"><?php echo nf_texte($c['currency']) ?></span></div>
			<div class="widget-donation-goal"><?php echo $this->lang('sur %s', '<strong>'.number_format($c['goal_amount'], 0, ',', ' ').' '.nf_texte($c['currency']).'</strong>') ?></div>
		</div>
		<div class="widget-donation-pct"><?php echo (int)$c['percentage'] ?>%</div>
	</div>

	<div class="widget-donation-progress">
		<div class="widget-donation-progress-bar" style="width: <?php echo $c['percentage'] ?>%"></div>
	</div>

	<div class="widget-donation-stats">
		<i class="fas fa-users"></i> <strong><?php echo (int)$c['count'] ?></strong> <?php echo $this->lang($c['count'] > 1 ? 'donateurs' : 'donateur') ?>
		<?php if ($c['deadline']): ?>
		<span class="widget-donation-deadline"><i class="far fa-calendar"></i> <?php echo timetostr('j M', $c['deadline']) ?></span>
		<?php endif ?>
	</div>

	<?php if ($top_donor): ?>
	<div class="widget-donation-top">
		<i class="fas fa-trophy"></i>
		<span class="widget-donation-top-label"><?php echo $this->lang('Top donateur') ?></span>
		<strong><?php echo nf_texte($top_donor['donor_name']) ?></strong>
		<span class="widget-donation-top-amount"><?php echo number_format($top_donor['total'], 2, ',', ' ') ?> <?php echo nf_texte($c['currency']) ?></span>
	</div>
	<?php endif ?>

	<?php if (!empty($recent)): ?>
	<div class="widget-donation-recent">
		<?php foreach ($recent as $r):
			$display = $r['is_anonymous'] ? $this->lang('Anonyme') : $r['donor_name'];
		?>
		<div class="widget-donation-recent-item">
			<?php /* L'initiale du nom ENREGISTRÉ codé (« &Eacute;lodie ») était « & » : on la prend sur le texte décodé,
			   comme la page d'une campagne (relecture du journal des versions, 2026-10-10). */ ?>
			<span class="widget-donation-recent-avatar"><?php echo nf_texte(mb_strtoupper(mb_substr(nf_texte_brut($display), 0, 1))) ?></span>
			<span class="widget-donation-recent-name"><?php echo nf_texte($display) ?></span>
			<span class="widget-donation-recent-amount"><?php echo number_format($r['amount'], 0, ',', ' ') ?>&nbsp;<?php echo nf_texte($r['currency']) ?></span>
		</div>
		<?php endforeach ?>
	</div>
	<?php endif ?>

	<?php if ($donate_url): ?>
	<a href="<?php echo nf_texte($donate_url) ?>" target="_blank" rel="noopener" class="widget-donation-cta">
		<i class="fab fa-paypal"></i> <?php echo $this->lang('Faire un don') ?>
	</a>
	<?php endif ?>

	<a href="<?php echo nf_texte($campaign_url) ?>" class="widget-donation-link"><?php echo $this->lang('Voir la campagne') ?> →</a>
</div>
