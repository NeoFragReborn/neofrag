<div class="card-body text-center">
<?php if ($total_pending): ?>
	<h1 class="m-0"><?php echo icon('far fa-clock') ?></h1>
	<a href="<?php echo url('admin/recruits/pending') ?>"><b><?php echo $this->lang('%d pending application|%d pending applications', $total_pending, $total_pending) ?></b></a>
<?php else: ?>
	<?php echo $this->lang('Aucune candidature en attente...') ?>
<?php endif ?>
</div>
<ul class="list-group">
	<li class="list-group-item"><?php echo icon('fas fa-briefcase').' '.$this->lang('%d candidature déposée|%d candidatures déposées', $total_candidacies, $total_candidacies) ?></li>
	<li class="list-group-item"><?php echo icon('fas fa-check text-success').' '.$this->lang('%d candidature acceptée|%d candidatures acceptées', $total_accepted, $total_accepted) ?></li>
	<li class="list-group-item"><?php echo icon('fas fa-ban text-danger').' '.$this->lang('%d candidature refusée|%d candidatures refusées', $total_declined, $total_declined) ?></li>
</ul>
