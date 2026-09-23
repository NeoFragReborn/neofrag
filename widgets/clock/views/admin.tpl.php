<ul class="nav nav-pills" id="clock-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="clock-options-tab" data-bs-toggle="pill" href="#clock-options" role="tab" aria-controls="clock-options" aria-selected="true"><?php echo icon('fas fa-cogs').' '.$this->lang('Options') ?></a></li>
</ul>
<div class="tab-content border-light" id="clock-tabContent">
	<div class="tab-pane fade show active" id="clock-options" role="tabpanel" aria-labelledby="clock-options-tab">
		<div class="nf-field row">
			<label for="settings-clock" class="col-12 col-lg-4 col-form-label"><i class="far fa-clock"></i> <?php echo $this->lang('Heure') ?></label>
			<div class="col-12 col-lg-7">
				<select class="form-select" name="settings[clock]" id="settings-clock">
					<option value="1"<?php if ($clock === '1') echo ' selected="selected"' ?>><?php echo $this->lang('Afficher') ?></option>
					<option value="0"<?php if ($clock === '0') echo ' selected="selected"' ?>><?php echo $this->lang('Masquer') ?></option>
				</select>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-calendar" class="col-12 col-lg-4 col-form-label"><i class="far fa-calendar"></i> <?php echo $this->lang('Date du jour') ?></label>
			<div class="col-12 col-lg-7">
				<select class="form-select" name="settings[calendar]" id="settings-calendar">
					<option value="1"<?php if ($calendar === '1') echo ' selected="selected"' ?>><?php echo $this->lang('Afficher') ?></option>
					<option value="0"<?php if ($calendar === '0') echo ' selected="selected"' ?>><?php echo $this->lang('Masquer') ?></option>
				</select>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-birthday" class="col-12 col-lg-4 col-form-label"><i class="fas fa-birthday-cake"></i> <?php echo $this->lang('Anniversaires') ?></label>
			<div class="col-12 col-lg-7">
				<select class="form-select" name="settings[birthday]" id="settings-birthday">
					<option value="1"<?php if ($birthday === '1') echo ' selected="selected"' ?>><?php echo $this->lang('Afficher') ?></option>
					<option value="0"<?php if ($birthday === '0') echo ' selected="selected"' ?>><?php echo $this->lang('Masquer') ?></option>
				</select>
			</div>
		</div>
	</div>
</div>
