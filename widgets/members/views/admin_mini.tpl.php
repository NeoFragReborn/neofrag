<ul class="nav nav-pills" id="pills-tab" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="pills-options-tab" data-bs-toggle="pill" href="#pills-options" role="tab" aria-controls="pills-options" aria-selected="true"><?php echo icon('fas fa-cogs').' Options' ?></a></li>
</ul>
<div class="tab-content border-light" id="pills-tabContent">
	<div class="tab-pane fade show active" id="pills-options" role="tabpanel" aria-labelledby="pills-options-tab">
		<div class="form-group row">
			<label for="settings-title" class="col-3 col-form-label"><?php echo $this->lang('Alignement') ?></label>
			<div class="col-9">
				<label class="radio-inline">
					<input type="radio" name="settings[align]" value="float-start"<?php if (!isset($align) || $align != 'float-end') echo ' checked="checked"' ?> /> <?php echo $this->lang('à gauche') ?>
				</label>
				<label class="radio-inline">
					<input type="radio" name="settings[align]" value="float-end"<?php if (isset($align) && $align == 'float-end') echo ' checked="checked"' ?> /> <?php echo $this->lang('à droite') ?>
				</label>
			</div>
		</div>
	</div>
</div>
