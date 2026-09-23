<ul class="list-group list-group-flush">
	<?php foreach ($recruits as $recruit): ?>
	<li class="list-group-item">
		<div class="float-end">
			<?php if ($recruit['closed'] || ($recruit['candidacies_accepted'] >= $recruit['size']) || ($recruit['date_end'] && strtotime($recruit['date_end']) < time())): ?>
				<span class="badge text-bg-danger"><?php echo $this->lang('Clôturée') ?></span>
			<?php else: ?>
				<?php if ($recruit['team_id']): ?>
				<span class="badge text-bg-dark" data-bs-toggle="tooltip" title="<?php echo $this->lang('Pour intégrer l\'équipe %s', $recruit['team_name']) ?>"><?php echo icon('fas fa-headset') ?></span>
				<?php endif ?>
				<span class="badge text-bg-dark" data-bs-toggle="tooltip" title="<?php echo $this->lang('Poste|Postes', $recruit['size'] - $recruit['candidacies_accepted']) ?>"><?php echo icon('fas fa-briefcase').' '.($recruit['size'] - $recruit['candidacies_accepted']) ?></span>
			<?php endif ?>
		</div>
		<a href="<?php echo url('recruits/'.$recruit['recruit_id'].'/'.url_title($recruit['title'])) ?>"><?php echo ($recruit['icon'] ? icon($recruit['icon']) : icon('fas fa-bullhorn')).' '.str_shortener($recruit['title'], 30) ?></a>
	</li>
	<?php endforeach ?>
</ul>
