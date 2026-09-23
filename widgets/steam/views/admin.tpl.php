<ul class="nav nav-pills" id="steam-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="steam-options-tab" data-bs-toggle="pill" href="#steam-options" role="tab" aria-controls="steam-options" aria-selected="true"><?php echo icon('fas fa-cogs').' '.$this->lang('Options') ?></a></li>
</ul>
<div class="tab-content border-light" id="steam-tabContent">
	<div class="tab-pane fade show active" id="steam-options" role="tabpanel" aria-labelledby="steam-options-tab">
		<div class="nf-field row">
			<label for="settings-steam-group" class="col-12 col-lg-4 col-form-label"><i class="fab fa-steam"></i> <?php echo $this->lang('Groupe Steam') ?></label>
			<div class="col-12 col-lg-7">
				<input type="text" class="form-control" name="settings[group]" id="settings-steam-group" value="<?php echo htmlspecialchars($group) ?>" placeholder="ex: Valve OU 103582791429521408" autocomplete="off" />
				<small class="form-text text-muted"><?php echo $this->lang('URL personnalisée du groupe (vanity URL) OU son SteamID64 numérique. Visible dans l\'URL https://steamcommunity.com/groups/<b>VOTRE-GROUPE</b>.') ?></small>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-steam-display" class="col-12 col-lg-4 col-form-label"><?php echo $this->lang('Affichage') ?></label>
			<div class="col-12 col-lg-7">
				<select class="form-select" name="settings[display]" id="settings-steam-display">
					<option value="compact"<?php if ($display === 'compact') echo ' selected="selected"' ?>><?php echo $this->lang('Compact (avatar + statistiques)') ?></option>
					<option value="full"<?php if ($display === 'full') echo ' selected="selected"' ?>><?php echo $this->lang('Étendu (titre + détails)') ?></option>
				</select>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-steam-avatar" class="col-12 col-lg-4 col-form-label"><?php echo $this->lang('Afficher l\'avatar') ?></label>
			<div class="col-12 col-lg-7">
				<select class="form-select" name="settings[show_avatar]" id="settings-steam-avatar">
					<option value="1"<?php if ($show_avatar === '1') echo ' selected="selected"' ?>><?php echo $this->lang('Oui') ?></option>
					<option value="0"<?php if ($show_avatar === '0') echo ' selected="selected"' ?>><?php echo $this->lang('Non') ?></option>
				</select>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-steam-summary" class="col-12 col-lg-4 col-form-label"><?php echo $this->lang('Afficher la description') ?></label>
			<div class="col-12 col-lg-7">
				<select class="form-select" name="settings[show_summary]" id="settings-steam-summary">
					<option value="1"<?php if ($show_summary === '1') echo ' selected="selected"' ?>><?php echo $this->lang('Oui') ?></option>
					<option value="0"<?php if ($show_summary === '0') echo ' selected="selected"' ?>><?php echo $this->lang('Non') ?></option>
				</select>
			</div>
		</div>
	</div>
</div>
