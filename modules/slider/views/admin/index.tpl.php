<?php if (empty($slides)): ?>
	<div class="card">
		<div class="card-body text-center text-muted py-5">
			<?php echo icon('fas fa-images fa-3x text-muted mb-3') ?>
			<h5><?php echo $this->lang('Aucune slide pour le moment.') ?></h5>
			<p><?php echo $this->lang('Le widget slider affichera un placeholder par défaut tant qu\'aucune slide n\'est ajoutée.') ?></p>
			<a class="btn btn-primary mt-2" href="<?php echo url('admin/slider/add') ?>"><?php echo icon('fas fa-plus').' '.$this->lang('Créer la première slide') ?></a>
		</div>
	</div>
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
								$src = $slide['image_url'];
								$resolved = (strpos($src, 'http') === 0 || strpos($src, '//') === 0) ? $src : (empty($src) ? '' : '/'.ltrim($src, '/'));
							?>
							<?php if ($resolved): ?>
								<img src="<?php echo htmlspecialchars($resolved) ?>" alt="" style="max-width: 160px; max-height: 80px; border-radius: 4px;" />
							<?php else: ?>
								<span class="text-muted"><?php echo icon('fas fa-image').' '.$this->lang('Pas d\'image') ?></span>
							<?php endif ?>
						</td>
						<td>
							<strong><?php echo htmlspecialchars($slide['title'] ?: $this->lang('(sans titre)')) ?></strong>
							<?php if (!empty($slide['caption'])): ?>
								<div class="small text-muted"><?php echo nl2br(htmlspecialchars($slide['caption'])) ?></div>
							<?php endif ?>
						</td>
						<td>
							<?php if (!empty($slide['link'])): ?>
								<small><a href="<?php echo htmlspecialchars($slide['link']) ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(mb_substr($slide['link'], 0, 50)) ?></a></small>
							<?php else: ?>
								<small class="text-muted">—</small>
							<?php endif ?>
						</td>
						<td class="text-center">
							<?php if (!empty($slide['active'])): ?>
								<span class="badge badge-success"><?php echo icon('fas fa-check').' '.$this->lang('Actif') ?></span>
							<?php else: ?>
								<span class="badge badge-secondary"><?php echo icon('fas fa-eye-slash').' '.$this->lang('Inactif') ?></span>
							<?php endif ?>
						</td>
						<td class="text-center">
							<a class="btn btn-sm btn-light" href="<?php echo url('admin/slider/toggle/'.(int)$slide['id']) ?>" data-toggle="tooltip" title="<?php echo !empty($slide['active']) ? $this->lang('Désactiver') : $this->lang('Activer') ?>">
								<?php echo icon(!empty($slide['active']) ? 'fas fa-eye-slash' : 'fas fa-eye') ?>
							</a>
							<a class="btn btn-sm btn-primary" href="<?php echo url('admin/slider/edit/'.(int)$slide['id']) ?>" data-toggle="tooltip" title="<?php echo $this->lang('Modifier') ?>">
								<?php echo icon('fas fa-edit') ?>
							</a>
							<a class="btn btn-sm btn-danger" href="<?php echo url('admin/slider/delete/'.(int)$slide['id']) ?>" data-toggle="tooltip" title="<?php echo $this->lang('Supprimer') ?>" data-confirm="<?php echo htmlspecialchars($this->lang('Confirmer la suppression de cette slide ?'), ENT_QUOTES) ?>">
								<?php echo icon('fas fa-times') ?>
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
