<?php /* Les icônes sont FACULTATIVES : un rôle ou une catégorie de permissions peut ne pas en
         déclarer. Sans le repli, chaque affichage de la matrice posait deux avertissements PHP
         par ligne — « Undefined array key "icon" » puis « htmlspecialchars(): Passing null » —
         soit 24 lignes de journal pour une seule visite. */ ?>
<?php
// R1.3 — Vue matricielle : Permissions × Roles d'un module sur 1 écran.
//
// Cellule = tristate radio (allow / default / never), couleurs vert / gris / rouge.
// Inheritance : si la valeur vient d'un rôle parent, on affiche en gris avec un indicateur "↓ from <ParentName>".
// Wildcards : pas affichés ici (à voir dans une vue séparée du rôle, R1.4).
?>
<div class="matrix-toolbar mb-3 d-flex justify-content-between align-items-center">
	<div>
		<?php echo $this->lang('Module : <strong>%s</strong>', nf_texte($module_title)) ?>
		<?php if ($scope_id > 0): ?>
			<span class="badge text-bg-info ms-2"><?php echo $this->lang('Scope %d', $scope_id) ?></span>
		<?php endif ?>
	</div>
	<div>
		<small class="text-muted">
			<i class="fas fa-info-circle"></i>
			<?php echo $this->lang('Vert = autorisé, Rouge = jamais (override-block), Gris = défaut. Les valeurs héritées sont marquées avec ↓.') ?>
		</small>
	</div>
</div>

<div class="table-responsive matrix-table-wrapper">
	<table class="table table-bordered table-sm matrix-table" data-module="<?php echo nf_texte($module_name) ?>" data-scope-id="<?php echo (int)$scope_id ?>">
		<thead>
			<tr>
				<th class="matrix-perm-col"><?php echo $this->lang('Permission') ?></th>
				<?php foreach ($roles as $role): ?>
					<th class="text-center matrix-role-col" data-role-id="<?php echo (int)$role['role_id'] ?>">
						<i class="<?php echo nf_texte($role['icon'] ?? '') ?>"></i>
						<div class="role-name"><?php echo nf_texte($this->lang($role['title'])) ?></div>
						<?php if ($role['parent_role_id']): ?>
							<small class="text-muted">↓ <?php echo nf_texte(isset($roles[$role['parent_role_id']]['title']) ? (string) $this->lang($roles[$role['parent_role_id']]['title']) : '') ?></small>
						<?php endif ?>
					</th>
				<?php endforeach ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($access['access'] as $cat_idx => $category): ?>
				<tr class="matrix-category-row">
					<td colspan="<?php echo count($roles) + 1 ?>" class="bg-light">
						<strong><i class="<?php echo nf_texte($category['icon'] ?? '') ?>"></i> <?php echo nf_texte($category['title']) ?></strong>
					</td>
				</tr>
				<?php foreach ($category['access'] as $action => $info): ?>
					<?php $perm = $module_name.'.'.$action ?>
					<tr data-permission="<?php echo nf_texte($perm) ?>">
						<td class="matrix-perm-cell">
							<i class="<?php echo nf_texte($info['icon'] ?? '') ?> text-primary"></i>
							<?php echo nf_texte($info['title']) ?>
							<small class="d-block text-muted"><?php echo nf_texte($action) ?></small>
						</td>
						<?php foreach ($roles as $role): ?>
							<?php
								$cell = $matrix[$perm][$role['role_id']] ?? ['value'=>'default','source'=>'none','parent_role_name'=>NULL];
								$value = $cell['value'];
								$source = $cell['source'];
								$cell_class = 'matrix-cell matrix-cell-' . $value . ' matrix-cell-source-' . $source;
								$tooltip = '';
								if ($source === 'inherited' && $cell['parent_role_name']) {
									$tooltip = $this->lang('Hérité de %s', $cell['parent_role_name']);
								} else if ($source === 'wildcard') {
									$tooltip = $this->lang('Via wildcard %s.*', explode('.', $perm)[0]);
								}
							?>
							<td class="<?php echo $cell_class ?>" data-role-id="<?php echo (int)$role['role_id'] ?>" data-current="<?php echo nf_texte($value) ?>" <?php if ($tooltip): ?>data-bs-toggle="tooltip" title="<?php echo nf_texte($tooltip) ?>"<?php endif ?>>
								<div class="matrix-radios">
									<label class="matrix-radio matrix-radio-allow" title="<?php echo nf_texte($this->lang('Autoriser')) ?>">
										<input type="radio" name="cell-<?php echo (int)$role['role_id'] ?>-<?php echo md5($perm) ?>" value="allow" <?php if ($source === 'direct' && $value === 'allow') echo 'checked' ?> <?php if ($source !== 'direct') echo 'data-inherited="1"' ?> />
										<i class="fas fa-check"></i>
									</label>
									<label class="matrix-radio matrix-radio-default" title="<?php echo nf_texte($this->lang('Défaut (pas d\'override)')) ?>">
										<input type="radio" name="cell-<?php echo (int)$role['role_id'] ?>-<?php echo md5($perm) ?>" value="default" <?php if ($source !== 'direct' || $value === 'default') echo 'checked' ?> />
										<i class="fas fa-minus"></i>
									</label>
									<label class="matrix-radio matrix-radio-never" title="<?php echo nf_texte($this->lang('Jamais (override-block)')) ?>">
										<input type="radio" name="cell-<?php echo (int)$role['role_id'] ?>-<?php echo md5($perm) ?>" value="never" <?php if ($source === 'direct' && $value === 'never') echo 'checked' ?> />
										<i class="fas fa-ban"></i>
									</label>
								</div>
								<?php if ($source === 'inherited'): ?>
									<small class="matrix-inherited-marker"><?php echo icon('fas fa-arrow-down') ?></small>
								<?php endif ?>
							</td>
						<?php endforeach ?>
					</tr>
				<?php endforeach ?>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<div class="matrix-status mt-3 text-muted small">
	<span class="matrix-save-status"></span>
</div>
