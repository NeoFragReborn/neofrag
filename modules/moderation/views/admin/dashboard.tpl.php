<?php $_modbase = !empty($_user_side ?? FALSE) ? 'moderation' : 'admin/moderation'; ?>
<div class="nf-stats-grid">
	<div class="nf-stat-card<?php echo $stats['pending'] > 0 ? ' nf-stat-attention' : '' ?>">
		<div class="nf-stat-label"><i class="fas fa-flag"></i> <?php echo $this->lang('Signalements en attente') ?></div>
		<div class="nf-stat-value"><?php echo (int)$stats['pending'] ?></div>
		<?php if ($stats['pending'] > 0): ?>
		<div class="nf-stat-trend"><a href="<?php echo url($_modbase.'/reports?status=pending') ?>"><?php echo $this->lang('Examiner') ?> →</a></div>
		<?php endif ?>
	</div>
	<div class="nf-stat-card">
		<div class="nf-stat-label"><i class="far fa-clock"></i> <?php echo $this->lang('Signalements 7j') ?></div>
		<div class="nf-stat-value"><?php echo (int)$stats['last_7d_reports'] ?></div>
		<div class="nf-stat-trend"><?php echo $this->lang('total derniers 7 jours') ?></div>
	</div>
	<div class="nf-stat-card">
		<div class="nf-stat-label"><i class="fas fa-gavel"></i> <?php echo $this->lang('Sanctions actives') ?></div>
		<div class="nf-stat-value"><?php echo (int)$stats['active_sanctions'] ?></div>
		<div class="nf-stat-trend"><a href="<?php echo url($_modbase.'/sanctions?active_only=1') ?>"><?php echo $this->lang('Voir la liste') ?> →</a></div>
	</div>
	<div class="nf-stat-card<?php echo $stats['pending_approval'] > 0 ? ' nf-stat-attention' : '' ?>">
		<div class="nf-stat-label"><i class="fas fa-check-double"></i> <?php echo $this->lang('Validations en attente') ?></div>
		<div class="nf-stat-value"><?php echo (int)$stats['pending_approval'] ?></div>
		<?php if ($stats['pending_approval'] > 0): ?>
		<div class="nf-stat-trend"><a href="<?php echo url($_modbase.'/sanctions?pending_approval=1') ?>"><?php echo $this->lang('À valider') ?> →</a></div>
		<?php endif ?>
	</div>
</div>

<div class="row mt-3">
	<!-- Signalements récents -->
	<div class="col-12 col-lg-7">
		<div class="card">
			<div class="nf-card-header">
				<span><i class="fas fa-flag"></i> <?php echo $this->lang('Signalements récents en attente') ?></span>
				<a class="btn btn-sm btn-outline-primary" href="<?php echo url($_modbase.'/reports') ?>"><?php echo $this->lang('Tous les signalements') ?></a>
			</div>
			<?php if (empty($recent_reports)): ?>
				<div class="card-body text-center text-muted py-4">
					<i class="far fa-smile fa-2x mb-2"></i><br>
					<?php echo $this->lang('Aucun signalement en attente. Tout va bien !') ?>
				</div>
			<?php else: ?>
				<table class="table table-hover m-0">
					<thead>
						<tr>
							<th><?php echo $this->lang('Date') ?></th>
							<th><?php echo $this->lang('Type') ?></th>
							<th><?php echo $this->lang('Cible') ?></th>
							<th><?php echo $this->lang('Raison') ?></th>
							<th class="text-end"><?php echo $this->lang('Action') ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($recent_reports as $r): ?>
						<tr>
							<td><small class="text-muted"><?php echo time_span(strtotime($r['created_at'])) ?></small></td>
							<td><span class="badge text-bg-secondary"><?php echo htmlspecialchars($r['target_type']) ?></span></td>
							<td><?php echo $r['target_username'] ? '<a href="'.url($_modbase.'/users/'.(int)$r['target_user_id']).'">@'.htmlspecialchars($r['target_username']).'</a>' : '<i class="text-muted">'.$this->lang('inconnu').'</i>' ?></td>
							<td><?php echo htmlspecialchars($r['reason']) ?></td>
							<td class="text-end">
								<a class="btn btn-sm btn-outline-primary" href="<?php echo url($_modbase.'/reports/'.(int)$r['id']) ?>"><i class="fas fa-eye"></i></a>
							</td>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			<?php endif ?>
		</div>
	</div>

	<!-- Top users signalés -->
	<div class="col-12 col-lg-5">
		<div class="card mb-3">
			<div class="nf-card-header">
				<span><i class="fas fa-user-times"></i> <?php echo $this->lang('Top users signalés (30 jours)') ?></span>
			</div>
			<?php if (empty($top_reported)): ?>
				<div class="card-body text-center text-muted py-3"><small><?php echo $this->lang('Aucune donnée') ?></small></div>
			<?php else: ?>
				<table class="table m-0">
					<tbody>
					<?php foreach ($top_reported as $u): ?>
						<tr>
							<td><a href="<?php echo url($_modbase.'/users/'.(int)$u['target_user_id']) ?>">@<?php echo htmlspecialchars($u['username']) ?></a></td>
							<td class="text-end"><span class="badge text-bg-warning"><?php echo (int)$u['report_count'] ?></span></td>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			<?php endif ?>
		</div>
		<div class="card">
			<div class="nf-card-header">
				<span><i class="fas fa-user-shield"></i> <?php echo $this->lang('Top reporters (30 jours)') ?></span>
			</div>
			<?php if (empty($top_reporters)): ?>
				<div class="card-body text-center text-muted py-3"><small><?php echo $this->lang('Aucune donnée') ?></small></div>
			<?php else: ?>
				<table class="table m-0">
					<thead>
						<tr><th><?php echo $this->lang('Reporter') ?></th><th class="text-end"><?php echo $this->lang('Total') ?></th><th class="text-end"><?php echo $this->lang('Actionné') ?></th><th class="text-end"><?php echo $this->lang('Rejeté') ?></th></tr>
					</thead>
					<tbody>
					<?php foreach ($top_reporters as $u):
						$score = (int)$u['actioned'] - (int)$u['dismissed'];
					?>
						<tr<?php echo (int)$u['report_count'] >= 5 && $score < 0 ? ' class="text-danger" title="'.$this->lang('Reporter suspect (faux signalements)').'"' : '' ?>>
							<td><a href="<?php echo url($_modbase.'/users/'.(int)$u['reporter_id']) ?>">@<?php echo htmlspecialchars($u['username']) ?></a></td>
							<td class="text-end"><?php echo (int)$u['report_count'] ?></td>
							<td class="text-end text-success"><?php echo (int)$u['actioned'] ?></td>
							<td class="text-end text-danger"><?php echo (int)$u['dismissed'] ?></td>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			<?php endif ?>
		</div>
	</div>
</div>

<div class="row mt-3">
	<div class="col-12 text-end">
		<a class="btn btn-secondary" href="<?php echo url($_modbase.'/sanctions') ?>"><i class="fas fa-gavel"></i> <?php echo $this->lang('Sanctions') ?></a>
		<?php if ($this->access('moderation', 'manage_settings')): ?>
		<a class="btn btn-outline-secondary" href="<?php echo url($_modbase.'/settings') ?>"><i class="fas fa-cogs"></i> <?php echo $this->lang('Réglages') ?></a>
		<?php endif ?>
	</div>
</div>
