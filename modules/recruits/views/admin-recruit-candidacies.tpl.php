<div>
	<ul class="nav nav-tabs" role="tablist" style="margin-bottom: 20px;">
		<li class="active"><a href="#pending" aria-controls="pending" role="tab" data-bs-toggle="tab"><?php echo icon('far fa-clock') ?><?php echo $this->lang('En attente') ?></a></li>
		<li><a href="#accepted" aria-controls="accepted" role="tab" data-bs-toggle="tab"><?php echo icon('fas fa-check') ?><?php echo $this->lang('Acceptées') ?></a></li>
		<li><a href="#declined" aria-controls="declined" role="tab" data-bs-toggle="tab"><?php echo icon('fas fa-ban') ?><?php echo $this->lang('Refusées') ?></a></li>
	</ul>
	<div class="tab-content">
		<div role="tabpanel" class="tab-pane active" id="pending">
			<?php echo $table_pending ?>
		</div>
		<div role="tabpanel" class="tab-pane" id="accepted">
			<?php echo $table_accepted ?>
		</div>
		<div role="tabpanel" class="tab-pane" id="declined">
			<?php echo $table_declined ?>
		</div>
	</div>
</div>
