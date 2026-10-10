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
		<?php echo icon('fas fa-exclamation-triangle').' '.$this->lang('%d fichier(s) orphelin(s) dans /upload/forum/ : plus aucun message ne les joint, ou le site ne les connaît pas.', count($orphans)) ?>
		<a href="#orphan-list" class="alert-link" data-bs-toggle="collapse"><?php echo $this->lang('Voir la liste') ?></a>
		<form id="orphan-list" class="collapse mt-2" action="<?php echo url($this->url->request) ?>" method="post">
			<ul class="list-unstyled small mb-2">
				<?php foreach ($orphans as $o): ?>
					<li>
						<label class="form-check">
							<input class="form-check-input" type="checkbox" name="purger_orphelins[]" value="<?php echo nf_texte($o['path']) ?>" checked />
							<code><?php echo nf_texte($o['path']) ?></code><?php echo $o['name'] !== '' ? ' · '.nf_texte($o['name']) : '' ?> · <?php echo human_size($o['size']) ?> · <?php echo timetostr('j M Y', $o['date']) ?>
						</label>
					</li>
				<?php endforeach ?>
			</ul>
			<button type="submit" class="btn btn-sm btn-danger"><?php echo icon('far fa-trash-alt').' '.$this->lang('Effacer les fichiers cochés') ?></button>
		</form>
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
							<i class="fas fa-image" data-bs-toggle="tooltip" title="<?php echo nf_texte($a['name']) ?>"></i>
						<?php else: ?>
							<i class="fas fa-file"></i>
						<?php endif ?>
					</td>
					<td><a href="<?php echo url('forum/piece-jointe/'.(int) $a['attachment_id']) ?>" target="_blank" rel="noopener"><?php echo nf_texte($a['name']) ?></a></td>
					<td><small><?php echo human_size((int)$a['file_size']) ?></small></td>
					<td><small><code><?php echo nf_texte($a['mime_type']) ?></code></small></td>
					<td><a href="<?php echo url('forum/topic/'.(int)$a['topic_id'].'/'.url_title($a['topic_title']).'#'.(int)$a['message_id']) ?>"><?php echo nf_texte($a['topic_title']) ?></a></td>
					<td><small><?php echo nf_texte($a['forum_title']) ?></small></td>
					<td><?php echo nf_texte($a['uploader_username']) ?></td>
					<td><small><?php echo time_span(strtotime($a['uploaded_at'])) ?></small></td>
					<td class="text-center">
						<button type="submit" name="delete_attachment[]" value="<?php echo (int)$a['attachment_id'] ?>" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="<?php echo $this->lang('Supprimer') ?>"
							data-confirm="<?php echo nf_texte($this->lang('Confirmer la suppression de cette pièce jointe ?')) ?>"
							data-confirm-title="<?php echo nf_texte($this->lang('Supprimer la pièce jointe')) ?>"
							data-confirm-icon="fas fa-paperclip"><?php echo icon('far fa-trash-alt') ?></button>
					</td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</form>
<?php endif ?>
