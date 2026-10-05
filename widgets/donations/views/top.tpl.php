<?php if (empty($top)): ?>
<div class="text-center text-muted py-3"><small><?php echo $this->lang('Aucun donateur pour le moment.') ?></small></div>
<?php else: ?>
<ul class="widget-donation-top-list">
	<?php foreach ($top as $i => $d):
		$rank = $i + 1;
		$medal = ['', 'fas fa-medal text-warning', 'fas fa-medal text-secondary', 'fas fa-medal' /* bronze */][$rank] ?? '';
	?>
	<li class="widget-donation-top-item rank-<?php echo $rank ?>">
		<span class="widget-donation-top-rank"><?php echo $rank ?></span>
		<span class="widget-donation-top-name"><?php echo nf_texte($d['donor_name']) ?></span>
		<span class="widget-donation-top-amount-pill"><?php echo number_format($d['total'], 0, ',', ' ') ?>&nbsp;<?php echo nf_texte($campaign['currency']) ?></span>
	</li>
	<?php endforeach ?>
</ul>
<a href="<?php echo nf_texte($campaign_url) ?>" class="widget-donation-link mt-2"><?php echo $this->lang('Soutenir la campagne') ?> →</a>
<?php endif ?>
