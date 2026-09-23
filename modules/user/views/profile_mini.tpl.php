<div class="d-flex align-items-start gap-3 popover-user">
	<?php echo $user->avatar() ?>
	<div class="flex-grow-1">
		<?php echo $user->profile()->first_name.' '.$user->profile()->last_name ?> <b><?php echo $user->profile()->username ?></b>
		<?php //echo $user->groups() ?>
	</div>
</div>
