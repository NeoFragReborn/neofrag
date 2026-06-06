<?php $_modbase = !empty($_user_side ?? FALSE) ? 'moderation' : 'admin/moderation'; ?>
<form method="get" class="card mb-3">
	<div class="card-body">
		<div class="row">
			<div class="col-md-3">
				<label class="small text-muted"><?php echo $this->lang('Statut') ?></label>
				<select class="form-control form-control-sm" name="status">
					<option value=""><?php echo $this->lang('Tous') ?></option>
					<?php foreach (['pending','reviewed','actioned','dismissed','duplicate'] as $s): ?>
					<option value="<?php echo $s ?>"<?php echo ($filter['status'] === $s ? ' selected' : '') ?>><?php echo htmlspecialchars($s) ?></option>
					<?php endforeach ?>
				</select>
			</div>
			<div class="col-md-3">
				<label class="small text-muted"><?php echo $this->lang('Type cible') ?></label>
				<input type="text" class="form-control form-control-sm" name="target_type" value="<?php echo htmlspecialchars($filter['target_type']) ?>" placeholder="forum_message, talks_message, comment…" />
			</div>
			<div class="col-md-3">
				<label class="small text-muted"><?php echo $this->lang('Raison') ?></label>
				<select class="form-control form-control-sm" name="reason">
					<option value=""><?php echo $this->lang('Toutes') ?></option>
					<?php foreach (['spam','harassment','illegal','nsfw','misinformation','duplicate','other'] as $r): ?>
					<option value="<?php echo $r ?>"<?php echo ($filter['reason'] === $r ? ' selected' : '') ?>><?php echo htmlspecialchars($r) ?></option>
					<?php endforeach ?>
				</select>
			</div>
			<div class="col-md-3 d-flex align-items-end">
				<button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> <?php echo $this->lang('Filtrer') ?></button>
				<a class="btn btn-sm btn-link" href="<?php echo url($_modbase.'/reports') ?>"><?php echo $this->lang('Réinitialiser') ?></a>
			</div>
		</div>
	</div>
</form>

<div class="card">
	<?php if (empty($reports)): ?>
		<div class="card-body text-center text-muted py-5">
			<i class="far fa-smile fa-2x mb-2"></i><br>
			<?php echo $this->lang('Aucun signalement avec ces critères.') ?>
		</div>
	<?php else: ?>
		<table class="table table-hover m-0">
			<thead>
				<tr>
					<th><?php echo $this->lang('Date') ?></th>
					<th><?php echo $this->lang('Statut') ?></th>
					<th><?php echo $this->lang('Type') ?></th>
					<th><?php echo $this->lang('Reporter') ?></th>
					<th><?php echo $this->lang('Cible (user)') ?></th>
					<th><?php echo $this->lang('Raison') ?></th>
					<th><?php echo $this->lang('Commentaire') ?></th>
					<th class="text-right"><?php echo $this->lang('Action') ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($reports as $r):
				$status_class = [
					'pending'   => 'badge-warning',
					'reviewed'  => 'badge-info',
					'actioned'  => 'badge-success',
					'dismissed' => 'badge-secondary',
					'duplicate' => 'badge-light'
				][$r['status']] ?? 'badge-secondary';
			?>
				<tr>
					<td><small class="text-muted" title="<?php echo htmlspecialchars($r['created_at']) ?>"><?php echo time_span(strtotime($r['created_at'])) ?></small></td>
					<td><span class="badge <?php echo $status_class ?>"><?php echo htmlspecialchars($r['status']) ?></span></td>
					<td><span class="badge badge-light"><?php echo htmlspecialchars($r['target_type']) ?></span><br><small class="text-muted"><?php echo htmlspecialchars($r['target_id']) ?></small></td>
					<td>
						<?php if ($show_reporter && $r['reporter_username']): ?>
							<a href="<?php echo url($_modbase.'/users/'.(int)$r['reporter_id']) ?>">@<?php echo htmlspecialchars($r['reporter_username']) ?></a>
						<?php elseif ($r['reporter_id']): ?>
							<small class="text-muted"><i class="fas fa-user-secret"></i> <?php echo $this->lang('Anonymisé') ?></small>
						<?php else: ?>
							<small class="text-muted"><?php echo htmlspecialchars($r['reporter_ip']) ?></small>
						<?php endif ?>
					</td>
					<td>
						<?php if ($r['target_username']): ?>
							<a href="<?php echo url($_modbase.'/users/'.(int)$r['target_user_id']) ?>">@<?php echo htmlspecialchars($r['target_username']) ?></a>
						<?php else: ?>
							<small class="text-muted">—</small>
						<?php endif ?>
					</td>
					<td><span class="badge badge-secondary"><?php echo htmlspecialchars($r['reason']) ?></span></td>
					<td><?php echo htmlspecialchars(mb_strimwidth((string)$r['comment'], 0, 80, '…')) ?></td>
					<td class="text-right">
						<a class="btn btn-sm btn-outline-primary" href="<?php echo url($_modbase.'/reports/'.(int)$r['id']) ?>"><i class="fas fa-eye"></i> <?php echo $this->lang('Détails') ?></a>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	<?php endif ?>
</div>

<?php if (count($reports) >= 50): ?>
<div class="mt-3 text-center">
	<?php if ($page > 0): ?><a class="btn btn-secondary" href="<?php echo url($_modbase.'/reports/'.($page - 1)) ?>"><i class="fas fa-arrow-left"></i> <?php echo $this->lang('Précédent') ?></a><?php endif ?>
	<a class="btn btn-secondary" href="<?php echo url($_modbase.'/reports/'.($page + 1)) ?>"><?php echo $this->lang('Suivant') ?> <i class="fas fa-arrow-right"></i></a>
</div>
<?php endif ?>
