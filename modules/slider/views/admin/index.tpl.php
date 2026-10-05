<?php
/**
 * Corps de la liste des slides — SANS carte : l'enveloppe est posée par le contrôleur via
 * `admin_card()`. Cette vue avait une carte dans son état VIDE et aucune enveloppe dès qu'il y
 * avait des slides : deux mises en page pour un même écran, selon son contenu.
 */
?>
<?php if (empty($slides)): ?>
	<?php echo $vide ?>
<?php else: ?>
	<form action="<?php echo url('admin/slider/move') ?>" method="post" id="slider-reorder-form">
		<table class="table table-hover">
			<thead>
				<tr>
					<th width="40">&nbsp;</th>
					<th width="180"><?php echo $this->lang('Image') ?></th>
					<th><?php echo $this->lang('Titre / Sous-titre') ?></th>
					<th><?php echo $this->lang('Lien') ?></th>
					<th width="80" class="text-center"><?php echo $this->lang('Actif') ?></th>
					<th width="180" class="text-center"><?php echo $this->lang('Actions') ?></th>
				</tr>
			</thead>
			<tbody class="slider-sortable">
				<?php foreach ($slides as $i => $slide): ?>
					<tr data-slide-id="<?php echo (int)$slide['id'] ?>" class="<?php echo empty($slide['active']) ? 'table-secondary text-muted' : '' ?>">
						<td class="text-center" style="cursor: move;">
							<input type="hidden" name="order[]" value="<?php echo (int)$slide['id'] ?>" />
							<?php echo icon('fas fa-grip-vertical text-muted') ?>
						</td>
						<td>
							<?php
								// `url()` et non un `'/'` en dur : sur un site servi depuis un sous-dossier,
								// `'/upload/…'` désigne la racine du DOMAINE et l'aperçu reste vide.
								// Même défaut que dans le widget, cf. widgets/slider/views/index.tpl.php.
								$src = $slide['image_url'];
								$resolved = (strpos($src, 'http') === 0 || strpos($src, '//') === 0) ? $src : (empty($src) ? '' : url(ltrim($src, '/')));
							?>
							<?php if ($resolved): ?>
								<img src="<?php echo nf_texte($resolved) ?>" alt="" style="max-width: 160px; max-height: 80px; border-radius: 4px;" />
							<?php else: ?>
								<span class="text-muted"><?php echo icon('fas fa-image').' '.$this->lang('Pas d\'image') ?></span>
							<?php endif ?>
						</td>
						<td>
							<strong><?php echo nf_texte($slide['title'] ?: $this->lang('(sans titre)')) ?></strong>
							<?php if (!empty($slide['caption'])): ?>
								<div class="small text-muted"><?php echo nl2br(nf_texte($slide['caption'])) ?></div>
							<?php endif ?>
						</td>
						<td>
							<?php if (!empty($slide['link'])): ?>
								<small><a href="<?php echo nf_texte(nf_url_sure((string) $slide['link']) ? (string) $slide['link'] : '#') ?>" target="_blank" rel="noopener"><?php echo nf_texte($slide['link'], 50) ?></a></small>
							<?php else: ?>
								<small class="text-muted">—</small>
							<?php endif ?>
						</td>
						<td class="text-center">
							<?php if (!empty($slide['active'])): ?>
								<span class="badge text-bg-success"><?php echo icon('fas fa-check').' '.$this->lang('Actif') ?></span>
							<?php else: ?>
								<span class="badge text-bg-secondary"><?php echo icon('fas fa-eye-slash').' '.$this->lang('Inactif') ?></span>
							<?php endif ?>
						</td>
						<td class="text-center">
							<a class="btn btn-sm btn-light" href="<?php echo url('admin/slider/toggle/'.(int)$slide['id']) ?>?_=<?php echo $csrf ?>" data-bs-toggle="tooltip" title="<?php echo !empty($slide['active']) ? $this->lang('Désactiver') : $this->lang('Activer') ?>">
								<?php echo icon(!empty($slide['active']) ? 'fas fa-eye-slash' : 'fas fa-eye') ?>
							</a>
							<a class="btn btn-sm btn-outline-secondary" href="<?php echo url('admin/slider/edit/'.(int)$slide['id']) ?>" data-bs-toggle="tooltip" title="<?php echo $this->lang('Modifier') ?>">
								<?php echo icon('fas fa-edit') ?>
							</a>
							<a class="btn btn-sm btn-outline-danger" href="<?php echo url('admin/slider/delete/'.(int)$slide['id']) ?>?_=<?php echo $csrf ?>" data-bs-toggle="tooltip" title="<?php echo $this->lang('Supprimer') ?>" data-confirm="<?php echo nf_texte($this->lang('Confirmer la suppression de cette slide ?')) ?>">
								<?php echo icon('far fa-trash-alt') ?>
							</a>
						</td>
					</tr>
				<?php endforeach ?>
			</tbody>
		</table>
		<div class="d-flex justify-content-between mt-3">
			<small class="text-muted"><?php echo icon('fas fa-info-circle').' '.$this->lang('Glisse les lignes pour réordonner, puis clique "Sauvegarder l\'ordre".') ?></small>
			<button type="submit" class="btn btn-secondary"><?php echo icon('fas fa-save').' '.$this->lang('Sauvegarder l\'ordre') ?></button>
		</div>
	</form>

	<script>
	(function(){
		// Drag-drop sortable simple sans dépendance lourde
		var rows = document.querySelectorAll('.slider-sortable tr');
		var dragSrc = null;
		rows.forEach(function(row){
			row.draggable = true;
			row.addEventListener('dragstart', function(e){ dragSrc = row; row.style.opacity = 0.4; });
			row.addEventListener('dragend',   function(e){ row.style.opacity = 1; });
			row.addEventListener('dragover',  function(e){ e.preventDefault(); });
			row.addEventListener('drop', function(e){
				e.preventDefault();
				if (dragSrc && dragSrc !== row){
					var rect = row.getBoundingClientRect();
					var insertBefore = (e.clientY - rect.top) < rect.height / 2;
					row.parentNode.insertBefore(dragSrc, insertBefore ? row : row.nextSibling);
				}
			});
		});
	})();
	</script>
<?php endif ?>
