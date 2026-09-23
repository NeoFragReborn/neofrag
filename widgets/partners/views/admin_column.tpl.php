<ul class="nav nav-pills" id="pills-tab" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="pills-options-tab" data-bs-toggle="pill" href="#pills-options" role="tab" aria-controls="pills-options" aria-selected="true"><?php echo icon('fas fa-cogs').' '.$this->lang('Options') ?></a></li>
</ul>
<div class="tab-content border-light" id="pills-tabContent">
	<div class="tab-pane fade show active" id="pills-options" role="tabpanel" aria-labelledby="pills-options-tab">
		<div class="nf-field row">
			<label for="settings-display_style" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Style des logos') ?></label>
			<div class="col-12 col-lg-4">
				<select class="form-select" name="settings[display_style]" id="settings-display_style">
					<option value="light"<?php if (!isset($display_style) || $display_style == 'light') echo ' selected="selected"' ?>><?php echo $this->lang('Logo clair') ?></option>
					<option value="dark"<?php if (isset($display_style) && $display_style == 'dark') echo ' selected="selected"' ?>><?php echo $this->lang('Logo foncé') ?></option>
				</select>
			</div>
		</div>
	</div>
</div>
