<?php
$menus = $this->db->select('name', 'title')->from('nf_menus')->order_by('title ASC')->get();
$current_menu = isset($menu) ? $menu : '';
?>
<?php if ($menus): ?>
<div class="card px-2 py-3" style="margin-bottom:10px;">
	<div class="form-group" style="margin:0;">
		<label class="col-sm-3 control-label"><?php echo icon('fas fa-bars').' '.$this->lang('Menu géré') ?></label>
		<div class="col-sm-7">
			<select class="form-control" name="settings[menu]">
				<option value=""><?php echo $this->lang('— Liens manuels (ci-dessous) —') ?></option>
				<?php foreach ($menus as $mm): ?>
				<option value="<?php echo $mm['name'] ?>"<?php echo $current_menu === $mm['name'] ? ' selected' : '' ?>><?php echo htmlspecialchars($mm['title']) ?></option>
				<?php endforeach ?>
			</select>
			<small class="text-muted"><?php echo $this->lang('Affiche un menu créé dans « Menus » (remplace les liens manuels ci-dessous).') ?></small>
		</div>
	</div>
</div>
<?php endif ?>
<a id="link-delete" class="btn btn-outline-danger float-end" href="#" data-bs-toggle="popover" title="<?php echo $this->lang('Supprimer un lien') ?>" data-content="<?php echo $this->lang('Déplacez un lien ici pour le supprimer') ?>" data-placement="top"><?php echo icon('far fa-trash-alt').$this->lang('Supprimer') ?></a>
<ul class="nav nav-pills" id="pills-tab" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="pills-links-tab" data-bs-toggle="pill" href="#pills-links" role="tab" aria-controls="pills-links" aria-selected="true"><?php echo icon('fas fa-cogs').' Liens' ?></a></li>
	<li class="nav-item"><a class="nav-link" id="pills-add-tab" data-bs-toggle="pill" href="#pills-add" role="tab" aria-controls="pills-add" aria-selected="false"><?php echo icon('fas fa-plus').' Ajouter' ?></a></li>
</ul>
<div class="tab-content border-light" id="pills-tabContent">
	<div class="tab-pane fade show active" id="pills-links" role="tabcard" aria-labelledby="pills-links-tab">
		<ul class="list-group mb-3">
		<?php foreach (isset($links) ? $links : [] as $link): ?>
			<li class="list-group-item">
				<input type="hidden" name="settings[title][]" id="edit-title" value="<?php echo $link['title'] ?>" />
				<input type="hidden" name="settings[url][]" id="edit-url" value="<?php echo $link['url'] ?>" />
				<input type="hidden" name="settings[target][]" id="edit-target" value="<?php echo !empty($link['target']) ? $link['target'] : '_parent' ?>" />
				<ul class="list-inline m-0">
					<li class="list-inline-item"><a href="#" class="move-link" data-bs-toggle="tooltip" title="<?php echo $this->lang('Ordonner') ?>"><?php echo icon('fas fa-arrows-alt-v') ?></a></li>
					<li class="list-inline-item"><span data-bs-toggle="tooltip" title="<?php echo $link['url'] ?>"><?php echo icon('fas fa-link') ?></span></li>
					<li class="list-inline-item"><?php echo $link['title'] ?></li>
				</ul>
			</li>
		<?php endforeach ?>
		</ul>
	</div>
	<div class="tab-pane fade" id="pills-add" role="tabcard" aria-labelledby="pills-add-tab">
		<div id="add-link">
			<div class="card px-2 py-3">
				<div class="card-heading">
					<a class="type-collapse" role="button" data-bs-toggle="collapse" data-bs-parent="#add-link" href="#type-module" aria-controls="type-module">
						<?php echo icon('fas fa-edit') ?> <b>Lien vers un module</b>
					</a>
				</div>
				<div id="type-module" class="card-collapse collapse" role="tabcard">
					<?php
					$modules = [];

					foreach (NeoFrag()->model2('addon')->get('module') as $module)
					{
						if (@$module->controller('index') && !in_array($module->name, ['live_editor', 'pages']))
						{
							$modules[$module->info()->name] = $module->info()->title;
						}
					}

					array_natsort($modules);

					$modules = array_merge([
						'index' => NeoFrag()->lang('Accueil')
					], $modules);
					?>
					<div class="list-group mt-3">
						<?php foreach ($modules as $name => $title): ?>
							<a href="#" class="list-group-item link-item" data-link-title="<?php echo $title ?>" data-link-url="<?php echo $name ?>"><?php echo $title ?></a>
						<?php endforeach ?>
					</div>
					<div class="card-body" id="add-link">
						<div class="form-group">
							<label for="settings-title" class="col-sm-3 control-label"><?php echo $this->lang('Titre') ?></label>
							<div class="col-sm-5">
								<input type="text" class="form-control" id="settings-title" value="" placeholder="<?php echo $this->lang('Titre') ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="settings-url" class="col-sm-3 control-label"><?php echo $this->lang('Chemin') ?></label>
							<div class="col-sm-5">
								<input type="text" class="form-control" id="settings-url" value="" placeholder="<?php echo $this->lang('Chemin') ?>" disabled="disabled" />
							</div>
						</div>
						<div class="form-group">
							<label for="settings-target" class="col-sm-3 control-label"><?php echo $this->lang('Cible') ?></label>
							<div class="col-sm-5">
								<select class="form-control" id="settings-target">
									<option value="_parent"><?php echo $this->lang('Même fenêtre') ?></option>
									<option value="_blank"><?php echo $this->lang('Nouvelle fenêtre') ?></option>
								</select>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-5">
								<button class="btn btn-primary"><?php echo $this->lang('Ajouter') ?></button>
								<a class="btn btn-secondary cancel-link"><?php echo icon('fas fa-times').' '.$this->lang('Annuler') ?></a>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
			$pages = $this->db	->select('p.page_id', 'p.name', 'p.published', 'pl.title', 'pl.subtitle')
							->from('nf_pages p')
							->join('nf_pages_lang pl', 'p.page_id = pl.page_id')
							->where('p.published', TRUE)
							->where('pl.lang', $this->config->lang->info()->name)
							->order_by('pl.title ASC')
							->get();

			if ($pages): ?>
			<div class="card px-2 py-3" style="margin-top: 5px !important;">
				<div class="card-heading">
					<a class="type-collapse" role="button" data-bs-toggle="collapse" data-bs-parent="#add-link" href="#type-page" aria-controls="type-page">
						<?php echo icon('far fa-file-alt') ?> <b>Lien vers une page</b>
					</a>
				</div>
				<div id="type-page" class="card-collapse collapse" role="tabcard">
					<div class="list-group mt-3">
						<?php foreach ($pages as $page): ?>
							<a href="#" class="list-group-item link-item" data-link-title="<?php echo $page['title'] ?>" data-link-url="<?php echo $page['name'] ?>"><?php echo $page['title'] ?></a>
						<?php endforeach ?>
					</div>
					<div class="card-body" id="add-link">
						<div class="form-group">
							<label for="settings-title" class="col-sm-3 control-label"><?php echo $this->lang('Titre') ?></label>
							<div class="col-sm-5">
								<input type="text" class="form-control" id="settings-title" value="" placeholder="<?php echo $this->lang('Titre') ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="settings-url" class="col-sm-3 control-label"><?php echo $this->lang('Chemin') ?></label>
							<div class="col-sm-5">
								<input type="text" class="form-control" id="settings-url" value="" placeholder="<?php echo $this->lang('Chemin') ?>" disabled="disabled" />
							</div>
						</div>
						<div class="form-group">
							<label for="settings-target" class="col-sm-3 control-label"><?php echo $this->lang('Cible') ?></label>
							<div class="col-sm-5">
								<select class="form-control" id="settings-target">
									<option value="_parent"><?php echo $this->lang('Même fenêtre') ?></option>
									<option value="_blank"><?php echo $this->lang('Nouvelle fenêtre') ?></option>
								</select>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-5">
								<button class="btn btn-primary"><?php echo $this->lang('Ajouter') ?></button>
								<a class="btn btn-secondary cancel-link"><?php echo icon('fas fa-times').' '.$this->lang('Annuler') ?></a>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php endif ?>
			<div class="card px-2 py-3" style="margin-top: 5px !important;">
				<div class="card-heading">
					<a class="type-collapse link-item" role="button" data-link-title="" data-link-url="" data-bs-toggle="collapse" data-bs-parent="#add-link" href="#type-custom" aria-controls="type-custom">
						<?php echo icon('fas fa-link') ?> <b><?php echo $this->lang('Lien personnalisé') ?></b>
					</a>
				</div>
				<div id="type-custom" class="card-collapse collapse" role="tabcard">
					<div class="card-body">
						<div class="form-group">
							<label for="settings-title" class="col-sm-3 control-label"><?php echo $this->lang('Title') ?></label>
							<div class="col-sm-5">
								<input type="text" class="form-control" id="settings-title" value="" placeholder="<?php echo $this->lang('Title') ?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="settings-url" class="col-sm-3 control-label"><?php echo $this->lang('Chemin') ?></label>
							<div class="col-sm-5">
								<input type="text" class="form-control" id="settings-url" value="" placeholder="http://..." />
							</div>
						</div>
						<div class="form-group">
							<label for="settings-target" class="col-sm-3 control-label"><?php echo $this->lang('Cible') ?></label>
							<div class="col-sm-5">
								<select class="form-control" id="settings-target">
									<option value="_parent"><?php echo $this->lang('Même fenêtre') ?></option>
									<option value="_blank"><?php echo $this->lang('Nouvelle fenêtre') ?></option>
								</select>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-5">
								<button class="btn btn-primary"><?php echo $this->lang('Ajouter') ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php NeoFrag()->js('sortable.lib.min') ?>
<script type="text/javascript">
	document.addEventListener('DOMContentLoaded', function(){
		function setInvalid(sel, invalid){
			var el = document.querySelector(sel);
			var group = el ? el.closest('.form-group') : null;
			if (group){ group.classList.toggle('is-invalid', invalid); }
		}

		// SortableJS (remplace jQuery UI sortable/droppable) : la liste de liens + une zone de
		// suppression partagent un group ; déposer un lien sur #link-delete le supprime.
		document.querySelectorAll('#pills-links .list-group').forEach(function(el){
			new Sortable(el, { group: 'nav-links', animation: 150 });
		});

		var linkDelete = document.getElementById('link-delete');
		if (linkDelete){
			new Sortable(linkDelete, {
				group: 'nav-links',
				animation: 150,
				onAdd: function(evt){ evt.item.remove(); }
			});
		}

		var active_id = '';

		document.querySelectorAll('a.type-collapse').forEach(function(tab){
			tab.addEventListener('click', function(){
				active_id = this.getAttribute('href');

				document.querySelectorAll('.card-collapse .card-body').forEach(function(el){ el.style.display = 'none'; });
				document.querySelectorAll('.form-group').forEach(function(el){ el.classList.remove('has-error'); });

				var list = document.querySelector(active_id + ' .list-group');
				if (list){ list.style.display = ''; }
			});
		});

		document.querySelectorAll('#add-link .link-item').forEach(function(item){
			item.addEventListener('click', function(){
				var title = document.querySelector(active_id + ' #settings-title');
				var url   = document.querySelector(active_id + ' #settings-url');
				if (title){ title.value = this.dataset.linkTitle; }
				if (url){ url.value = this.dataset.linkUrl; }
				var list = document.querySelector(active_id + ' .list-group');
				var body = document.querySelector(active_id + ' .card-body');
				if (list){ list.style.display = 'none'; }
				if (body){ body.style.display = ''; }
			});
		});

		document.querySelectorAll('.cancel-link').forEach(function(btn){
			btn.addEventListener('click', function(){
				var body = document.querySelector(active_id + ' .card-body');
				var list = document.querySelector(active_id + '.list-group');
				if (body){ body.style.display = 'none'; }
				if (list){ list.style.display = ''; }
				active_id = '';
			});
		});

		document.querySelectorAll('#add-link .btn-primary').forEach(function(btn){
			btn.addEventListener('click', function(e){
				e.preventDefault();

				var titleEl  = document.querySelector(active_id + ' #settings-title');
				var urlEl    = document.querySelector(active_id + ' #settings-url');
				var targetEl = document.querySelector(active_id + ' #settings-target');
				var title  = titleEl ? titleEl.value : '';
				var url    = urlEl ? urlEl.value : '';
				var target = targetEl ? targetEl.value : '';

				if (title && url && target){
					['#pills-links-tab', '#pills-links'].forEach(function(s){ var el = document.querySelector(s); if (el){ el.classList.add('active', 'show'); } });
					['#pills-add-tab', '#pills-add'].forEach(function(s){ var el = document.querySelector(s); if (el){ el.classList.remove('active', 'show'); } });

					var list = document.querySelector('#pills-links .list-group');
					if (list){
						list.insertAdjacentHTML('beforeend', '<li class="list-group-item ui-sortable-handle">' +
								'<input type="hidden" name="settings[title][]" id="edit-title" value="' + title + '" />' +
								'<input type="hidden" name="settings[url][]" id="edit-url" value="' + url + '" />' +
								'<input type="hidden" name="settings[target][]" id="edit-target" value="' + target + '" />' +
								'<ul class="list-inline m-0">' +
									'<li class="list-inline-item"><a href="#" class="move-link" data-bs-toggle="tooltip" title="<?php echo $this->lang('Ordonner') ?>"><?php echo icon('fas fa-arrows-alt-v') ?></a></li>' +
									'<li class="list-inline-item"><span data-bs-toggle="tooltip" title="' + url + '"><?php echo icon('fas fa-link') ?></span></li>' +
									'<li class="list-inline-item">' + title + '</li>' +
								'</ul>' +
							'</li>');
					}

					setInvalid(active_id + ' #settings-title', false);
					setInvalid(active_id + ' #settings-url', false);
					setInvalid(active_id + ' #settings-target', false);

					if (titleEl){ titleEl.value = ''; }
					if (urlEl){ urlEl.value = ''; }

					var body = document.querySelector(active_id + ' .card-body');
					var listShow = document.querySelector(active_id + ' .list-group');
					if (body){ body.style.display = 'none'; }
					if (listShow){ listShow.style.display = ''; }
				}
				else {
					setInvalid(active_id + ' #settings-title', !title);
					setInvalid(active_id + ' #settings-url', !url);
					setInvalid(active_id + ' #settings-target', !target);
				}
			});
		});
	});
</script>
