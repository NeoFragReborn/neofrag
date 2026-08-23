<?php
// R1.5 — Matrice users × roles : checkboxes pour assigner / désassigner.
?>
<?php if (empty($users)): ?>
	<div class="alert alert-info text-center">
		<?php echo icon('fas fa-info-circle').' '.$this->lang('Aucun utilisateur.') ?>
	</div>
<?php else: ?>
	<div class="alert alert-info small">
		<?php echo icon('fas fa-info-circle').' '.$this->lang('Coche pour assigner un rôle. Les changements sont sauvegardés automatiquement. Le bypass <code>nf_user.admin=1</code> court-circuite toujours le système et donne tous les droits.') ?>
	</div>
	<div class="table-responsive matrix-table-wrapper">
		<table class="table table-hover table-sm matrix-table" data-mode="users">
			<thead>
				<tr>
					<th class="matrix-perm-col"><?php echo $this->lang('Utilisateur') ?></th>
					<?php foreach ($roles as $role): ?>
						<th class="text-center matrix-role-col" data-role-id="<?php echo (int)$role['role_id'] ?>">
							<i class="<?php echo htmlspecialchars($role['icon']) ?>"></i>
							<div class="role-name"><?php echo htmlspecialchars($role['title']) ?></div>
							<?php if ($role['built_in']): ?>
								<small class="text-muted"><i class="fas fa-lock"></i></small>
							<?php endif ?>
						</th>
					<?php endforeach ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($users as $u): ?>
					<tr data-user-id="<?php echo (int)$u['id'] ?>">
						<td class="matrix-perm-cell">
							<?php echo htmlspecialchars($u['username']) ?>
							<?php if ($u['admin']): ?>
								<small class="badge text-bg-danger ms-1" data-bs-toggle="tooltip" title="<?php echo $this->lang('Bypass admin actif') ?>"><i class="fas fa-rocket"></i></small>
							<?php endif ?>
							<a href="<?php echo url('admin/access/user-permissions/'.(int)$u['id'].'/'.url_title($u['username'])) ?>" class="btn btn-link btn-sm py-0 px-2" data-bs-toggle="tooltip" title="<?php echo $this->lang('Voir permissions effectives') ?>">
								<?php echo icon('fas fa-eye') ?>
							</a>
						</td>
						<?php foreach ($roles as $role): ?>
							<?php $assigned = !empty($u['roles'][$role['role_id']]) ?>
							<td class="text-center matrix-cell <?php echo $assigned ? 'matrix-cell-allow' : '' ?>">
								<input type="checkbox"
									class="matrix-toggle"
									data-user-id="<?php echo (int)$u['id'] ?>"
									data-role-id="<?php echo (int)$role['role_id'] ?>"
									<?php if ($assigned) echo 'checked' ?> />
							</td>
						<?php endforeach ?>
					</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>
	<div class="matrix-status mt-3 text-muted small">
		<span class="matrix-save-status"></span>
	</div>
<?php endif ?>
