<div class="row">
	<div class="col-md-9">
		<div class="card talks-conversation">
			<div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
				<div>
					<?php
						$icon = $talk['type'] === 'public' ? 'fas fa-hashtag' : ($talk['type'] === 'group' ? 'fas fa-users' : 'fas fa-user');
					?>
					<h5 class="m-0"><?php echo \icon($icon).' '.nf_texte($talk['name']) ?></h5>
					<?php if (!empty($talk['description'])): ?>
						<small class="text-muted"><?php echo nf_texte($talk['description']) ?></small>
					<?php endif ?>
				</div>
				<?php /* Au téléphone, les boutons passent à la ligne : sans écart, « Inviter » touchait « Quitter » (2026-10-10). */ ?>
				<div class="actions d-flex flex-wrap gap-1">
					<?php echo implode(' ', $actions) ?>
				</div>
			</div>

			<div class="card-body talks-messages" style="max-height: 60vh; overflow-y: auto;">
				<?php if (empty($messages)): ?>
					<div class="text-center text-muted py-5">
						<?php echo \icon('far fa-comment fa-3x mb-3') ?>
						<p><?php echo $this->lang('Aucun message pour l\'instant. Sois le premier à écrire !') ?></p>
					</div>
				<?php else: ?>
					<?php foreach ($messages as $m): ?>
						<?php
							$is_system = empty($m['user_id']);
							$is_me     = !$is_system && (int)$m['user_id'] === (int)$user_id;
							$align     = $is_system ? 'center' : ($is_me ? 'right' : 'left');
							$bg        = $is_system ? 'rgba(13, 110, 253, 0.06)' : ($is_me ? 'rgba(13, 110, 253, 0.12)' : 'rgba(0, 0, 0, 0.04)');
						?>
						<div class="talks-message mb-2" style="text-align: <?php echo $align ?>;">
							<?php if (!$is_system): ?>
								<small class="text-muted"><?php echo nf_texte($m['username'] ?? '?').' · '.\time_span($m['date']) ?></small>
							<?php endif ?>
							<div style="display: inline-block; max-width: 75%; padding: 8px 12px; background: <?php echo $bg ?>; border-radius: 12px; text-align: left;">
								<?php if ($is_system): ?>
									<small><?php echo \icon('fas fa-robot') ?> <i><?php echo $this->lang('Système') ?></i></small><br />
								<?php endif ?>
								<?php
									$is_deleted = !empty($m['deleted_at']);
									$is_edited  = !empty($m['edited_at']);
									$is_staff_audience = !empty($talk['audience']) && $talk['audience'] === 'staff';
									if ($is_deleted) {
										echo '<i class="text-muted">'.$this->lang('Message supprimé').'</i>';
									} else if ($is_staff_audience) {
										echo \NF\Modules\Talks\Security::render_staff_message((string)$m['message']);
									} else {
										echo \NF\Modules\Talks\Security::render_message((string)$m['message']);
									}
								?>
								<?php if ($is_edited): ?>
									<small class="text-muted">(<?php echo $this->lang('édité') ?>)</small>
								<?php endif ?>
								<?php if (!$is_system && !$is_me && !$is_deleted && ($mod = $this->module('moderation'))): ?>
									<?php echo $mod->report_button('talks_message', (int)$m['message_id'], \url('talks/'.(int)$talk_id.'/'.\url_title($title)), NULL, (int)$m['user_id']) ?>
								<?php endif ?>
								<?php
									$msg_attachments = $attachments_map[(int)$m['message_id']] ?? [];
									if (!empty($msg_attachments)):
								?>
									<div class="talks-attachments mt-2" style="border-top: 1px dashed rgba(0,0,0,0.1); padding-top: 6px;">
										<?php foreach ($msg_attachments as $att): ?>
											<?php
												$is_image = strpos((string)$att['mime_type'], 'image/') === 0;
												$file_url = \url('talks/piece-jointe/'.(int) $att['attachment_id']);
												$name_esc = nf_texte($att['name']);
											?>
											<?php if ($is_image): ?>
												<a href="<?php echo $file_url ?>" target="_blank" rel="noopener" title="<?php echo $name_esc ?>">
													<img src="<?php echo $file_url ?>" alt="<?php echo $name_esc ?>" style="max-width:200px;max-height:150px;border-radius:6px;display:block;margin-top:4px;" loading="lazy" />
												</a>
											<?php else: ?>
												<a href="<?php echo $file_url ?>" target="_blank" rel="noopener" download="<?php echo $name_esc ?>" style="display:inline-block;padding:4px 8px;background:rgba(0,0,0,0.04);border-radius:4px;text-decoration:none;color:inherit;margin-top:4px;">
													<?php echo \icon('fas fa-paperclip').' '.$name_esc ?>
													<small class="text-muted">(<?php echo \human_size((int)$att['file_size']) ?>)</small>
												</a>
											<?php endif ?>
										<?php endforeach ?>
									</div>
								<?php endif ?>
							</div>
						</div>
					<?php endforeach ?>
				<?php endif ?>
				<a name="bottom"></a>
			</div>

			<?php if (!empty($user_id)): ?>
			<?php $is_staff_audience = !empty($talk['audience']) && $talk['audience'] === 'staff'; ?>
			<?php if (!$is_staff_audience): ?><?php $this->js('talks-emoji'); ?><?php endif ?>
			<div class="card-footer">
				<?php if ($is_staff_audience): ?>
					<form method="post" action="<?php echo url('talks/'.(int)$talk_id.'/'.\url_title($title)) ?>" enctype="multipart/form-data" id="nf-talks-staff-form">
						<textarea name="talk_message" id="nf-talks-staff-editor" placeholder="<?php echo $this->lang('Ton message...') ?>"></textarea>
						<div class="d-flex justify-content-between align-items-center mt-2">
							<small class="form-text text-muted">
								<label class="btn btn-light btn-sm mb-0" style="cursor:pointer;" data-bs-toggle="tooltip" title="<?php echo $this->lang('Joindre un fichier') ?>">
									<?php echo \icon('fas fa-paperclip') ?>
									<input type="file" name="talk_attachment" style="display:none;" data-nf-file-name="talk-attached-name" />
								</label>
								<span class="text-info ms-2" id="talk-attached-name"></span>
							</small>
							<button type="submit" class="btn btn-primary"><?php echo \icon('fas fa-paper-plane').' '.$this->lang('Envoyer') ?></button>
						</div>
					</form>
					<script src="<?php echo js('tinymce/tinymce.min.js') ?>"></script>
					<script>(function(){
						function init(){
							if (typeof tinymce === "undefined") { setTimeout(init, 100); return; }
							tinymce.init({
								<?php // Les images collées ou glissées partent au site (cf. Editeur_Images). ?>
								<?php echo \NF\NeoFrag\Libraries\Editeur_Images::tinymce() ?>
								selector: "#nf-talks-staff-editor",
								height: 280,
								menubar: false,
								branding: false,
								promotion: false,
								license_key: "gpl",
								skin: (document.documentElement.getAttribute("data-theme") === "dark") ? "oxide-dark" : "oxide",
								content_css: (document.documentElement.getAttribute("data-theme") === "dark") ? "dark" : "default",
								plugins: "advlist autolink lists link image charmap preview anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media table emoticons codesample help",
								toolbar: "undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table codesample | emoticons charmap | searchreplace fullscreen | removeformat",
								content_style: "body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;font-size:14px;}"
							});
						}
						if (document.readyState === "loading") { document.addEventListener("DOMContentLoaded", init); } else { init(); }
					})();</script>
				<?php else: ?>
					<form method="post" action="<?php echo url('talks/'.(int)$talk_id.'/'.\url_title($title)) ?>" enctype="multipart/form-data">
						<div class="input-group">
							<label class="btn btn-light mb-0" style="cursor:pointer;" data-bs-toggle="tooltip" title="<?php echo $this->lang('Joindre un fichier') ?>">
								<?php echo \icon('fas fa-paperclip') ?>
								<input type="file" name="talk_attachment" style="display:none;" data-nf-file-name="talk-attached-name" />
							</label>
							<input type="text" name="talk_message" class="form-control" placeholder="<?php echo $this->lang('Ton message...') ?>" autocomplete="off" maxlength="2000" />
							<button type="submit" class="btn btn-primary"><?php echo \icon('fas fa-paper-plane').' '.$this->lang('Envoyer') ?></button>
						</div>
						<small class="form-text text-muted">
							<span><?php echo $this->lang('Max 2000 caractères.') ?></span>
							<span class="text-info" id="talk-attached-name" style="margin-left:8px;"></span>
							<span class="float-end">
								<?php echo $this->lang('Fichiers : %s · max %s', nf_texte(implode(', ', $allowed_mimes ?? [])), \human_size((int)($max_size_bytes ?? 5242880))) ?>
							</span>
						</small>
					</form>
				<?php endif ?>
			</div>
			<?php endif ?>
		</div>
	</div>

	<div class="col-md-3">
		<div class="card">
			<div class="card-header">
				<h6 class="m-0"><?php echo \icon('fas fa-users').' '.$this->lang('Participants').' ('.count($participants).')' ?></h6>
			</div>
			<ul class="list-group list-group-flush">
				<?php foreach ($participants as $p): ?>
					<li class="list-group-item d-flex justify-content-between align-items-center">
						<span>
							<?php echo $this->user->link((int)$p['user_id'], $p['username']) ?>
						</span>
						<?php if ($p['role'] === 'admin'): ?>
							<span class="badge text-bg-warning" title="<?php echo $this->lang('Admin de la conversation') ?>"><?php echo \icon('fas fa-crown') ?></span>
						<?php endif ?>
					</li>
				<?php endforeach ?>
			</ul>
		</div>
	</div>
</div>
