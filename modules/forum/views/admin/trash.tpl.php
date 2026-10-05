<?php if (empty($trashed)): ?>
	<div class="alert alert-info text-center">
		<?php echo icon('fas fa-info-circle').' '.$this->lang('La corbeille est vide.') ?>
	</div>
<?php else: ?>
<form action="<?php echo url($this->url->request) ?>" method="post">
	<div class="alert alert-warning small">
		<?php echo icon('fas fa-exclamation-triangle').' '.$this->lang('Restaurer remet le message visible (mais le contenu original a été perdu lors du soft-delete legacy). Purger supprime définitivement le record.') ?>
	</div>

	<table class="table table-hover table-sm">
		<thead>
			<tr>
				<th width="40"><input type="checkbox" id="trash-select-all" /></th>
				<th><?php echo $this->lang('Sujet') ?></th>
				<th><?php echo $this->lang('Forum') ?></th>
				<th><?php echo $this->lang('Auteur') ?></th>
				<th><?php echo $this->lang('Supprimé par') ?></th>
				<th><?php echo $this->lang('Raison') ?></th>
				<th><?php echo $this->lang('Date suppression') ?></th>
				<th width="160" class="text-center"><?php echo $this->lang('Actions') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($trashed as $m): ?>
				<tr>
					<td><input type="checkbox" name="select_msg[]" value="<?php echo (int)$m['message_id'] ?>" class="trash-row-cb" /></td>
					<td><a href="<?php echo url('forum/topic/'.(int)$m['topic_id'].'/'.url_title($m['topic_title'])) ?>"><?php echo nf_texte($m['topic_title']) ?></a></td>
					<td><small><?php echo nf_texte($m['forum_title']) ?></small></td>
					<td><?php echo nf_texte($m['username'] ?: '?') ?></td>
					<td><?php echo nf_texte($m['deleter_username'] ?: '?') ?></td>
					<td><small><code><?php echo nf_texte($m['deleted_reason'] ?: '—') ?></code></small></td>
					<td><small><?php echo time_span(strtotime($m['deleted_at'])) ?></small></td>
					<td class="text-center">
						<button type="submit" name="restore[]" value="<?php echo (int)$m['message_id'] ?>" class="btn btn-outline-success btn-sm" data-bs-toggle="tooltip" title="<?php echo $this->lang('Restaurer') ?>"><?php echo icon('fas fa-undo') ?></button>
						<button type="submit" name="purge[]" value="<?php echo (int)$m['message_id'] ?>" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="<?php echo $this->lang('Purger définitivement') ?>"
								data-confirm="<?php echo nf_texte($this->lang('Confirmer la suppression définitive de ce message ?')) ?>"
								data-confirm-title="<?php echo nf_texte($this->lang('Purger définitivement')) ?>"
								data-confirm-icon="fas fa-times"><?php echo icon('far fa-trash-alt') ?></button>
					</td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</form>
<script>
document.getElementById('trash-select-all')?.addEventListener('change', function(e){
	var checked = e.target.checked;
	document.querySelectorAll('.trash-row-cb').forEach(function(cb){ cb.checked = checked; });
});
</script>
<?php endif ?>
