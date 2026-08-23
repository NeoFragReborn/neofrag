// Chargeur de modèle au composer newsletter : remplit sujet + contenu depuis la sélection.
// Données portées par l'attribut data-templates (JSON) → aucun script inline (CSP stricte).
NF.ready(function () {
	var sel = document.getElementById('nf-nl-template');
	if (!sel) { return; }

	var data;
	try { data = JSON.parse(sel.getAttribute('data-templates') || '{}'); }
	catch (e) { data = {}; }

	sel.addEventListener('change', function () {
		var t = data[sel.value];
		if (!t) { return; }

		var subject = document.querySelector('input[name$="[subject]"]');
		var content = document.querySelector('textarea[name$="[content]"]');
		if (subject) { subject.value = t.subject; }
		if (content) { content.value = t.content; }
	});
});
