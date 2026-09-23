<ul class="nav nav-pills" id="pills-tab" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="pills-options-tab" data-bs-toggle="pill" href="#pills-options" role="tab" aria-controls="pills-options" aria-selected="true"><?php echo icon('fas fa-cogs').' '.$this->lang('Options') ?></a></li>
</ul>
<div class="tab-content border-light" id="pills-tabContent">
	<div class="tab-pane fade show active" id="pills-options" role="tabpanel" aria-labelledby="pills-options-tab">
		<?php
		/**
		 * La condition du premier bouton testait `float-start` au lieu de `float-end` : quand
		 * l'alignement valait justement `float-start`, AUCUN des deux boutons n'était coché.
		 *
		 * Le balisage est celui de Bootstrap 5 (`form-check`) : `radio-inline` est une classe de
		 * Bootstrap 3, définie nulle part dans le projet, donc sans aucun effet.
		 */
		$a_gauche = !isset($align) || $align != 'float-end';
		?>
		<div class="nf-field row">
			<label class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Alignement') ?></label>
			<div class="col-12 col-lg-9">
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" id="widget-search-align-start" name="settings[align]" value="float-start"<?php echo $a_gauche ? ' checked="checked"' : '' ?> />
					<label class="form-check-label" for="widget-search-align-start"><?php echo $this->lang('à gauche') ?></label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" id="widget-search-align-end" name="settings[align]" value="float-end"<?php echo $a_gauche ? '' : ' checked="checked"' ?> />
					<label class="form-check-label" for="widget-search-align-end"><?php echo $this->lang('à droite') ?></label>
				</div>
			</div>
		</div>
	</div>
</div>
