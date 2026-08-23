<ul class="list-group list-group-flush">
	<?php foreach ($members as $member): ?>
	<li class="list-group-item">
		<span class="float-end"><?php echo icon('far fa-clock').' '.timetostr('j M Y', $member['registration_date']) ?></span>
		<?php echo $this->user->link($member['user_id'], $member['username']) ?>
	</li>
	<?php endforeach ?>
</ul>
