<div class="accordion">
	<ul class="list-group">
		<?php foreach ($this->groups() as $group_id => $group): if (empty($group['users']) || !($list_users = array_intersect_key($users, array_flip($group['users'])))) continue ?>
		<li class="list-group-item">
			<input type="checkbox" name="select-all" class="me-2" /><a href="#" data-bs-toggle="collapse" data-bs-target="#<?php echo $id = url_title($group['url']) ?>"> <?php echo $this->groups->display($group_id, TRUE, FALSE) ?></a>
			<div id="<?php echo $id ?>" class="collapse ms-4 mt-3">
				<?php foreach ($list_users as $user_id => $username): ?>
				<div class="form-check">
					<input class="form-check-input" type="checkbox" id="participant-<?php echo $group_id.'-'.$user_id ?>" name="<?php echo $form_id ?>[users][]" value="<?php echo $user_id ?>" />
					<label class="form-check-label" for="participant-<?php echo $group_id.'-'.$user_id ?>"><?php echo $username ?></label>
				</div>
				<?php endforeach ?>
			</div>
		</li>
		<?php endforeach ?>
	</ul>
</div>
