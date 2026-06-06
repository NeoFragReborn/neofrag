<?php if (empty($comments)): ?>
	<div class="text-center text-muted"><?php echo $this->lang('Aucun commentaire pour le moment') ?></div>
<?php else: ?>
<ul class="list-unstyled mb-0 latest-comments">
	<?php foreach ($comments as $i => $c): ?>
	<li class="<?php echo $i ? 'mt-2 pt-2 border-top' : '' ?>">
		<div class="d-flex justify-content-between">
			<span><?php echo $c['user_id'] ? $this->user->link($c['user_id'], $c['username']) : htmlspecialchars((string)$c['username']) ?></span>
			<small class="text-muted"><?php echo icon('far fa-clock').' '.timetostr('j M H:i', $c['date']) ?></small>
		</div>
		<div class="small">
			<?php if ($c['url']): ?>
				<a href="<?php echo url($c['url']) ?>"><?php echo htmlspecialchars((string)$c['snippet']) ?></a>
			<?php else: ?>
				<?php echo htmlspecialchars((string)$c['snippet']) ?>
			<?php endif ?>
		</div>
	</li>
	<?php endforeach ?>
</ul>
<?php endif ?>
