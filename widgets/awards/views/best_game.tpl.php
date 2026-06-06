<div class="text-center">
	<b><?php echo $this->lang('Jeu le plus récompensé') ?></b>
	<h1><?php echo icon('fas fa-gamepad') ?></h1>
	<a href="<?php echo url('awards/game/'.$game_id.'/'.$name) ?>"><b><?php echo $game_title ?></b></a><br />
	<?php echo $this->lang('Avec %d trophée|Avec %d trophées', $nb_awards, $nb_awards) ?>
</div>
