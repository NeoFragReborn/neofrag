<div class="row">
	<div class="col-12 col-lg-5 text-center">
		<h5><?php echo $this->lang('Tous nos podiums') ?></h5>
		<div class="row">
			<div class="col text-center">
				<span data-bs-toggle="tooltip" title="<?php echo $this->lang('1ère place') ?>"><?php echo icon('fas fa-trophy trophy-gold fa-2x') ?></span>
				<h4 class="m-0"><?php echo $total_gold[0] ?></h4>
			</div>
			<div class="col text-center">
				<span data-bs-toggle="tooltip" title="<?php echo $this->lang('2e place') ?>"><?php echo icon('fas fa-trophy trophy-silver fa-2x') ?></span>
				<h4 class="m-0"><?php echo $total_silver[0] ?></h4>
			</div>
			<div class="col text-center">
				<span data-bs-toggle="tooltip" title="<?php echo $this->lang('3e place') ?>"><?php echo icon('fas fa-trophy trophy-bronze fa-2x') ?></span>
				<h4 class="m-0"><?php echo $total_bronze[0] ?></h4>
			</div>
		</div>
	</div>
	<div class="col-12 col-lg-7">
		<div class="table-responsive"><table class="table table-hover m-0">
			<thead>
				<tr>
					<th><?php echo $this->lang('Équipes') ?></th>
					<th class="text-center"><?php echo icon('fas fa-trophy trophy-gold') ?></th>
					<th class="text-center"><?php echo icon('fas fa-trophy trophy-silver') ?></th>
					<th class="text-center"><?php echo icon('fas fa-trophy trophy-bronze') ?></th>
					<th class="text-center"><?php echo icon('fas fa-plus') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($teams as $team): ?>
				<tr>
					<td><a href="<?php echo url('awards/team/'.$team['team_id'].'/'.$team['name']) ?>"><?php echo $team['team_title'] ?></a></td>
					<td class="text-center"><?php echo $team['total_gold'] ?></td>
					<td class="text-center"><?php echo $team['total_silver'] ?></td>
					<td class="text-center"><?php echo $team['total_bronze'] ?></td>
					<td class="text-center"><?php echo $team['total_other'] ?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table></div>
	</div>
</div>
