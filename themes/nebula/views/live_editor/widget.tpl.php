<?php
// Les aperçus des styles, aux couleurs du thème : ils reprenaient ceux de Forge, orange sur brun (2026-10-15).
$accent = $this->config->nebula_theme_color ?: '#2dd4bf';
$fond   = $this->config->nebula_background_color ?: '#070a10';
$texte  = $this->config->nebula_text_color ?: '#e7eef6';
?>
<div class="nf-le-style-section">
	<h6 class="nf-le-style-grid-title"><?php echo icon('far fa-square') ?> <?php echo $this->lang('Apparence du widget') ?></h6>
	<div class="nf-le-style-grid" data-nf-target="widget">
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="panel-default" data-label="<?php echo $this->lang('Widget classique') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 110" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="110" rx="4" fill="<?php echo $fond ?>"/>
					<rect x="20" y="16" width="160" height="78" rx="4" fill="#0d1220" stroke="#1f2a3d"/>
					<rect x="32" y="28" width="80"  height="8" rx="2" fill="<?php echo $texte ?>"/>
					<rect x="32" y="46" width="136" height="5" rx="2" fill="#5b6b82"/>
					<rect x="32" y="58" width="120" height="5" rx="2" fill="#5b6b82"/>
					<rect x="32" y="70" width="100" height="5" rx="2" fill="#5b6b82"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Widget classique') ?></span>
		</button>
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="panel-header" data-label="<?php echo $this->lang('Titre coloré') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 110" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="110" rx="4" fill="<?php echo $fond ?>"/>
					<rect x="20" y="16" width="160" height="78" rx="4" fill="#0d1220" stroke="#1f2a3d"/>
					<rect x="20" y="16" width="160" height="22" rx="4" fill="<?php echo $accent ?>"/>
					<rect x="20" y="32" width="160" height="6" fill="<?php echo $accent ?>"/>
					<rect x="32" y="22" width="80" height="8" rx="2" fill="#ffffff"/>
					<rect x="32" y="50" width="136" height="5" rx="2" fill="#5b6b82"/>
					<rect x="32" y="62" width="120" height="5" rx="2" fill="#5b6b82"/>
					<rect x="32" y="74" width="100" height="5" rx="2" fill="#5b6b82"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Titre coloré') ?></span>
		</button>
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="panel-color" data-label="<?php echo $this->lang('Widget coloré') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 110" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="110" rx="4" fill="<?php echo $fond ?>"/>
					<rect x="20" y="16" width="160" height="78" rx="4" fill="<?php echo $accent ?>"/>
					<rect x="32" y="28" width="80"  height="8" rx="2" fill="#ffffff"/>
					<rect x="32" y="46" width="136" height="5" rx="2" fill="#ffffff" opacity="0.75"/>
					<rect x="32" y="58" width="120" height="5" rx="2" fill="#ffffff" opacity="0.75"/>
					<rect x="32" y="70" width="100" height="5" rx="2" fill="#ffffff" opacity="0.75"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Widget coloré') ?></span>
		</button>
	</div>
</div>
