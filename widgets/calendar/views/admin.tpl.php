<div class="nf-field row">
	<label for="settings-count" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Nombre d\'événements') ?></label>
	<div class="col-12 col-lg-2">
		<input type="number" class="form-control" name="settings[count]" id="settings-count" min="1" max="20" value="<?php echo (int)$count ?>" />
	</div>
</div>
<div class="nf-field row">
	<label for="settings-display_panel" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Afficher dans un panneau') ?></label>
	<div class="col-12 col-lg-2">
		<select class="form-select" name="settings[display_panel]" id="settings-display_panel">
			<option value="oui"<?php if ($display_panel == 'oui') echo ' selected="selected"' ?>><?php echo $this->lang('Oui') ?></option>
			<option value="non"<?php if ($display_panel == 'non') echo ' selected="selected"' ?>><?php echo $this->lang('Non') ?></option>
		</select>
	</div>
</div>
