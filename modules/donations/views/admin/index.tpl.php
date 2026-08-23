<?php
$header_left = '<span><i class="fas fa-bullseye"></i> '.$this->lang('Campagnes de dons').'</span>';
$header_right = '<span><a class="btn btn-primary btn-sm" href="'.url('admin/donations/new').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle campagne').'</a></span>';
?>
<div class="card">
	<div class="card-header"><?php echo $header_left.$header_right ?></div>
	<?php if (empty($campaigns)): ?>
	<div class="card-body text-center text-muted py-4"><?php echo $this->lang('Aucune campagne. Créez-en une pour commencer.') ?></div>
	<?php else: ?>
	<table class="table table-hover mb-0">
		<thead>
			<tr>
				<th><?php echo $this->lang('Campagne') ?></th>
				<th class="text-end"><?php echo $this->lang('Collecté') ?></th>
				<th><?php echo $this->lang('Progression') ?></th>
				<th class="text-center"><?php echo $this->lang('Statut') ?></th>
				<th class="text-end"><?php echo $this->lang('Actions') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($campaigns as $c): ?>
			<tr>
				<td>
					<strong><?php echo htmlspecialchars($c['title']) ?></strong><br>
					<small class="text-muted"><code>/donations/<?php echo htmlspecialchars($c['name']) ?></code> · <?php echo (int)$c['count'] ?> <?php echo $this->lang($c['count'] > 1 ? 'donateurs' : 'donateur') ?></small>
				</td>
				<td class="text-end">
					<strong><?php echo number_format($c['raised'], 2, ',', ' ') ?> <?php echo htmlspecialchars($c['currency']) ?></strong><br>
					<small class="text-muted">/ <?php echo number_format($c['goal_amount'], 2, ',', ' ') ?></small>
				</td>
				<td style="min-width: 180px;">
					<div class="donation-progress" style="height: 6px;">
						<div class="donation-progress-bar" style="width: <?php echo $c['percentage'] ?>%"></div>
					</div>
					<small class="text-muted"><?php echo $c['percentage'] ?>%</small>
				</td>
				<td class="text-center">
					<?php
					$badges = ['active' => 'success', 'paused' => 'warning', 'archived' => 'secondary'];
					$labels = ['active' => $this->lang('Active'), 'paused' => $this->lang('En pause'), 'archived' => $this->lang('Archivée')];
					?>
					<span class="badge badge-<?php echo $badges[$c['status']] ?>"><?php echo $labels[$c['status']] ?></span>
				</td>
				<td class="text-end">
					<a class="btn btn-sm btn-secondary" href="<?php echo url('admin/donations/'.$c['id'].'/donations') ?>" title="<?php echo $this->lang('Gérer les dons') ?>"><i class="fas fa-list"></i></a>
					<a class="btn btn-sm btn-primary" href="<?php echo url('admin/donations/edit/'.$c['id']) ?>" title="<?php echo $this->lang('Modifier') ?>"><i class="fas fa-edit"></i></a>
					<a class="btn btn-sm btn-info" href="<?php echo url('donations/'.$c['name']) ?>" target="_blank" rel="noopener" title="<?php echo $this->lang('Voir publique') ?>"><i class="fas fa-eye"></i></a>
					<a class="btn btn-sm btn-danger" href="<?php echo url('admin/donations/delete/'.$c['id']) ?>?_=<?php echo $csrf ?>" data-confirm="<?php echo $this->lang('Supprimer cette campagne ET tous ses dons ?') ?>" title="<?php echo $this->lang('Supprimer') ?>"><i class="far fa-trash-alt"></i></a>
				</td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
	<?php endif ?>
</div>
