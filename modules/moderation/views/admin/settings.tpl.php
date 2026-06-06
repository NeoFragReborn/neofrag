<form method="post" class="card">
	<div class="card-body">
		<h5 class="mb-3"><i class="fas fa-cogs"></i> <?php echo $this->lang('Réglages modération') ?></h5>

		<div class="form-group">
			<div class="form-check">
				<input type="checkbox" class="form-check-input" id="nf_moderation_enabled" name="nf_moderation_enabled" value="1"<?php echo $config->nf_moderation_enabled ? ' checked' : '' ?> />
				<label class="form-check-label" for="nf_moderation_enabled"><?php echo $this->lang('Système de modération activé') ?></label>
			</div>
		</div>

		<hr />
		<h6><i class="fas fa-clock"></i> <?php echo $this->lang('Durées par défaut') ?></h6>

		<div class="form-row">
			<div class="form-group col-md-6">
				<label><?php echo $this->lang('Mute par défaut (secondes)') ?></label>
				<input type="number" class="form-control" name="nf_moderation_default_mute_duration_seconds" value="<?php echo (int)$config->nf_moderation_default_mute_duration_seconds ?>" min="60" />
				<small class="text-muted"><?php echo $this->lang('86400 = 24h') ?></small>
			</div>
			<div class="form-group col-md-6">
				<label><?php echo $this->lang('Ban temp par défaut (secondes)') ?></label>
				<input type="number" class="form-control" name="nf_moderation_default_ban_temp_duration_seconds" value="<?php echo (int)$config->nf_moderation_default_ban_temp_duration_seconds ?>" min="3600" />
				<small class="text-muted"><?php echo $this->lang('604800 = 7 jours') ?></small>
			</div>
		</div>

		<hr />
		<h6><i class="fas fa-check-double"></i> <?php echo $this->lang('Validation hiérarchique') ?></h6>

		<div class="form-group">
			<div class="form-check">
				<input type="checkbox" class="form-check-input" id="nf_moderation_require_approval_ban_perm" name="nf_moderation_require_approval_ban_perm" value="1"<?php echo $config->nf_moderation_require_approval_ban_perm ? ' checked' : '' ?> />
				<label class="form-check-label" for="nf_moderation_require_approval_ban_perm"><?php echo $this->lang('Ban définitif requiert validation hiérarchique') ?></label>
			</div>
			<div class="form-check">
				<input type="checkbox" class="form-check-input" id="nf_moderation_require_approval_ban_temp" name="nf_moderation_require_approval_ban_temp" value="1"<?php echo $config->nf_moderation_require_approval_ban_temp ? ' checked' : '' ?> />
				<label class="form-check-label" for="nf_moderation_require_approval_ban_temp"><?php echo $this->lang('Ban temporaire requiert validation hiérarchique') ?></label>
			</div>
		</div>

		<hr />
		<h6><i class="fas fa-fast-forward"></i> <?php echo $this->lang('Escalade automatique') ?></h6>

		<div class="form-group">
			<div class="form-check">
				<input type="checkbox" class="form-check-input" id="nf_moderation_auto_escalation" name="nf_moderation_auto_escalation" value="1"<?php echo $config->nf_moderation_auto_escalation ? ' checked' : '' ?> />
				<label class="form-check-label" for="nf_moderation_auto_escalation"><?php echo $this->lang('Activer l\'escalade automatique (warnings → mute → ban)') ?></label>
			</div>
		</div>

		<div class="form-row">
			<div class="form-group col-md-4">
				<label><?php echo $this->lang('Fenêtre warnings (jours)') ?></label>
				<input type="number" class="form-control" name="nf_moderation_warning_window_days" value="<?php echo (int)$config->nf_moderation_warning_window_days ?>" min="1" />
			</div>
			<div class="form-group col-md-4">
				<label><?php echo $this->lang('Seuil mute auto') ?></label>
				<input type="number" class="form-control" name="nf_moderation_warning_threshold_mute" value="<?php echo (int)$config->nf_moderation_warning_threshold_mute ?>" min="1" />
				<small class="text-muted"><?php echo $this->lang('Nb warnings → mute auto 24h') ?></small>
			</div>
			<div class="form-group col-md-4">
				<label><?php echo $this->lang('Seuil ban auto') ?></label>
				<input type="number" class="form-control" name="nf_moderation_warning_threshold_ban" value="<?php echo (int)$config->nf_moderation_warning_threshold_ban ?>" min="1" />
				<small class="text-muted"><?php echo $this->lang('Nb warnings → ban temp auto') ?></small>
			</div>
		</div>

		<hr />
		<h6><i class="fas fa-shield-alt"></i> <?php echo $this->lang('Anti-abus reports') ?></h6>

		<div class="form-row">
			<div class="form-group col-md-6">
				<label><?php echo $this->lang('Max reports / heure / user') ?></label>
				<input type="number" class="form-control" name="nf_moderation_report_rate_limit_per_hour" value="<?php echo (int)$config->nf_moderation_report_rate_limit_per_hour ?>" min="1" />
			</div>
			<div class="form-group col-md-6">
				<label><?php echo $this->lang('Seuil flag reporter suspect (par jour)') ?></label>
				<input type="number" class="form-control" name="nf_moderation_report_flag_threshold_per_day" value="<?php echo (int)$config->nf_moderation_report_flag_threshold_per_day ?>" min="1" />
			</div>
		</div>

		<hr />

		<div class="form-group">
			<div class="form-check">
				<input type="checkbox" class="form-check-input" id="nf_moderation_preserve_content_snapshot" name="nf_moderation_preserve_content_snapshot" value="1"<?php echo $config->nf_moderation_preserve_content_snapshot ? ' checked' : '' ?> />
				<label class="form-check-label" for="nf_moderation_preserve_content_snapshot"><?php echo $this->lang('Préserver une copie du contenu signalé (preuve, même si édité après)') ?></label>
			</div>
		</div>

		<button type="submit" name="save_moderation_settings" value="1" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $this->lang('Enregistrer') ?></button>
	</div>
</form>
