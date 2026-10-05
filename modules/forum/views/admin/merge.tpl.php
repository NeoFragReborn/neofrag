<form action="<?php echo url($this->url->request) ?>" method="post">
	<div class="alert alert-warning">
		<?php echo icon('fas fa-exclamation-triangle').' '.$this->lang('La fusion déplace tous les messages de ce sujet vers le sujet cible. Le sujet courant sera supprimé. Cette action est irréversible.') ?>
	</div>

	<div class="nf-field">
		<label><?php echo $this->lang('Sujet cible (dans le même forum)') ?></label>
		<select name="<?php echo $form_id ?>[target_topic_id]" class="form-select" required>
			<option value=""><?php echo $this->lang('— Choisir un sujet —') ?></option>
			<?php foreach ($other_topics as $t): ?>
				<option value="<?php echo (int)$t['topic_id'] ?>"><?php echo nf_texte($t['title']).' ('.((int)$t['count_messages'] + 1).' messages)' ?></option>
			<?php endforeach ?>
		</select>
		<?php if (empty($other_topics)): ?>
			<small class="form-text text-muted"><?php echo $this->lang('Aucun autre sujet disponible dans ce forum.') ?></small>
		<?php endif ?>
	</div>

	<div class="nf-field">
		<a href="<?php echo url('forum/topic/'.(int)$topic_id.'/'.url_title($title)) ?>" class="btn btn-secondary"><?php echo $this->lang('Annuler') ?></a>
		<button type="submit" class="btn btn-warning" <?php echo empty($other_topics) ? 'disabled' : '' ?>><?php echo icon('fas fa-compress-arrows-alt').' '.$this->lang('Fusionner') ?></button>
	</div>
</form>
