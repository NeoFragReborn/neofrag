<?php
// R1.5 — Matrice groups × roles : assignation bulk via groupe.
?>
<?php if (empty($groups)): ?>
	<div class="alert alert-info text-center">
		<?php echo icon('fas fa-info-circle').' '.$this->lang('Aucun groupe persistant en BDD. Les groupes auto (admins/members/visitors) ne sont pas configurables ici — ils sont mappés directement aux rôles built-in super_admin/member/visitor.') ?>
	</div>
<?php else: ?>
	<div class="alert alert-info small">
		<?php echo icon('fas fa-info-circle').' '.$this->lang('Assigner un rôle à un groupe le donne automatiquement à tous ses membres. Pratique pour bulk : ajoute un user au groupe → il hérite des rôles.') ?>
	</div>
	<div class="table-responsive matrix-table-wrapper">
		<table class="table table-hover table-sm matrix-table" data-mode="groups">
			<thead>
				<tr>
					<th class="matrix-perm-col"><?php echo $this->lang('Groupe') ?></th>
					<?php foreach ($roles as $role): ?>
						<th class="text-center matrix-role-col" data-role-id="<?php echo (int)$role['role_id'] ?>">
							<i class="<?php echo htmlspecialchars($role['icon']) ?>"></i>
							<div class="role-name"><?php echo htmlspecialchars($role['title']) ?></div>
						</th>
					<?php endforeach ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($groups as $g): ?>
					<tr data-group-id="<?php echo (int)$g['group_id'] ?>">
						<td class="matrix-perm-cell">
							<span class="badge badge-<?php echo htmlspecialchars($g['color']) ?>">
								<i class="<?php echo htmlspecialchars($g['icon']) ?>"></i>
								<?php echo htmlspecialchars($g['title']) ?>
							</span>
						</td>
						<?php foreach ($roles as $role): ?>
							<?php $assigned = !empty($g['roles'][$role['role_id']]) ?>
							<td class="text-center matrix-cell <?php echo $assigned ? 'matrix-cell-allow' : '' ?>">
								<input type="checkbox"
									class="matrix-toggle"
									data-group-id="<?php echo (int)$g['group_id'] ?>"
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
