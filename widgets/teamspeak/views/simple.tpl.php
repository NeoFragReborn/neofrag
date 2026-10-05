<div class="widget-ts widget-ts-simple">
	<?php if ($error): ?>
	<div class="widget-ts-error">
		<i class="fas fa-exclamation-triangle"></i>
		<small><?php echo nf_texte($error) ?></small>
	</div>
	<?php endif ?>

	<div class="widget-ts-info">
		<div class="widget-ts-icon"><i class="fas fa-microphone-alt"></i></div>
		<div class="widget-ts-meta">
			<div class="widget-ts-name"><?php echo nf_texte($name) ?></div>
			<div class="widget-ts-host"><?php echo nf_texte($host).(($port != 9987) ? ':'.(int)$port : '') ?></div>
		</div>
	</div>

	<a href="<?php echo nf_texte($ts_url) ?>" class="widget-ts-cta">
		<i class="fas fa-sign-in-alt"></i> <?php echo $this->lang('Rejoindre TeamSpeak') ?>
	</a>
</div>
