<?php
// Les aperçus des styles, aux couleurs du thème : ils reprenaient ceux de Forge, orange sur brun (2026-10-15).
$accent = $this->config->nebula_theme_color ?: '#2dd4bf';
$fond   = $this->config->nebula_background_color ?: '#070a10';
$texte  = $this->config->nebula_text_color ?: '#e7eef6';
?>
<div class="nf-le-style-section">
	<h6 class="nf-le-style-grid-title"><?php echo icon('fas fa-square-full') ?> <?php echo $this->lang('Apparence de la ligne') ?></h6>
	<div class="nf-le-style-grid" data-nf-target="row">
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="row-default" data-label="<?php echo $this->lang('Fond transparent') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<defs>
						<pattern id="leFgDot" width="6" height="6" patternUnits="userSpaceOnUse"><circle cx="1.5" cy="1.5" r="1" fill="#2c3b52"/></pattern>
					</defs>
					<rect width="200" height="90" rx="4" fill="url(#leFgDot)"/>
					<rect x="20"  y="20" width="50" height="50" rx="2" fill="#0d1220" stroke="#1f2a3d"/>
					<rect x="80"  y="20" width="50" height="50" rx="2" fill="#0d1220" stroke="#1f2a3d"/>
					<rect x="140" y="20" width="40" height="50" rx="2" fill="#0d1220" stroke="#1f2a3d"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Fond transparent') ?></span>
		</button>
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="row-dark" data-label="<?php echo $this->lang('Fond sombre') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="90" rx="4" fill="#0d1220"/>
					<rect x="20"  y="20" width="50" height="50" rx="2" fill="#131b2d"/>
					<rect x="80"  y="20" width="50" height="50" rx="2" fill="#131b2d"/>
					<rect x="140" y="20" width="40" height="50" rx="2" fill="<?php echo $accent ?>"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Fond sombre') ?></span>
		</button>
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="row-space" data-label="<?php echo $this->lang('Marge en haut') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="90" rx="4" fill="<?php echo $fond ?>"/>
					<rect x="0" y="0" width="200" height="22" fill="#131b2d"/>
					<rect x="20"  y="38" width="50" height="38" rx="2" fill="#0d1220" stroke="#1f2a3d"/>
					<rect x="80"  y="38" width="50" height="38" rx="2" fill="#0d1220" stroke="#1f2a3d"/>
					<rect x="140" y="38" width="40" height="38" rx="2" fill="#0d1220" stroke="#1f2a3d"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Marge en haut') ?></span>
		</button>
	</div>
</div>
