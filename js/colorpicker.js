// Color picker vanilla (remplace bootstrap-colorpicker, jQuery+BS4). La valeur stockée peut être un
// nom de couleur Bootstrap (primary, success…) OU un hex — get_colors() valide les deux côté PHP.
// Le bouton « pipette » ouvre un dropdown BS5 : palette de presets nommés + <input type="color"> custom.
NF.ready(function(){
	var PRESETS = <?php echo json_encode(get_colors()) ?>; // { name: '#hex' }

	function resolveColor(val){
		if (!val){ return ''; }
		if (PRESETS[val]){ return PRESETS[val]; }
		return /^#([a-f0-9]{3}){1,2}$/i.test(val) ? val : '';
	}

	document.querySelectorAll('.input-group.color').forEach(function(group){
		if (group.dataset.nfColor){ return; }
		group.dataset.nfColor = '1';

		var input  = group.querySelector('input[type="text"]');
		var swatch = group.querySelector('.input-group-prepend i') || group.querySelector('i');
		if (!input){ return; }

		function paint(){
			if (swatch){ swatch.style.background = resolveColor(input.value) || 'transparent'; }
		}

		var toggle = group.querySelector('.input-group-append');
		if (toggle){
			toggle.style.cursor = 'pointer';
			toggle.setAttribute('data-bs-toggle', 'dropdown');
			toggle.classList.add('dropdown-toggle');
		}

		var menu = document.createElement('div');
		menu.className = 'dropdown-menu dropdown-menu-end p-2';

		var row = document.createElement('div');
		row.className = 'd-flex flex-wrap mb-2';
		row.style.maxWidth = '170px';

		Object.keys(PRESETS).forEach(function(name){
			var b = document.createElement('button');
			b.type = 'button';
			b.className = 'btn btn-sm m-1 p-0';
			b.style.cssText = 'width:24px;height:24px;background:' + PRESETS[name] + ';border:1px solid rgba(127,127,127,.4)';
			b.title = name;
			b.addEventListener('click', function(){
				input.value = name;
				paint();
				input.dispatchEvent(new Event('change', { bubbles: true }));
			});
			row.appendChild(b);
		});
		menu.appendChild(row);

		var native = document.createElement('input');
		native.type = 'color';
		native.className = 'form-control form-control-color w-100';
		var current = resolveColor(input.value);
		native.value = /^#([a-f0-9]{6})$/i.test(current) ? current : '#007bff';
		native.addEventListener('input', function(){
			input.value = native.value;
			paint();
			input.dispatchEvent(new Event('change', { bubbles: true }));
		});
		menu.appendChild(native);

		group.appendChild(menu);

		input.addEventListener('change', paint);
		input.addEventListener('keyup', paint);
		paint();
	});
});
