<?php
/**
 * Corps de la liste des campagnes — SANS carte ni en-tête.
 *
 * L'enveloppe est posée par le contrôleur via `admin_card()`, comme sur les 34 autres écrans
 * d'administration. Cette vue roulait sa propre `<div class="card">` : en-tête plus fin, pas de
 * pastille d'icône, bouton d'action à un autre endroit — d'où la sensation d'incohérence d'une
 * page à l'autre. Le bouton « Nouvelle campagne » vit dans la barre d'outils de la page
 * (`add_action` dans le contrôleur), une seule fois.
 */
?>
<?php if (empty($campaigns)): ?>
	<?php echo $vide ?>
<?php else: ?>
<div class="table-responsive">
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
					<strong><?php echo nf_texte($c['title']) ?></strong><br>
					<small class="text-muted"><code>/donations/<?php echo nf_texte($c['name']) ?></code> · <?php echo (int)$c['count'] ?> <?php echo $this->lang($c['count'] > 1 ? 'donateurs' : 'donateur') ?></small>
				</td>
				<td class="text-end">
					<strong><?php echo number_format($c['raised'], 2, ',', ' ') ?> <?php echo nf_texte($c['currency']) ?></strong><br>
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
					<span class="badge text-bg-<?php echo $badges[$c['status']] ?>"><?php echo $labels[$c['status']] ?></span>
				</td>
				<td class="text-end">
					<a class="btn btn-sm btn-outline-secondary" href="<?php echo url('admin/donations/'.$c['id'].'/donations') ?>" title="<?php echo $this->lang('Gérer les dons') ?>"><i class="fas fa-list"></i></a>
					<a class="btn btn-sm btn-outline-secondary" href="<?php echo url('admin/donations/edit/'.$c['id']) ?>" title="<?php echo $this->lang('Modifier') ?>"><i class="fas fa-pen"></i></a>
					<a class="btn btn-sm btn-outline-info" href="<?php echo url('donations/'.$c['name']) ?>" target="_blank" rel="noopener" title="<?php echo $this->lang('Voir la page publique') ?>"><i class="far fa-eye"></i></a>
					<a class="btn btn-sm btn-outline-danger" href="<?php echo url('admin/donations/delete/'.$c['id']) ?>?_=<?php echo $csrf ?>" data-confirm="<?php echo $this->lang('Supprimer cette campagne ET tous ses dons ?') ?>" title="<?php echo $this->lang('Supprimer') ?>"><i class="far fa-trash-alt"></i></a>
				</td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php endif ?>
