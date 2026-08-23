/**
 * Palier 1 page-builder — composer de blocs (admin). Vanilla + SortableJS + window.NF.
 * Construit une liste ordonnable de blocs (depuis le registre passé en JSON), chaque bloc
 * exposant ses `fields` comme inputs ; sérialise et POST sur l'endpoint dédié à la sauvegarde.
 */
NF.ready(function(){
	var root = document.querySelector('[data-composer]');
	if (!root){ return; }

	var listEl   = root.querySelector('[data-composer-list]');
	var selectEl = root.querySelector('[data-composer-select]');
	var addBtn   = root.querySelector('[data-composer-add]');
	var saveBtn  = root.querySelector('[data-composer-save]');
	var statusEl = root.querySelector('[data-composer-status]');
	var saveUrl  = root.getAttribute('data-save-url');
	var pageId   = root.getAttribute('data-page-id');

	function parseJson(sel, fallback){
		var el = document.querySelector(sel);
		try { return JSON.parse(el ? el.textContent : ''); } catch (e){ return fallback; }
	}

	var blocks    = parseJson('[data-composer-blocks]', {});
	var instances = parseJson('[data-composer-instances]', []);

	function esc(s){ var d = document.createElement('div'); d.textContent = (s == null ? '' : String(s)); return d.innerHTML; }

	function fieldRow(fname, spec, value){
		var wrap = document.createElement('label');
		wrap.style.cssText = 'display:inline-flex;align-items:center;gap:6px;margin:0 16px 8px 0;';

		var lab = document.createElement('span');
		lab.className = 'small text-muted';
		lab.textContent = fname;

		var input;
		if (spec.type === 'bool'){
			input = document.createElement('input');
			input.type = 'checkbox';
			input.checked = (value === true || value === 1 || value === '1' || (value == null && spec.default === true));
		} else if (spec.type === 'int'){
			input = document.createElement('input');
			input.type = 'number';
			input.className = 'form-control form-control-sm';
			input.style.width = '92px';
			if (spec.min != null){ input.min = spec.min; }
			if (spec.max != null){ input.max = spec.max; }
			input.value = (value != null ? value : (spec.default != null ? spec.default : ''));
		} else {
			input = document.createElement('input');
			input.type = 'text';
			input.className = 'form-control form-control-sm';
			if (spec.max_length != null){ input.maxLength = spec.max_length; }
			input.value = (value != null ? value : (spec.default != null ? spec.default : ''));
		}

		input.setAttribute('data-field', fname);
		input.setAttribute('data-type', spec.type || 'string');

		wrap.appendChild(lab);
		wrap.appendChild(input);
		return wrap;
	}

	function card(block, settings){
		var meta = blocks[block];
		if (!meta){ return null; }
		settings = settings || {};

		var el = document.createElement('div');
		el.className = 'card mb-2';
		el.setAttribute('data-item', block);

		var head = document.createElement('div');
		head.className = 'card-header d-flex align-items-center';
		head.style.cssText = 'gap:8px;padding:7px 12px;';
		head.innerHTML = '<i class="fas fa-grip-vertical" data-handle style="cursor:grab;color:#888;"></i> <strong>' + esc(meta.title) + '</strong> <code style="margin-left:auto">' + esc(block) + '</code>';

		var rm = document.createElement('button');
		rm.type = 'button';
		rm.className = 'btn btn-sm btn-outline-danger ms-2';
		rm.innerHTML = '<i class="fas fa-times"></i>';
		rm.addEventListener('click', function(){ el.remove(); });
		head.appendChild(rm);
		el.appendChild(head);

		var fields = meta.fields || {};
		if (Object.keys(fields).length){
			var body = document.createElement('div');
			body.className = 'card-body';
			body.style.padding = '10px 12px';
			Object.keys(fields).forEach(function(f){ body.appendChild(fieldRow(f, fields[f], settings[f])); });
			el.appendChild(body);
		}

		return el;
	}

	function serialize(){
		var out = [];
		listEl.querySelectorAll('[data-item]').forEach(function(item){
			var block = item.getAttribute('data-item');
			if (!blocks[block]){ return; }

			var settings = {};
			item.querySelectorAll('[data-field]').forEach(function(inp){
				var f = inp.getAttribute('data-field'), t = inp.getAttribute('data-type');
				if (t === 'bool'){ settings[f] = inp.checked; }
				else if (t === 'int'){ settings[f] = inp.value === '' ? null : parseInt(inp.value, 10); }
				else { settings[f] = inp.value; }
			});

			out.push({ block: block, settings: settings });
		});
		return out;
	}

	(instances || []).forEach(function(inst){
		var c = card(inst.block, inst.settings);
		if (c){ listEl.appendChild(c); }
	});

	if (addBtn){
		addBtn.addEventListener('click', function(){
			if (selectEl && selectEl.value){
				var c = card(selectEl.value, {});
				if (c){ listEl.appendChild(c); }
			}
		});
	}

	if (window.Sortable){
		new Sortable(listEl, { handle: '[data-handle]', animation: 150 });
	}

	function status(msg, type){
		if (typeof notify !== 'undefined'){ notify(msg, type || 'success'); }
		else if (statusEl){ statusEl.textContent = msg; }
	}

	if (saveBtn){
		saveBtn.addEventListener('click', function(){
			saveBtn.classList.add('disabled');
			fetch(saveUrl, {
				method: 'POST',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
				credentials: 'same-origin',
				body: new URLSearchParams({ page_id: pageId, instances: JSON.stringify(serialize()) })
			}).then(function(r){ return r.ok ? r.json() : Promise.reject(r.status); })
			.then(function(d){
				saveBtn.classList.remove('disabled');
				status('Blocs enregistrés (' + (d.count || 0) + ')', 'success');
			}).catch(function(s){
				saveBtn.classList.remove('disabled');
				status('Erreur de sauvegarde (' + s + ')', 'danger');
			});
		});
	}
});
