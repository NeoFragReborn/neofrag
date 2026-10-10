<?php $_modbase = !empty($_user_side ?? FALSE) ? 'moderation' : 'admin/moderation'; $report = $report ?? []; $csrf = $csrf ?? ''; ?>
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
				<span><i class="fas fa-flag"></i> <?php echo $this->lang('Signalement #%d', (int)$report['id']) ?> <span class="badge <?php echo badge_class($status_class) ?>"><?php echo nf_texte($this->module('moderation')->libelle('statut', $report['status'])) ?></span></span>
				<?php if (!empty($report['url'])): ?>
				<a class="btn btn-sm btn-outline-secondary" href="<?php echo nf_texte(nf_url_sure((string) $report['url']) ? (string) $report['url'] : '#') ?>" target="_blank"><i class="fas fa-external-link-alt"></i> <?php echo $this->lang('Voir le contenu en contexte') ?></a>
				<?php else: ?>
				<span class="badge text-bg-light" title="<?php echo nf_texte($this->lang('Aucune URL de contexte fournie. Voir le commentaire du reporter pour situer le contenu.')) ?>"><i class="fas fa-unlink"></i> <?php echo $this->lang('Pas de contexte URL') ?></span>
				<?php endif ?>
			</div>
			<div class="card-body">
				<dl class="row mb-0">
					<dt class="col-sm-3"><?php echo $this->lang('Date') ?></dt>
					<dd class="col-sm-9"><?php echo nf_date_heure($report['created_at']) ?> <small class="text-muted">(<?php echo time_span(strtotime($report['created_at'])) ?>)</small></dd>

					<dt class="col-sm-3"><?php echo $this->lang('Type cible') ?></dt>
					<dd class="col-sm-9"><span class="badge text-bg-light"><?php echo nf_texte($this->module('moderation')->libelle('cible', $report['target_type'])) ?></span> <code><?php echo nf_texte($report['target_id']) ?></code></dd>

					<dt class="col-sm-3"><?php echo $this->lang('Raison') ?></dt>
					<dd class="col-sm-9"><span class="badge text-bg-secondary"><?php echo nf_texte($this->module('moderation')->libelle('raison', $report['reason'])) ?></span></dd>

					<?php if (!empty($report['comment'])): ?>
					<dt class="col-sm-3"><?php echo $this->lang('Commentaire reporter') ?></dt>
					<dd class="col-sm-9"><blockquote class="m-0"><?php echo nl2br(nf_texte($report['comment'])) ?></blockquote></dd>
					<?php endif ?>

					<?php if (!empty($report['url'])): ?>
					<dt class="col-sm-3"><?php echo $this->lang('URL') ?></dt>
					<dd class="col-sm-9"><a href="<?php echo nf_texte(nf_url_sure((string) $report['url']) ? (string) $report['url'] : '#') ?>" target="_blank"><?php echo nf_texte($report['url']) ?></a></dd>
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
					<?php
					// Re-sanitize au rendu : le snapshot agrège du contenu editor (sanitized) ET des
					// parts texte construites avec des données client (noms de PJ) — ne jamais faire
					// confiance à l'échappement amont pour du HTML affiché côté modérateur.
					echo sanitize_html($report['content_snapshot'])
					?>
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
					<th class="text-end"><?php echo $this->lang('Taille') ?></th>
					<th><?php echo $this->lang('SHA-256') ?></th>
					<th class="text-end"><?php echo $this->lang('Action') ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ($snapshot_attachments as $a): ?>
					<tr>
						<td><i class="fas fa-file"></i> <?php echo nf_texte($a['original_name']) ?></td>
						<td><small class="text-muted"><code><?php echo nf_texte($a['mime_type']) ?></code></small></td>
						<td class="text-end"><small><?php echo round((int)$a['file_size'] / 1024, 1) ?> KB</small></td>
						<td><small class="text-muted" title="<?php echo nf_texte($a['sha256_hash']) ?>"><code><?php echo nf_texte(substr((string)$a['sha256_hash'], 0, 12)) ?>…</code></small></td>
						<td class="text-end">
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
				<?php echo $formulaire_sanction ?? '' ?>

				<hr />

				<!-- Dismiss form (sans sanction) -->
				<form method="post" action="<?php echo url($_modbase.'/reports/'.(int)$report['id'].'/dismiss') ?>" class="mt-2"><input type="hidden" name="_" value="<?php echo $csrf ?>">
					<div class="nf-field">
						<label><?php echo $this->lang('Ignorer ce signalement (note interne)') ?></label>
						<input type="text" name="note" class="form-control" maxlength="500" placeholder="<?php echo $this->lang('Pourquoi ce signalement est rejeté ? (optionnel, interne)') ?>" />
					</div>
					<button type="submit" class="btn btn-outline-secondary"><i class="fas fa-times"></i> <?php echo $this->lang('Ignorer (dismiss)') ?></button>
				</form>

				<?php // La médiation : une conversation privée à trois pour régler un conflit sans sanction (2026-10-09). Le modérateur la
				      // propose ; elle ne s'ouvre qu'avec l'accord de celui qui a signalé, que le membre signalé y verra. ?>
				<?php if ($report['reporter_id'] && $report['target_user_id'] && (int) $report['reporter_id'] !== (int) $report['target_user_id'] && $this->access('moderation', 'mediation')): ?>
				<hr />
				<?php if (empty($report['mediation_le'])): ?>
				<form method="post" action="<?php echo url($_modbase.'/reports/'.(int)$report['id'].'/mediation') ?>" class="mt-2"><input type="hidden" name="_" value="<?php echo $csrf ?>">
					<p class="mb-2"><?php echo $this->lang('Proposer une médiation : une conversation privée entre toi, le membre signalé et celui qui l’a signalé, pour régler le conflit sans sanction.') ?></p>
					<div class="alert alert-info py-2 small"><?php echo icon('fas fa-user-shield').' '.$this->lang('Celui qui a signalé décide : le membre signalé saura que c’est lui, la conversation ne s’ouvre donc qu’avec son accord. Tu seras prévenu de sa réponse.') ?></div>
					<button type="submit" class="btn btn-outline-primary"><i class="fas fa-handshake"></i> <?php echo $this->lang('Proposer une médiation') ?></button>
				</form>
				<?php elseif ($report['mediation_accord'] === 'non'): ?>
				<p class="mb-0 small text-muted"><?php echo icon('fas fa-handshake-slash').' '.$this->lang('Médiation refusée par celui qui a signalé, le %s : le signalement reste à traiter.', nf_date_heure((string) $report['mediation_reponse_le'])) ?></p>
				<?php elseif ($report['mediation_accord'] === 'oui'): ?>
				<p class="mb-0 small text-muted"><?php echo icon('fas fa-handshake').' '.$this->lang('Médiation acceptée le %s (discussion #%d).', nf_date_heure((string) $report['mediation_reponse_le']), (int) $report['mediation_talk_id']) ?></p>
				<?php else: ?>
				<p class="mb-0 small text-muted"><?php echo icon('fas fa-hourglass-half').' '.$this->lang('Médiation proposée le %s : en attente de la réponse de celui qui a signalé.', nf_date_heure((string) $report['mediation_le'])) ?></p>
				<?php endif ?>
				<?php endif ?>
			</div>
		</div>
		<?php else: ?>
		<div class="alert alert-info">
			<?php echo $this->lang('Ce signalement a déjà été traité (statut : <strong>%s</strong>).', nf_texte($this->module('moderation')->libelle('statut', $report['status']))) ?>
			<?php if ($report['handled_by']): ?>
				<br><small><?php echo $this->lang('Traité le %s', nf_date_heure($report['handled_at'])) ?></small>
			<?php endif ?>
			<?php if (!empty($report['handled_note'])): ?>
				<br><strong><?php echo $this->lang('Note :') ?></strong> <?php echo nf_texte($report['handled_note']) ?>
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
					<strong><a href="<?php echo url($_modbase.'/users/'.(int)$report['reporter_id']) ?>">@<?php echo nf_texte($report['reporter_username']) ?></a></strong>
				<?php else: ?>
					<small class="text-muted"><i class="fas fa-user-secret"></i> <?php echo $this->lang('Identité masquée selon vos permissions') ?></small>
				<?php endif ?>
				<?php if ($reporter_score): ?>
				<hr class="my-2" />
				<small class="d-block text-muted"><?php echo $this->lang('Qualité du reporter (signalements)') ?></small>
				<div class="mt-1">
					<span class="badge text-bg-success"><?php echo (int)$reporter_score['counts']['actioned'] ?> <?php echo $this->lang('actionnés') ?></span>
					<span class="badge text-bg-secondary"><?php echo (int)$reporter_score['counts']['dismissed'] ?> <?php echo $this->lang('rejetés') ?></span>
					<span class="badge text-bg-light"><?php echo (int)$reporter_score['counts']['pending'] ?> <?php echo $this->lang('en cours') ?></span>
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
			<div class="card-body"><small class="text-muted"><?php echo $this->lang('IP : %s', nf_texte($report['reporter_ip'])) ?></small></div>
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
				<strong><a href="<?php echo url($_modbase.'/users/'.(int)$report['target_user_id']) ?>">@<?php echo nf_texte($report['target_username']) ?></a></strong>
				<hr class="my-2" />
				<small class="d-block text-muted mb-1"><?php echo $this->lang('Signalements reçus (20 derniers)') ?></small>
				<span class="badge text-bg-warning"><?php echo count($target_history['reports_received']) ?></span>
				<?php if (!empty($target_history['active_sanctions'])): ?>
				<hr class="my-2" />
				<small class="d-block text-muted mb-1"><?php echo $this->lang('Sanctions actives') ?></small>
				<?php foreach ($target_history['active_sanctions'] as $s): ?>
				<div class="badge text-bg-danger d-block mb-1 text-start p-2">
					<?php echo nf_texte($this->module('moderation')->libelle('sanction', $s['type'])) ?> · <?php echo nf_texte($this->module('moderation')->libelle('portee', $s['scope'])) ?>
					<?php if (!empty($s['expires_at'])): ?> · <?php echo $this->lang('jusqu\'au %s', nf_date_heure($s['expires_at'])) ?><?php endif ?>
				</div>
				<?php endforeach ?>
				<?php endif ?>
			</div>
		</div>
		<?php endif ?>
	</div>
</div>
