<ul class="nav nav-pills mb-3">
	<li class="nav-item">
		<a class="nav-link<?php echo ($filter ?? 'all') === 'all' ? ' active' : '' ?>" href="<?php echo \url('talks') ?>">
			<?php echo \icon('far fa-comments').' '.$this->lang('Toutes') ?>
			<span class="badge badge-light ml-1"><?php echo (int)($counts['all'] ?? 0) ?></span>
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link<?php echo ($filter ?? '') === 'private' ? ' active' : '' ?>" href="<?php echo \url('talks?type=private') ?>">
			<?php echo \icon('far fa-envelope').' '.$this->lang('Messagerie privée') ?>
			<span class="badge badge-light ml-1"><?php echo (int)($counts['private'] ?? 0) ?></span>
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link<?php echo ($filter ?? '') === 'public' ? ' active' : '' ?>" href="<?php echo \url('talks?type=public') ?>">
			<?php echo \icon('fas fa-hashtag').' '.$this->lang('Salons publics') ?>
			<span class="badge badge-light ml-1"><?php echo (int)($counts['public'] ?? 0) ?></span>
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" href="<?php echo \url('talks/archives') ?>">
			<?php echo \icon('fas fa-archive').' '.$this->lang('Archives') ?>
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" href="<?php echo \url('talks/trash') ?>">
			<?php echo \icon('far fa-trash-alt').' '.$this->lang('Corbeille') ?>
		</a>
	</li>
</ul>

<div class="row">
	<div class="col-md-<?php echo empty($publics) ? '12' : '7' ?>">
		<div class="card">
			<div class="card-header">
				<h5 class="m-0">
					<?php
					$header_icon = ($filter ?? 'all') === 'private' ? 'far fa-envelope' : (($filter ?? 'all') === 'public' ? 'fas fa-hashtag' : 'far fa-comments');
					$header_text = ($filter ?? 'all') === 'private' ? $this->lang('Mes messages privés') : (($filter ?? 'all') === 'public' ? $this->lang('Salons publics rejoints') : $this->lang('Mes conversations'));
					echo \icon($header_icon).' '.$header_text;
					?>
				</h5>
			</div>
			<?php if (empty($conversations)): ?>
				<div class="card-body text-center text-muted py-4">
					<?php echo \icon('far fa-comment-dots fa-3x mb-3') ?>
					<p><?php echo $this->lang('Tu n\'as pas encore de conversation. Crée-en une ou rejoins un salon public ci-contre.') ?></p>
				</div>
			<?php else: ?>
				<ul class="list-group list-group-flush">
					<?php foreach ($conversations as $c): ?>
						<?php
							$icon = $c['type'] === 'public' ? 'fas fa-hashtag' : ($c['type'] === 'group' ? 'fas fa-users' : 'fas fa-user');
							$href = url('talks/'.(int)$c['talk_id'].'/'.\url_title($c['name']));
						?>
						<li class="list-group-item">
							<div class="d-flex justify-content-between align-items-start">
								<div class="flex-grow-1">
									<a href="<?php echo $href ?>" class="font-weight-bold">
										<?php echo \icon($icon).' '.htmlspecialchars($c['name']) ?>
									</a>
									<?php if ((int)$c['unread_count'] > 0): ?>
										<span class="badge badge-danger ml-1"><?php echo (int)$c['unread_count'] ?></span>
									<?php endif ?>
									<?php if (!empty($c['description'])): ?>
										<div class="small text-muted"><?php echo htmlspecialchars(mb_substr($c['description'], 0, 100)) ?></div>
									<?php endif ?>
									<div class="small text-muted">
										<?php echo $this->lang('%d participant|%d participants', (int)$c['participants_count'], (int)$c['participants_count']) ?>
										<?php if ($c['last_message_date']): ?>
											· <?php echo \icon('far fa-clock').' '.\time_span(strtotime($c['last_message_date'])) ?>
										<?php endif ?>
									</div>
								</div>
							</div>
						</li>
					<?php endforeach ?>
				</ul>
			<?php endif ?>
		</div>
	</div>

	<?php if (!empty($publics)): ?>
	<div class="col-md-5">
		<div class="card">
			<div class="card-header">
				<h5 class="m-0"><?php echo \icon('fas fa-hashtag').' '.$this->lang('Salons publics à découvrir') ?></h5>
			</div>
			<?php if (empty($publics)): ?>
				<div class="card-body text-center text-muted py-3">
					<small><?php echo $this->lang('Aucun salon public pour le moment.') ?></small>
				</div>
			<?php else: ?>
				<ul class="list-group list-group-flush">
					<?php foreach ($publics as $p): ?>
						<?php $href = url('talks/'.(int)$p['talk_id'].'/'.\url_title($p['name'])); ?>
						<li class="list-group-item d-flex justify-content-between align-items-center">
							<div>
								<a href="<?php echo $href ?>"><?php echo \icon('fas fa-hashtag').' '.htmlspecialchars($p['name']) ?></a>
								<?php if (!empty($p['description'])): ?>
									<div class="small text-muted"><?php echo htmlspecialchars(mb_substr($p['description'], 0, 80)) ?></div>
								<?php endif ?>
								<small class="text-muted">
									<?php echo (int)$p['participants_count'].' '.$this->lang('membres') ?>
									· <?php echo (int)$p['messages_count'].' '.$this->lang('messages') ?>
								</small>
							</div>
							<?php if (!empty($p['is_joined'])): ?>
								<span class="badge badge-success"><?php echo $this->lang('Rejoint') ?></span>
							<?php else: ?>
								<a class="btn btn-sm btn-outline-primary" href="<?php echo $href ?>"><?php echo $this->lang('Rejoindre') ?></a>
							<?php endif ?>
						</li>
					<?php endforeach ?>
				</ul>
			<?php endif ?>
		</div>
	</div>
	<?php endif ?>
</div>
