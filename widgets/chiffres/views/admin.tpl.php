<?php
$nombres       = (array) ($nombres ?? []);
$display_panel = $display_panel ?? 'oui';
$libelles      = [
	'membres'     => $this->lang('Les membres'),
	'discussions' => $this->lang('Les discussions du forum'),
	'messages'    => $this->lang('Les messages du forum'),
	'actualites'  => $this->lang('Les actualités'),
	'rendez_vous' => $this->lang('Les rendez-vous à venir'),
	'photos'      => $this->lang('Les photos')
];
?>
<fieldset class="nf-field row">
	<legend class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Nombres montrés') ?></legend>
	<div class="col-12 col-lg-9">
		<?php foreach ($libelles as $nom => $libelle): ?>
		<div class="form-check">
			<input class="form-check-input" type="checkbox" name="settings[nombres][]" id="settings-nombres-<?php echo $nom ?>" value="<?php echo $nom ?>"<?php if (in_array($nom, $nombres, TRUE)) echo ' checked="checked"' ?> />
			<label class="form-check-label" for="settings-nombres-<?php echo $nom ?>"><?php echo $libelle ?></label>
		</div>
		<?php endforeach ?>
		<div class="form-text"><?php echo $this->lang('Quatre au plus ; chacun ne paraît que si son module est installé.') ?></div>
	</div>
</fieldset>
<div class="nf-field row">
	<label for="settings-display_panel" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Afficher dans un panneau') ?></label>
	<div class="col-12 col-lg-2">
		<select class="form-select" name="settings[display_panel]" id="settings-display_panel">
			<option value="oui"<?php if ($display_panel == 'oui') echo ' selected="selected"' ?>><?php echo $this->lang('Oui') ?></option>
			<option value="non"<?php if ($display_panel == 'non') echo ' selected="selected"' ?>><?php echo $this->lang('Non') ?></option>
		</select>
	</div>
</div>
