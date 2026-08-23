/**
 * R1.5 — JS pour les matrices users-roles et groups-roles : checkbox toggle + AJAX assign/unassign.
 */
NF.ready(function(){
	var table = document.querySelector('.matrix-table[data-mode]');
	if (!table){ return; }

	var mode = NF.data(table, 'mode'); // 'users' ou 'groups'
	var statusEl = document.querySelector('.matrix-save-status');
	var saveTimer = null;

	function showStatus(text, isError){
		if (statusEl){
			statusEl.textContent = text;
			statusEl.style.color = isError ? '#dc3545' : '#28a745';
		}
		clearTimeout(saveTimer);
		saveTimer = setTimeout(function(){ if (statusEl){ statusEl.textContent = ''; } }, 2500);
	}

	table.addEventListener('change', function(e){
		var cb = e.target.closest('.matrix-toggle');
		if (!cb){ return; }

		var cell    = cb.closest('td');
		var roleId  = parseInt(NF.data(cb, 'role-id'), 10);
		var checked = cb.checked;
		var action  = checked ? 'assign' : 'unassign';
		var params  = { role_id: roleId };

		if (mode === 'users'){  params.user_id  = parseInt(NF.data(cb, 'user-id'), 10); }
		if (mode === 'groups'){ params.group_id = parseInt(NF.data(cb, 'group-id'), 10); }

		// Optimistic UI.
		cell.classList.toggle('matrix-cell-allow', checked);
		cell.classList.add('matrix-cell-loading');

		fetch('<?php echo url('admin/ajax/access') ?>/' + mode + '-roles-' + action + '.json', {
			method: 'POST',
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
			credentials: 'same-origin',
			body: new URLSearchParams(params)
		}).then(function(resp){
			if (!resp.ok){ throw resp.status; }
			cell.classList.remove('matrix-cell-loading');
			cell.classList.add('matrix-cell-saved');
			cell.classList.toggle('matrix-cell-allow', checked);
			setTimeout(function(){ cell.classList.remove('matrix-cell-saved'); }, 700);
			showStatus('<?php echo addslashes($this->lang('Sauvegardé')) ?>', false);
		}).catch(function(httpStatus){
			// Rollback.
			cb.checked = !checked;
			cell.classList.remove('matrix-cell-loading');
			cell.classList.toggle('matrix-cell-allow', !checked);
			cell.classList.add('matrix-cell-error');
			setTimeout(function(){ cell.classList.remove('matrix-cell-error'); }, 700);
			showStatus('<?php echo addslashes($this->lang('Erreur')) ?> (' + (httpStatus || 0) + ')', true);
		});
	});
});
