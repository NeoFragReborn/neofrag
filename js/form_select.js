// tom-select (fork maintenu de selectize, sans jQuery). Les options sont des tableaux [value, label, …]
// (valueField 0, label affiché via les render-functions = data[1], comme avant).
form.find('select.selectize', function(){
	if (this.tomselect){
		return;
	}

	var el   = this;
	var data = {};

	// tom-select interprète un tableau comme une LISTE d'options (≠ selectize). Les options NeoFrag
	// sont des tableaux [value, label, …] -> on les passe en objets {0:value, 1:label} : valueField 0,
	// labelField/render data[1] fonctionnent à l'identique sur un objet.
	var toObj = function(o){ return Array.isArray(o) ? Object.assign({}, o) : o; };

	// tom-select attend des noms de champ en CHAÎNES (il fait `'weight' in field`) : '0'/'1', pas 0/1.
	if (NF.data(el, 'options')){
		data.options     = NF.data(el, 'options').map(toObj);
		data.valueField  = '0';
		data.labelField  = '1';
		data.searchField = ['1'];
	}

	if (NF.data(el, 'search-field')){
		data.searchField = [String(NF.data(el, 'search-field'))];
	}

	if (NF.data(el, 'optgroups')){
		data.optgroups          = NF.data(el, 'optgroups').map(toObj);
		data.optgroupValueField = '0';
		data.optgroupLabelField = '1';
		data.optgroupField      = '1';
	}

	if (NF.data(el, 'optgroup-field')){
		data.optgroupField = String(NF.data(el, 'optgroup-field'));
	}

	// Render fixe (le label = data[1] échappé). On n'utilise plus `new Function` à partir d'un template
	// data-* — inutilisé en pratique et incompatible avec une CSP sans 'unsafe-eval'.
	data.render = {
		option:          function(data, escape){ return '<div class="option">' + escape(data[1]) + '</div>'; },
		item:            function(data, escape){ return '<div class="item">' + escape(data[1]) + '</div>'; },
		optgroup_header: function(data, escape){ return '<div class="optgroup-header">' + escape(data[1]) + '</div>'; }
	};

	if (NF.data(el, 'placeholder')){
		data.placeholder = NF.data(el, 'placeholder');
	}

	if (typeof NF.data(el, 'value') !== 'undefined'){
		data.items = String(NF.data(el, 'value')).split(',');
	}

	if (this.hasAttribute('multiple')){
		data.plugins = ['remove_button'];
	}

	new TomSelect(this, data);
});
