<div class="row mb-3">
	<div class="col-md-4">
		<div class="card text-center">
			<div class="card-body">
				<h5 class="card-title text-muted small text-uppercase"><?php echo $this->lang('Total') ?></h5>
				<h3 class="m-0"><?php echo (int)$stats['total'] ?></h3>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card text-center">
			<div class="card-body">
				<h5 class="card-title text-muted small text-uppercase"><?php echo $this->lang('Espace utilisé') ?></h5>
				<h3 class="m-0"><?php echo human_size((int)$stats['total_size']) ?></h3>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card text-center">
			<div class="card-body">
				<h5 class="card-title text-muted small text-uppercase"><?php echo $this->lang('Taille moyenne') ?></h5>
				<h3 class="m-0"><?php echo human_size((int)$stats['avg_size']) ?></h3>
			</div>
		</div>
	</div>
</div>

<?php if (!empty($orphans)): ?>
	<div class="alert alert-warning">
		<?php echo icon('fas fa-exclamation-triangle').' '.$this->lang('%d fichier(s) orphelin(s) détecté(s) dans /upload/forum/ (présents en nf_file mais sans entry attachment associée).', count($orphans)) ?>
		<a href="#orphan-list" class="alert-link" data-bs-toggle="collapse"><?php echo $this->lang('Voir la liste') ?></a>
		<div id="orphan-list" class="collapse mt-2">
			<ul class="small">
				<?php foreach ($orphans as $o): ?>
					<li><code><?php echo htmlspecialchars($o['path']) ?></code> · <?php echo htmlspecialchars($o['name']) ?> (file_id=<?php echo (int)$o['file_id'] ?>)</li>
				<?php endforeach ?>
			</ul>
		</div>
	</div>
<?php endif ?>

<?php if (empty($attachments)): ?>
	<div class="alert alert-info text-center">
		<?php echo icon('fas fa-info-circle').' '.$this->lang('Aucune pièce jointe pour le moment.') ?>
	</div>
<?php else: ?>
<form action="<?php echo url($this->url->request) ?>" method="post">
	<table class="table table-hover table-sm">
		<thead>
			<tr>
				<th width="40">&nbsp;</th>
				<th><?php echo $this->lang('Fichier') ?></th>
				<th><?php echo $this->lang('Taille') ?></th>
				<th><?php echo $this->lang('Type') ?></th>
				<th><?php echo $this->lang('Sujet') ?></th>
				<th><?php echo $this->lang('Forum') ?></th>
				<th><?php echo $this->lang('Uploader') ?></th>
				<th><?php echo $this->lang('Date') ?></th>
				<th width="80" class="text-center"><?php echo $this->lang('Action') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($attachments as $a): ?>
				<tr>
					<td>
						<?php if (strpos((string)$a['mime_type'], 'image/') === 0): ?>
							<i class="fas fa-image" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($a['name']) ?>"></i>
						<?php else: ?>
							<i class="fas fa-file"></i>
						<?php endif ?>
					</td>
					<td><a href="<?php echo url($a['path']) ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($a['name']) ?></a></td>
					<td><small><?php echo human_size((int)$a['file_size']) ?></small></td>
					<td><small><code><?php echo htmlspecialchars($a['mime_type']) ?></code></small></td>
					<td><a href="<?php echo url('forum/topic/'.(int)$a['topic_id'].'/'.url_title($a['topic_title']).'#'.(int)$a['message_id']) ?>"><?php echo htmlspecialchars($a['topic_title']) ?></a></td>
					<td><small><?php echo htmlspecialchars($a['forum_title']) ?></small></td>
					<td><?php echo htmlspecialchars($a['uploader_username']) ?></td>
					<td><small><?php echo time_span(strtotime($a['uploaded_at'])) ?></small></td>
					<td class="text-center">
						<button type="submit" name="delete_attachment[]" value="<?php echo (int)$a['attachment_id'] ?>" class="btn btn-danger btn-sm" data-bs-toggle="tooltip" title="<?php echo $this->lang('Supprimer') ?>"
							data-confirm="<?php echo htmlspecialchars($this->lang('Confirmer la suppression de cette pièce jointe ?'), ENT_QUOTES) ?>"
							data-confirm-title="<?php echo htmlspecialchars($this->lang('Supprimer la pièce jointe'), ENT_QUOTES) ?>"
							data-confirm-icon="fas fa-paperclip"><?php echo icon('fas fa-times') ?></button>
					</td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</form>
<?php endif ?>
