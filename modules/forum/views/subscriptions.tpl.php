<table class="table table-hover" data-forum-view="subscriptions">
	<thead class="forum-heading">
		<tr>
			<th class="col-6"><h5 class="m-0"><?php echo icon('fas fa-bell').' '.$this->lang('Sujets suivis') ?></h5></th>
			<th class="col-2"><h5 class="m-0"><?php echo icon('fas fa-folder') ?><span class="d-none d-sm-inline-block ms-1"><?php echo $this->lang('Forum') ?></span></h5></th>
			<th class="col-2"><h5 class="m-0"><?php echo icon('far fa-comment') ?><span class="d-none d-sm-inline-block ms-1"><?php echo $this->lang('Dernier message') ?></span></h5></th>
			<th class="col-2 text-center"><h5 class="m-0"><?php echo $this->lang('Action') ?></h5></th>
		</tr>
	</thead>
	<tbody class="forum-content">
		<?php foreach ($subscriptions as $sub): ?>
		<tr>
			<td class="col-6">
				<h5 class="m-0">
					<a href="<?php echo url('forum/topic/'.$sub['topic_id'].'/'.url_title($sub['title'])) ?>"><?php echo $sub['title'] ?></a>
				</h5>
				<div><small><?php echo icon('far fa-bell').' '.$this->lang('Abonné depuis').' '.time_span(strtotime($sub['subscribed_at'])) ?></small></div>
			</td>
			<td class="col-2">
				<a href="<?php echo url('forum/'.$sub['forum_id'].'/'.url_title($sub['forum_title'])) ?>"><?php echo $sub['forum_title'] ?></a>
			</td>
			<td class="col-2">
				<?php if ($sub['count_messages']): ?>
				<div><small><?php echo icon('fas fa-user').' '.$this->user->link(NULL, $sub['last_username']).'<br />'.icon('far fa-clock').' '.time_span($sub['last_message_date']) ?></small></div>
				<?php else: ?>
					<small><?php echo $this->lang('Aucune réponse') ?></small>
				<?php endif ?>
			</td>
			<td class="col-2 text-center">
				<a class="btn btn-outline-danger btn-sm" href="<?php echo url('forum/topic/unsubscribe/'.$sub['topic_id'].'/'.url_title($sub['title'])) ?>" data-bs-toggle="tooltip" title="<?php echo $this->lang('Se désabonner') ?>"><?php echo icon('fas fa-bell-slash') ?></a>
			</td>
		</tr>
		<?php endforeach ?>
		<?php if (empty($subscriptions)): ?>
		<tr>
			<td colspan="4"><div class="alert alert-info text-center mb-0"><?php echo $this->lang('Tu n\'es abonné(e) à aucun sujet pour le moment.') ?></div></td>
		</tr>
		<?php endif ?>
	</tbody>
</table>
