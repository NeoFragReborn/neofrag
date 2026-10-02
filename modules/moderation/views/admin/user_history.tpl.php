<?php $_modbase = !empty($_user_side ?? FALSE) ? 'moderation' : 'admin/moderation'; ?>
<div class="row">
	<div class="col-12 col-lg-4">
		<div class="card mb-3">
			<div class="nf-card-header"><span><i class="fas fa-user"></i> <?php echo $this->lang('Profil') ?></span></div>
			<div class="card-body">
				<h5>@<?php echo htmlspecialchars($user['username']) ?></h5>
				<dl class="row mb-0">
					<dt class="col-sm-5"><?php echo $this->lang('ID') ?></dt><dd class="col-sm-7"><?php echo (int)$user['id'] ?></dd>
					<dt class="col-sm-5"><?php echo $this->lang('Inscrit') ?></dt><dd class="col-sm-7"><small><?php echo nf_date_heure($user['registration_date']) ?></small></dd>
					<dt class="col-sm-5"><?php echo $this->lang('Admin') ?></dt><dd class="col-sm-7"><?php echo $user['admin'] === '1' ? '<span class="badge text-bg-danger">'.$this->lang('Oui').'</span>' : $this->lang('Non') ?></dd>
					<dt class="col-sm-5"><?php echo $this->lang('Supprimé') ?></dt><dd class="col-sm-7"><?php echo $user['deleted'] === '1' ? '<span class="badge text-bg-secondary">'.$this->lang('Oui').'</span>' : $this->lang('Non') ?></dd>
				</dl>
			</div>
		</div>

		<!-- Sanctions actives -->
		<div class="card mb-3">
			<div class="nf-card-header"><span><i class="fas fa-gavel"></i> <?php echo $this->lang('Sanctions actives') ?></span></div>
			<?php if (empty($active_sanctions)): ?>
				<div class="card-body text-center text-muted py-3"><small><?php echo $this->lang('Aucune sanction active') ?></small></div>
			<?php else: ?>
				<ul class="list-group list-group-flush">
					<?php foreach ($active_sanctions as $s): ?>
					<li class="list-group-item">
						<a href="<?php echo url($_modbase.'/sanctions/'.(int)$s['id']) ?>">
							<span class="badge text-bg-danger"><?php echo htmlspecialchars($this->module('moderation')->libelle('sanction', $s['type'])) ?></span>
							<small class="text-muted"><?php echo htmlspecialchars($s['scope']) ?></small>
						</a>
						<div class="small text-muted mt-1">
							<?php if (empty($s['expires_at'])): ?>
								<?php echo $this->lang('Permanent') ?>
							<?php else: ?>
								<?php echo $this->lang('Jusqu\'au %s', nf_date_heure($s['expires_at'])) ?>
							<?php endif ?>
						</div>
					</li>
					<?php endforeach ?>
				</ul>
			<?php endif ?>
		</div>

		<!-- Reporter quality (si user a signalé d'autres) -->
		<?php if ($reporter_score['total'] > 0): ?>
		<div class="card mb-3">
			<div class="nf-card-header"><span><i class="fas fa-user-shield"></i> <?php echo $this->lang('Qualité reporter') ?></span></div>
			<div class="card-body">
				<small class="d-block text-muted mb-2"><?php echo $this->lang('Cet user a fait %d signalements au total', (int)$reporter_score['total']) ?></small>
				<div>
					<span class="badge text-bg-success"><?php echo (int)$reporter_score['counts']['actioned'] ?> <?php echo $this->lang('actionnés') ?></span>
					<span class="badge text-bg-secondary"><?php echo (int)$reporter_score['counts']['dismissed'] ?> <?php echo $this->lang('rejetés') ?></span>
					<span class="badge text-bg-light"><?php echo (int)$reporter_score['counts']['pending'] ?> <?php echo $this->lang('en cours') ?></span>
				</div>
				<?php if ($reporter_score['is_suspect']): ?>
				<div class="alert alert-warning mt-2 mb-0 p-2"><small><i class="fas fa-exclamation-triangle"></i> <?php echo $this->lang('Reporter suspect') ?></small></div>
				<?php endif ?>
			</div>
		</div>
		<?php endif ?>
	</div>

	<!-- Timeline mergée -->
	<div class="col-12 col-lg-8">
		<div class="card">
			<div class="nf-card-header">
				<span><i class="fas fa-history"></i> <?php echo $this->lang('Timeline modération') ?></span>
				<small class="text-muted"><?php echo count($timeline) ?> <?php echo $this->lang('événements') ?></small>
			</div>
			<?php if (empty($timeline)): ?>
				<div class="card-body text-center text-muted py-5">
					<i class="far fa-smile fa-2x mb-2"></i><br>
					<?php echo $this->lang('Aucun historique de modération sur cet user.') ?>
				</div>
			<?php else: ?>
				<ul class="list-group list-group-flush">
					<?php foreach ($timeline as $event):
						if ($event['event_type'] === 'sanction'):
					?>
						<li class="list-group-item">
							<i class="fas fa-gavel text-danger"></i>
							<strong><?php echo $this->lang('Sanction') ?> :</strong>
							<a href="<?php echo url($_modbase.'/sanctions/'.(int)$event['id']) ?>"><?php echo htmlspecialchars($this->module('moderation')->libelle('sanction', $event['type'])) ?> · <?php echo htmlspecialchars($this->module('moderation')->libelle('portee', $event['scope'])) ?></a>
							<?php if (!empty($event['revoked_at'])): ?><span class="badge text-bg-secondary"><?php echo $this->lang('Levée') ?></span><?php endif ?>
							<small class="float-end text-muted" title="<?php echo nf_date_heure($event['created_at']) ?>"><?php echo time_span(strtotime($event['created_at'])) ?></small>
							<?php if (!empty($event['reason'])): ?>
							<div class="text-muted mt-1 small"><?php echo htmlspecialchars(mb_strimwidth((string)$event['reason'], 0, 200, '…')) ?></div>
							<?php endif ?>
						</li>
					<?php elseif ($event['event_type'] === 'report_received'): ?>
						<li class="list-group-item">
							<i class="fas fa-flag text-warning"></i>
							<strong><?php echo $this->lang('Signalé') ?> :</strong>
							<a href="<?php echo url($_modbase.'/reports/'.(int)$event['id']) ?>"><?php echo htmlspecialchars($this->module('moderation')->libelle('cible', $event['target_type'])) ?> #<?php echo htmlspecialchars($event['target_id']) ?></a>
							<small class="text-muted">(<?php echo htmlspecialchars($this->module('moderation')->libelle('raison', $event['reason'])) ?>)</small>
							<span class="badge text-bg-light"><?php echo htmlspecialchars($this->module('moderation')->libelle('statut', $event['status'])) ?></span>
							<small class="float-end text-muted"><?php echo time_span(strtotime($event['created_at'])) ?></small>
						</li>
					<?php elseif ($event['event_type'] === 'report_made'): ?>
						<li class="list-group-item">
							<i class="fas fa-bullhorn text-info"></i>
							<strong><?php echo $this->lang('A signalé') ?> :</strong>
							<a href="<?php echo url($_modbase.'/reports/'.(int)$event['id']) ?>"><?php echo htmlspecialchars($this->module('moderation')->libelle('cible', $event['target_type'])) ?> #<?php echo htmlspecialchars($event['target_id']) ?></a>
							<small class="text-muted">(<?php echo htmlspecialchars($this->module('moderation')->libelle('raison', $event['reason'])) ?>)</small>
							<span class="badge text-bg-light"><?php echo htmlspecialchars($this->module('moderation')->libelle('statut', $event['status'])) ?></span>
							<small class="float-end text-muted"><?php echo time_span(strtotime($event['created_at'])) ?></small>
						</li>
					<?php endif; endforeach ?>
				</ul>
			<?php endif ?>
		</div>
	</div>
</div>
