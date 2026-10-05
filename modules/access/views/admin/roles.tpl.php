<?php
// R1.4 — Liste des rôles avec actions (edit, clone, delete) et compteurs.
$jeton = $jeton ?? '';
?>
<?php if (empty($roles)): ?>
	<div class="alert alert-info text-center">
		<?php echo icon('fas fa-info-circle').' '.$this->lang('Aucun rôle. Crée le premier via le bouton ci-dessus.') ?>
	</div>
<?php else: ?>
	<table class="table table-hover table-sm">
		<thead>
			<tr>
				<th><?php echo $this->lang('Rôle') ?></th>
				<th><?php echo $this->lang('Hérite de') ?></th>
				<th class="text-center"><?php echo $this->lang('Permissions') ?></th>
				<th class="text-center"><?php echo $this->lang('Utilisateurs') ?></th>
				<th class="text-center"><?php echo $this->lang('Groupes') ?></th>
				<th><?php echo $this->lang('Type') ?></th>
				<th width="200" class="text-end"><?php echo $this->lang('Actions') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($roles as $r): ?>
				<tr>
					<td>
						<span class="badge <?php echo badge_class($r['color']) ?>">
							<i class="<?php echo nf_texte($r['icon']) ?>"></i>
							<?php echo nf_texte($this->lang($r['title'])) ?>
						</span>
						<small class="text-muted ms-2"><code><?php echo nf_texte($r['name']) ?></code></small>
						<?php if (!empty($r['description'])): ?>
							<div class="small text-muted mt-1"><?php echo nf_texte($this->lang($r['description'])) ?></div>
						<?php endif ?>
					</td>
					<td>
						<?php if ($r['parent_title']): ?>
							<small><?php echo icon('fas fa-arrow-down').' '.nf_texte($this->lang($r['parent_title'])) ?></small>
						<?php else: ?>
							<small class="text-muted">—</small>
						<?php endif ?>
					</td>
					<td class="text-center">
						<a href="<?php echo url('admin/access/matrix') ?>" class="badge text-bg-light" data-bs-toggle="tooltip" title="<?php echo $this->lang('Configurer dans la matrice') ?>">
							<?php echo (int)$r['perm_count'] ?>
						</a>
					</td>
					<td class="text-center">
						<span class="badge text-bg-light"><?php echo (int)$r['user_count'] ?></span>
					</td>
					<td class="text-center">
						<span class="badge text-bg-light"><?php echo (int)$r['group_count'] ?></span>
					</td>
					<td>
						<?php if ($r['built_in']): ?>
							<span class="badge text-bg-secondary" data-bs-toggle="tooltip" title="<?php echo $this->lang('Rôle système non supprimable') ?>">
								<i class="fas fa-lock"></i> <?php echo $this->lang('Built-in') ?>
							</span>
						<?php else: ?>
							<span class="badge text-bg-info"><?php echo $this->lang('Personnalisé') ?></span>
						<?php endif ?>
					</td>
					<td class="text-end">
						<?php if ($r['name'] !== 'super_admin'): ?>
							<a class="btn btn-sm btn-outline-warning" href="<?php echo url('admin/access/preview/role/'.(int)$r['role_id']) ?>"
								data-confirm="<?php echo nf_texte($this->lang('Activer le mode preview "voir comme %s" ? Tu verras le site avec les permissions de ce rôle (et non plus avec ton accès super-admin) jusqu\'à ce que tu cliques "Quitter".', $this->lang($r['title']))) ?>"
								data-confirm-title="<?php echo nf_texte($this->lang('Voir comme ce rôle')) ?>"
								data-confirm-style="warning"
								data-confirm-icon="fas fa-eye"
								data-confirm-ok="<?php echo nf_texte($this->lang('Activer')) ?>"
								data-bs-toggle="tooltip" title="<?php echo $this->lang('Voir le site comme ce rôle') ?>"><i class="fas fa-eye"></i></a>
						<?php endif ?>
						<a class="btn btn-sm btn-outline-secondary" href="<?php echo url('admin/access/roles/edit/'.(int)$r['role_id'].'/'.url_title($r['title'])) ?>" data-bs-toggle="tooltip" title="<?php echo $this->lang('Éditer') ?>"><i class="fas fa-pen"></i></a>
						<a class="btn btn-sm btn-outline-secondary" href="<?php echo url('admin/access/roles/clone/'.(int)$r['role_id'].'/'.url_title($r['title'])) ?>" data-bs-toggle="tooltip" title="<?php echo $this->lang('Cloner') ?>"><i class="fas fa-copy"></i></a>
						<?php if (!$r['built_in']): ?>
							<a class="btn btn-sm btn-outline-danger" href="<?php echo url('admin/access/roles/delete/'.(int)$r['role_id'].'/'.url_title($r['title'])).'?_='.$jeton ?>"
								data-confirm="<?php echo nf_texte($this->lang('Supprimer le rôle "%s" ? Tous ses %d utilisateurs perdront ces permissions.', $this->lang($r['title']), (int)$r['user_count'])) ?>"
								data-confirm-title="<?php echo nf_texte($this->lang('Supprimer le rôle')) ?>"
								data-bs-toggle="tooltip" title="<?php echo $this->lang('Supprimer') ?>"><i class="far fa-trash-alt"></i></a>
						<?php else: ?>
							<button class="btn btn-sm btn-outline-danger" disabled data-bs-toggle="tooltip" title="<?php echo $this->lang('Built-in non supprimable') ?>"><i class="far fa-trash-alt"></i></button>
						<?php endif ?>
					</td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
<?php endif ?>
