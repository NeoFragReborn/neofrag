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
		<?php if ($is_active): ?><span class="badge badge-success"><?php echo $this->lang('Active') ?></span>
		<?php elseif ($is_pending): ?><span class="badge badge-warning"><?php echo $this->lang('Validation requise') ?></span>
		<?php elseif ($is_revoked): ?><span class="badge badge-secondary"><?php echo $this->lang('Levée') ?></span>
		<?php else: ?><span class="badge badge-light"><?php echo $this->lang('Expirée') ?></span>
		<?php endif ?>
	</div>
	<div class="card-body">
		<dl class="row">
			<dt class="col-sm-3"><?php echo $this->lang('Type') ?></dt>
			<dd class="col-sm-9"><span class="badge badge-danger"><?php echo htmlspecialchars($sanction['type']) ?></span></dd>

			<dt class="col-sm-3"><?php echo $this->lang('Scope') ?></dt>
			<dd class="col-sm-9"><?php echo htmlspecialchars($sanction['scope']) ?></dd>

			<dt class="col-sm-3"><?php echo $this->lang('User sanctionné') ?></dt>
			<dd class="col-sm-9"><a href="<?php echo url($_modbase.'/users/'.(int)$sanction['user_id']) ?>">@<?php echo htmlspecialchars((string)$sanction['user_username']) ?></a></dd>

			<dt class="col-sm-3"><?php echo $this->lang('Émise par') ?></dt>
			<dd class="col-sm-9">@<?php echo htmlspecialchars((string)$sanction['issuer_username']) ?> <small class="text-muted">(<?php echo htmlspecialchars($sanction['created_at']) ?>)</small></dd>

			<dt class="col-sm-3"><?php echo $this->lang('Démarre le') ?></dt>
			<dd class="col-sm-9"><?php echo htmlspecialchars($sanction['starts_at']) ?></dd>

			<dt class="col-sm-3"><?php echo $this->lang('Expire le') ?></dt>
			<dd class="col-sm-9">
				<?php if (empty($sanction['expires_at'])): ?>
					<span class="badge badge-dark"><?php echo $this->lang('Permanent') ?></span>
				<?php else: ?>
					<?php echo htmlspecialchars($sanction['expires_at']) ?>
					<?php if (!$is_expired && !$is_revoked): ?>
						<small class="text-muted">(<?php echo time_span(strtotime($sanction['expires_at'])) ?>)</small>
					<?php endif ?>
				<?php endif ?>
			</dd>

			<dt class="col-sm-3"><?php echo $this->lang('Raison') ?></dt>
			<dd class="col-sm-9"><blockquote class="m-0"><?php echo nl2br(htmlspecialchars($sanction['reason'])) ?></blockquote></dd>

			<?php if ($sanction['requires_approval']): ?>
			<dt class="col-sm-3"><?php echo $this->lang('Validation hiérarchique') ?></dt>
			<dd class="col-sm-9">
				<?php if (!empty($sanction['approved_at'])): ?>
					<span class="badge badge-success"><i class="fas fa-check"></i> <?php echo $this->lang('Approuvée par @%s le %s', htmlspecialchars((string)$sanction['approver_username']), htmlspecialchars($sanction['approved_at'])) ?></span>
				<?php else: ?>
					<span class="badge badge-warning"><?php echo $this->lang('En attente d\'approbation') ?></span>
				<?php endif ?>
			</dd>
			<?php endif ?>

			<?php if ($is_revoked): ?>
			<dt class="col-sm-3"><?php echo $this->lang('Levée') ?></dt>
			<dd class="col-sm-9">
				<small class="text-muted"><?php echo $this->lang('Par @%s le %s', htmlspecialchars((string)$sanction['revoker_username']), htmlspecialchars($sanction['revoked_at'])) ?></small><br>
				<strong><?php echo $this->lang('Raison de la levée :') ?></strong> <?php echo htmlspecialchars($sanction['revoke_reason']) ?>
			</dd>
			<?php endif ?>

			<?php if ($sanction['related_report_id']): ?>
			<dt class="col-sm-3"><?php echo $this->lang('Issue d\'un signalement') ?></dt>
			<dd class="col-sm-9"><a href="<?php echo url($_modbase.'/reports/'.(int)$sanction['related_report_id']) ?>"><?php echo $this->lang('Signalement #%d', (int)$sanction['related_report_id']) ?></a></dd>
			<?php endif ?>
		</dl>

		<hr />

		<div class="text-right">
			<?php if ($is_pending && $can_approve): ?>
			<form method="post" action="<?php echo url($_modbase.'/sanctions/'.(int)$sanction['id'].'/approve') ?>" style="display:inline;"
				data-confirm="<?php echo htmlspecialchars($this->lang('Approuver cette sanction ? Elle deviendra immédiatement active.'), ENT_QUOTES) ?>"
				data-confirm-title="<?php echo htmlspecialchars($this->lang('Approuver la sanction'), ENT_QUOTES) ?>"
				data-confirm-style="success"
				data-confirm-icon="fas fa-check-double"
				data-confirm-ok="<?php echo htmlspecialchars($this->lang('Approuver'), ENT_QUOTES) ?>"
				data-confirm-cancel="<?php echo htmlspecialchars($this->lang('Annuler'), ENT_QUOTES) ?>">
				<button type="submit" class="btn btn-success"><i class="fas fa-check-double"></i> <?php echo $this->lang('Approuver') ?></button>
			</form>
			<?php endif ?>
			<?php if (($is_active || $is_pending) && $can_revoke): ?>
			<button type="button" class="btn btn-warning" data-toggle="modal" data-target="#nf-revoke-modal">
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
		<form method="post" action="<?php echo url($_modbase.'/sanctions/'.(int)$sanction['id'].'/revoke') ?>">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="nf-revoke-modal-title">
						<i class="fas fa-undo"></i> <?php echo $this->lang('Lever la sanction #%d', (int)$sanction['id']) ?>
					</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="<?php echo $this->lang('Fermer') ?>">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="alert alert-info mb-3">
						<i class="fas fa-info-circle"></i>
						<?php echo $this->lang('Tu vas lever la sanction <strong>%s</strong> appliquée à <strong>@%s</strong>. Cette action est tracée dans l\'audit log.', htmlspecialchars($sanction['type']), htmlspecialchars((string)$sanction['user_username'])) ?>
					</div>
					<div class="form-group">
						<label for="nf-revoke-reason" class="font-weight-bold">
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
					<button type="button" class="btn btn-secondary" data-dismiss="modal">
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
