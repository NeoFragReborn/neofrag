(function(){
	function init(){
		document.body.addEventListener('click', function(e){
			var link = e.target.closest('.languages [data-language]');
			if (!link){ return; }

			e.preventDefault();

			fetch('<?php echo url('ajax/settings/languages') ?>', {
				method: 'POST',
				headers: {'X-Requested-With': 'XMLHttpRequest'},
				body: new URLSearchParams({
					url: window.location.pathname + window.location.search + window.location.hash,
					language: link.dataset.language
				})
			}).then(function(response){
				return response.json();
			}).then(function(data){
				if (typeof data.redirect !== 'undefined'){
					window.location.href = data.redirect;
				} else {
					document.querySelectorAll('.modal.show').forEach(function(modal){
						bootstrap.Modal.getOrCreateInstance(modal).hide();
					});
				}
			});
		});
	}
	if (document.readyState !== 'loading'){ init(); } else { document.addEventListener('DOMContentLoaded', init); }
})();
