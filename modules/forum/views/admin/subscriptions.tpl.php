<?php if (empty($subs)): ?>
	<div class="alert alert-info text-center"><?php echo icon('fas fa-info-circle').' '.$this->lang('Aucun abonnement pour le moment.') ?></div>
<?php else: ?>
<form action="<?php echo url($this->url->request) ?>" method="post">
	<table class="table table-hover table-sm">
		<thead>
			<tr>
				<th width="40"><input type="checkbox" id="subs-select-all" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($this->lang('Tout sélectionner'), ENT_QUOTES) ?>" /></th>
				<th><?php echo $this->lang('Utilisateur') ?></th>
				<th><?php echo $this->lang('Sujet') ?></th>
				<th><?php echo $this->lang('Forum') ?></th>
				<th><?php echo $this->lang('Abonné depuis') ?></th>
				<th><?php echo $this->lang('Dernière notif') ?></th>
				<th width="80" class="text-center"><?php echo $this->lang('Action') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($subs as $s): ?>
				<tr>
					<td><input type="checkbox" name="unsub[]" value="<?php echo (int)$s['topic_id'].'_'.(int)$s['user_id'] ?>" class="subs-row-cb" /></td>
					<td><?php echo htmlspecialchars($s['username']) ?></td>
					<td><a href="<?php echo url('forum/topic/'.(int)$s['topic_id'].'/'.url_title($s['topic_title'])) ?>"><?php echo htmlspecialchars($s['topic_title']) ?></a></td>
					<td><small><?php echo htmlspecialchars($s['forum_title']) ?></small></td>
					<td><small><?php echo time_span(strtotime($s['created_at'])) ?></small></td>
					<td><small><?php echo $s['last_notified_at'] ? time_span(strtotime($s['last_notified_at'])) : '<i class="text-muted">'.$this->lang('jamais').'</i>' ?></small></td>
					<td class="text-center">
						<button type="submit" name="unsub[]" value="<?php echo (int)$s['topic_id'].'_'.(int)$s['user_id'] ?>" class="btn btn-warning btn-sm" data-bs-toggle="tooltip" title="<?php echo $this->lang('Désabonner') ?>"><?php echo icon('fas fa-bell-slash') ?></button>
					</td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
	<button type="submit" class="btn btn-warning"
			data-confirm="<?php echo htmlspecialchars($this->lang('Désabonner les utilisateurs sélectionnés des sujets cochés ?'), ENT_QUOTES) ?>"
			data-confirm-title="<?php echo htmlspecialchars($this->lang('Désabonner les sélectionnés'), ENT_QUOTES) ?>"
			data-confirm-style="warning"
			data-confirm-icon="fas fa-bell-slash"
			data-confirm-ok="<?php echo htmlspecialchars($this->lang('Désabonner'), ENT_QUOTES) ?>"><?php echo icon('fas fa-bell-slash').' '.$this->lang('Désabonner les sélectionnés') ?></button>
</form>
<script>
document.getElementById('subs-select-all')?.addEventListener('change', function(e){
	var checked = e.target.checked;
	document.querySelectorAll('.subs-row-cb').forEach(function(cb){ cb.checked = checked; });
});
</script>
<?php endif ?>
