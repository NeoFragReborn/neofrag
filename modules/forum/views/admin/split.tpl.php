<form action="<?php echo url($this->url->request) ?>" method="post">
	<div class="alert alert-info">
		<?php echo icon('fas fa-info-circle').' '.$this->lang('Sélectionne les messages à déplacer vers un nouveau sujet. Le 1er message (starter) ne peut pas être déplacé.') ?>
	</div>

	<div class="nf-field">
		<label><?php echo $this->lang('Titre du nouveau sujet') ?></label>
		<input type="text" class="form-control" name="<?php echo $form_id ?>[new_title]" value="<?php echo nf_texte($this->lang('Re: %s', $title)) ?>" required />
	</div>

	<div class="nf-field">
		<label><?php echo $this->lang('Messages à déplacer') ?></label>
		<table class="table table-hover table-sm">
			<thead>
				<tr>
					<th width="30">&nbsp;</th>
					<th><?php echo $this->lang('Auteur') ?></th>
					<th><?php echo $this->lang('Date') ?></th>
					<th><?php echo $this->lang('Aperçu') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($messages as $message): ?>
					<?php $is_starter = ((int)$message['message_id'] === (int)$starter_id); ?>
					<tr<?php echo $is_starter ? ' class="table-secondary"' : '' ?>>
						<td>
							<?php if (!$is_starter): ?>
								<input type="checkbox" name="split_messages[]" value="<?php echo (int)$message['message_id'] ?>" />
							<?php else: ?>
								<i class="fas fa-flag" data-bs-toggle="tooltip" title="<?php echo $this->lang('Starter (non déplaçable)') ?>"></i>
							<?php endif ?>
						</td>
						<td><?php echo nf_texte($users[(int)$message['user_id']] ?? '?') ?></td>
						<td><small><?php echo time_span($message['date']) ?></small></td>
						<td><small><?php echo nf_texte(strip_tags(str_replace('<br />', ' ', (string)$message['message'])), 120) ?>...</small></td>
					</tr>
				<?php endforeach ?>
			</tbody>
		</table>
	</div>

	<div class="nf-field">
		<a href="<?php echo url('forum/topic/'.(int)$topic_id.'/'.url_title($title)) ?>" class="btn btn-secondary"><?php echo $this->lang('Annuler') ?></a>
		<button type="submit" class="btn btn-primary"><?php echo icon('fas fa-cut').' '.$this->lang('Scinder') ?></button>
	</div>
</form>
