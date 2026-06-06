<?php if (!empty($user_activity)): ?>
<div class="activity-message">
	<?php foreach ($user_activity as $item): ?>
	<div class="activity-message-item">
		<p>
			<b><a href="<?php echo url($item['url']) ?>"><?php echo icon($item['icon']).' '.htmlspecialchars($item['title']) ?></a></b><br />
			<small class="text-muted"><?php echo icon('far fa-clock').' '.timetostr('j M Y', $item['date']) ?></small>
		</p>
		<?php if (!empty($item['excerpt'])): ?>
		<div class="activity-excerpt text-muted"><?php echo htmlspecialchars($item['excerpt']) ?></div>
		<?php endif ?>
	</div>
	<?php endforeach ?>
</div>
<?php else: ?>
<?php echo $this->lang('Aucune activité récente...') ?>
<?php endif ?>
