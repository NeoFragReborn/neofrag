<?php
$cid = $campaign['id'];
$header_left  = '<span><i class="fas fa-list"></i> '.htmlspecialchars($campaign['title']).' <small class="text-muted">'.(int)$totals['count'].' '.$this->lang($totals['count'] > 1 ? 'dons' : 'don').' · '.number_format($totals['total'], 2, ',', ' ').' '.htmlspecialchars($campaign['currency']).'</small></span>';
$header_right = '<span><a class="btn btn-primary btn-sm" href="'.url('admin/donations/'.$cid.'/donation/add').'"><i class="fas fa-plus"></i> '.$this->lang('Ajouter un don').'</a></span>';
?>
<div class="card">
	<div class="card-header"><?php echo $header_left.$header_right ?></div>
	<?php if (empty($donations)): ?>
	<div class="card-body text-center text-muted py-4"><?php echo $this->lang('Aucun don pour le moment.') ?></div>
	<?php else: ?>
	<table class="table table-hover mb-0">
		<thead>
			<tr>
				<th><?php echo $this->lang('Donateur') ?></th>
				<th class="text-right"><?php echo $this->lang('Montant') ?></th>
				<th><?php echo $this->lang('Source') ?></th>
				<th class="text-center"><?php echo $this->lang('Statut') ?></th>
				<th class="text-center"><?php echo $this->lang('Visibilité') ?></th>
				<th><?php echo $this->lang('Date') ?></th>
				<th class="text-right"></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($donations as $d):
				$status_badges = ['pending' => 'warning', 'completed' => 'success', 'refunded' => 'danger'];
			?>
			<tr>
				<td>
					<?php if ($d['is_anonymous']): ?>
						<i class="fas fa-user-secret text-muted"></i> <em><?php echo $this->lang('Anonyme') ?></em>
						<small class="text-muted">(<?php echo htmlspecialchars($d['donor_name']) ?>)</small>
					<?php else: ?>
						<i class="fas fa-user"></i> <?php echo htmlspecialchars($d['donor_name']) ?>
					<?php endif ?>
					<?php if (!empty($d['message'])): ?>
					<br><small class="text-muted"><i class="far fa-comment"></i> <?php echo htmlspecialchars(mb_strimwidth($d['message'], 0, 80, '...')) ?></small>
					<?php endif ?>
				</td>
				<td class="text-right"><strong><?php echo number_format($d['amount'], 2, ',', ' ') ?> <?php echo htmlspecialchars($d['currency']) ?></strong></td>
				<td><?php echo $d['source'] === 'paypal' ? '<i class="fab fa-paypal"></i> PayPal' : '<i class="fas fa-keyboard"></i> '.$this->lang('Manuel') ?></td>
				<?php $status_labels = ['pending' => $this->lang('En attente'), 'completed' => $this->lang('Validé'), 'refunded' => $this->lang('Remboursé')]; ?>
				<td class="text-center"><span class="badge badge-<?php echo $status_badges[$d['status']] ?>"><?php echo $status_labels[$d['status']] ?? htmlspecialchars($d['status']) ?></span></td>
				<td class="text-center">
					<?php if ($d['is_public']): ?>
						<i class="far fa-eye text-success" title="<?php echo $this->lang('Public') ?>"></i>
					<?php else: ?>
						<i class="far fa-eye-slash text-muted" title="<?php echo $this->lang('Masqué') ?>"></i>
					<?php endif ?>
				</td>
				<td><small><?php echo timetostr('j F Y H:i', $d['created_at']) ?></small></td>
				<td class="text-right">
					<a class="btn btn-sm btn-primary" href="<?php echo url('admin/donations/donation/edit/'.$d['id']) ?>"><i class="fas fa-edit"></i></a>
					<a class="btn btn-sm btn-danger" href="<?php echo url('admin/donations/donation/delete/'.$d['id']) ?>" data-confirm="<?php echo $this->lang('Supprimer ce don ?') ?>"><i class="far fa-trash-alt"></i></a>
				</td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
	<?php endif ?>
</div>
