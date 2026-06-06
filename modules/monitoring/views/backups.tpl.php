<?php
$total_size = 0;
$old_count = 0;
foreach ($backups as $b)
{
	$total_size += $b['size'];
	if ($b['age_days'] > 30) $old_count++;
}
?>
<div class="card panel-backups">
	<div class="nf-card-header">
		<span><i class="fas fa-archive"></i> <?php echo $this->lang('Sauvegardes existantes') ?>
			<small class="text-muted ml-2">
				<?php echo $this->lang('%d fichier|%d fichiers', count($backups), count($backups)) ?>
				<?php if (count($backups)): ?> — <?php echo human_size($total_size) ?><?php endif ?>
			</small>
		</span>
		<?php if ($old_count > 0): ?>
		<a class="btn btn-sm btn-outline-warning" href="<?php echo url('admin/monitoring/purge') ?>?_=<?php echo $csrf ?>" data-confirm="<?php echo htmlspecialchars($this->lang('Supprimer définitivement %d sauvegarde de plus de 30 jours ?|Supprimer définitivement %d sauvegardes de plus de 30 jours ?', $old_count, $old_count), ENT_QUOTES) ?>" data-confirm-style="warning">
			<i class="fas fa-trash-alt"></i> <?php echo $this->lang('Purger > 30j (%d)', $old_count) ?>
		</a>
		<?php endif ?>
	</div>
	<?php if (empty($backups)): ?>
		<div class="card-body text-center text-muted py-4">
			<?php echo icon('fas fa-archive fa-2x mb-2') ?><br>
			<?php echo $this->lang('Aucune sauvegarde pour le moment. Utilise le bouton <i class="far fa-save"></i> du panneau Stockage pour créer la première.') ?>
		</div>
	<?php else: ?>
		<div class="card-body p-0">
			<table class="table table-hover m-0">
				<thead>
					<tr>
						<th><?php echo $this->lang('Date') ?></th>
						<th><?php echo $this->lang('Fichier') ?></th>
						<th class="text-right"><?php echo $this->lang('Taille') ?></th>
						<th class="text-right"><?php echo $this->lang('Ancienneté') ?></th>
						<th class="text-right"><?php echo $this->lang('Actions') ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($backups as $b): ?>
					<tr>
						<td><?php echo icon('far fa-clock').' '.htmlspecialchars($b['date']) ?></td>
						<td><code><?php echo htmlspecialchars($b['name']) ?></code></td>
						<td class="text-right"><?php echo human_size($b['size']) ?></td>
						<td class="text-right<?php echo $b['age_days'] > 30 ? ' text-warning' : '' ?>">
							<?php echo $b['age_days'] === 0.0 ? $this->lang('Aujourd\'hui') : $this->lang('%d jour|%d jours', (int)$b['age_days'], (int)$b['age_days']) ?>
						</td>
						<td class="text-right">
							<a class="btn btn-sm btn-outline-primary" href="<?php echo url('admin/monitoring/download/'.urlencode($b['name'])) ?>" data-toggle="tooltip" title="<?php echo $this->lang('Télécharger') ?>">
								<?php echo icon('fas fa-download') ?>
							</a>
							<a class="btn btn-sm btn-outline-danger" href="<?php echo url('admin/monitoring/delete/'.urlencode($b['name'])) ?>?_=<?php echo $csrf ?>" data-toggle="tooltip" title="<?php echo $this->lang('Supprimer') ?>" data-confirm="<?php echo htmlspecialchars($this->lang('Supprimer définitivement cette sauvegarde ?'), ENT_QUOTES) ?>">
								<?php echo icon('fas fa-trash-alt') ?>
							</a>
						</td>
					</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		</div>
	<?php endif ?>
</div>
