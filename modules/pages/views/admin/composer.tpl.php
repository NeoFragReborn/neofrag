<?php
/**
 * Palier 1 page-builder — composer de blocs d'une page (admin). Liste ordonnable de blocs de
 * module, chacun avec ses options (dérivées des `fields` déclarés). Sauvegarde AJAX dédiée
 * (indépendante du formulaire texte de la page). Équivalent visuel des shortcodes [block:…].
 */
$blocks    = $blocks ?? [];
$instances = $instances ?? [];
?>
<div class="card mt-3" data-composer data-page-id="<?php echo (int) $page_id ?>" data-save-url="<?php echo url('admin/ajax/pages/save-instances') ?>">
	<div class="card-header"><?php echo icon('fas fa-cubes') ?> <?php echo $this->lang('Blocs de la page') ?></div>
	<div class="card-body">
		<p class="text-muted small mb-3"><?php echo $this->lang('Ajoutez des blocs de module sous le contenu, réordonnez-les par glisser-déposer et configurez leurs options. Équivalent visuel des shortcodes [block:…].') ?></p>
		<div data-composer-list></div>
		<div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
			<select class="form-select" style="max-width:340px" data-composer-select>
				<?php foreach ($blocks as $key => $b): ?>
				<option value="<?php echo htmlspecialchars($key, ENT_QUOTES) ?>"><?php echo htmlspecialchars($b['title']) ?> — <?php echo htmlspecialchars($key) ?></option>
				<?php endforeach ?>
			</select>
			<button type="button" class="btn btn-secondary" data-composer-add><?php echo icon('fas fa-plus') ?> <?php echo $this->lang('Ajouter') ?></button>
			<button type="button" class="btn btn-primary ms-auto" data-composer-save><?php echo icon('fas fa-save') ?> <?php echo $this->lang('Enregistrer les blocs') ?></button>
		</div>
		<div class="small text-muted mt-2" data-composer-status></div>
	</div>
</div>
<script type="application/json" data-composer-blocks><?php echo json_encode($blocks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<script type="application/json" data-composer-instances><?php echo json_encode($instances, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
