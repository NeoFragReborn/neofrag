<ul class="nav nav-pills" id="pills-tab" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="pills-options-tab" data-bs-toggle="pill" href="#pills-options" role="tab" aria-controls="pills-options" aria-selected="true"><?php echo icon('fas fa-cogs').' '.$this->lang('Disposition') ?></a></li>
	<li class="nav-item"><a class="nav-link" id="pills-style-tab" data-bs-toggle="pill" href="#pills-style" role="tab" aria-controls="pills-style" aria-selected="false"><?php echo icon('fas fa-paint-brush').' '.$this->lang('Style') ?></a></li>
	<li class="nav-item"><a class="nav-link" id="pills-display-tab" data-bs-toggle="pill" href="#pills-display" role="tab" aria-controls="pills-display" aria-selected="false"><?php echo icon('fas fa-desktop').' '.$this->lang('Affichage') ?></a></li>
</ul>
<div class="tab-content border-light" id="pills-tabContent">
	<div class="tab-pane fade show active" id="pills-options" role="tabpanel" aria-labelledby="pills-options-tab">
		<div class="nf-field row">
			<label for="settings-display_panel" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Dans un panel') ?></label>
			<div class="col-12 col-lg-2">
				<select class="form-select" name="settings[display_panel]" id="settings-display_panel">
					<option value="non"<?php if (!isset($display_panel) || $display_panel == 'non') echo ' selected="selected"' ?>><?php echo $this->lang('Non') ?></option>
					<option value="oui"<?php if (isset($display_panel) && $display_panel == 'oui') echo ' selected="selected"' ?>><?php echo $this->lang('Oui') ?></option>
				</select>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-social_display" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Disposition') ?></label>
			<div class="col-12 col-lg-9">
				<select class="form-select" name="settings[social_display]" id="settings-display_teamname">
					<option value="col-12"<?php if (!isset($social_display) || $social_display == 'col-12') echo ' selected="selected"' ?>><?php echo $this->lang('%d bouton par ligne|%d boutons par ligne', 1, 1) ?></option>
					<option value="col-6"<?php if (isset($social_display) && $social_display == 'col-6') echo ' selected="selected"' ?>><?php echo $this->lang('%d bouton par ligne|%d boutons par ligne', 2, 2) ?></option>
					<option value="col-4"<?php if (isset($social_display) && $social_display == 'col-4') echo ' selected="selected"' ?>><?php echo $this->lang('%d bouton par ligne|%d boutons par ligne', 3, 3) ?></option>
					<option value="col-3"<?php if (isset($social_display) && $social_display == 'col-3') echo ' selected="selected"' ?>><?php echo $this->lang('%d bouton par ligne|%d boutons par ligne', 4, 4) ?></option>
					<option value="col-2"<?php if (isset($social_display) && $social_display == 'col-2') echo ' selected="selected"' ?>><?php echo $this->lang('%d bouton par ligne|%d boutons par ligne', 6, 6) ?></option>
					<option value="col-1"<?php if (isset($social_display) && $social_display == 'col-1') echo ' selected="selected"' ?>><?php echo $this->lang('%d bouton par ligne|%d boutons par ligne', 12, 12) ?></option>
					<option value="col"<?php if (isset($social_display) && $social_display == 'col') echo ' selected="selected"' ?>><?php echo $this->lang('Répartition automatique') ?></option>
					<option value="ul-inline"<?php if (isset($social_display) && $social_display == 'ul-inline') echo ' selected="selected"' ?>><?php echo $this->lang('Liste horizontale') ?></option>
					<option value="ul"<?php if (isset($social_display) && $social_display == 'ul') echo ' selected="selected"' ?>><?php echo $this->lang('Liste verticale') ?></option>
				</select>
			</div>
		</div>
	</div>
	<div class="tab-pane fade" id="pills-style" role="tabpanel" aria-labelledby="pills-style-tab">
		<div class="nf-field row">
			<label for="settings-social_style" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Apparence') ?></label>
			<div class="col-12 col-lg-6">
				<select class="form-select" name="settings[social_style]" id="settings-social_style">
					<option value="btn btn-social"<?php if (!isset($social_style) || $social_style == 'btn btn-social') echo ' selected="selected"' ?>><?php echo $this->lang('Bouton normal') ?></option>
					<option value="btn btn-social btn-sm"<?php if (isset($social_style) && $social_style == 'btn btn-social btn-sm') echo ' selected="selected"' ?>><?php echo $this->lang('Petit bouton') ?></option>
					<option value="btn btn-social btn-lg"<?php if (isset($social_style) && $social_style == 'btn btn-social btn-lg') echo ' selected="selected"' ?>><?php echo $this->lang('Grand bouton') ?></option>
					<option value="btn btn-link"<?php if (isset($social_style) && $social_style == 'btn btn-link') echo ' selected="selected"' ?>><?php echo $this->lang('Simple lien') ?></option>
				</select>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-content_display" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Contenu') ?></label>
			<div class="col-12 col-lg-6">
				<select class="form-select" name="settings[content_display]" id="settings-content_display">
					<option value="all"<?php if (!isset($content_display) || $content_display == 'all') echo ' selected="selected"' ?>><?php echo $this->lang('Icône et légende') ?></option>
					<option value="icon"<?php if (isset($content_display) && $content_display == 'icon') echo ' selected="selected"' ?>><?php echo $this->lang('Icône seule') ?></option>
					<option value="legend"<?php if (isset($content_display) && $content_display == 'legend') echo ' selected="selected"' ?>><?php echo $this->lang('Légende seule') ?></option>
				</select>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-icon_size" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Taille de l\'icône') ?></label>
			<div class="col-12 col-lg-3">
				<select class="form-select" name="settings[icon_size]" id="settings-icon_size">
					<option value="fa-1x"<?php if (!isset($icon_size) || $icon_size == 'fa-1x') echo ' selected="selected"' ?>><?php echo $this->lang('Par défaut') ?></option>
					<option value="fa-2x"<?php if (isset($icon_size) && $icon_size == 'fa-2x') echo ' selected="selected"' ?>><?php echo $this->lang('Grande') ?></option>
					<option value="fa-3x"<?php if (isset($icon_size) && $icon_size == 'fa-3x') echo ' selected="selected"' ?>><?php echo $this->lang('Très grande') ?></option>
					<option value="fa-4x"<?php if (isset($icon_size) && $icon_size == 'fa-4x') echo ' selected="selected"' ?>><?php echo $this->lang('Énorme !') ?></option>
				</select>
			</div>
		</div>
	</div>
	<div class="tab-pane fade" id="pills-display" role="tabpanel" aria-labelledby="pills-display-tab">
		<div class="nf-field row">
			<label for="settings-margin_top" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Marge en haut') ?></label>
			<div class="col-12 col-lg-4">
				<div class="nf-field mb-0">
					<div class="input-group">
						<div class="input-group-text"><?php echo icon('fas fa-caret-up') ?></div>
						<input type="number" class="form-control" name="settings[margin_top]" value="<?php echo $margin_top ? $margin_top : '0' ?>">
						<div class="input-group-text">px</div>
					</div>
				</div>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-margin_right" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Marge à droite') ?></label>
			<div class="col-12 col-lg-4">
				<div class="nf-field mb-0">
					<div class="input-group">
						<div class="input-group-text"><?php echo icon('fas fa-caret-right') ?></div>
						<input type="number" class="form-control" name="settings[margin_right]" value="<?php echo $margin_right ? $margin_right : '0' ?>">
						<div class="input-group-text">px</div>
					</div>
				</div>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-margin_bottom" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Marge en bas') ?></label>
			<div class="col-12 col-lg-4">
				<div class="nf-field mb-0">
					<div class="input-group">
						<div class="input-group-text"><?php echo icon('fas fa-caret-down') ?></div>
						<input type="number" class="form-control" name="settings[margin_bottom]" value="<?php echo $margin_bottom ? $margin_bottom : '0' ?>">
						<div class="input-group-text">px</div>
					</div>
				</div>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-margin_left" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Marge à gauche') ?></label>
			<div class="col-12 col-lg-4">
				<div class="nf-field mb-0">
					<div class="input-group">
						<div class="input-group-text"><?php echo icon('fas fa-caret-left') ?></div>
						<input type="number" class="form-control" name="settings[margin_left]" value="<?php echo $margin_left ? $margin_left : '0' ?>">
						<div class="input-group-text">px</div>
					</div>
				</div>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-padding_top" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Marge intérieure en haut') ?></label>
			<div class="col-12 col-lg-4">
				<div class="nf-field mb-0">
					<div class="input-group">
						<div class="input-group-text"><?php echo icon('far fa-caret-square-up') ?></div>
						<input type="number" class="form-control" name="settings[padding_top]" value="<?php echo $padding_top ? $padding_top : '0' ?>">
						<div class="input-group-text">px</div>
					</div>
				</div>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-padding_right" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Marge intérieure à droite') ?></label>
			<div class="col-12 col-lg-4">
				<div class="nf-field mb-0">
					<div class="input-group">
						<div class="input-group-text"><?php echo icon('far fa-caret-square-right') ?></div>
						<input type="number" class="form-control" name="settings[padding_right]" value="<?php echo $padding_right ? $padding_right : '0' ?>">
						<div class="input-group-text">px</div>
					</div>
				</div>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-padding_bottom" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Marge intérieure en bas') ?></label>
			<div class="col-12 col-lg-4">
				<div class="nf-field mb-0">
					<div class="input-group">
						<div class="input-group-text"><?php echo icon('far fa-caret-square-down') ?></div>
						<input type="number" class="form-control" name="settings[padding_bottom]" value="<?php echo $padding_bottom ? $padding_bottom : '0' ?>">
						<div class="input-group-text">px</div>
					</div>
				</div>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-padding_left" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Marge intérieure à gauche') ?></label>
			<div class="col-12 col-lg-4">
				<div class="nf-field mb-0">
					<div class="input-group">
						<div class="input-group-text"><?php echo icon('far fa-caret-square-left') ?></div>
						<input type="number" class="form-control" name="settings[padding_left]" value="<?php echo $padding_left ? $padding_left : '0' ?>">
						<div class="input-group-text">px</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
