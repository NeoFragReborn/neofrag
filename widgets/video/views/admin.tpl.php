<div class="nf-field row">
	<label for="settings-count" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Nombre de vidéos') ?></label>
	<div class="col-12 col-lg-3">
		<input type="number" min="1" max="20" class="form-control" name="settings[count]" value="<?php echo (int) ($count ?: 5) ?>" />
	</div>
</div>
