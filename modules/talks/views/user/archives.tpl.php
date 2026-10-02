<ul class="nav nav-pills mb-3">
	<li class="nav-item">
		<a class="nav-link" href="<?php echo \url('talks') ?>">
			<?php echo \icon('far fa-comments').' '.$this->lang('Toutes') ?>
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" href="<?php echo \url('talks?type=private') ?>">
			<?php echo \icon('far fa-envelope').' '.$this->lang('Messagerie privée') ?>
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" href="<?php echo \url('talks?type=public') ?>">
			<?php echo \icon('fas fa-hashtag').' '.$this->lang('Salons publics') ?>
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link active" href="<?php echo \url('talks/archives') ?>">
			<?php echo \icon('fas fa-archive').' '.$this->lang('Archives') ?>
			<span class="badge text-bg-light ms-1"><?php echo (int)count($conversations) ?></span>
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" href="<?php echo \url('talks/trash') ?>">
			<?php echo \icon('far fa-trash-alt').' '.$this->lang('Corbeille') ?>
		</a>
	</li>
</ul>

<div class="card">
	<div class="card-header">
		<h5 class="m-0"><?php echo \icon('fas fa-archive').' '.$this->lang('Conversations archivées') ?></h5>
	</div>
	<?php if (empty($conversations)): ?>
		<div class="card-body text-center text-muted py-4">
			<?php echo \icon('fas fa-archive fa-3x mb-3') ?>
			<p><?php echo $this->lang('Aucune conversation archivée. Les conversations archivées apparaissent ici et restent accessibles tant que tu ne les désarchives pas.') ?></p>
		</div>
	<?php else: ?>
		<ul class="list-group list-group-flush">
			<?php foreach ($conversations as $c): ?>
				<?php
					$icon = $c['type'] === 'public' ? 'fas fa-hashtag' : ($c['type'] === 'group' ? 'fas fa-users' : 'fas fa-user');
					$href = \url('talks/'.(int)$c['talk_id'].'/'.\url_title($c['name']));
				?>
				<li class="list-group-item d-flex justify-content-between align-items-center">
					<div class="flex-grow-1">
						<a href="<?php echo $href ?>" class="fw-bold">
							<?php echo \icon($icon).' '.htmlspecialchars($c['name']) ?>
						</a>
						<?php if (!empty($c['description'])): ?>
							<div class="small text-muted"><?php echo htmlspecialchars(mb_substr($c['description'], 0, 100)) ?></div>
						<?php endif ?>
						<div class="small text-muted">
							<?php echo \icon('fas fa-archive').' '.$this->lang('Archivée le %s', timetostr('d/m/Y H:i', $c['archived_at'])) ?>
							· <?php echo (int)$c['messages_count'].' '.$this->lang('messages') ?>
						</div>
					</div>
					<div>
						<a class="btn btn-sm btn-outline-primary" href="<?php echo \url('talks/'.(int)$c['talk_id'].'/'.\url_title($c['name']).'/unarchive') ?>">
							<?php echo \icon('fas fa-undo').' '.$this->lang('Désarchiver') ?>
						</a>
					</div>
				</li>
			<?php endforeach ?>
		</ul>
	<?php endif ?>
</div>
