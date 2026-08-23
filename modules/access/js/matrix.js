/**
 * R1.3 — Matrice permissions : update AJAX optimiste sur change radio.
 * R1.10 — délégation globale sur `document` : la matrice peut être injectée dans une
 *         MODALE, où modal.js charge ce script AVANT d'insérer le HTML (un querySelector
 *         immédiat ne trouverait pas la table). Marche identiquement en page pleine.
 * Pattern : POST module/permission/role_id/scope_id/value, retour JSON status.
 */
NF.ready(function(){
	var saveTimer = null;

	function showStatus(statusEl, text, isError){
		if (!statusEl){ return; }
		statusEl.textContent = text;
		statusEl.style.color = isError ? '#dc3545' : '#28a745';
		clearTimeout(saveTimer);
		saveTimer = setTimeout(function(){ statusEl.textContent = ''; }, 2500);
	}

	document.addEventListener('change', function(e){
		var input = e.target;
		if (!input.matches || !input.matches('.matrix-radio input[type="radio"]')){ return; }

		var table = input.closest('.matrix-table');
		var cell  = input.closest('td.matrix-cell');
		var row   = input.closest('tr');
		if (!table || !cell || !row){ return; }

		// Statut scopé au conteneur courant (modale ou page) → pas de collision si les deux coexistent.
		var scope    = table.closest('.modal-content') || table.closest('.module') || document;
		var statusEl = scope.querySelector('.matrix-save-status');

		var roleId   = parseInt(NF.data(cell, 'role-id'), 10);
		var perm     = NF.data(row, 'permission');
		var scopeId  = parseInt(NF.data(table, 'scope-id'), 10) || 0;
		var newValue = input.value;
		var oldValue = NF.data(cell, 'current');

		if (newValue === oldValue){ return; } // pas de change réel

		// Optimistic UI : appliquer la classe immédiatement.
		cell.classList.remove('matrix-cell-allow', 'matrix-cell-never', 'matrix-cell-source-direct', 'matrix-cell-source-inherited', 'matrix-cell-source-none');
		cell.classList.add('matrix-cell-' + newValue, 'matrix-cell-source-direct', 'matrix-cell-loading');
		var marker = cell.querySelector('.matrix-inherited-marker');
		if (marker){ marker.remove(); }

		fetch('<?php echo url('admin/ajax/access/matrix-update.json') ?>', {
			method: 'POST',
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
			credentials: 'same-origin',
			body: new URLSearchParams({ role_id: roleId, permission: perm, scope_id: scopeId, value: newValue })
		}).then(function(resp){
			if (!resp.ok){ throw resp.status; }
			cell.classList.remove('matrix-cell-loading');
			cell.classList.add('matrix-cell-saved');
			cell.setAttribute('data-current', newValue);
			setTimeout(function(){ cell.classList.remove('matrix-cell-saved'); }, 700);
			showStatus(statusEl, '<?php echo addslashes($this->lang('Sauvegardé')) ?>', false);
		}).catch(function(httpStatus){
			// Rollback UI.
			cell.classList.remove('matrix-cell-loading', 'matrix-cell-' + newValue);
			cell.classList.add('matrix-cell-' + oldValue, 'matrix-cell-error');
			var revert = cell.querySelector('input[value="' + oldValue + '"]');
			if (revert){ revert.checked = true; }
			setTimeout(function(){ cell.classList.remove('matrix-cell-error'); }, 700);
			showStatus(statusEl, '<?php echo addslashes($this->lang('Erreur de sauvegarde')) ?> (' + (httpStatus || 0) + ')', true);
		});
	});
});
