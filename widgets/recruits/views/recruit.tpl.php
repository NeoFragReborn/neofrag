<?php if ($image_id): ?>
<img class="img-fluid" src="<?php echo NeoFrag()->model2('file', $image_id)->path() ?>" alt="" />
<?php endif ?>
<div class="card-body">
	<p class="h6 mb-2"><?php echo icon($icon) ?> <a href="<?php echo url('recruits/'.$recruit_id.'/'.url_title($title)) ?>"><?php echo $title ?></a></p>
	<?php echo $introduction ?>
</div>
<ul class="list-group list-group-flush">
	<?php if ($team_id): ?>
	<li class="list-group-item">
		<span class="float-end"><b><?php echo $team_name ?></b></span>
		<?php echo icon('fas fa-headset') ?> <?php echo $this->lang('Équipe') ?>
	</li>
	<?php endif ?>
	<li class="list-group-item">
		<span class="float-end"><b><?php echo $role ?></b></span>
		<?php echo icon('fas fa-sitemap') ?> <?php echo $this->lang('Rôle proposé') ?>
	</li>
	<li class="list-group-item">
		<span class="float-end"><b><?php echo ($size) ?></b></span>
		<?php echo icon('fas fa-users').' '.$this->lang('Poste disponible|Postes disponibles', $size) ?>
	</li>
	<?php if ($date_end): ?>
	<li class="list-group-item">
		<span class="float-end"><b><?php echo timetostr('j M Y', $date_end) ?></b></span>
		<?php echo icon('far fa-calendar') ?> <?php echo $this->lang('Date limite') ?>
	</li>
	<?php endif ?>
</ul>
