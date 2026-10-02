NF.ready(function(){
	/*
	 * La taille d'un fichier, dans la langue de la page : Intl écrit l'unité (« ko » en français, « kB »
	 * en anglais) et le séparateur décimal propres à chaque langue. Les unités étaient écrites en dur, en
	 * français. Un navigateur sans les unités d'Intl retombe sur les symboles internationaux.
	 */
	var formatSize = function(size){
		var units = ['byte', 'kilobyte', 'megabyte', 'gigabyte'];
		var value = size || 0;
		var unit  = 0;

		while (value >= 1024 && unit < units.length - 1){
			value = value / 1024;
			unit++;
		}

		var digits = unit ? 1 : 0;

		try {
			return new Intl.NumberFormat(document.documentElement.lang || undefined, {
				style: 'unit', unit: units[unit], unitDisplay: 'short',
				minimumFractionDigits: digits, maximumFractionDigits: digits
			}).format(value);
		}
		catch (e){
			return value.toFixed(digits) + ' ' + ['B', 'KB', 'MB', 'GB'][unit];
		}
	};

	/*
	 * Le compte des éléments sélectionnés, au pluriel de la langue de la page. Les deux formes sont
	 * rendues côté serveur avec « {n} » à la place du nombre, que le script remplace.
	 */
	var selectionLabel = function(n){
		if (!n){
			return <?php echo json_encode((string) $this->lang('Aucun élément sélectionné')) ?>;
		}

		return (n > 1
			? <?php echo json_encode((string) $this->lang('%s élément sélectionné|%s éléments sélectionnés', 2, '{n}')) ?>
			: <?php echo json_encode((string) $this->lang('%s élément sélectionné|%s éléments sélectionnés', 1, '{n}')) ?>
		).replace('{n}', n);
	};

	var init = function(){
		document.querySelectorAll('.files-manager').forEach(function(manager){
			if (manager._filesSelectionBound){ return; }
			manager._filesSelectionBound = true;

			var bar       = document.querySelector('[data-files-selection]');
			var count     = bar ? bar.querySelector('.files-selection-count') : null;
			var buttons   = bar ? bar.querySelectorAll('.files-selection-action') : [];
			var selectAll = manager.querySelector('.files-select-all');
			var items     = manager.querySelectorAll('.files-select-item');

			var selected = function(){
				return Array.prototype.filter.call(items, function(i){ return i.checked; }).map(function(item){
					return { path: item.value, name: NF.data(item, 'name') || item.value };
				});
			};

			var update = function(){
				var paths   = selected();
				var total   = items.length;
				var checked = paths.length;

				buttons.forEach(function(b){ b.disabled = !checked; });
				manager.querySelectorAll('[data-file-row]').forEach(function(row){ row.classList.remove('is-selected'); });

				Array.prototype.filter.call(items, function(i){ return i.checked; }).forEach(function(i){
					var row = i.closest('[data-file-row]');
					if (row){ row.classList.add('is-selected'); }
				});

				if (count){
					count.textContent = selectionLabel(checked);
				}

				if (selectAll){
					selectAll.checked = total > 0 && checked === total;
					selectAll.indeterminate = checked > 0 && checked < total;
				}
			};

			var fillSelectionForm = function(modal){
				var paths     = selected();
				var inputs    = modal.querySelector('.files-selected-inputs');
				var summary   = modal.querySelector('.files-selected-summary');
				var rename    = modal.querySelector('.files-rename-field');
				var nameInput = rename ? rename.querySelector('input[name="name"]') : null;

				if (inputs){ inputs.innerHTML = ''; }

				paths.forEach(function(item){
					if (!inputs){ return; }
					var hidden = document.createElement('input');
					hidden.type  = 'hidden';
					hidden.name  = 'paths[]';
					hidden.value = item.path;
					inputs.appendChild(hidden);
				});

				if (summary){
					summary.textContent = selectionLabel(paths.length);
				}

				if (paths.length === 1){
					if (rename){ rename.style.display = ''; }
					if (nameInput){ nameInput.value = paths[0].name; }
				}
				else {
					if (rename){ rename.style.display = 'none'; }
					if (nameInput){ nameInput.value = ''; }
				}
			};

			if (selectAll){
				selectAll.addEventListener('change', function(){
					items.forEach(function(i){ i.checked = selectAll.checked; });
					update();
				});
			}

			items.forEach(function(i){ i.addEventListener('change', update); });

			manager.querySelectorAll('[data-file-row]').forEach(function(row){
				row.addEventListener('click', function(e){
					if (e.target.closest('a, input, button, label')){ return; }
					var checkbox = row.querySelector('.files-select-item');
					if (checkbox){
						checkbox.checked = !checkbox.checked;
						checkbox.dispatchEvent(new Event('change', { bubbles: true }));
					}
				});
			});

			document.querySelectorAll('.files-move-modal, .files-delete-modal').forEach(function(modal){
				modal.addEventListener('show.bs.modal', function(e){
					if (!selected().length){
						e.preventDefault();
						return;
					}
					fillSelectionForm(modal);
				});
			});

			update();
		});

		document.querySelectorAll('.files-mkdir-modal').forEach(function(modal){
			if (modal._filesMkdirBound){ return; }
			modal._filesMkdirBound = true;

			modal.addEventListener('shown.bs.modal', function(){
				var input = modal.querySelector('input[name="name"]');
				if (input){ input.focus(); }
			});
		});

		document.querySelectorAll('.files-upload-modal').forEach(function(modal){
			if (modal._filesUploadBound){ return; }
			modal._filesUploadBound = true;

			var input    = modal.querySelector('.files-upload-input');
			var dropzone = modal.querySelector('.files-upload-dropzone');
			var list     = modal.querySelector('.files-upload-list');
			var files    = [];

			var syncInput = function(){
				if (typeof DataTransfer === 'undefined'){ return; }
				var transfer = new DataTransfer();
				files.forEach(function(file){ transfer.items.add(file); });
				if (input){ input.files = transfer.files; }
			};

			var render = function(){
				if (!list){ return; }
				list.innerHTML = '';

				files.forEach(function(file, index){
					var li = document.createElement('li');

					var name = document.createElement('span');
					name.className = 'files-upload-list-name';
					name.textContent = file.name;

					var meta = document.createElement('span');
					meta.className = 'files-upload-list-meta';
					meta.textContent = formatSize(file.size);

					var remove = document.createElement('button');
					remove.type = 'button';
					remove.className = 'btn btn-sm btn-outline-danger';
					remove.title = <?php echo json_encode((string) $this->lang('Retirer')) ?>;
					remove.setAttribute('aria-label', remove.title);
					remove.innerHTML = '<i class="fas fa-times"></i>';
					remove.addEventListener('click', function(){
						files.splice(index, 1);
						syncInput();
						render();
					});

					li.appendChild(name);
					li.appendChild(meta);
					li.appendChild(remove);
					list.appendChild(li);
				});
			};

			var setFiles = function(fileList){
				files = Array.prototype.slice.call(fileList || []);
				syncInput();
				render();
			};

			if (dropzone){
				dropzone.addEventListener('click', function(){ if (input){ input.click(); } });

				['dragenter', 'dragover'].forEach(function(evt){
					dropzone.addEventListener(evt, function(e){
						e.preventDefault();
						e.stopPropagation();
						dropzone.classList.add('is-dragover');
					});
				});

				['dragleave', 'dragend', 'drop'].forEach(function(evt){
					dropzone.addEventListener(evt, function(e){
						e.preventDefault();
						e.stopPropagation();
						dropzone.classList.remove('is-dragover');
					});
				});

				dropzone.addEventListener('drop', function(e){
					if (e.dataTransfer && e.dataTransfer.files.length){
						setFiles(e.dataTransfer.files);
					}
				});
			}

			if (input){
				input.addEventListener('change', function(){ setFiles(this.files); });
			}

			modal.addEventListener('hidden.bs.modal', function(){
				files = [];
				if (input){ input.value = ''; }
				render();
				if (dropzone){ dropzone.classList.remove('is-dragover'); }
			});

			var uploadForm = modal.querySelector('.files-upload-form');
			if (uploadForm){
				uploadForm.addEventListener('submit', function(e){
					if (input && !input.files.length){
						e.preventDefault();
						if (dropzone){ dropzone.classList.add('is-dragover'); }
					}
				});
			}
		});
	};

	document.body.addEventListener('nf.load', init);
	init();
});
