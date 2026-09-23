<div class="nf-comment d-flex align-items-start<?php if ($comment->parent()) echo ' comments-child' ?>">
	<?php echo $comment->user->avatar() ?>
	<div class="nf-comment-body flex-grow-1">
		<?php
			$actions = $this->array()
							->append_if($this->user() && !$comment->parent(), '<a class="btn btn-link btn-sm comment-reply" href="#comments" data-comment-id="'.$comment->id.'">'.icon('fas fa-reply').' '.$this->lang('Répondre').'</a>')
							->append_if(!$comment->deleted_at && ($this->access->effective_admin() || ($this->user() && $this->user->id == $comment->user->id)), $this->button_delete('ajax/comments/delete/'.$comment->id)->compact());

			// Bouton signaler (si user connecté et pas l'auteur du commentaire)
			if (!$comment->deleted_at && ($mod = $this->module('moderation')) && $this->user() && $comment->user() && (int)$this->user->id !== (int)$comment->user->id)
			{
				$rb = $mod->report_button('comment', (int)$comment->id, $this->url->request, NULL, (int)$comment->user->id);
				if ($rb) $actions->append($rb);
			}

			if ($actions)
			{
				echo '<ul class="list-inline float-end">'.$actions->each(function($a){
					return '<li class="list-inline-item">'.$a.'</li>';
				}).'</ul>';
			}
		?>
		<h6>
			<?php echo $comment->user() ? $comment->user->link() : $this->lang('Visiteur') ?>
			<small><?php echo icon('far fa-clock').' '.$comment->date ?></small>
		</h6>
		<?php echo !$comment->deleted_at ? strtolink(sanitize_html(nl2br($comment->content)), TRUE) : '<i>'.$this->lang('Message supprimé').'</i>' ?>
		<?php if (!$comment->deleted_at && ($r = $this->module('reactions'))): ?>
		<div class="mt-2"><?php echo $r->bar('comment', $comment->id) ?></div>
		<?php endif ?>
	</div>
</div>
