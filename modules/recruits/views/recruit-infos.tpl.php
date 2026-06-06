<ul class="list-unstyled">
	<?php if ($team_id): ?>
	<li class="list-item"><b><?php echo $this->lang('Équipe') ?> :</b> <?php echo $team_name ?></li>
	<?php endif ?>
	<li class="list-item"><b><?php echo $this->lang('Rôle') ?> :</b> <?php echo $role ?></li>
	<li class="list-item"><b><?php echo $this->lang('Place disponible|Places disponibles', $size) ?> :</b> <?php echo $size ?></li>
	<?php if ($date_end): ?>
	<li class="list-item"><b><?php echo $this->lang('Expiration') ?> :</b> <?php echo timetostr($this->lang('j M Y'), $date_end) ?></li>
	<?php endif ?>
</ul>
