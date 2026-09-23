<form action="<?php echo url($this->url->request.(empty($forum_id) && empty($is_topic) ? '#reply' : '')) ?>" method="post" enctype="multipart/form-data">
	<table class="table forum-reply-fullwidth">
		<tbody class="forum-content">
			<?php if (!empty($forum_id) || !empty($is_topic)): ?>
			<tr>
				<td><input type="text" class="form-control form-control-lg" name="<?php echo $form_id ?>[title]" value="<?php echo isset($post['title']) ? $post['title'] : (isset($title) && !empty($is_topic) ? $title : '') ?>" placeholder="<?php echo $this->lang('Titre du sujet') ?>" /></td>
			</tr>
			<?php endif ?>
			<tr>
				<td>
					<div class="nf-field">
						<textarea class="form-control editor" name="<?php echo $form_id ?>[message]" rows="12"><?php echo isset($post['message']) ? $post['message'] : (isset($message) ? $message : '') ?></textarea>
					</div>
					<?php // Pièce jointe (Phase 5) — pas affiché en mode édition pour ne pas recréer un attachment ?>
					<?php if (empty($is_topic) && empty($message)): ?>
					<div class="nf-field">
						<label class="d-block mb-1" for="forum_attachment">
							<?php echo icon('fas fa-paperclip').' '.$this->lang('Pièce jointe (optionnel)') ?>
						</label>
						<input type="file" name="attachment" id="forum_attachment" class="form-control" />
						<?php
							$mimes_setting = $this->config->forum_attachments_mimes ?: 'image/jpeg,image/png,image/gif,image/webp,application/pdf,text/plain,application/zip';
							$size_kb = $this->config->forum_attachments_size_max_kb ?: 5120;
						?>
						<small class="form-text text-muted">
							<?php // Une espace après chaque virgule : écrite d'un bloc, la liste formait un seul mot de 400 px que le navigateur ne pouvait pas couper, et le formulaire débordait d'un téléphone (2026-09-23). ?>
							<?php echo $this->lang('Types autorisés : %s', htmlspecialchars(implode(', ', array_map('trim', explode(',', $mimes_setting))))) ?>
							·
							<?php echo $this->lang('Taille max : %s', human_size($size_kb * 1024)) ?>
						</small>
					</div>
					<?php endif ?>
					<?php if (!empty($forum_id) && $this->access('forum', 'category_announce', $category_id)): ?>
					<div class="form-check">
						<input class="form-check-input" type="checkbox" id="forum-announce" name="<?php echo $form_id ?>[announce][]"<?php if (!empty($post['announce']) && in_array('on', $post['announce'])) echo ' checked="checked"' ?> />
						<label class="form-check-label" for="forum-announce"><?php echo $this->lang('Mettre en annonce') ?></label>
					</div>
					<?php endif ?>
					<?php if (!empty($forum_id)): ?>
					<a href="<?php echo url($this->url->back() ?: 'forum/'.$forum_id.'/'.url_title($title)) ?>" class="btn btn-secondary"><?php echo $this->lang('Retour') ?></a>
					<button type="submit" class="btn btn-primary"><?php echo $this->lang('Poster le sujet') ?></button>
					<?php elseif (!empty($topic_id)): ?>
					<a href="<?php echo url($this->url->back() ?: 'forum/topic/'.$topic_id.'/'.url_title($title)) ?>" class="btn btn-secondary"><?php echo $this->lang('Retour') ?></a>
					<button type="submit" class="btn btn-primary"><?php echo $this->lang($is_topic ? 'Modifier le sujet' : 'Modifier le message') ?></button>
					<?php else: ?>
					<button type="submit" class="btn btn-primary"><?php echo $this->lang('Répondre au sujet') ?></button>
					<?php endif ?>
				</td>
			</tr>
		</tbody>
	</table>
</form>
<script src="<?php echo js('tinymce/tinymce.min.js') ?>"></script>
<script>(function(){
	function init(){
		if (typeof tinymce === "undefined") { setTimeout(init, 100); return; }
		tinymce.init({
			selector: "textarea.editor",
			height: 360,
			menubar: false,
			branding: false,
			promotion: false,
			license_key: "gpl",
			skin: (document.documentElement.getAttribute("data-theme") === "dark") ? "oxide-dark" : "oxide",
			content_css: (document.documentElement.getAttribute("data-theme") === "dark") ? "dark" : "default",
			plugins: "advlist autolink lists link image charmap preview anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media table emoticons codesample help",
			toolbar: "undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table codesample | emoticons charmap | searchreplace fullscreen | removeformat",
			content_style: "body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;font-size:14px;}",
			image_advtab: true,
			link_default_target: "_blank",
			link_assume_external_targets: true
		});
	}
	if (document.readyState === "loading") { document.addEventListener("DOMContentLoaded", init); } else { init(); }
})();</script>
