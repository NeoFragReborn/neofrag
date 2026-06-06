<div class="text-center">
	<b><?php echo $this->lang('Équipe la plus récompensée') ?></b>
	<h1><?php echo icon('fas fa-trophy') ?></h1>
	<?php echo $this->lang('Équipe %s', '<a href="'.url('awards/team/'.$team_id.'/'.$name).'"><b>'.$team_title.'</b></a>') ?><br />
	<?php echo $this->lang('Avec %d trophée|Avec %d trophées', $nb_awards, $nb_awards) ?>
</div>
