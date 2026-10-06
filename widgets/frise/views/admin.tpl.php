<?php
$count         = $count ?? 12;
$mois          = $mois ?? 3;
$display_panel = $display_panel ?? 'oui';
?>
<div class="nf-field row">
	<label for="settings-count" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Nombre d\'entrées') ?></label>
	<div class="col-12 col-lg-2">
		<input type="number" class="form-control" name="settings[count]" id="settings-count" min="5" max="30" value="<?php echo (int) $count ?>" />
	</div>
</div>
<div class="nf-field row">
	<label for="settings-mois" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Mois passés') ?></label>
	<div class="col-12 col-lg-2">
		<input type="number" class="form-control" name="settings[mois]" id="settings-mois" min="1" max="12" value="<?php echo (int) $mois ?>" aria-describedby="settings-mois-aide" />
	</div>
	<div class="col-12 col-lg-7 form-text" id="settings-mois-aide"><?php echo $this->lang('Jusqu\'où la frise remonte dans le passé ; les rendez-vous à venir y sont toujours.') ?></div>
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
