<?php if ($unreads = $this->module('talks')->model()->get_unread_count($this->user->id)): ?>
<a href="<?php echo url('talks?type=private') ?>" class="btn btn-primary btn-block">Vous avez <?php echo $unreads > 0 ? $unreads.' messages non lus !' : '1 message non lu !' ?></a>
<?php else: ?>
Aucun nouveau message...
<?php endif ?>
