<ul class="nav nav-pills" id="pills-tab" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="pills-options-tab" data-bs-toggle="pill" href="#pills-options" role="tab" aria-controls="pills-options" aria-selected="true"><?php echo icon('fas fa-cogs').' '.$this->lang('Options') ?></a></li>
</ul>
<div class="tab-content border-light" id="pills-tabContent">
	<div class="tab-pane fade show active" id="pills-options" role="tabpanel" aria-labelledby="pills-options-tab">
		<div class="nf-field row">
			<label for="settings-recruits" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Offre à afficher') ?></label>
			<div class="col-12 col-lg-9">
				<select class="form-select" name="settings[recruit_id]" id="settings-recruits">
					<?php foreach ($recruits as $recruit): ?>
						<option value="<?php echo $recruit['recruit_id'] ?>"<?php if ($recruit_id == $recruit['recruit_id']) echo ' selected="selected"' ?>><?php echo $recruit['title'] ?></option>
					<?php endforeach ?>
				</select>
			</div>
		</div>
	</div>
</div>
