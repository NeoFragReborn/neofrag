<?php if ($image_id): ?>
	<img class="img-fluid" src="<?php echo NeoFrag()->model2('file', $image_id)->path() ?>" alt="" />
<?php endif ?>
<div class="card-body">
	<div class="text-center mb-5">
		<h5><?php echo $this->lang('Classement de l\'équipe %s', '<a href="'.url('awards/team/'.$team_id.'/'.$team_name).'"><b>'.$team_title.'</b></a>') ?></h5>
		<?php if ($ranking == 1): ?>
			<h1 class="m-0"><?php echo icon('fas fa-trophy trophy-gold fa-3x') ?></h1>
			<big><?php echo $this->lang('<b>1er</b> sur %d équipe|<b>1er</b> sur %d équipes', $participants, $participants) ?></big>
		<?php endif ?>
		<?php if ($ranking == 2): ?>
			<h1 class="m-0"><?php echo icon('fas fa-trophy trophy-silver fa-3x') ?></h1>
			<big><?php echo $this->lang('<b>2e</b> sur %d équipe|<b>2e</b> sur %d équipes', $participants, $participants) ?></big>
		<?php endif ?>
		<?php if ($ranking == 3): ?>
			<h1 class="m-0"><?php echo icon('fas fa-trophy trophy-bronze fa-3x') ?></h1>
			<big><?php echo $this->lang('<b>3e</b> sur %d équipe|<b>3e</b> sur %d équipes', $participants, $participants) ?></big>
		<?php endif ?>
		<?php if ($ranking >= 4): ?>
			<big><?php echo $this->lang('Rang <b>%d</b> sur %d équipe|Rang <b>%d</b> sur %d équipes', $participants, $ranking, $participants) ?></big>
		<?php endif ?>
	</div>
	<ul class="list-inline<?php echo $description ? '' : ' m-0' ?>">
		<li class="list-inline-item"><span data-bs-toggle="tooltip" title="<?php echo $this->lang('Date') ?>"><?php echo icon('far fa-calendar').' '.timetostr($this->lang('d/m/Y'), $date) ?></span></li>
		<?php if ($location): ?><li class="list-inline-item"><span data-bs-toggle="tooltip" title="<?php echo $this->lang('Lieu') ?>"><?php echo icon('fas fa-map-marker-alt').' '.$location ?></span></li><?php endif ?>
		<li class="list-inline-item"><span data-bs-toggle="tooltip" title="<?php echo $this->lang('Jeu') ?>"><a href="<?php echo url('awards/game/'.$game_id.'/'.$game_name) ?>"><?php echo icon('fas fa-gamepad').' '.$game_title ?></a></span></li>
		<li class="list-inline-item"><span data-bs-toggle="tooltip" title="<?php echo $this->lang('Plateforme') ?>"><?php echo icon('fas fa-tv').' '.$platform ?></span></li>
		<li class="list-inline-item"><?php echo icon('fas fa-users').' '.$this->lang('%d participant|%d participants', $participants, $participants) ?></li>
	</ul>
	<?php echo $description ?: '' ?>
</div>
