<?php $_modbase = !empty($_user_side ?? FALSE) ? 'moderation' : 'admin/moderation'; ?>
<?php
$is_revoked  = !empty($sanction['revoked_at']);
$is_pending  = $sanction['requires_approval'] && empty($sanction['approved_at']) && !$is_revoked;
$is_expired  = !empty($sanction['expires_at']) && strtotime($sanction['expires_at']) < time();
$is_active   = !$is_revoked && !$is_pending && !$is_expired;
?>
<div class="card">
	<div class="nf-card-header">
		<span><i class="fas fa-gavel"></i> <?php echo $this->lang('Sanction #%d', (int)$sanction['id']) ?></span>
		<?php if ($is_active): ?><span class="badge text-bg-success"><?php echo $this->lang('Active') ?></span>
		<?php elseif ($is_pending): ?><span class="badge text-bg-warning"><?php echo $this->lang('Validation requise') ?></span>
		<?php elseif ($is_revoked): ?><span class="badge text-bg-secondary"><?php echo $this->lang('Levée') ?></span>
		<?php else: ?><span class="badge text-bg-light"><?php echo $this->lang('Expirée') ?></span>
		<?php endif ?>
	</div>
	<div class="card-body">
		<dl class="row">
			<dt class="col-sm-3"><?php echo $this->lang('Type') ?></dt>
			<dd class="col-sm-9"><span class="badge text-bg-danger"><?php echo nf_texte($this->module('moderation')->libelle('sanction', $sanction['type'])) ?></span></dd>

			<dt class="col-sm-3"><?php echo $this->lang('Scope') ?></dt>
			<dd class="col-sm-9"><?php echo nf_texte($this->module('moderation')->libelle('portee', $sanction['scope'])) ?></dd>

			<dt class="col-sm-3"><?php echo $this->lang('User sanctionné') ?></dt>
			<dd class="col-sm-9"><a href="<?php echo url($_modbase.'/users/'.(int)$sanction['user_id']) ?>">@<?php echo nf_texte($sanction['user_username']) ?></a></dd>

			<dt class="col-sm-3"><?php echo $this->lang('Émise par') ?></dt>
			<dd class="col-sm-9"><?php echo !empty($sanction['issuer_username']) ? '@'.nf_texte($sanction['issuer_username']) : '<i>'.$this->lang('Escalade automatique').'</i>' ?> <small class="text-muted">(<?php echo nf_date_heure($sanction['created_at']) ?>)</small></dd>

			<dt class="col-sm-3"><?php echo $this->lang('Démarre le') ?></dt>
			<dd class="col-sm-9"><?php echo nf_date_heure($sanction['starts_at']) ?></dd>

			<dt class="col-sm-3"><?php echo $this->lang('Expire le') ?></dt>
			<dd class="col-sm-9">
				<?php if (empty($sanction['expires_at'])): ?>
					<span class="badge text-bg-dark"><?php echo $this->lang('Permanent') ?></span>
				<?php else: ?>
					<?php echo nf_date_heure($sanction['expires_at']) ?>
					<?php if (!$is_expired && !$is_revoked): ?>
						<small class="text-muted">(<?php echo time_span(strtotime($sanction['expires_at'])) ?>)</small>
					<?php endif ?>
				<?php endif ?>
			</dd>

			<dt class="col-sm-3"><?php echo $this->lang('Raison') ?></dt>
			<dd class="col-sm-9"><blockquote class="m-0"><?php echo nl2br(nf_texte($sanction['reason'])) ?></blockquote></dd>

			<?php if ($sanction['requires_approval']): ?>
			<dt class="col-sm-3"><?php echo $this->lang('Validation hiérarchique') ?></dt>
			<dd class="col-sm-9">
				<?php if (!empty($sanction['approved_at'])): ?>
					<span class="badge text-bg-success"><i class="fas fa-check"></i> <?php echo $this->lang('Approuvée par @%s le %s', nf_texte($sanction['approver_username']), nf_date_heure($sanction['approved_at'])) ?></span>
				<?php else: ?>
					<span class="badge text-bg-warning"><?php echo $this->lang('En attente d\'approbation') ?></span>
				<?php endif ?>
			</dd>
			<?php endif ?>

			<?php if ($is_revoked): ?>
			<dt class="col-sm-3"><?php echo $this->lang('Levée') ?></dt>
			<dd class="col-sm-9">
				<small class="text-muted"><?php echo $this->lang('Par @%s le %s', nf_texte($sanction['revoker_username']), nf_date_heure($sanction['revoked_at'])) ?></small><br>
				<strong><?php echo $this->lang('Raison de la levée :') ?></strong> <?php echo nf_texte($sanction['revoke_reason']) ?>
			</dd>
			<?php endif ?>

			<?php if ($sanction['related_report_id']): ?>
			<dt class="col-sm-3"><?php echo $this->lang('Issue d\'un signalement') ?></dt>
			<dd class="col-sm-9"><a href="<?php echo url($_modbase.'/reports/'.(int)$sanction['related_report_id']) ?>"><?php echo $this->lang('Signalement #%d', (int)$sanction['related_report_id']) ?></a></dd>
			<?php endif ?>
		</dl>

		<hr />

		<div class="text-end">
			<?php if ($is_pending && $can_approve): ?>
			<form method="post" action="<?php echo url($_modbase.'/sanctions/'.(int)$sanction['id'].'/approve') ?>" style="display:inline;"
				data-confirm="<?php echo nf_texte($this->lang('Approuver cette sanction ? Elle deviendra immédiatement active.')) ?>"
				data-confirm-title="<?php echo nf_texte($this->lang('Approuver la sanction')) ?>"
				data-confirm-style="success"
				data-confirm-icon="fas fa-check-double"
				data-confirm-ok="<?php echo nf_texte($this->lang('Approuver')) ?>"
				data-confirm-cancel="<?php echo nf_texte($this->lang('Annuler')) ?>">
				<input type="hidden" name="_" value="<?php echo $csrf ?>"><button type="submit" class="btn btn-success"><i class="fas fa-check-double"></i> <?php echo $this->lang('Approuver') ?></button>
			</form>
			<?php endif ?>
			<?php if (($is_active || $is_pending) && $can_revoke): ?>
			<button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#nf-revoke-modal">
				<i class="fas fa-undo"></i> <?php echo $this->lang('Lever cette sanction') ?>
			</button>
			<?php endif ?>
		</div>
	</div>
</div>

<?php if (($is_active || $is_pending) && $can_revoke): ?>
<!-- Modal stylé pour la levée de sanction -->
<div class="modal fade" id="nf-revoke-modal" tabindex="-1" role="dialog" aria-labelledby="nf-revoke-modal-title" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<form method="post" action="<?php echo url($_modbase.'/sanctions/'.(int)$sanction['id'].'/revoke') ?>"><input type="hidden" name="_" value="<?php echo $csrf ?>">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="nf-revoke-modal-title">
						<i class="fas fa-undo"></i> <?php echo $this->lang('Lever la sanction #%d', (int)$sanction['id']) ?>
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo $this->lang('Fermer') ?>">
					</button>
				</div>
				<div class="modal-body">
					<div class="alert alert-info mb-3">
						<i class="fas fa-info-circle"></i>
						<?php echo $this->lang('Tu vas lever la sanction <strong>%s</strong> appliquée à <strong>@%s</strong>. Cette action est tracée dans l\'audit log.', nf_texte($this->module('moderation')->libelle('sanction', $sanction['type'])), nf_texte($sanction['user_username'])) ?>
					</div>
					<div class="nf-field">
						<label for="nf-revoke-reason" class="fw-bold">
							<?php echo $this->lang('Raison de la levée') ?> <span class="text-danger">*</span>
						</label>
						<textarea
							id="nf-revoke-reason"
							name="reason"
							class="form-control"
							rows="3"
							required
							maxlength="1000"
							placeholder="<?php echo $this->lang('Pourquoi cette sanction est-elle levée ? (visible dans l\'historique de modération)') ?>"></textarea>
						<small class="text-muted"><?php echo $this->lang('Cette raison apparaît dans l\'historique du user et l\'audit log admin.') ?></small>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
						<i class="fas fa-times"></i> <?php echo $this->lang('Annuler') ?>
					</button>
					<button type="submit" class="btn btn-warning">
						<i class="fas fa-undo"></i> <?php echo $this->lang('Lever la sanction') ?>
					</button>
				</div>
			</div>
		</form>
	</div>
</div>
<?php endif ?>
