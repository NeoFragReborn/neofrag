<ul class="nav nav-pills" id="pills-tab" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="pills-options-tab" data-bs-toggle="pill" href="#pills-options" role="tab" aria-controls="pills-options" aria-selected="true"><?php echo icon('fas fa-cogs').' '.$this->lang('Options') ?></a></li>
</ul>
<div class="tab-content border-light" id="pills-tabContent">
	<div class="tab-pane fade show active" id="pills-options" role="tabpanel" aria-labelledby="pills-options-tab">
		<div class="nf-field row">
			<label for="settings-display" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Affichage') ?></label>
			<div class="col-12 col-lg-3">
				<select class="form-select" name="settings[display]" id="settings-display">
					<option value="logo"<?php if (isset($display) && $display == 'logo') echo ' selected="selected"' ?>><?php echo $this->lang('Logo') ?></option>
					<option value="title"<?php if (!isset($display) || $display == 'title') echo ' selected="selected"' ?>><?php echo $this->lang('Titre et slogan') ?></option>
				</select>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-align" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Alignement') ?></label>
			<div class="col-12 col-lg-3">
				<select class="form-select" name="settings[align]" id="settings-align">
					<option value="text-start"<?php if (isset($align) && $align == 'text-start') echo ' selected="selected"' ?>><?php echo $this->lang('Gauche') ?></option>
					<option value="text-center"<?php if (!isset($align) || $align == 'text-center') echo ' selected="selected"' ?>><?php echo $this->lang('Centré') ?></option>
					<option value="text-end"<?php if (isset($align) && $align == 'text-end') echo ' selected="selected"' ?>><?php echo $this->lang('Droite') ?></option>
				</select>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-title" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Titre du site') ?></label>
			<div class="col-12 col-lg-6">
				<input type="text" class="form-control" name="settings[title]" value="<?php if (isset($title)) echo $title ?>" id="settings-title" placeholder="<?php echo $this->lang('Titre par défaut') ?>" />
			</div>
			<div class="col-12 col-lg-3">
				<div class="input-group">
					<span class="input-group-text"><?php echo icon('fas fa-paint-brush') ?></span>
					<input type="text" class="form-control" name="settings[color_title]" value="<?php if (isset($color_title)) echo $color_title ?>" placeholder="#000000" /><!-- //TODO color picker -->
				</div>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-description" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Description') ?></label>
			<div class="col-12 col-lg-6">
				<input type="text" class="form-control" name="settings[description]" value="<?php if (isset($description)) echo $description ?>" id="settings-description" placeholder="<?php echo $this->lang('Description par défaut') ?>" />
			</div>
			<div class="col-12 col-lg-3">
				<div class="input-group">
					<span class="input-group-text"><?php echo icon('fas fa-paint-brush') ?></span>
					<input type="text" class="form-control" name="settings[color_description]" value="<?php if (isset($color_description)) echo $color_description ?>" placeholder="#000000" /><!-- //TODO color picker -->
				</div>
			</div>
		</div>
	</div>
</div>
