<?php $title = $title ?? ''; $solution_id = $solution_id ?? 0; ?>
<script>(function(){
	function init(){
		if (typeof bootstrap === "undefined" || !bootstrap.Popover) { setTimeout(init, 100); return; }
		document.querySelectorAll(".forum-profile-has-popover").forEach(function(el){
			new bootstrap.Popover(el, { container: "body" });
		});
	}
	if (document.readyState === "loading") { document.addEventListener("DOMContentLoaded", init); } else { init(); }
})();</script>
<div class="forum-thread" data-forum-view="messages">
	<div class="forum-thread-header">
		<div class="float-end">
			<?php echo icon('fas fa-users').' '.$this->lang('%d participant|%d participants', $nb_users, $nb_users) ?>
		</div>
		<h5 class="m-0"><?php echo icon('far fa-comments').' '.$this->lang('%d réponse|%d réponses', $nb_messages, $nb_messages) ?></h5>
	</div>
	<div class="forum-thread-body">
		<?php foreach ($messages as $message): ?>
		<?php $est_solution = !empty($solution_id) && (int) $message['message_id'] === (int) $solution_id && $message['message'] !== NULL ?>
		<div data-message-id="<?php echo (int)$message['message_id'] ?>" data-depth="<?php echo (int)($message['depth'] ?? 0) ?>" class="forum-message-row<?php echo !empty($message['depth']) ? ' forum-message-nested forum-message-depth-'.min(5, (int)$message['depth']) : '' ?><?php echo $est_solution ? ' forum-message-solution' : '' ?>">
			<div class="forum-user-cell">
				<?php echo $this->output->module()->get_profile($message['user_id'], $profile, $message['identite'] ?? NULL) ?>
			</div>
			<div class="forum-message-cell">
				<div class="actions float-end">
				<?php if (!empty($peut_resoudre) && $message['message'] !== NULL): ?>
					<a href="<?php echo url('forum/solution/'.(int) $message['message_id'].'/'.url_title($title)).'?_='.urlencode((string) ($jeton ?? '')) ?>" class="btn btn-sm <?php echo $est_solution ? 'btn-success' : 'btn-light' ?>" data-bs-toggle="tooltip" title="<?php echo $est_solution ? $this->lang('Retirer la solution') : $this->lang('Marquer comme solution') ?>"><?php echo icon('fas fa-check') ?></a>
				<?php endif ?>
				<?php if ($this->user() && empty($is_locked)): ?>
					<a href="<?php echo url('forum/topic/'.$topic_id.'/'.url_title($title)).'?reply_to='.(int)$message['message_id'].'#reply' ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="<?php echo $this->lang('Répondre à ce message') ?>"><?php echo icon('fas fa-reply') ?></a>
				<?php endif ?>
				<?php if (($this->user() && $this->user->id == $message['user_id']) || $this->access('forum', 'category_modify', $category_id)): ?>
					<a href="<?php echo url('forum/message/edit/'.$message['message_id'].'/'.url_title($title)) ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="<?php echo $this->lang('Editer') ?>"><?php echo icon('fas fa-edit') ?></a>
					<a href="<?php echo url('forum/message/delete/'.$message['message_id'].'/'.url_title($title)) ?>" class="btn btn-sm btn-primary delete" data-bs-toggle="tooltip" title="<?php echo $this->lang('Supprimer') ?>"><?php echo icon('fas fa-times') ?></a>
				<?php endif ?>
				<?php if (($mod = $this->module('moderation'))): echo $mod->report_button('forum_message', (int)$message['message_id'], url('forum/topic/'.$topic_id.'/'.url_title($title)).'#'.(int)$message['message_id'], NULL, (int)$message['user_id']); endif ?>
				</div>
				<?php if ($est_solution): ?><span class="forum-solution-marque"><?php echo icon('fas fa-check-circle').' '.$this->lang('Solution') ?></span> <?php endif ?>
				<a name="<?php echo $message['message_id'] ?>"></a><?php echo icon('far fa-clock').' '.time_span($message['date']).' '.($last_message_read && $message['date'] <= $last_message_read ? icon('far fa-comment').' '.$this->lang('Message lu') : icon('fas fa-comment').' '.$this->lang('Message non lu')) ?>
				<hr />
				<?php
					// Phase B — bandeau "En réponse à" si parent_id défini
					if (!empty($message['parent_id']) && !empty($message['parent_username'])):
						$excerpt = trim((string)($message['parent_excerpt'] ?? ''));
						if ($excerpt === '') $excerpt = '…';
				?>
				<a class="forum-reply-to" href="#<?php echo (int)$message['parent_id'] ?>">
					<?php echo icon('fas fa-reply') ?>
					<?php echo $this->lang('En réponse à') ?>
					<span class="forum-reply-to-author">@<?php echo htmlspecialchars($message['parent_username']) ?></span> ·
					<span class="forum-reply-to-excerpt"><?php echo htmlspecialchars(mb_strimwidth($excerpt, 0, 100, '…')) ?></span>
				</a>
				<?php endif ?>
				<?php echo $message['message'] !== NULL ? $this->output->module()->render_mentions($this->output->module()->forum_render($message['message'])) : $this->lang('<i>Message supprimé</i>') ?>
				<?php echo $this->output->module()->render_attachments($message['message_id']) ?>
				<?php if ($message['message'] !== NULL && ($reactions = $this->module('reactions'))): ?>
				<div class="mt-2"><?php echo $reactions->bar('forum-message', (int)$message['message_id']) ?></div>
				<?php endif ?>
				<?php if (!empty($profile['signature'])): ?>
				<hr />
				<?php echo bbcode($profile['signature']) ?>
				<?php endif ?>
			</div>
		</div>
		<?php endforeach ?>
	</div>
</div>
