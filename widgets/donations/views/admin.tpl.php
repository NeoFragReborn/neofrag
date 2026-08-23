<ul class="nav nav-pills" id="don-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#don-options" role="tab"><?php echo icon('fas fa-cogs').' '.$this->lang('Options') ?></a></li>
</ul>
<div class="tab-content border-light">
	<div class="tab-pane fade show active" id="don-options" role="tabpanel">
		<div class="form-group row">
			<label for="settings-don-campaign" class="col-4 col-form-label"><?php echo $this->lang('Campagne') ?></label>
			<div class="col-7">
				<select class="form-control" name="settings[campaign_id]" id="settings-don-campaign">
					<option value=""<?php if ((string)$campaign_id === '' || $campaign_id === '0') echo ' selected="selected"' ?>><?php echo $this->lang('Première campagne active (auto)') ?></option>
					<?php foreach ($campaigns as $c): ?>
					<option value="<?php echo $c['id'] ?>"<?php if ((int)$campaign_id === (int)$c['id']) echo ' selected="selected"' ?>><?php echo htmlspecialchars($c['title']) ?> (<?php echo $c['status'] ?>)</option>
					<?php endforeach ?>
				</select>
				<?php if (empty($campaigns)): ?>
				<small class="form-text text-warning"><?php echo $this->lang('Aucune campagne. Créez-en une depuis <a href="%s">l\'admin Dons</a>.', url('admin/donations')) ?></small>
				<?php endif ?>
			</div>
		</div>

		<?php if ($type === 'progress'): ?>
		<div class="form-group row">
			<label for="settings-don-recent" class="col-4 col-form-label"><?php echo $this->lang('Derniers donateurs') ?></label>
			<div class="col-7">
				<select class="form-control" name="settings[show_recent]" id="settings-don-recent">
					<option value="1"<?php if ($show_recent === '1') echo ' selected="selected"' ?>><?php echo $this->lang('Afficher (3 derniers)') ?></option>
					<option value="0"<?php if ($show_recent === '0') echo ' selected="selected"' ?>><?php echo $this->lang('Masquer') ?></option>
				</select>
			</div>
		</div>
		<div class="form-group row">
			<label for="settings-don-top" class="col-4 col-form-label"><?php echo $this->lang('Top donateur') ?></label>
			<div class="col-7">
				<select class="form-control" name="settings[show_top]" id="settings-don-top">
					<option value="1"<?php if ($show_top === '1') echo ' selected="selected"' ?>><?php echo $this->lang('Afficher') ?></option>
					<option value="0"<?php if ($show_top === '0') echo ' selected="selected"' ?>><?php echo $this->lang('Masquer') ?></option>
				</select>
			</div>
		</div>
		<?php else: /* type top */ ?>
		<div class="form-group row">
			<label for="settings-don-limit" class="col-4 col-form-label"><?php echo $this->lang('Nombre de donateurs') ?></label>
			<div class="col-3">
				<input type="number" class="form-control" name="settings[limit]" id="settings-don-limit" value="<?php echo (int)$limit ?: 5 ?>" min="1" max="20" />
			</div>
		</div>
		<?php endif ?>
	</div>
</div>
