if (document && document.body){ document.body.classList.add('nf-le-chrome'); }

// switchClass de jQuery UI (retiré) -> bascule de classe simple (la transition est cosmétique).
var nfLeSwitchClass = function(el, oldClass, newClass){
	if (!el){ return; }
	if (oldClass){ el.classList.remove(oldClass); }
	if (newClass){ el.classList.add(newClass); }
};

// Style « courant » d'un widget : data-widget-style (attribut serveur) puis suivi en mémoire.
var nfLeWidgetStyle = function(widget, value){
	if (arguments.length > 1){ widget._nfWidgetStyle = value; return value; }
	return widget._nfWidgetStyle !== undefined ? widget._nfWidgetStyle : NF.data(widget, 'widget-style');
};

var nfLeCloneModal = function(templateId){
	var tpl = document.getElementById(templateId);
	if (!tpl || !tpl.content){
		return null;
	}
	return tpl.content.cloneNode(true).querySelector('.modal');
};

var nfLeOpenModal = function(templateId, title){
	if (document.querySelector('.live-editor-modal')){
		return null;
	}
	var modal = nfLeCloneModal(templateId);
	if (!modal){
		return null;
	}
	if (title){
		var titleEl = modal.querySelector('.nf-le-modal-title-text');
		if (titleEl){ titleEl.textContent = title; }
	}
	document.body.appendChild(modal);
	return modal;
};

var modal_style = function(title, element, styles, callback){
	var modal = nfLeOpenModal('nf-le-tpl-modal-style', title);
	if (!modal){ return; }

	var stylesEl = document.querySelector(styles);
	modal.querySelector('.modal-body').innerHTML = stylesEl ? stylesEl.innerHTML : '';
	modal._nfElement = element;
	bootstrap.Modal.getOrCreateInstance(modal).show();

	var widget = element.closest('.widget');

	element._nfPreviousStyle = nfLeWidgetStyle(widget);

	var entries = modal.querySelectorAll('[data-style]');
	for (var i = 0; i < entries.length; i++){
		if (NF.data(entries[i], 'style') == nfLeWidgetStyle(widget)){
			entries[i].classList.add('active');
			break;
		}
	}

	modal.addEventListener('hidden.bs.modal', function(){
		if (element._nfPreviousStyle != nfLeWidgetStyle(widget)){
			nfLeSwitchClass(element, element._nfPreviousStyle, nfLeWidgetStyle(widget));
		}
		modal.remove();
	});

	modal.querySelector('[data-action="confirm"]').addEventListener('click', function(){
		var style = element._nfPreviousStyle;
		nfLeWidgetStyle(widget, style);
		bootstrap.Modal.getOrCreateInstance(modal).hide();
		callback(style);
	});
};

var modal_settings = function(title, settings, callback){
	var load_settings = function(){
		var settingsEl = document.getElementById('live-editor-settings');
		var widget = document.getElementById('live-editor-settings-widget').value;
		var type   = document.getElementById('live-editor-settings-type').value;

		var data;
		if (NF.data(settingsEl, 'widget-id') && NF.data(settingsEl, 'original-widget') == widget && NF.data(settingsEl, 'original-type') == type){
			data = { widget_id: NF.data(settingsEl, 'widget-id') };
		}
		else {
			data = { widget: widget, type: type };
		}

		settingsEl.innerHTML = '';

		NF.post('<?php echo url('admin/ajax/live-editor/widget-admin') ?>', data).then(function(html){
			if (html){ NF.setHtml(settingsEl, html); }
		});
	};

	var modal = nfLeOpenModal('nf-le-tpl-modal-settings', title);
	if (!modal){ return; }

	NF.setHtml(modal.querySelector('.modal-body'), settings);

	modal.addEventListener('change', function(e){
		if (!e.target.closest('#live-editor-settings-widget')){ return; }

		var widgets = document.getElementById('live-editor-settings-widget');
		var typeSelect = document.getElementById('live-editor-settings-type');
		var count = 0;

		Array.prototype.forEach.call(typeSelect.querySelectorAll('option'), function(opt){ opt.selected = false; });

		Array.prototype.forEach.call(typeSelect.querySelectorAll('option'), function(opt){
			if (NF.data(opt, 'widget') == widgets.value){
				opt.style.display = '';
				count++;
			}
			else {
				opt.style.display = 'none';
			}
		});

		var typeGroup = typeSelect.closest('.form-group');

		if (count){
			if (typeGroup){ typeGroup.style.display = ''; }
			var firstOpt = typeSelect.querySelector('option[data-widget="' + widgets.value + '"]');
			if (firstOpt){ firstOpt.selected = true; }
		}
		else if (typeGroup){
			typeGroup.style.display = 'none';
		}

		var titleField = document.getElementById('live-editor-settings-title');
		var titleGroup = titleField.closest('.form-group');

		if (widgets.value == 'module'){
			titleField._nfValue = titleField.value;
			titleField.value = '';
			if (titleGroup){ titleGroup.style.display = 'none'; }
		}
		else {
			if (!titleField.value && titleField._nfValue){
				titleField.value = titleField._nfValue;
			}
			if (titleGroup){ titleGroup.style.display = ''; }
		}

		if (!modal.querySelector('#live-editor-settings-type option[data-widget="' + widgets.value + '"]')){
			typeSelect.value = 'index';
			if (typeGroup){ typeGroup.style.display = 'none'; }
		}

		load_settings();
	});

	modal.addEventListener('change', function(e){
		if (e.target.closest('#live-editor-settings-type')){ load_settings(); }
	});

	document.getElementById('live-editor-settings-type').dispatchEvent(new Event('change', { bubbles: true }));

	var settingsForm = modal.querySelector('#live-editor-settings-form');
	if (settingsForm){
		settingsForm.addEventListener('submit', function(e){
			e.preventDefault();
			modal.querySelector('[data-action="confirm"]').click();
		});
	}

	bootstrap.Modal.getOrCreateInstance(modal).show();

	modal.addEventListener('hidden.bs.modal', function(){
		modal.remove();
	});

	modal.querySelector('[data-action="confirm"]').addEventListener('click', function(){
		var form = document.getElementById('live-editor-settings-form');
		form.dispatchEvent(new CustomEvent('nf.live-editor-settings.submit', { bubbles: true }));

		bootstrap.Modal.getOrCreateInstance(modal).hide();

		// Sérialise le form avec ses names natifs (doublons -> tableau ; NF.ajax gère le suffixe []).
		var settings = { settings: null };
		new FormData(form).forEach(function(value, name){
			if (settings[name] !== undefined){
				if (!Array.isArray(settings[name])){ settings[name] = [settings[name]]; }
				settings[name].push(value || '');
			}
			else {
				settings[name] = value || '';
			}
		});

		if (typeof settings.title == 'undefined'){
			settings.title = '';
		}

		callback(settings);
	});
};

var modal_fork = function(callback){
	var modal = nfLeOpenModal('nf-le-tpl-modal-fork');
	if (!modal){ return; }

	bootstrap.Modal.getOrCreateInstance(modal).show();

	modal.addEventListener('hidden.bs.modal', function(){ modal.remove(); });

	modal.querySelector('[data-action="confirm"]').addEventListener('click', function(){
		bootstrap.Modal.getOrCreateInstance(modal).hide();
		callback();
	});
};

var modal_delete = function(message, callback){
	var modal = nfLeOpenModal('nf-le-tpl-modal-delete');
	if (!modal){ return; }

	modal.querySelector('.modal-body').innerHTML = message;
	bootstrap.Modal.getOrCreateInstance(modal).show();

	modal.addEventListener('hidden.bs.modal', function(){ modal.remove(); });

	modal.querySelector('[data-action="confirm"]').addEventListener('click', function(){
		bootstrap.Modal.getOrCreateInstance(modal).hide();
		callback();
	});
};

NF.ready(function(){
	var widgetsMode = document.querySelector('[data-mode="<?php echo \NF\NeoFrag\Core\Output::WIDGETS ?>"]');

	var liveEditorForm = function(){ return document.querySelector('form[target="live-editor-iframe"]'); };
	var liveEditorValue = function(){ var i = document.querySelector('input[type="hidden"][name="live_editor"]'); return i ? i.value : ''; };
	var showSave = function(){ document.querySelectorAll('.live-editor-save').forEach(function(s){ s.style.display = ''; }); };
	var hideSave = function(){ document.querySelectorAll('.live-editor-save').forEach(function(s){ s.style.display = 'none'; }); };

	var initialForm = liveEditorForm();
	if (initialForm){ initialForm.submit(); }

	document.querySelectorAll('.live-editor-screen[data-width]').forEach(function(screen){
		screen.addEventListener('click', function(){
			var width = NF.data(this, 'width');
			var size;

			if (width == '100%'){
				size = '20px';
				width = 'calc(' + width + ' - 40px)';
			}
			else {
				size = 'calc(50% - ' + width + ' / 2)';
			}

			document.querySelectorAll('.live-editor-iframe').forEach(function(f){ f.style.width = width; f.style.left = size; });
			document.querySelectorAll('.live-editor-screen').forEach(function(s){ s.classList.remove('active'); });
			this.classList.add('active');
			var dropdown = document.getElementById('navbarDropdownScreen');
			if (dropdown){ dropdown.innerHTML = this.innerHTML + ' <?php echo icon('fas fa-angle-down') ?>'; }
		});
	});

	document.querySelectorAll('.live-editor-mode').forEach(function(modeBtn){
		modeBtn.addEventListener('click', function(){
			this.classList.toggle('active');

			if (NF.data(this, 'mode') == <?php echo \NF\NeoFrag\Core\Output::WIDGETS ?>){
				return;
			}

			var mode = <?php echo $this->output->live_editor() ?>;
			document.querySelectorAll('.live-editor-mode.active').forEach(function(m){
				mode += NF.data(m, 'mode');
			});

			var hidden = document.querySelector('input[type="hidden"][name="live_editor"]');
			if (hidden){ hidden.value = mode; }
			var form = liveEditorForm();
			if (form){ form.submit(); }
		});
	});

	var modulesLinks = document.getElementById('modules-links-collapse');
	if (modulesLinks){
		modulesLinks.addEventListener('click', function(e){
			var link = e.target.closest('.dropdown-menu > a');
			if (!link){ return; }
			e.preventDefault();

			var map = document.getElementById('live-editor-map');
			if (map){ map.innerHTML = '<?php echo icon('fas fa-spinner fa-spin').' '.$this->lang('Chargement en cours...') ?>'; }
			var form = liveEditorForm();
			if (form){ form.action = link.getAttribute('href'); form.submit(); }
			document.querySelectorAll('.dropdown-menu').forEach(function(m){ m.classList.remove('show'); });
			document.querySelectorAll('.nav-item.dropdown').forEach(function(m){ m.classList.remove('show'); });
		});
	}

	/* Styles Overview */
	document.body.addEventListener('click', function(e){
		var overview = e.target.closest('.live-editor-overview:not(.active)');
		if (!overview){ return; }

		var modal = overview.closest('.modal');
		var element = modal ? modal._nfElement : null;
		if (!element){ return; }

		nfLeSwitchClass(element, element._nfPreviousStyle, NF.data(overview, 'style'));
		element._nfPreviousStyle = NF.data(overview, 'style');
		document.querySelectorAll('.live-editor-overview').forEach(function(o){ o.classList.remove('active'); });
		overview.classList.add('active');
	});

	document.querySelectorAll('.live-editor-iframe iframe').forEach(function(iframe){
		iframe.addEventListener('load', function(){
			var doc = iframe.contentDocument || iframe.contentWindow.document;

			var liveEditorEl = doc.querySelector('#live_editor');
			var map = document.getElementById('live-editor-map');
			if (map && liveEditorEl){ map.innerHTML = NF.data(liveEditorEl, 'module-title'); }

			doc.addEventListener('mouseover', function(e){
				var el = e.target.closest('.widget, .module');
				if (!el){ return; }

				if (widgetsMode && widgetsMode.classList.contains('active') && !el.querySelector('.widget-hover')){
					doc.querySelectorAll('.widget-hover').forEach(function(h){ h.remove(); });
					if (getComputedStyle(el).position === 'static'){
						el.style.position = 'relative';
					}
					var isModule  = el.classList.contains('module');
					var typeLabel = isModule ? '<?php echo $this->lang('Module') ?>' : '<?php echo $this->lang('Widget') ?>';
					var title     = NF.data(el, 'title') || '';
					var styleBtn  = isModule ? '' : '<button type="button" class="nf-le-btn live-editor-style" title="<?php echo $this->lang('Apparence') ?>" aria-label="<?php echo $this->lang('Apparence') ?>"><?php echo icon('fas fa-paint-brush') ?></button>';

					var hover = document.createElement('div');
					hover.className = 'widget-hover nf-le-widget-hover';
					hover.innerHTML = '<div class="nf-le-widget-hover-card">' +
							'<span class="nf-le-widget-hover-type">' + typeLabel + '</span>' +
							'<span class="nf-le-widget-hover-title">' + title + '</span>' +
							'<div class="nf-le-toolbar" role="toolbar">' +
								styleBtn +
								'<button type="button" class="nf-le-btn live-editor-setting" title="<?php echo $this->lang('Configurer') ?>" aria-label="<?php echo $this->lang('Configurer') ?>"><?php echo icon('fas fa-cog') ?></button>' +
								'<button type="button" class="nf-le-btn nf-le-btn-danger live-editor-delete" title="<?php echo $this->lang('Supprimer') ?>" aria-label="<?php echo $this->lang('Supprimer') ?>"><?php echo icon('far fa-trash-alt') ?></button>' +
							'</div>' +
						'</div>';
					hover.style.opacity = '0';
					hover.style.transition = 'opacity .2s';
					hover.addEventListener('mouseleave', function(){ hover.remove(); });
					el.insertBefore(hover, el.firstChild);
					requestAnimationFrame(function(){ hover.style.opacity = '1'; });
				}
			});

			doc.addEventListener('click', function(e){
				var link = e.target.closest('a');
				if (!link || !doc.contains(link)){ return; }
				// Délégué plus bas pour les boutons spécifiques ; ici uniquement les liens de navigation.
				if (e.target.closest('.live-editor-fork, .live-editor-add-row, .live-editor-add-col, .live-editor-add-widget, .live-editor-style, .live-editor-setting, .live-editor-delete, .live-editor-size')){ return; }

				var href = link.getAttribute('href');
				if (href && href.match(/<?php echo str_replace('/', '\/', url()) ?>(?!(admin|live-editor|#))/)){
					if (map){ map.innerHTML = '<?php echo icon('fas fa-spinner fa-spin').' '.$this->lang('Chargement en cours...') ?>'; }
					var form = liveEditorForm();
					if (form){ form.action = href; form.submit(); }
				}

				e.preventDefault();
			});

			/* Zone Fork */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-zone .live-editor-fork');
				if (!btn){ return; }

				var fork = function(){
					showSave();
					var zone = btn.closest('[data-disposition-id]');

					NF.post('<?php echo url('admin/ajax/live-editor/zone-fork') ?>', {
						disposition_id: NF.data(zone, 'disposition-id'),
						url: doc.location.pathname,
						live_editor: liveEditorValue()
					}).then(function(data){
						var tmp = document.createElement('div');
						tmp.innerHTML = data;
						if (tmp.querySelector('.live-editor-widget.module')){
							var form = liveEditorForm();
							if (form){ form.submit(); }
						}
						else {
							zone.outerHTML = data;
						}
					}).catch(function(){}).finally(hideSave);
				};

				if (NF.data(btn, 'enabled')){ modal_fork(fork); }
				else { fork(); }
			});

			/* Row Add */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-add-row');
				if (!btn){ return; }

				var disposition = btn.closest('[data-disposition-id]');
				showSave();

				NF.post('<?php echo url('admin/ajax/live-editor/row-add') ?>', {
					disposition_id: NF.data(disposition, 'disposition-id'),
					live_editor: liveEditorValue()
				}).then(function(data){
					var rowsButton = document.querySelector('.live-editor-mode[data-mode="<?php echo \NF\NeoFrag\Core\Output::ROWS ?>"]');
					if (rowsButton && !rowsButton.classList.contains('active')){
						rowsButton.click();
					}
					else {
						disposition.insertAdjacentHTML('beforeend', data);
					}
				}).catch(function(){}).finally(hideSave);
			});

			/* Row Move */
			doc.querySelectorAll('[data-disposition-id]').forEach(function(el){
				new Sortable(el, {
					draggable: '.live-editor-row',
					animation: 150,
					ghostClass: 'live-editor-placeholder',
					onEnd: function(evt){
						showSave();
						var handle = evt.item.querySelector('.row');
						NF.post('<?php echo url('admin/ajax/live-editor/row-move') ?>', {
							disposition_id: NF.data(evt.to, 'disposition-id'),
							row_id: handle ? NF.data(handle, 'row-id') : '',
							position: evt.newIndex
						}).catch(function(){}).finally(hideSave);
					}
				});
			});

			/* Row Style */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-row-header .live-editor-style');
				if (!btn){ return; }

				var header = btn.closest('.live-editor-row-header');
				var row    = header ? header.nextElementSibling : null;

				modal_style('<?php echo $this->lang('Apparence de la ligne') ?>', row, '.live-editor-styles-row', function(style){
					showSave();
					NF.post('<?php echo url('admin/ajax/live-editor/row-style') ?>', {
						disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
						row_id: NF.data(row, 'row-id'),
						style: style
					}).catch(function(){}).finally(hideSave);
				});
			});

			/* Row Delete */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-row-header .live-editor-delete');
				if (!btn){ return; }

				modal_delete('<?php echo $this->lang('Êtes-vous sûr(e) de vouloir supprimer cette <b>ligne</b> ?<br />Toutes les <b>colonnes</b> et <b>widgets</b> contenus seront également supprimés.') ?>', function(){
					var header = btn.closest('.live-editor-row-header');
					var row    = header ? header.nextElementSibling : null;
					showSave();

					NF.post('<?php echo url('admin/ajax/live-editor/row-delete') ?>', {
						disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
						row_id: NF.data(row, 'row-id')
					}).then(function(){
						var wrapper = row ? row.closest('.live-editor-row') : null;
						if (wrapper){ wrapper.remove(); }
					}).catch(function(){}).finally(hideSave);
				});
			});

			/* Col Add */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-add-col');
				if (!btn){ return; }

				var header = btn.closest('.live-editor-row-header');
				var row    = header ? header.nextElementSibling : null;
				showSave();

				NF.post('<?php echo url('admin/ajax/live-editor/col-add') ?>', {
					disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
					row_id: NF.data(row, 'row-id'),
					live_editor: liveEditorValue()
				}).then(function(data){
					var colsButton = document.querySelector('.live-editor-mode[data-mode="<?php echo \NF\NeoFrag\Core\Output::COLS ?>"]');
					if (colsButton && !colsButton.classList.contains('active')){
						colsButton.click();
					}
					else if (row){
						row.insertAdjacentHTML('beforeend', data);
					}
				}).catch(function(){}).finally(hideSave);
			});

			/* Col Move */
			doc.querySelectorAll('[data-row-id]').forEach(function(el){
				new Sortable(el, {
					draggable: '[data-col-id]',
					animation: 150,
					ghostClass: 'live-editor-placeholder',
					onEnd: function(evt){
						showSave();
						NF.post('<?php echo url('admin/ajax/live-editor/col-move') ?>', {
							disposition_id: NF.data(evt.to.closest('[data-disposition-id]'), 'disposition-id'),
							row_id: NF.data(evt.to, 'row-id'),
							col_id: NF.data(evt.item, 'col-id'),
							position: evt.newIndex
						}).catch(function(){}).finally(hideSave);
					}
				});
			});

			/* Col Size */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-col .live-editor-size');
				if (!btn){ return; }

				var col       = btn.closest('[data-col-id]');
				var classList = col.getAttribute('class') || '';
				var oldSize   = 12;
				var prefix    = 'col-lg-';
				var match     = classList.match(/\bcol-lg-(\d{1,2})\b/);

				if (match){
					oldSize = parseInt(match[1], 10);
				}
				else if ((match = classList.match(/\bcol-(\d{1,2})\b/))){
					oldSize = parseInt(match[1], 10);
					prefix  = 'col-';
				}

				var newSize = Math.max(1, Math.min(12, oldSize + parseInt(NF.data(btn, 'size'), 10)));

				if (newSize !== oldSize){
					col.classList.remove(prefix + oldSize);
					col.classList.add(prefix + newSize);
					showSave();

					NF.post('<?php echo url('admin/ajax/live-editor/col-size') ?>', {
						disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
						row_id:         NF.data(btn.closest('[data-row-id]'), 'row-id'),
						col_id:         NF.data(col, 'col-id'),
						size:           newSize
					}).catch(function(){}).finally(hideSave);
				}
			});

			/* Col Delete */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-col > .nf-le-col-header .live-editor-delete');
				if (!btn){ return; }

				var col = btn.closest('[data-col-id]');

				modal_delete('<?php echo $this->lang('Êtes-vous sûr(e) de vouloir supprimer cette <b>colonne</b> ?<br />Tous les <b>widgets</b> contenus seront également supprimés.') ?>', function(){
					showSave();
					NF.post('<?php echo url('admin/ajax/live-editor/col-delete') ?>', {
						disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
						row_id: NF.data(btn.closest('[data-row-id]'), 'row-id'),
						col_id: NF.data(col, 'col-id')
					}).then(function(){
						if (col){ col.remove(); }
					}).catch(function(){}).finally(hideSave);
				});
			});

			/* Widget Add */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-add-widget');
				if (!btn){ return; }

				var col  = btn.closest('[data-col-id]');
				var data = {
					disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
					row_id: NF.data(btn.closest('[data-row-id]'), 'row-id'),
					col_id: NF.data(col, 'col-id'),
					widget_id: -1
				};

				NF.post('<?php echo url('admin/ajax/live-editor/widget-settings') ?>', data).then(function(html){
					modal_settings('<?php echo $this->lang('Nouveau Widget') ?>', html, function(settings){
						Object.assign(data, settings, { live_editor: liveEditorValue() });
						showSave();

						NF.post('<?php echo url('admin/ajax/live-editor/widget-add') ?>', data).then(function(result){
							if (settings.widget == 'module'){
								var form = liveEditorForm();
								if (form){ form.submit(); }
							}
							else {
								var target = col.querySelector('.live-editor-col');
								if (target){ target.insertAdjacentHTML('beforeend', result); }
							}
						}).catch(function(){}).finally(hideSave);
					});
				});
			});

			/* Widget Move */
			doc.querySelectorAll('[data-col-id]').forEach(function(el){
				new Sortable(el, {
					draggable: '[data-widget-id]',
					animation: 150,
					ghostClass: 'live-editor-placeholder',
					onEnd: function(evt){
						showSave();
						NF.post('<?php echo url('admin/ajax/live-editor/widget-move') ?>', {
							disposition_id: NF.data(evt.to.closest('[data-disposition-id]'), 'disposition-id'),
							row_id: NF.data(evt.to.closest('[data-row-id]'), 'row-id'),
							col_id: NF.data(evt.to, 'col-id'),
							widget_id: NF.data(evt.item, 'widget-id'),
							position: evt.newIndex
						}).catch(function(){}).finally(hideSave);
					}
				});
			});

			/* Widget Style */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-widget .live-editor-style');
				if (!btn){ return; }

				var widget = btn.closest('[data-widget-id]');
				var data   = {
					disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
					row_id: NF.data(btn.closest('[data-row-id]'), 'row-id'),
					col_id: NF.data(btn.closest('[data-col-id]'), 'col-id'),
					widget_id: NF.data(widget, 'widget-id')
				};

				modal_style('<?php echo $this->lang('Apparence du Widget') ?>', widget.querySelector('.card'), '.live-editor-styles-widget', function(style){
					Object.assign(data, { style: style });
					showSave();
					NF.post('<?php echo url('admin/ajax/live-editor/widget-style') ?>', data).catch(function(){}).finally(hideSave);
				});
			});

			/* Widget Settings */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-widget .live-editor-setting');
				if (!btn){ return; }

				var widget = btn.closest('[data-widget-id]');
				var data   = {
					disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
					row_id: NF.data(btn.closest('[data-row-id]'), 'row-id'),
					col_id: NF.data(btn.closest('[data-col-id]'), 'col-id'),
					widget_id: NF.data(widget, 'widget-id')
				};

				NF.post('<?php echo url('admin/ajax/live-editor/widget-settings') ?>', data).then(function(html){
					modal_settings('<?php echo $this->lang('Configuration du Widget') ?>', html, function(settings){
						Object.assign(data, settings);
						showSave();

						NF.post('<?php echo url('admin/ajax/live-editor/widget-update') ?>', data).then(function(result){
							if (settings.widget == 'module'){
								var form = liveEditorForm();
								if (form){ form.submit(); }
							}
							else {
								widget.outerHTML = result;
							}
						}).catch(function(){}).finally(hideSave);
					});
				});
			});

			/* Widget Delete */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-widget .live-editor-delete');
				if (!btn){ return; }

				var widget = btn.closest('[data-widget-id]');
				var data   = {
					disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
					row_id: NF.data(btn.closest('[data-row-id]'), 'row-id'),
					col_id: NF.data(btn.closest('[data-col-id]'), 'col-id'),
					widget_id: NF.data(widget, 'widget-id')
				};

				modal_delete('<?php echo $this->lang('Êtes-vous sûr(e) de vouloir supprimer ce <b>widget</b> ?') ?>', function(){
					showSave();
					NF.post('<?php echo url('admin/ajax/live-editor/widget-delete') ?>', data).then(function(){
						if (widget){ widget.remove(); }
						hideSave();
					}).catch(hideSave);
				});
			});
		});
	});
});

document.querySelectorAll('[data-typer]').forEach(function(typer){
	var txt      = typer.getAttribute('data-typer');
	var tot      = txt.length;
	var pauseMax = 300;
	var pauseMin = 60;
	var ch       = 0;

	(function typeIt(){
		if (ch > tot){ return; }
		typer.textContent = txt.substring(0, ch++);
		setTimeout(typeIt, ~~(Math.random() * (pauseMax - pauseMin + 1) + pauseMin));
	}());
});
