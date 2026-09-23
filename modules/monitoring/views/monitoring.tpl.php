<div class="panel-monitoring nf-sante-inconnue">
	<div class="status-legend">
		<h3><?php echo $this->lang('Santé du site') ?></h3>
		<span id="monitoring-text">&nbsp;</span>
	</div>
	<div class="text-center">
		<span class="monitoring-icon-status"><?php echo icon('fas fa-heartbeat') ?></span>
	</div>
</div>
<div class="card-body">
	<div class="row">
		<div class="col monitoring-legend pt-2 pb-3">
			<h3 id="monitoring-info"><?php echo icon('fas fa-spinner fa-spin') ?></h3>
			<?php echo icon('fas fa-exclamation-circle text-info') ?> <?php echo $this->lang('Conseils') ?>
		</div>
		<div class="col monitoring-legend pt-2 pb-3">
			<h3 id="monitoring-warning"><?php echo icon('fas fa-spinner fa-spin') ?></h3>
			<?php echo icon('fas fa-bolt text-warning') ?> <?php echo $this->lang('Anomalies') ?>
		</div>
		<div class="col monitoring-legend pt-2 pb-3">
			<h3 id="monitoring-danger"><?php echo icon('fas fa-spinner fa-spin') ?></h3>
			<?php echo icon('fas fa-bug text-danger') ?> <?php echo $this->lang('Erreurs') ?>
		</div>
	</div>
</div>
