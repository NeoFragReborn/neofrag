<?php if(!empty($votes)): ?>
	<?php foreach ($votes as $k => $vote): ?>
		<div class="d-flex align-items-start gap-3">
			<?php echo $this->user->avatar() ?>
			<div class="flex-grow-1">
				<div class="float-end">
					<span class="badge<?php echo $vote['vote'] ? ' text-bg-success' : ' text-bg-danger' ?>" style="display: inline-block"><?php echo $vote['vote'] ? icon('far fa-thumbs-up').' '.$this->lang('Favorable') : icon('far fa-thumbs-down').' '.$this->lang('Défavorable') ?></span>
				</div>
				<b><?php echo $this->user->link($vote['user_id'], $vote['username']) ?></b><br />
				<?php echo bbcode($vote['comment']) ?>
			</div>
		</div>
		<?php end($votes) ?>
		<?php $lastElementKey = key($votes) ?>
		<?php echo ($k != $lastElementKey) ? '<hr style="margin-top: 12px; margin-bottom: 12px;"/>' : '' ?>
	<?php endforeach ?>
<?php else: ?>
	<?php echo $this->lang('Aucun avis déposé.') ?>
<?php endif ?>
