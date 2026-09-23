<ul class="nav nav-pills" id="pills-tab" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="pills-options-tab" data-bs-toggle="pill" href="#pills-options" role="tab" aria-controls="pills-options" aria-selected="true"><?php echo icon('fas fa-cogs').' '.$this->lang('Options') ?></a></li>
</ul>
<div class="tab-content border-light" id="pills-tabContent">
	<div class="tab-pane fade show active" id="pills-options" role="tabpanel" aria-labelledby="pills-options-tab">
		<div class="nf-field row">
			<label for="settings-category" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Galerie à afficher') ?></label>
			<div class="col-12 col-lg-6">
				<select class="form-select" name="settings[category_id]" id="settings-category">
					<?php foreach ($categories as $category): ?>
						<option value="<?php echo $category['category_id'] ?>"<?php if ($category_id == $category['category_id']) echo ' selected="selected"' ?>><?php echo $category['title'] ?></option>
					<?php endforeach ?>
				</select>
			</div>
		</div>
	</div>
</div>
