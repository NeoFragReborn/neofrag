// Autocomplétion via <datalist> natif (remplace jQuery UI autocomplete). La source (data-source) est
// une liste de chaînes encodée côté serveur.
form.find('input[type="text"].autocomplete', function(){
	var input = this;
	if (input._nfAuto){ return; }
	input._nfAuto = true;

	var source = NF.data(input, 'source');
	if (!source || !source.length){ return; }

	var dl = document.createElement('datalist');
	dl.id = 'nf-auto-' + (input.id || Math.random().toString(36).slice(2));
	source.forEach(function(v){
		var opt = document.createElement('option');
		opt.value = v;
		dl.appendChild(opt);
	});

	input.parentNode.appendChild(dl);
	input.setAttribute('list', dl.id);
	input.setAttribute('autocomplete', 'off');
});
