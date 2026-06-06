/**
 * R1.3 — Matrice permissions : update AJAX optimiste sur change radio.
 * Pattern : envoi POST avec module/permission/role_id/scope_id/value, retour JSON status.
 */
$(function(){
	var $table = $('.matrix-table');
	if (!$table.length) return;

	var $status = $('.matrix-save-status');
	var saveTimer = null;

	function showStatus(text, isError) {
		$status.text(text).css('color', isError ? '#dc3545' : '#28a745');
		clearTimeout(saveTimer);
		saveTimer = setTimeout(function() { $status.text(''); }, 2500);
	}

	$table.on('change', '.matrix-radio input[type="radio"]', function(){
		var $input    = $(this);
		var $cell     = $input.closest('td.matrix-cell');
		var $row      = $input.closest('tr');
		var roleId    = parseInt($cell.data('role-id'), 10);
		var perm      = $row.data('permission');
		var scopeId   = parseInt($table.data('scope-id'), 10) || 0;
		var newValue  = $input.val();
		var oldValue  = $cell.data('current');

		if (newValue === oldValue) return; // pas de change réel

		// Optimistic UI : appliquer la classe immédiatement
		$cell.removeClass('matrix-cell-allow matrix-cell-never matrix-cell-source-direct matrix-cell-source-inherited matrix-cell-source-none')
			.addClass('matrix-cell-' + newValue + ' matrix-cell-source-direct matrix-cell-loading');
		// Retirer le marker hérité si présent
		$cell.find('.matrix-inherited-marker').remove();

		$.ajax({
			url:  '<?php echo url('admin/ajax/access/matrix-update.json') ?>',
			type: 'POST',
			data: {
				role_id:    roleId,
				permission: perm,
				scope_id:   scopeId,
				value:      newValue
			},
			dataType: 'json'
		}).done(function(resp){
			$cell.removeClass('matrix-cell-loading').addClass('matrix-cell-saved');
			$cell.data('current', newValue);
			setTimeout(function(){ $cell.removeClass('matrix-cell-saved'); }, 700);
			showStatus('<?php echo addslashes($this->lang('Sauvegardé')) ?>', false);
		}).fail(function(xhr){
			// Rollback UI
			$cell.removeClass('matrix-cell-loading matrix-cell-' + newValue).addClass('matrix-cell-' + oldValue + ' matrix-cell-error');
			$cell.find('input[value="' + oldValue + '"]').prop('checked', true);
			setTimeout(function(){ $cell.removeClass('matrix-cell-error'); }, 700);
			showStatus('<?php echo addslashes($this->lang('Erreur de sauvegarde')) ?> (' + xhr.status + ')', true);
		});
	});
});
