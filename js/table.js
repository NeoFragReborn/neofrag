NF.ready(function(){
	var request = {};

	function ajaxTable(table, params){
		var tableId = NF.data(table, 'table-id');

		if (request[tableId]){ request[tableId].abort(); }

		var controller = new AbortController();
		request[tableId] = controller;

		params.set('table_id', tableId);

		return NF.ajax({
			url: NF.data(table, 'ajax-url') ? NF.data(table, 'ajax-url') : window.location.pathname,
			method: 'POST',
			body: params,
			signal: controller.signal
		}).then(function(data){
			NF.setHtml(table.querySelector('.table-content'), data.content);
			document.body.dispatchEvent(new CustomEvent('nf.load', { bubbles: true }));
			return data;
		}).catch(function(e){
			if (e.name !== 'AbortError'){ throw e; }
		});
	}

	document.body.addEventListener('change', function(e){
		var head = e.target.closest('th > input[type="checkbox"].table-checkbox');
		if (head){
			document.querySelectorAll('td > input[type="checkbox"].table-checkbox').forEach(function(cb){
				cb.checked = head.checked;
			});
			var label = head.checked ? 'Désélectionner tout' : 'Sélectionner toutes les lignes';
			head.setAttribute('data-bs-original-title', label);
			bootstrap.Tooltip.getOrCreateInstance(head).show();
			return;
		}

		var cell = e.target.closest('td > input[type="checkbox"].table-checkbox');
		if (cell){
			var all     = document.querySelectorAll('td > input[type="checkbox"].table-checkbox');
			var checked = document.querySelectorAll('td > input[type="checkbox"].table-checkbox:checked');
			var label2  = cell.checked ? 'Désélectionner tout' : 'Sélectionner toutes les lignes';
			document.querySelectorAll('th > input[type="checkbox"].table-checkbox').forEach(function(h){
				h.checked = all.length === checked.length;
				h.setAttribute('data-bs-original-title', label2);
			});
		}
	});

	document.body.addEventListener('click', function(e){
		var col = e.target.closest('.table thead .sort');
		if (!col){ return; }

		var table  = col.closest('.table-area');
		var params = new URLSearchParams(NF.data(table, 'ajax-post') || '');
		params.set('sort', '[' + NF.data(col, 'column') + ',"' + NF.data(col, 'order-by') + '"]');

		ajaxTable(table, params);
	});

	document.body.addEventListener('keyup', function(e){
		var input = e.target.closest('.table-search input');
		if (!input){ return; }

		var table = input.closest('.table-area');

		var feedback = input.nextElementSibling;
		if (!feedback || !feedback.classList.contains('form-control-feedback')){
			input.insertAdjacentHTML('afterend', '<span class="form-control-feedback" style="background: url(<?php echo image('ajax-loader.gif') ?>) 50% 50% no-repeat;"></span>');
		}

		var params = new URLSearchParams(NF.data(table, 'ajax-post') || '');
		params.set('search', input.value);

		ajaxTable(table, params).then(function(){
			var fb = input.nextElementSibling;
			if (fb && fb.classList.contains('form-control-feedback')){ fb.remove(); }
		});
	});
});
