<?php if ($unreads = $this->module('talks')->model()->get_unread_count($this->user->id)): ?>
<a href="<?php echo url('talks?type=private') ?>" class="btn btn-primary d-block w-100"><?php echo $this->lang('Vous avez %d message non lu !|Vous avez %d messages non lus !', $unreads, $unreads) ?></a>
<?php else: ?>
<?php echo $this->lang('Aucun nouveau message...') ?>
<?php endif ?>
