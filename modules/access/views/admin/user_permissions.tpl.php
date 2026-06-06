<?php
// R1.5 — Page "permissions effectives" pour 1 user : liste exhaustive de ses perms cumulées.
?>
<div class="alert alert-info small d-flex justify-content-between align-items-center">
	<div>
		<?php echo icon('fas fa-info-circle').' '.$this->lang('Cette page liste les permissions <strong>effectives</strong> de %s — c\'est-à-dire ce qu\'il peut <em>réellement</em> faire en tenant compte de ses rôles directs, des rôles via ses groupes, de l\'inheritance, et du bypass admin si actif.', '<strong>'.htmlspecialchars($user['username']).'</strong>') ?>
	</div>
	<div>
		<a class="btn btn-sm btn-warning ml-3" href="<?php echo url('admin/access/preview/user/'.(int)$user['id']) ?>"
			data-confirm="<?php echo htmlspecialchars($this->lang('Activer le mode preview "voir comme %s" ? Tu verras le site avec ses permissions, son admin status, etc.', $user['username']), ENT_QUOTES) ?>"
			data-confirm-title="<?php echo htmlspecialchars($this->lang('Voir comme cet utilisateur'), ENT_QUOTES) ?>"
			data-confirm-style="warning"
			data-confirm-icon="fas fa-eye"
			data-confirm-ok="<?php echo htmlspecialchars($this->lang('Activer'), ENT_QUOTES) ?>">
			<i class="fas fa-eye"></i> <?php echo $this->lang('Voir comme cet user') ?>
		</a>
	</div>
</div>

<?php if ($user['admin']): ?>
	<div class="alert alert-danger">
		<?php echo icon('fas fa-rocket').' '.$this->lang('Cet utilisateur a le flag <code>nf_user.admin=1</code> — il a accès à <strong>toutes</strong> les permissions via le bypass super-admin, indépendamment de la liste ci-dessous.') ?>
	</div>
<?php endif ?>

<?php if (empty($effective)): ?>
	<div class="alert alert-warning">
		<?php echo icon('fas fa-exclamation-triangle').' '.$this->lang('Aucune permission effective. Cet utilisateur n\'a aucun rôle assigné directement ni via ses groupes.') ?>
	</div>
<?php else: ?>
	<table class="table table-sm table-hover">
		<thead>
			<tr>
				<th><?php echo $this->lang('Permission') ?></th>
				<th class="text-center"><?php echo $this->lang('Scope') ?></th>
				<th class="text-center"><?php echo $this->lang('Valeur') ?></th>
				<th><?php echo $this->lang('Source (rôle)') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($effective as $perm => $scopes): ?>
				<?php foreach ($scopes as $scope_id => $info): ?>
					<tr>
						<td><code><?php echo htmlspecialchars($perm) ?></code></td>
						<td class="text-center">
							<?php if ($scope_id == 0): ?>
								<small class="text-muted"><?php echo $this->lang('global') ?></small>
							<?php else: ?>
								<span class="badge badge-light"><?php echo (int)$scope_id ?></span>
							<?php endif ?>
						</td>
						<td class="text-center">
							<?php if ($info['value'] === 'allow'): ?>
								<span class="badge badge-success"><i class="fas fa-check"></i> allow</span>
							<?php elseif ($info['value'] === 'never'): ?>
								<span class="badge badge-danger"><i class="fas fa-ban"></i> never</span>
							<?php else: ?>
								<span class="badge badge-secondary">default</span>
							<?php endif ?>
						</td>
						<td><small><?php echo htmlspecialchars($info['source']) ?></small></td>
					</tr>
				<?php endforeach ?>
			<?php endforeach ?>
		</tbody>
	</table>
<?php endif ?>
