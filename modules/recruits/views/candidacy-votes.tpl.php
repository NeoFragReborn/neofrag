<?php if(!empty($votes)): ?>
<div class="float-end text-end">
	<ul class="list-inline m-0">
		<li class="text-success"><?php echo $total_up ?> <?php echo icon('far fa-thumbs-up') ?></li>
		<li class="text-danger"><?php echo $total_down ?> <?php echo icon('far fa-thumbs-down') ?></li>
	</ul>
</div>
<p><b><?php echo $this->lang('Tendance des votes') ?></b></p>
<div class="progress">
	<div class="progress-bar bg-success" style="width: <?php echo ceil(($total_up/$total_votes)*100) ?>%"><?php echo ceil(($total_up/$total_votes)*100) ?>%</div>
	<div class="progress-bar bg-danger" style="width: <?php echo ceil(($total_down/$total_votes)*100) - 1 ?>%"><?php echo ceil(($total_down/$total_votes)*100) - 1 ?>%</div>
</div>
<div class="row">
	<?php foreach ($votes as $vote): ?>
	<div class="p-3 mb-3 border rounded bg-body-tertiary">
		<div class="d-flex align-items-start gap-3">
			<?php echo $this->user->avatar() ?>
			<div class="flex-grow-1">
				<div class="float-end">
					<span class="badge<?php echo $vote['vote'] ? ' text-bg-success' : ' text-bg-danger' ?>" style="display: inline-block"><?php echo $vote['vote'] ? icon('far fa-thumbs-up').' '.$this->lang('Favorable') : icon('far fa-thumbs-down').' '.$this->lang('Défavorable') ?></span>
				</div>
				<?php echo $this->user->link($vote['user_id'], $vote['username']) ?>
				<?php echo bbcode($vote['comment']) ?>
			</div>
		</div>
	</div>
	<?php endforeach ?>
</div>
<?php else: ?>
<?php echo $this->lang('Il n\'y a pas encore de vote...') ?>
<?php endif ?>
<?php if ($status == 1): ?>
<hr />
<h4><?php echo $this->lang('Mon avis sur cette candidature') ?></h4>
<?php echo $vote_form ?>
<?php endif ?>
