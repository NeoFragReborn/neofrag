<div class="forum-thread" data-forum-view="topic">
	<div class="forum-thread-header">
		<div class="float-end">
			<?php if ($this->user()): ?>
				<?php if (!empty($is_subscribed)): ?>
					<a class="btn btn-sm btn-outline-warning me-2" href="<?php echo url('forum/topic/unsubscribe/'.$topic_id.'/'.url_title($title)) ?>" data-bs-toggle="tooltip" title="<?php echo $this->lang('Se désabonner de ce sujet') ?>">
						<?php echo icon('fas fa-bell-slash').' '.$this->lang('Suivi') ?>
					</a>
				<?php else: ?>
					<a class="btn btn-sm btn-outline-primary me-2" href="<?php echo url('forum/topic/subscribe/'.$topic_id.'/'.url_title($title)) ?>" data-bs-toggle="tooltip" title="<?php echo $this->lang('S\'abonner à ce sujet') ?>">
						<?php echo icon('far fa-bell').' '.$this->lang('Suivre') ?>
					</a>
				<?php endif ?>
			<?php endif ?>
			<?php echo icon('far fa-eye').' '.$this->lang('%d vue|%d vues', $views, $views) ?>
		</div>
		<h5 class="m-0"><?php echo icon('far fa-file-alt').' '.\NF\Modules\Forum\Models\Forum::pastille_prefixe($prefixe ?? NULL).' '.$title ?></h5>
	</div>
	<div class="forum-thread-body">
		<div class="forum-message-row">
			<div class="forum-user-cell">
				<?php echo $this->output->module()->get_profile($user_id, $profile, $identite ?? NULL) ?>
			</div>
			<div class="forum-message-cell">
				<div class="actions float-end">
				<?php if (($this->user() && $this->user->id == $user_id) || $this->access('forum', 'category_modify', $category_id)): ?>
					<a href="<?php echo url('forum/message/edit/'.$message_id.'/'.url_title($title)) ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="<?php echo $this->lang('Editer le sujet') ?>"><?php echo icon('fas fa-edit') ?></a>
					<a href="<?php echo url('forum/message/delete/'.$message_id.'/'.url_title($title)) ?>" class="btn btn-sm btn-primary delete" data-bs-toggle="tooltip" title="<?php echo $this->lang('Supprimer le sujet') ?>"><?php echo icon('fas fa-times') ?></a>
				<?php endif ?>
				<?php if (($mod = $this->module('moderation'))): echo $mod->report_button('forum_topic', (int)$topic_id, url('forum/topic/'.$topic_id.'/'.url_title($title)), NULL, (int)$user_id); endif ?>
				</div>
				<a name="<?php echo $message_id ?>"></a><?php echo icon('far fa-clock').' '.time_span($date).' '.($last_message_read && $date <= $last_message_read ? icon('far fa-comment').' '.$this->lang('Message lu') : icon('fas fa-comment').' '.$this->lang('Message non lu')) ?>
				<hr />
				<?php echo $this->output->module()->render_mentions($this->output->module()->forum_render($message)) ?>
				<?php echo $this->output->module()->render_attachments($message_id) ?>
				<?php if (!empty($solution)): ?>
				<a class="forum-solution-encart" href="#<?php echo (int) $solution['message_id'] ?>">
					<strong><?php echo icon('fas fa-check-circle').' '.$this->lang('Résolu') ?></strong>
					<span><?php echo $this->lang('La réponse de %s résout ce sujet.', htmlspecialchars((string) ($this->db->select('username')->from('nf_user')->where('id', (int) $solution['user_id'])->row() ?: $this->lang('Visiteur')))) ?></span>
					<em><?php echo htmlspecialchars(mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', (string) $solution['message'])))), 0, 160, '…')) ?></em>
				</a>
				<?php endif ?>
				<?php if (!empty($profile['signature'])): ?>
				<hr />
				<?php echo bbcode($profile['signature']) ?>
				<?php endif ?>
			</div>
		</div>
	</div>
</div>
