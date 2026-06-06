/**
 * R1.5 — JS pour les matrices users-roles et groups-roles : checkbox toggle + AJAX assign/unassign.
 */
$(function(){
	var $table = $('.matrix-table[data-mode]');
	if (!$table.length) return;

	var mode = $table.data('mode'); // 'users' ou 'groups'
	var $status = $('.matrix-save-status');
	var saveTimer = null;

	function showStatus(text, isError) {
		$status.text(text).css('color', isError ? '#dc3545' : '#28a745');
		clearTimeout(saveTimer);
		saveTimer = setTimeout(function() { $status.text(''); }, 2500);
	}

	$table.on('change', '.matrix-toggle', function(){
		var $cb     = $(this);
		var $cell   = $cb.closest('td');
		var roleId  = parseInt($cb.data('role-id'), 10);
		var checked = $cb.is(':checked');
		var url     = checked ? 'assign' : 'unassign';
		var data    = { role_id: roleId };

		if (mode === 'users')  data.user_id  = parseInt($cb.data('user-id'), 10);
		if (mode === 'groups') data.group_id = parseInt($cb.data('group-id'), 10);

		// Optimistic UI
		$cell.toggleClass('matrix-cell-allow matrix-cell-loading', checked);
		$cell.addClass('matrix-cell-loading');

		$.ajax({
			url:  '<?php echo url('admin/ajax/access') ?>/' + mode + '-roles-' + url + '.json',
			type: 'POST',
			data: data,
			dataType: 'json'
		}).done(function(){
			$cell.removeClass('matrix-cell-loading').addClass('matrix-cell-saved');
			if (!checked) $cell.removeClass('matrix-cell-allow');
			else $cell.addClass('matrix-cell-allow');
			setTimeout(function(){ $cell.removeClass('matrix-cell-saved'); }, 700);
			showStatus('<?php echo addslashes($this->lang('Sauvegardé')) ?>', false);
		}).fail(function(xhr){
			// Rollback
			$cb.prop('checked', !checked);
			$cell.removeClass('matrix-cell-loading').toggleClass('matrix-cell-allow', !checked).addClass('matrix-cell-error');
			setTimeout(function(){ $cell.removeClass('matrix-cell-error'); }, 700);
			showStatus('<?php echo addslashes($this->lang('Erreur')) ?> (' + xhr.status + ')', true);
		});
	});
});
