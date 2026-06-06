<?php if (empty($reports)): ?>
	<div class="alert alert-info text-center">
		<?php echo icon('fas fa-flag').' '.$this->lang('Aucun signalement pour le moment.') ?>
	</div>
<?php else: ?>
	<div class="alert alert-secondary small">
		<?php echo icon('fas fa-info-circle').' '.$this->lang('Les signalements sont conservés dans l\'audit log. Cliquer sur un message pour ouvrir la conversation et modérer.') ?>
	</div>
	<table class="table table-hover table-sm">
		<thead>
			<tr>
				<th><?php echo $this->lang('Date') ?></th>
				<th><?php echo $this->lang('Signalé par') ?></th>
				<th><?php echo $this->lang('Conversation') ?></th>
				<th><?php echo $this->lang('Auteur') ?></th>
				<th><?php echo $this->lang('Message') ?></th>
				<th width="80" class="text-center"><?php echo $this->lang('Action') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($reports as $r): ?>
				<tr>
					<td><small><?php echo time_span((int)$r['date']) ?></small></td>
					<td><?php echo htmlspecialchars($r['reporter_username'] ?: '?') ?></td>
					<td>
						<?php if (!empty($r['talk_id'])): ?>
							<a href="<?php echo url('talks/'.(int)$r['talk_id'].'/'.url_title($r['talk_name'] ?? 'conversation')) ?>"><?php echo htmlspecialchars($r['talk_name'] ?? '?') ?></a>
						<?php else: ?>
							<small class="text-muted">—</small>
						<?php endif ?>
					</td>
					<td><?php echo htmlspecialchars($r['message_author'] ?? '?') ?></td>
					<td><small><?php echo htmlspecialchars(mb_substr((string)($r['message_text'] ?? ''), 0, 200)) ?></small></td>
					<td class="text-center">
						<?php if (!empty($r['message_id'])): ?>
							<a href="<?php echo url('talks/'.(int)$r['talk_id'].'/'.url_title($r['talk_name'] ?? 'conversation')) ?>" class="btn btn-sm btn-light" data-toggle="tooltip" title="<?php echo $this->lang('Voir et modérer') ?>"><?php echo icon('fas fa-eye') ?></a>
						<?php endif ?>
					</td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
<?php endif ?>
