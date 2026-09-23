<ul class="nav nav-pills" id="pills-tab" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="pills-options-tab" data-bs-toggle="pill" href="#pills-options" role="tab" aria-controls="pills-options" aria-selected="true"><?php echo icon('fas fa-cogs').' '.$this->lang('Options') ?></a></li>
</ul>
<div class="tab-content border-light" id="pills-tabContent">
	<div class="tab-pane fade show active" id="pills-options" role="tabpanel" aria-labelledby="pills-options-tab">
		<div class="nf-field row">
			<label for="settings-events" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Type') ?></label>
			<div class="col-12 col-lg-6">
				<select class="form-select" name="settings[type_id]" id="settings-events">
					<option value="0"<?php if ($type_id == 0) echo ' selected="selected"' ?>><?php echo $this->lang('Tous') ?></option>
					<?php foreach ($types as $type): ?>
						<option value="<?php echo $type['type_id'] ?>"<?php if ($type_id == $type['type_id']) echo ' selected="selected"' ?>><?php echo $type['title'] ?></option>
					<?php endforeach ?>
				</select>
			</div>
		</div>
	</div>
</div>
