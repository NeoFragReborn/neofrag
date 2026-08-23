// Icon picker vanilla (remplace bootstrap-iconpicker, jQuery+BS4, ~343 Ko). La valeur = '<style> fa-<nom>'
// (ex. 'far fa-clock'). La liste d'icônes est extraite à la volée du CSS Font Awesome chargé (propriété
// --fa), donc toujours alignée sur la version FA du site. Le <button> garde un <input type="hidden"> pour
// la sérialisation FormData (comme le faisait bootstrap-iconpicker).
(function(){
	var _icons = null;

	function loadIcons(){
		if (_icons){ return _icons; }
		var set = {}, skip = ['solid', 'regular', 'brands', 'fw', 'spin', 'pulse', 'beat', 'bounce', 'flip', 'shake'];
		for (var s = 0; s < document.styleSheets.length; s++){
			var rules;
			try { rules = document.styleSheets[s].cssRules; } catch (e) { continue; }
			if (!rules){ continue; }
			for (var r = 0; r < rules.length; r++){
				var rule = rules[r];
				if (!rule.selectorText || !rule.style || !rule.style.getPropertyValue('--fa')){ continue; }
				var ms = rule.selectorText.match(/\.fa-([a-z0-9-]+)/g);
				if (ms){ ms.forEach(function(x){ var n = x.replace('.fa-', ''); if (skip.indexOf(n) === -1){ set[n] = 1; } }); }
			}
		}
		_icons = Object.keys(set).sort();
		return _icons;
	}

	function init(btn){
		var name   = btn.getAttribute('name');
		var value  = btn.getAttribute('data-icon') || '';
		if (value === 'empty'){ value = ''; }

		// L'aperçu : <i> existant (form2) ou créé (form.php n'en met pas).
		var iEl = btn.querySelector('i');
		if (!iEl){ iEl = document.createElement('i'); btn.insertBefore(iEl, btn.firstChild); }
		if (value){ iEl.className = value; }

		// hidden input pour la soumission (FormData ignore les <button>)
		var hidden = btn.querySelector('input[type="hidden"]');
		if (!hidden){
			hidden = document.createElement('input');
			hidden.type = 'hidden';
			if (name){ hidden.name = name; }
			btn.appendChild(hidden);
		}
		hidden.value = value;

		btn.setAttribute('type', 'button');
		btn.classList.add('dropdown-toggle');
		btn.setAttribute('data-bs-toggle', 'dropdown');
		btn.setAttribute('data-bs-auto-close', 'outside');

		var prefix = (value.match(/\b(fas|far|fab)\b/) || ['fas'])[0];

		var menu = document.createElement('div');
		menu.className = 'dropdown-menu p-2';
		menu.style.width = '320px';
		menu.addEventListener('click', function(e){ e.stopPropagation(); });

		var tabs = document.createElement('div');
		tabs.className = 'btn-group btn-group-sm w-100 mb-2';

		var search = document.createElement('input');
		search.type = 'search';
		search.className = 'form-control form-control-sm mb-2';
		search.placeholder = '<?php echo $this->lang('Rechercher...') ?>';

		var grid = document.createElement('div');
		grid.className = 'd-flex flex-wrap';
		grid.style.cssText = 'max-height:240px;overflow:auto';

		function build(){
			grid.innerHTML = '';
			loadIcons().forEach(function(nm){
				var ib = document.createElement('button');
				ib.type = 'button';
				ib.className = 'btn btn-sm';
				ib.style.cssText = 'width:36px;height:36px';
				ib.title = nm;
				ib.dataset.name = nm;
				ib.innerHTML = '<i class="' + prefix + ' fa-' + nm + '"></i>';
				ib.addEventListener('click', function(){
					var val = prefix + ' fa-' + nm;
					btn.setAttribute('data-icon', val);
					if (iEl){ iEl.className = val; }
					hidden.value = val;
					hidden.dispatchEvent(new Event('change', { bubbles: true }));
					bootstrap.Dropdown.getOrCreateInstance(btn).hide();
				});
				grid.appendChild(ib);
			});
			filter();
		}

		function filter(){
			var q = search.value.toLowerCase();
			grid.querySelectorAll('button').forEach(function(b){
				b.style.display = (!q || b.dataset.name.indexOf(q) !== -1) ? '' : 'none';
			});
		}

		[['fas', 'Solid'], ['far', 'Regular'], ['fab', 'Brands']].forEach(function(st){
			var t = document.createElement('button');
			t.type = 'button';
			t.className = 'btn btn-outline-secondary' + (st[0] === prefix ? ' active' : '');
			t.textContent = st[1];
			t.addEventListener('click', function(){
				prefix = st[0];
				tabs.querySelectorAll('button').forEach(function(x){ x.classList.remove('active'); });
				t.classList.add('active');
				build();
			});
			tabs.appendChild(t);
		});

		search.addEventListener('input', filter);

		menu.appendChild(tabs);
		menu.appendChild(search);
		menu.appendChild(grid);
		btn.parentNode.insertBefore(menu, btn.nextSibling);

		btn.addEventListener('shown.bs.dropdown', function(){ if (!grid.childNodes.length){ build(); } });
	}

	document.body.addEventListener('nf.load', function(){
		document.querySelectorAll('.iconpicker:not(.nf-iconpicker)').forEach(function(btn){
			btn.classList.add('nf-iconpicker');
			init(btn);
		});
	});
})();
