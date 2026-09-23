<?php
	$stats_team = ${'stats-team'} ?? FALSE;
	$stats_game = ${'stats-game'} ?? FALSE;
?>
<?php if ($stats_team || $stats_game): ?>
	<?php if (!empty($image_id)): ?>
		<img class="img-fluid" src="<?php echo NeoFrag()->model2('file', $image_id)->path() ?>" alt="" />
	<?php endif ?>
	<div class="card-body">
		<div class="text-center">
			<h5><?php echo $stats_team ? $this->lang('Palmarès de cette équipe') : $this->lang('Palmarès sur ce jeu') ?></h5>
			<ul class="list-inline">
				<li class="list-inline-item">
					<span data-bs-toggle="tooltip" title="<?php echo $this->lang('1ère place') ?>"><?php echo icon('fas fa-trophy fa-2x trophy-gold') ?></span><br />
					<?php echo $this->lang('%d trophée|%d trophées', $total_gold[0], $total_gold[0]) ?>
				</li>
				<li class="list-inline-item">
					<span data-bs-toggle="tooltip" title="<?php echo $this->lang('2e place') ?>"><?php echo icon('fas fa-trophy fa-2x trophy-silver') ?></span><br />
					<?php echo $this->lang('%d trophée|%d trophées', $total_silver[0], $total_silver[0]) ?>
				</li>
				<li class="list-inline-item">
					<span data-bs-toggle="tooltip" title="<?php echo $this->lang('3e place') ?>"><?php echo icon('fas fa-trophy fa-2x trophy-bronze') ?></span><br />
					<?php echo $this->lang('%d trophée|%d trophées', $total_bronze[0], $total_bronze[0]) ?>
				</li>
			</ul>
		</div>
	</div>
<?php endif ?>
<div class="table-responsive"><table class="table table-hover">
	<thead>
		<tr>
			<th></th>
			<th><span data-bs-toggle="tooltip" title="<?php echo $this->lang('Classement') ?>"><?php echo icon('fas fa-trophy') ?></span></th>
			<th><span data-bs-toggle="tooltip" title="<?php echo $this->lang('Plateforme') ?>"><?php echo icon('fas fa-tv') ?></span></th>
			<th colspan="2"><?php echo $this->lang('Événement') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		if ($awards):
			foreach ($awards as $award): ?>
			<tr>
				<td>
					<span data-bs-toggle="tooltip" title="<?php echo timetostr($this->lang('l j F Y'), $award['date']) ?>"><?php echo icon('far fa-calendar') ?></span>
				</td>
				<td>
					<?php
					if ($award['ranking'] == 1)
					{
						echo '<span data-bs-toggle="tooltip" title="'.$this->lang('1er sur %d équipe|1er sur %d équipes', $award['participants'], $award['participants']).'">'.icon('fas fa-trophy trophy-gold').'</span>';
					}
					else if ($award['ranking'] == 2)
					{
						echo '<span data-bs-toggle="tooltip" title="'.$this->lang('2e sur %d équipe|2e sur %d équipes', $award['participants'], $award['participants']).'">'.icon('fas fa-trophy trophy-silver').'</span>';
					}
					else if ($award['ranking'] == 3)
					{
						echo '<span data-bs-toggle="tooltip" title="'.$this->lang('3e sur %d équipe|3e sur %d équipes', $award['participants'], $award['participants']).'">'.icon('fas fa-trophy trophy-bronze').'</span>';
					}
					else
					{
						// Le rang en chiffre, l'infobulle le dit en toutes lettres : un suffixe ordinal ne se traduit pas
						// (« 21st », « 22nd » en anglais).
						echo '<span data-bs-toggle="tooltip" title="'.$this->lang('Rang %d sur %d équipe|Rang %d sur %d équipes', $award['participants'], $award['ranking'], $award['participants']).'">'.$award['ranking'].'</span>';
					}
					?>
				</td>
				<td><?php echo $award['platform'] ?></td>
				<td>
					<a href="<?php echo url('awards/'.$award['award_id'].'/'.url_title($award['name'])) ?>"><?php echo $award['name'] ?></a>
				</td>
				<td>
					<?php if ($award['location']): ?><div><span data-bs-toggle="tooltip" title="<?php echo $this->lang('Lieu') ?>"><?php echo icon('fas fa-map-marker-alt').' '.$award['location'] ?></span></div><?php endif ?>
				</td>
			</tr>
		<?php
			endforeach;
		else:
		?>
		<tr>
			<td colspan="4"><?php echo $this->lang('Aucun trophée…') ?></td>
		</tr>
		<?php endif ?>
	</tbody>
</table></div>
