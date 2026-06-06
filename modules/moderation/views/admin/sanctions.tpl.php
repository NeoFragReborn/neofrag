<?php $_modbase = !empty($_user_side ?? FALSE) ? 'moderation' : 'admin/moderation'; ?>
<form method="get" class="card mb-3">
	<div class="card-body">
		<div class="row align-items-end">
			<div class="col-md-3">
				<label class="small text-muted"><?php echo $this->lang('Type') ?></label>
				<select class="form-control form-control-sm" name="type">
					<option value=""><?php echo $this->lang('Tous') ?></option>
					<?php foreach (['warning','mute','ban_temp','ban_perm','restrict_upload','restrict_links','restrict_avatar','restrict_signature','restrict_comment','shadow_ban'] as $t): ?>
					<option value="<?php echo $t ?>"<?php echo ($filter['type'] === $t ? ' selected' : '') ?>><?php echo htmlspecialchars($t) ?></option>
					<?php endforeach ?>
				</select>
			</div>
			<div class="col-md-3">
				<div class="form-check">
					<input type="checkbox" class="form-check-input" id="active_only" name="active_only" value="1"<?php echo $filter['active_only'] ? ' checked' : '' ?> />
					<label class="form-check-label" for="active_only"><?php echo $this->lang('Sanctions actives uniquement') ?></label>
				</div>
				<div class="form-check">
					<input type="checkbox" class="form-check-input" id="pending_approval" name="pending_approval" value="1"<?php echo $filter['pending_approval'] ? ' checked' : '' ?> />
					<label class="form-check-label" for="pending_approval"><?php echo $this->lang('En attente d\'approbation') ?></label>
				</div>
			</div>
			<div class="col-md-3">
				<button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> <?php echo $this->lang('Filtrer') ?></button>
				<a class="btn btn-sm btn-link" href="<?php echo url($_modbase.'/sanctions') ?>"><?php echo $this->lang('Réinitialiser') ?></a>
			</div>
		</div>
	</div>
</form>

<div class="card">
	<?php if (empty($sanctions)): ?>
		<div class="card-body text-center text-muted py-5">
			<i class="fas fa-gavel fa-2x mb-2"></i><br>
			<?php echo $this->lang('Aucune sanction avec ces critères.') ?>
		</div>
	<?php else: ?>
		<table class="table table-hover m-0">
			<thead>
				<tr>
					<th><?php echo $this->lang('Date') ?></th>
					<th><?php echo $this->lang('Type / Scope') ?></th>
					<th><?php echo $this->lang('User sanctionné') ?></th>
					<th><?php echo $this->lang('Émis par') ?></th>
					<th><?php echo $this->lang('Durée / Expiration') ?></th>
					<th><?php echo $this->lang('Statut') ?></th>
					<th class="text-right"><?php echo $this->lang('Action') ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($sanctions as $s):
				$is_revoked = !empty($s['revoked_at']);
				$is_pending = $s['requires_approval'] && empty($s['approved_at']) && !$is_revoked;
				$is_expired = !empty($s['expires_at']) && strtotime($s['expires_at']) < time();
				$is_active  = !$is_revoked && !$is_pending && !$is_expired;
			?>
				<tr<?php echo $is_active ? '' : ' class="text-muted"' ?>>
					<td><small title="<?php echo htmlspecialchars($s['created_at']) ?>"><?php echo time_span(strtotime($s['created_at'])) ?></small></td>
					<td><span class="badge badge-<?php echo strpos($s['type'], 'ban') !== FALSE ? 'danger' : (strpos($s['type'], 'restrict') !== FALSE ? 'warning' : (strpos($s['type'], 'mute') !== FALSE ? 'orange' : 'info')) ?>"><?php echo htmlspecialchars($s['type']) ?></span><br><small class="text-muted"><?php echo htmlspecialchars($s['scope']) ?></small></td>
					<td><a href="<?php echo url($_modbase.'/users/'.(int)$s['user_id']) ?>">@<?php echo htmlspecialchars((string)$s['user_username']) ?></a></td>
					<td><small><?php echo htmlspecialchars((string)$s['issuer_username']) ?></small></td>
					<td>
						<?php if (empty($s['expires_at'])): ?>
							<span class="badge badge-dark"><?php echo $this->lang('Permanent') ?></span>
						<?php else: ?>
							<small><?php echo htmlspecialchars($s['expires_at']) ?></small>
						<?php endif ?>
					</td>
					<td>
						<?php if ($is_revoked): ?>
							<span class="badge badge-secondary"><?php echo $this->lang('Levée') ?></span>
						<?php elseif ($is_pending): ?>
							<span class="badge badge-warning"><?php echo $this->lang('Validation requise') ?></span>
						<?php elseif ($is_expired): ?>
							<span class="badge badge-light"><?php echo $this->lang('Expirée') ?></span>
						<?php else: ?>
							<span class="badge badge-success"><?php echo $this->lang('Active') ?></span>
						<?php endif ?>
					</td>
					<td class="text-right">
						<a class="btn btn-sm btn-outline-primary" href="<?php echo url($_modbase.'/sanctions/'.(int)$s['id']) ?>"><i class="fas fa-eye"></i></a>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	<?php endif ?>
</div>

<?php if (count($sanctions) >= 50): ?>
<div class="mt-3 text-center">
	<?php if ($page > 0): ?><a class="btn btn-secondary" href="<?php echo url($_modbase.'/sanctions/'.($page - 1)) ?>"><i class="fas fa-arrow-left"></i> <?php echo $this->lang('Précédent') ?></a><?php endif ?>
	<a class="btn btn-secondary" href="<?php echo url($_modbase.'/sanctions/'.($page + 1)) ?>"><?php echo $this->lang('Suivant') ?> <i class="fas fa-arrow-right"></i></a>
</div>
<?php endif ?>
