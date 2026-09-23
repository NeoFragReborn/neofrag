<div id="modal-backup" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Sauvegarde</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo $this->lang('Fermer') ?>"></button>
			</div>
			<div class="modal-body">
				<div class="steps-body text-center">
					<div class="row">
						<div class="col-12 col-lg-5 offset-md-1">
							<div class="progress">
								<div class="progress-bar" role="progressbar"></div>
							</div>
						</div>
						<div class="col-12 col-lg-5">
							<div class="progress">
								<div class="progress-bar" role="progressbar"></div>
							</div>
						</div>
					</div>
					<div class="row steps-legends">
						<div class="col-12 col-lg-2">
							<div class="step">
								<?php echo icon('far fa-save') ?>
							</div>
						</div>
						<div class="col-12 col-lg-4 offset-lg-2">
							<div class="step">
								<?php echo icon('fas fa-database') ?>
							</div>
							<?php echo $this->lang('Données') ?>
						</div>
						<div class="col-12 col-lg-2 offset-lg-2">
							<div class="step">
								<?php echo icon('far fa-copy') ?>
							</div>
							<?php echo $this->lang('Fichiers') ?>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $this->lang('Annuler') ?></button>
				<a class="btn btn-primary text-white"><?php echo $this->lang('Lancer la sauvegarde') ?></a>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-12 text-center">
		<?php /* Les deux textes vivent DANS la jauge : la feuille les place en absolu (top 60 et 88),
		         ce qui suppose un parent positionne. Ils etaient a cote, donc rattaches au premier
		         ancetre positionne — la carte — et s'affichaient tout en haut, au-dessus du titre.
		         Signale le 2026-09-22. */ ?>
		<div class="knob-storage">
			<input class="knob" type="text" value="0" data-thickness="0.2" data-angleArc="180" data-angleOffset="-90" data-min="0" data-max="100" data-width="190" data-height="180" data-displayInput="false" data-readonly="true" autocomplete="off" />
			<h2 id="storage-used" class="monitoring-storage-title"><?php echo icon('fas fa-spinner fa-spin') ?></h2>
			<span id="storage-pourcent" class="span-legend">&nbsp;</span>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-12 col-lg-6 text-center">
		<h5 id="storage-free" class="monitoring-storage-title"><?php echo icon('fas fa-spinner fa-spin') ?></h5>
		Libre
	</div>
	<div class="col-12 col-lg-6 text-center">
		<h5 id="storage-total" class="monitoring-storage-title"><?php echo icon('fas fa-spinner fa-spin') ?></h5>
		Total
	</div>
</div>
