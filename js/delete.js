NF.ready(function(){
	document.body.addEventListener('click', function(e){
		var trigger = e.target.closest('a.delete');
		if (!trigger){ return; }
		e.preventDefault();

		if (document.querySelector('.delete.alert')){ return; }

		NF.ajax({ url: trigger.getAttribute('href'), dataType: 'text' }).then(function(data){
			var wrapper = document.createElement('div');
			wrapper.innerHTML = '<div class="modal fade" tabindex="-1" role="dialog">'
				+ '<div class="modal-dialog"><div class="modal-content">' + data + '</div></div>'
				+ '</div>';

			var modalEl = wrapper.firstElementChild;
			document.body.appendChild(modalEl);
			NF.runScripts(modalEl);
			bootstrap.Modal.getOrCreateInstance(modalEl).show();
		});
	});

	// Bouton « Supprimer » du modal de confirmation rendu par form.php : délégué (le onclick inline
	// serait bloqué par le CSP strict). La délégation sur body couvre le modal injecté en AJAX.
	document.body.addEventListener('click', function(e){
		var confirmBtn = e.target.closest('a.delete-confirm');
		if (!confirmBtn){ return; }
		e.preventDefault();
		confirm_deletion(confirmBtn);
	});
});

// Globale : appelée par le onclick des boutons de confirmation rendus côté serveur (form.php).
window.confirm_deletion = function(anchor){
	NF.ajax({
		url: anchor.getAttribute('href'),
		method: 'POST',
		body: anchor.getAttribute('data-form-id') + '[]=delete',
		dataType: 'text'
	}).then(function(data){
		if (data === 'OK'){
			var alert = anchor.closest('.alert');
			if (alert){ bootstrap.Alert.getOrCreateInstance(alert).close(); }

			var table = null;
			for (var node = alert ? alert.nextElementSibling : null; node; node = node.nextElementSibling){
				if (node.classList && node.classList.contains('table-area')){ table = node; break; }
			}

			if (table){
				NF.ajax({
					url: window.location.pathname,
					method: 'POST',
					body: 'table_id=' + table.getAttribute('data-table-id'),
					dataType: 'json'
				}).then(function(data){
					var content = table.querySelector(':scope > .table-content');
					if (content){ NF.setHtml(content, data.content); }
				});
			}
			else {
				document.location.reload();
			}
		}
		else {
			var json = null;
			try { json = JSON.parse(data); } catch (e) {}

			if (json && typeof json === 'object' && typeof json.redirect !== 'undefined'){
				if (window.location.pathname === json.redirect.split('#')[0]){
					window.location.href = json.redirect;
					location.reload();
				}
				else {
					window.location.href = json.redirect;
				}
			}
			else {
				// `alertBox`, et non `alert` : ce nom-là masquait `window.alert` dans toute la fonction.
				var alertBox = anchor.closest('.alert');
				if (alertBox){
					alertBox.innerHTML = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>' + data;
				}
			}
		}
	});

	return false;
};
