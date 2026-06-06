<div class="card">
	<h6 class="card-header"><i class="fas fa-edit"></i> <?php echo $id ? $this->lang('Modifier la campagne') : $this->lang('Nouvelle campagne') ?></h6>
	<div class="card-body"><?php echo $form->display() ?></div>
</div>
