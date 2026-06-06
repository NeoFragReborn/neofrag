<div class="widget-ts widget-ts-tree">
	<div class="widget-ts-header">
		<div class="widget-ts-icon"><i class="fas fa-microphone-alt"></i></div>
		<div class="widget-ts-meta">
			<div class="widget-ts-name"><?php echo htmlspecialchars($server_name) ?></div>
			<div class="widget-ts-stats">
				<span class="widget-ts-dot"></span>
				<strong><?php echo (int)$clients_online ?></strong> /
				<span><?php echo (int)$clients_max ?></span>
				<span class="widget-ts-stats-lbl"><?php echo $this->lang('clients') ?></span>
			</div>
		</div>
	</div>

	<div class="widget-ts-tree-content">
		<?php echo $tree_html ?>
	</div>

	<a href="<?php echo htmlspecialchars($ts_url) ?>" class="widget-ts-cta">
		<i class="fas fa-sign-in-alt"></i> <?php echo $this->lang('Rejoindre TeamSpeak') ?>
	</a>
</div>
