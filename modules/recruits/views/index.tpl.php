<div class="row">
	<?php if ($image_id): ?>
	<div class="col-4">
		<a class="thumbnail" href="<?php echo url('recruits/'.$recruit_id.'/'.url_title($title)) ?>"><img class="img-fluid" src="<?php echo NeoFrag()->model2('file', $image_id)->path() ?>" alt="" /></a>
	</div>
	<?php endif ?>
	<div class="col-<?php echo $image_id ? '8' : '12' ?>">
		<p><i><?php echo icon('far fa-clock') ?> <?php echo $this->lang('Offre publiée le %s', timetostr($this->lang('j M Y'), $date)) ?></i></p>
		<ul class="list-unstyled">
			<?php if ($team_id): ?>
			<li><b><?php echo $this->lang('Équipe') ?> :</b> <?php echo $team_name ?></li>
			<?php endif ?>
			<li><b><?php echo $this->lang('Rôle') ?> :</b> <?php echo $role ?></li>
			<li><b><?php echo $this->lang('Place disponible|Places disponibles', $size) ?> :</b> <?php echo $size ?></li>
			<?php if ($date_end): ?>
			<li><b><?php echo $this->lang('Expiration') ?> :</b> <?php echo timetostr($this->lang('j M Y'), $date_end) ?></li>
			<?php endif ?>
		</ul>
		<p><?php echo $introduction ?></p>
	</div>
</div>
