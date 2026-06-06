<?php $_modbase = !empty($_user_side ?? FALSE) ? 'moderation' : 'admin/moderation'; ?>
<?php
$status_class = [
	'pending'   => 'warning',
	'reviewed'  => 'info',
	'actioned'  => 'success',
	'dismissed' => 'secondary',
	'duplicate' => 'light'
][$report['status']] ?? 'secondary';
?>
<div class="row">
	<!-- Colonne gauche : infos report + contenu signalé + actions -->
	<div class="col-12 col-lg-8">

		<!-- En-tête report -->
		<div class="card mb-3">
			<div class="nf-card-header">
				<span><i class="fas fa-flag"></i> <?php echo $this->lang('Signalement #%d', (int)$report['id']) ?> <span class="badge badge-<?php echo $status_class ?>"><?php echo htmlspecialchars($report['status']) ?></span></span>
				<?php if (!empty($report['url'])): ?>
				<a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($report['url']) ?>" target="_blank"><i class="fas fa-external-link-alt"></i> <?php echo $this->lang('Voir le contenu en contexte') ?></a>
				<?php else: ?>
				<span class="badge badge-light" title="<?php echo htmlspecialchars($this->lang('Aucune URL de contexte fournie. Voir le commentaire du reporter pour situer le contenu.')) ?>"><i class="fas fa-unlink"></i> <?php echo $this->lang('Pas de contexte URL') ?></span>
				<?php endif ?>
			</div>
			<div class="card-body">
				<dl class="row mb-0">
					<dt class="col-sm-3"><?php echo $this->lang('Date') ?></dt>
					<dd class="col-sm-9"><?php echo htmlspecialchars($report['created_at']) ?> <small class="text-muted">(<?php echo time_span(strtotime($report['created_at'])) ?>)</small></dd>

					<dt class="col-sm-3"><?php echo $this->lang('Type cible') ?></dt>
					<dd class="col-sm-9"><span class="badge badge-light"><?php echo htmlspecialchars($report['target_type']) ?></span> <code><?php echo htmlspecialchars($report['target_id']) ?></code></dd>

					<dt class="col-sm-3"><?php echo $this->lang('Raison') ?></dt>
					<dd class="col-sm-9"><span class="badge badge-secondary"><?php echo htmlspecialchars($report['reason']) ?></span></dd>

					<?php if (!empty($report['comment'])): ?>
					<dt class="col-sm-3"><?php echo $this->lang('Commentaire reporter') ?></dt>
					<dd class="col-sm-9"><blockquote class="m-0"><?php echo nl2br(htmlspecialchars($report['comment'])) ?></blockquote></dd>
					<?php endif ?>

					<?php if (!empty($report['url'])): ?>
					<dt class="col-sm-3"><?php echo $this->lang('URL') ?></dt>
					<dd class="col-sm-9"><a href="<?php echo htmlspecialchars($report['url']) ?>" target="_blank"><?php echo htmlspecialchars($report['url']) ?></a></dd>
					<?php endif ?>
				</dl>
			</div>
		</div>

		<!-- Contenu signalé (snapshot conservé) -->
		<?php if (!empty($report['content_snapshot'])): ?>
		<div class="card mb-3">
			<div class="nf-card-header">
				<span><i class="fas fa-quote-left"></i> <?php echo $this->lang('Contenu signalé (snapshot)') ?></span>
				<small class="text-muted"><?php echo $this->lang('Préservé même si édité ou supprimé après') ?></small>
			</div>
			<div class="card-body">
				<div style="background:rgba(0,0,0,.04);padding:12px;border-radius:6px;">
					<?php echo $report['content_snapshot'] /* déjà sanitized au moment du report */ ?>
				</div>
			</div>
		</div>
		<?php endif ?>

		<!-- Pièces jointes — copie défensive -->
		<?php
		$snapshot_attachments = $this->db->select('id', 'original_name', 'mime_type', 'file_size', 'sha256_hash')
			->from('nf_reports_attachments_snapshot')
			->where('report_id', (int)$report['id'])
			->order_by('id')
			->get();
		?>
		<?php if (!empty($snapshot_attachments)): ?>
		<div class="card mb-3">
			<div class="nf-card-header">
				<span><i class="fas fa-paperclip"></i> <?php echo $this->lang('Pièces jointes (copie défensive)') ?></span>
				<small class="text-muted"><?php echo $this->lang('Préservées même si l\'auteur supprime les originaux') ?></small>
			</div>
			<table class="table table-sm m-0">
				<thead><tr>
					<th><?php echo $this->lang('Nom') ?></th>
					<th><?php echo $this->lang('Type') ?></th>
					<th class="text-right"><?php echo $this->lang('Taille') ?></th>
					<th><?php echo $this->lang('SHA-256') ?></th>
					<th class="text-right"><?php echo $this->lang('Action') ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ($snapshot_attachments as $a): ?>
					<tr>
						<td><i class="fas fa-file"></i> <?php echo htmlspecialchars($a['original_name']) ?></td>
						<td><small class="text-muted"><code><?php echo htmlspecialchars($a['mime_type']) ?></code></small></td>
						<td class="text-right"><small><?php echo round((int)$a['file_size'] / 1024, 1) ?> KB</small></td>
						<td><small class="text-muted" title="<?php echo htmlspecialchars($a['sha256_hash']) ?>"><code><?php echo htmlspecialchars(substr((string)$a['sha256_hash'], 0, 12)) ?>…</code></small></td>
						<td class="text-right">
							<a class="btn btn-sm btn-outline-primary" href="<?php echo url($_modbase.'/snapshot/download/'.(int)$a['id']) ?>" download>
								<i class="fas fa-download"></i> <?php echo $this->lang('Télécharger') ?>
							</a>
						</td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
		<?php endif ?>

		<!-- Actions admin -->
		<?php if ($report['status'] === 'pending' || $report['status'] === 'reviewed'): ?>
		<div class="card mb-3">
			<div class="nf-card-header"><span><i class="fas fa-gavel"></i> <?php echo $this->lang('Actions') ?></span></div>
			<div class="card-body">
				<form method="post" action="<?php echo url($_modbase.'/reports/'.(int)$report['id'].'/sanction') ?>">
					<div class="row">
						<div class="col-md-6">
							<label><?php echo $this->lang('Type de sanction') ?></label>
							<select name="type" class="form-control" required>
								<option value=""><?php echo $this->lang('— Choisir —') ?></option>
								<?php
								$types = [
									'warning'           => $this->lang('Avertissement'),
									'mute'              => $this->lang('Mute (empêche de poster)'),
									'ban_temp'          => $this->lang('Ban temporaire'),
									'ban_perm'          => $this->lang('Ban définitif'),
									'restrict_upload'   => $this->lang('Restreindre l\'upload de fichiers'),
									'restrict_links'    => $this->lang('Restreindre les liens'),
									'restrict_avatar'   => $this->lang('Restreindre la modif d\'avatar'),
									'restrict_signature' => $this->lang('Restreindre la modif de signature'),
									'restrict_comment'  => $this->lang('Restreindre les commentaires'),
									'shadow_ban'        => $this->lang('Shadow ban (silencieux, sans notif)')
								];
								foreach ($types as $val => $label): ?>
								<option value="<?php echo $val ?>"><?php echo htmlspecialchars($label) ?></option>
								<?php endforeach ?>
							</select>
						</div>
						<div class="col-md-3">
							<label><?php echo $this->lang('Scope') ?></label>
							<select name="scope" class="form-control">
								<option value="global"><?php echo $this->lang('Global (tout le site)') ?></option>
								<option value="forum">Forum</option>
								<option value="talks">Talks</option>
								<option value="comments"><?php echo $this->lang('Commentaires') ?></option>
								<option value="wiki">Wiki</option>
								<option value="gallery">Galerie</option>
								<option value="guestbook">Livre d'or</option>
							</select>
						</div>
						<div class="col-md-3">
							<label><?php echo $this->lang('Durée (heures)') ?></label>
							<input type="number" class="form-control" name="duration_seconds_h" min="0" step="1" placeholder="0 = permanent" />
							<small class="text-muted"><?php echo $this->lang('Vide ou 0 pour permanent (warning/restrict ignorent)') ?></small>
						</div>
					</div>
					<div class="form-group mt-3">
						<label id="reason_label"><?php echo $this->lang('Raison (visible par le user sanctionné)') ?></label>
						<textarea name="reason" id="reason_textarea" class="form-control" rows="3" required maxlength="1000" placeholder="<?php echo $this->lang('Explique la sanction. Tu peux référencer le commentaire du reporter.') ?>"></textarea>
					</div>
					<div class="form-check mt-2">
						<input type="checkbox" name="notify_user" id="notify_user" value="1" checked class="form-check-input" />
						<label for="notify_user" class="form-check-label" id="notify_user_label"><?php echo $this->lang('Notifier l\'utilisateur (email + in-site)') ?></label>
						<small class="d-block text-muted" id="shadow_ban_hint" style="display:none !important;"><i class="fas fa-user-secret"></i> <?php echo $this->lang('Shadow ban : pas de notif par nature (silencieux côté user, raison écrite obligatoire pour le staff).') ?></small>
					</div>
					<input type="hidden" name="duration_seconds" id="duration_seconds_hidden" value="" />
					<script>
						(function(){
							var hourInput = document.querySelector('input[name="duration_seconds_h"]');
							var hidden    = document.getElementById('duration_seconds_hidden');
							hourInput.addEventListener('input', function(){
								hidden.value = this.value ? (parseInt(this.value, 10) * 3600) : '';
							});

							var typeSel  = document.querySelector('select[name="type"]');
							var notifyCb = document.getElementById('notify_user');
							var notifyLb = document.getElementById('notify_user_label');
							var hint     = document.getElementById('shadow_ban_hint');
							var reasonLb = document.getElementById('reason_label');
							var reasonTa = document.getElementById('reason_textarea');
							var REASON_PUBLIC = <?php echo json_encode((string)$this->lang('Raison (visible par le user sanctionné)')) ?>;
							var REASON_INTERN = <?php echo json_encode((string)$this->lang('Raison interne (visible uniquement par le staff)')) ?>;
							var PH_PUBLIC = <?php echo json_encode((string)$this->lang('Explique la sanction. Tu peux référencer le commentaire du reporter.')) ?>;
							var PH_INTERN = <?php echo json_encode((string)$this->lang('Justification interne pour le staff. Le user ne verra jamais ce texte.')) ?>;
							typeSel.addEventListener('change', function(){
								var isShadow = (this.value === 'shadow_ban');
								notifyCb.checked  = !isShadow;
								notifyCb.disabled = isShadow;
								notifyLb.classList.toggle('text-muted', isShadow);
								hint.style.cssText = isShadow ? '' : 'display:none !important;';
								reasonLb.textContent = isShadow ? REASON_INTERN : REASON_PUBLIC;
								reasonTa.placeholder = isShadow ? PH_INTERN : PH_PUBLIC;
							});
						})();
					</script>
					<div class="mt-3">
						<button type="submit" class="btn btn-primary"><i class="fas fa-gavel"></i> <?php echo $this->lang('Appliquer la sanction') ?></button>
					</div>
				</form>

				<hr />

				<!-- Dismiss form (sans sanction) -->
				<form method="post" action="<?php echo url($_modbase.'/reports/'.(int)$report['id'].'/dismiss') ?>" class="mt-2">
					<div class="form-group">
						<label><?php echo $this->lang('Ignorer ce signalement (note interne)') ?></label>
						<input type="text" name="note" class="form-control" maxlength="500" placeholder="<?php echo $this->lang('Pourquoi ce signalement est rejeté ? (optionnel, interne)') ?>" />
					</div>
					<button type="submit" class="btn btn-outline-secondary"><i class="fas fa-times"></i> <?php echo $this->lang('Ignorer (dismiss)') ?></button>
				</form>
			</div>
		</div>
		<?php else: ?>
		<div class="alert alert-info">
			<?php echo $this->lang('Ce signalement a déjà été traité (statut : <strong>%s</strong>).', htmlspecialchars($report['status'])) ?>
			<?php if ($report['handled_by']): ?>
				<br><small><?php echo $this->lang('Traité le %s', htmlspecialchars((string)$report['handled_at'])) ?></small>
			<?php endif ?>
			<?php if (!empty($report['handled_note'])): ?>
				<br><strong><?php echo $this->lang('Note :') ?></strong> <?php echo htmlspecialchars($report['handled_note']) ?>
			<?php endif ?>
		</div>
		<?php endif ?>
	</div>

	<!-- Colonne droite : reporter + cible -->
	<div class="col-12 col-lg-4">

		<!-- Reporter info -->
		<?php if ($report['reporter_id']): ?>
		<div class="card mb-3">
			<div class="nf-card-header"><span><i class="fas fa-user-shield"></i> <?php echo $this->lang('Reporter') ?></span></div>
			<div class="card-body">
				<?php if ($show_reporter): ?>
					<strong><a href="<?php echo url($_modbase.'/users/'.(int)$report['reporter_id']) ?>">@<?php echo htmlspecialchars((string)$report['reporter_username']) ?></a></strong>
				<?php else: ?>
					<small class="text-muted"><i class="fas fa-user-secret"></i> <?php echo $this->lang('Identité masquée selon vos permissions') ?></small>
				<?php endif ?>
				<?php if ($reporter_score): ?>
				<hr class="my-2" />
				<small class="d-block text-muted"><?php echo $this->lang('Qualité du reporter (signalements)') ?></small>
				<div class="mt-1">
					<span class="badge badge-success"><?php echo (int)$reporter_score['counts']['actioned'] ?> <?php echo $this->lang('actionnés') ?></span>
					<span class="badge badge-secondary"><?php echo (int)$reporter_score['counts']['dismissed'] ?> <?php echo $this->lang('rejetés') ?></span>
					<span class="badge badge-light"><?php echo (int)$reporter_score['counts']['pending'] ?> <?php echo $this->lang('en cours') ?></span>
				</div>
				<?php if ($reporter_score['is_suspect']): ?>
				<div class="alert alert-warning mt-2 mb-0 p-2"><small><i class="fas fa-exclamation-triangle"></i> <?php echo $this->lang('Reporter suspect (faux signalements répétés)') ?></small></div>
				<?php endif ?>
				<?php endif ?>
			</div>
		</div>
		<?php else: ?>
		<div class="card mb-3">
			<div class="nf-card-header"><span><i class="fas fa-user-secret"></i> <?php echo $this->lang('Reporter anonyme') ?></span></div>
			<div class="card-body"><small class="text-muted"><?php echo $this->lang('IP : %s', htmlspecialchars($report['reporter_ip'])) ?></small></div>
		</div>
		<?php endif ?>

		<!-- Cible info -->
		<?php if ($target_history): ?>
		<div class="card mb-3">
			<div class="nf-card-header">
				<span><i class="fas fa-user-times"></i> <?php echo $this->lang('User signalé') ?></span>
				<a class="btn btn-sm btn-outline-primary" href="<?php echo url($_modbase.'/users/'.(int)$report['target_user_id']) ?>"><?php echo $this->lang('Historique complet') ?> →</a>
			</div>
			<div class="card-body">
				<strong><a href="<?php echo url($_modbase.'/users/'.(int)$report['target_user_id']) ?>">@<?php echo htmlspecialchars((string)$report['target_username']) ?></a></strong>
				<hr class="my-2" />
				<small class="d-block text-muted mb-1"><?php echo $this->lang('Signalements reçus (20 derniers)') ?></small>
				<span class="badge badge-warning"><?php echo count($target_history['reports_received']) ?></span>
				<?php if (!empty($target_history['active_sanctions'])): ?>
				<hr class="my-2" />
				<small class="d-block text-muted mb-1"><?php echo $this->lang('Sanctions actives') ?></small>
				<?php foreach ($target_history['active_sanctions'] as $s): ?>
				<div class="badge badge-danger d-block mb-1 text-left p-2">
					<?php echo htmlspecialchars($s['type']) ?> · <?php echo htmlspecialchars($s['scope']) ?>
					<?php if (!empty($s['expires_at'])): ?> · <?php echo $this->lang('jusqu\'au %s', htmlspecialchars($s['expires_at'])) ?><?php endif ?>
				</div>
				<?php endforeach ?>
				<?php endif ?>
			</div>
		</div>
		<?php endif ?>
	</div>
</div>
