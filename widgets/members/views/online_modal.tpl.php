<div class="modal fade" id="modal-online-<?php echo $name ?>" tabindex="-1" role="dialog" aria-labelledby="modal-title-online-<?php echo $name ?>" aria-hidden="true">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modal-title-online-<?php echo $name ?>"><?php echo $title ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo $this->lang('Fermer') ?>"></button>
			</div>
			<div class="modal-body">
				<?php foreach ($users as $user): ?>
				<div class="d-flex align-items-start">
					<div class="me-3 flex-shrink-0"><?php echo $this->module('user')->model2('user', $user['user_id'])->avatar() ?></div>
					<div class="flex-grow-1">
						<?php echo $this->user->link($user['user_id'], $user['username']) ?><br />
						<small><?php echo icon('far fa-clock').' '.time_span($user['last_activity']) ?></small>
					</div>
				</div>
				<?php endforeach ?>
			</div>
		</div>
	</div>
</div>
