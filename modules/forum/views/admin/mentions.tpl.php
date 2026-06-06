<?php $f = $filters ?? ['status' => '', 'user' => '']; ?>

<form action="<?php echo url($this->url->request) ?>" method="get" class="row form-inline mb-3">
	<div class="col-md-5 mb-1">
		<label class="sr-only" for="filter-user"><?php echo $this->lang('Utilisateur') ?></label>
		<input type="text" id="filter-user" name="user" class="form-control form-control-sm w-100" placeholder="<?php echo htmlspecialchars($this->lang('Filtrer par utilisateur (mentionné ou auteur)…'), ENT_QUOTES) ?>" value="<?php echo htmlspecialchars($f['user']) ?>" />
	</div>
	<div class="col-md-3 mb-1">
		<label class="sr-only" for="filter-status"><?php echo $this->lang('Statut') ?></label>
		<select id="filter-status" name="status" class="form-control form-control-sm w-100">
			<option value=""       <?php echo $f['status'] === ''       ? 'selected' : '' ?>><?php echo $this->lang('Toutes les mentions') ?></option>
			<option value="unread" <?php echo $f['status'] === 'unread' ? 'selected' : '' ?>><?php echo $this->lang('Non lues uniquement') ?></option>
			<option value="read"   <?php echo $f['status'] === 'read'   ? 'selected' : '' ?>><?php echo $this->lang('Lues uniquement') ?></option>
		</select>
	</div>
	<div class="col-md-4 mb-1">
		<button type="submit" class="btn btn-sm btn-primary"><?php echo icon('fas fa-filter').' '.$this->lang('Filtrer') ?></button>
		<?php if ($f['user'] !== '' || $f['status'] !== ''): ?>
			<a href="<?php echo url($this->url->request) ?>" class="btn btn-sm btn-light"><?php echo icon('fas fa-times').' '.$this->lang('Réinitialiser') ?></a>
		<?php endif ?>
	</div>
</form>

<?php if (empty($mentions)): ?>
	<div class="alert alert-info text-center"><?php echo icon('fas fa-info-circle').' '.$this->lang('Aucune mention enregistrée pour ces critères.') ?></div>
<?php else: ?>
<form action="<?php echo url($this->url->request).($f['user'] !== '' || $f['status'] !== '' ? '?'.http_build_query(array_filter($f)) : '') ?>" method="post">
	<table class="table table-hover table-sm">
		<thead>
			<tr>
				<th width="40"><input type="checkbox" id="mentions-select-all" data-toggle="tooltip" title="<?php echo htmlspecialchars($this->lang('Tout sélectionner'), ENT_QUOTES) ?>" /></th>
				<th><?php echo $this->lang('De') ?></th>
				<th><?php echo $this->lang('Vers') ?></th>
				<th><?php echo $this->lang('Sujet') ?></th>
				<th><?php echo $this->lang('Date') ?></th>
				<th><?php echo $this->lang('Statut') ?></th>
				<th width="120" class="text-center"><?php echo $this->lang('Actions') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($mentions as $m): ?>
				<tr>
					<td><input type="checkbox" name="select_mention[]" value="<?php echo (int)$m['mention_id'] ?>" class="mention-row-cb" /></td>
					<td><?php echo htmlspecialchars($m['mentioner_username']) ?></td>
					<td><span class="badge badge-primary">@<?php echo htmlspecialchars($m['mentioned_username']) ?></span></td>
					<td><a href="<?php echo url('forum/topic/'.(int)$m['topic_id'].'/'.url_title($m['topic_title']).'#'.(int)$m['message_id']) ?>"><?php echo htmlspecialchars($m['topic_title']) ?></a></td>
					<td><small><?php echo time_span(strtotime($m['created_at'])) ?></small></td>
					<td>
						<?php if ($m['read_at']): ?>
							<span class="badge badge-success" data-toggle="tooltip" title="<?php echo htmlspecialchars($this->lang('Lue %s', time_span(strtotime($m['read_at']))), ENT_QUOTES) ?>"><?php echo $this->lang('Lue') ?></span>
						<?php else: ?>
							<span class="badge badge-warning"><?php echo $this->lang('Non lue') ?></span>
						<?php endif ?>
					</td>
					<td class="text-center">
						<?php if (!$m['read_at']): ?>
							<button type="submit" name="mark_read[]" value="<?php echo (int)$m['mention_id'] ?>" class="btn btn-success btn-sm" data-toggle="tooltip" title="<?php echo htmlspecialchars($this->lang('Marquer comme lue'), ENT_QUOTES) ?>"><?php echo icon('fas fa-check') ?></button>
						<?php endif ?>
						<button type="submit" name="delete[]" value="<?php echo (int)$m['mention_id'] ?>" class="btn btn-danger btn-sm" data-toggle="tooltip" title="<?php echo htmlspecialchars($this->lang('Supprimer'), ENT_QUOTES) ?>"
								data-confirm="<?php echo htmlspecialchars($this->lang('Supprimer cette mention ?'), ENT_QUOTES) ?>"
								data-confirm-icon="fas fa-at"><?php echo icon('fas fa-times') ?></button>
					</td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>

	<div class="btn-group">
		<button type="submit" name="bulk-action-marker" class="btn btn-sm btn-success" formaction="<?php echo url($this->url->request) ?>" onclick="this.form.querySelectorAll('.mention-row-cb:checked').forEach(function(cb){ var i=document.createElement('input'); i.type='hidden'; i.name='mark_read[]'; i.value=cb.value; cb.form.appendChild(i); });">
			<?php echo icon('fas fa-check').' '.$this->lang('Marquer comme lues') ?>
		</button>
		<button type="submit" name="bulk-action-delete" class="btn btn-sm btn-danger"
				data-confirm="<?php echo htmlspecialchars($this->lang('Supprimer toutes les mentions sélectionnées ?'), ENT_QUOTES) ?>"
				data-confirm-icon="fas fa-at"
				onclick="this.form.querySelectorAll('.mention-row-cb:checked').forEach(function(cb){ var i=document.createElement('input'); i.type='hidden'; i.name='delete[]'; i.value=cb.value; cb.form.appendChild(i); });">
			<?php echo icon('fas fa-trash-alt').' '.$this->lang('Supprimer les sélectionnées') ?>
		</button>
	</div>
</form>
<script>
document.getElementById('mentions-select-all')?.addEventListener('change', function(e){
	var checked = e.target.checked;
	document.querySelectorAll('.mention-row-cb').forEach(function(cb){ cb.checked = checked; });
});
</script>
<?php endif ?>
