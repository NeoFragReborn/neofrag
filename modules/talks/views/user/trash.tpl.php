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
		<a class="nav-link" href="<?php echo \url('talks/archives') ?>">
			<?php echo \icon('fas fa-archive').' '.$this->lang('Archives') ?>
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link active" href="<?php echo \url('talks/trash') ?>">
			<?php echo \icon('far fa-trash-alt').' '.$this->lang('Corbeille') ?>
			<span class="badge text-bg-light ms-1"><?php echo (int)count($conversations) ?></span>
		</a>
	</li>
</ul>

<div class="alert alert-info">
	<?php echo \icon('fas fa-info-circle').' '.$this->lang('Les conversations supprimées sont conservées <b>%d jours</b> avant suppression définitive de ton historique. Tu peux les restaurer à tout moment depuis cette page. La conversation reste intacte pour les autres participants ; ce n\'est qu\'une suppression de ton côté.', (int)$retention_days) ?>
</div>

<div class="card">
	<div class="card-header">
		<h5 class="m-0"><?php echo \icon('far fa-trash-alt').' '.$this->lang('Conversations dans la corbeille') ?></h5>
	</div>
	<?php if (empty($conversations)): ?>
		<div class="card-body text-center text-muted py-4">
			<?php echo \icon('far fa-trash-alt fa-3x mb-3') ?>
			<p><?php echo $this->lang('Ta corbeille est vide.') ?></p>
		</div>
	<?php else: ?>
		<ul class="list-group list-group-flush">
			<?php foreach ($conversations as $c): ?>
				<?php
					$icon = $c['type'] === 'public' ? 'fas fa-hashtag' : ($c['type'] === 'group' ? 'fas fa-users' : 'fas fa-user');
					$days_left = max(0, $retention_days - (int)$c['days_since_delete']);
				?>
				<li class="list-group-item d-flex justify-content-between align-items-center">
					<div class="flex-grow-1">
						<span class="fw-bold text-muted">
							<?php echo \icon($icon).' '.nf_texte($c['name']) ?>
						</span>
						<?php if (!empty($c['description'])): ?>
							<div class="small text-muted"><?php echo nf_texte($c['description'], 100) ?></div>
						<?php endif ?>
						<div class="small text-muted">
							<?php echo \icon('far fa-trash-alt').' '.$this->lang('Supprimée le %s', nf_date_heure($c['deleted_at'])) ?>
							· <?php echo (int)$c['messages_count'].' '.$this->lang('messages') ?>
							· <span class="text-danger"><?php echo $this->lang('Suppression définitive dans %d jour|Suppression définitive dans %d jours', $days_left, $days_left) ?></span>
						</div>
					</div>
					<div>
						<a class="btn btn-sm btn-outline-success" href="<?php echo \url('talks/'.(int)$c['talk_id'].'/'.\url_title($c['name']).'/restore') ?>">
							<?php echo \icon('fas fa-undo').' '.$this->lang('Restaurer') ?>
						</a>
					</div>
				</li>
			<?php endforeach ?>
		</ul>
	<?php endif ?>
</div>
